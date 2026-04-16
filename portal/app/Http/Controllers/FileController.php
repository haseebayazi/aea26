<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Student;
use App\Models\StudentFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class FileController extends Controller
{
    public function upload(Request $request, Student $student)
    {
        $request->validate([
            'file'      => 'required|file|max:51200|mimes:pdf,doc,docx,jpg,jpeg,png,xlsx,xls',
            'file_type' => ['required', \Illuminate\Validation\Rule::in(['cv', 'citation', 'supporting', 'other'])],
        ]);

        $uploadedFile = $request->file('file');
        $path = $uploadedFile->store(
            "students/{$student->submission_id}",
            'local'
        );

        $file = StudentFile::create([
            'student_id'    => $student->id,
            'file_type'     => $request->input('file_type'),
            'original_name' => $uploadedFile->getClientOriginalName(),
            'file_path'     => $path,
            'file_size'     => $uploadedFile->getSize(),
            'mime_type'     => $uploadedFile->getMimeType(),
            'uploaded_by'   => Auth::id(),
        ]);

        // Update shortcut on student
        if ($request->input('file_type') === 'cv') {
            $student->update(['cv_path' => $path]);
        } elseif ($request->input('file_type') === 'citation') {
            $student->update(['citation_path' => $path]);
        }

        ActivityLog::record('file_uploaded', $file, [
            'student'   => $student->name,
            'file_type' => $file->file_type,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'file' => $file->only('id', 'original_name', 'file_type', 'file_size')]);
        }

        return back()->with('success', "File '{$file->original_name}' uploaded successfully.");
    }

    public function download(StudentFile $file)
    {
        $user = Auth::user();

        // Authorization
        if (!$user->isAdmin() && !$user->canAccessCategory($file->student->category_id)) {
            abort(403);
        }

        // If file_path is absolute (seeded from data folder) and exists on disk
        if (str_starts_with($file->file_path, '/') && file_exists($file->file_path)) {
            return response()->download($file->file_path, $file->original_name);
        }

        // Storage-managed file
        if (!Storage::disk('local')->exists($file->file_path)) {
            abort(404, 'File not found.');
        }

        return Storage::disk('local')->download($file->file_path, $file->original_name);
    }

    public function destroy(StudentFile $file)
    {
        $fileName = $file->original_name;
        $student  = $file->student;

        // Remove from storage if managed
        if (!str_starts_with($file->file_path, '/')) {
            Storage::disk('local')->delete($file->file_path);
        }

        $file->delete();

        ActivityLog::record('file_deleted', $student, ['file_name' => $fileName]);

        return back()->with('success', "File '{$fileName}' deleted.");
    }

    public function bulkUpload(Request $request)
    {
        $request->validate([
            'zip_file' => 'required|file|max:524288|mimes:zip',
        ]);

        $zip = new ZipArchive();
        $tmpPath = $request->file('zip_file')->getPathname();

        if ($zip->open($tmpPath) !== true) {
            return back()->with('error', 'Could not open ZIP file.');
        }

        $linked   = 0;
        $skipped  = 0;
        $errors   = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            $parts = explode('/', trim($name, '/'));

            if (count($parts) < 2) continue;

            $folderName = $parts[0];
            $filename   = end($parts);

            if (empty($filename) || str_ends_with($name, '/')) continue;

            // Parse submission_id from folder name
            if (!preg_match('/^(\d+)-/', $folderName, $matches)) {
                $skipped++;
                continue;
            }

            $submissionId = (int) $matches[1];
            $student = Student::where('submission_id', $submissionId)->first();

            if (!$student) {
                $skipped++;
                continue;
            }

            $contents = $zip->getFromIndex($i);
            $storePath = "students/{$student->submission_id}/{$filename}";

            Storage::disk('local')->put($storePath, $contents);

            $fileType = $this->guessFileType($filename);

            StudentFile::firstOrCreate(
                ['student_id' => $student->id, 'original_name' => $filename],
                [
                    'file_type'   => $fileType,
                    'file_path'   => $storePath,
                    'file_size'   => strlen($contents),
                    'mime_type'   => 'application/octet-stream',
                    'uploaded_by' => Auth::id(),
                ]
            );
            $linked++;
        }

        $zip->close();

        ActivityLog::record('bulk_upload', null, ['linked' => $linked, 'skipped' => $skipped]);

        return back()->with('success', "Bulk upload: {$linked} files linked, {$skipped} skipped.");
    }

    private function guessFileType(string $filename): string
    {
        $lower = strtolower($filename);
        if (str_contains($lower, 'citation')) return 'citation';
        if (str_contains($lower, 'cv') || str_contains($lower, 'resume') || str_contains($lower, 'profile')) return 'cv';
        return 'supporting';
    }
}

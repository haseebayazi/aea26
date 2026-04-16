<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\RubricItem;
use App\Models\SelfScore;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportController extends Controller
{
    public function index()
    {
        Session::forget('import_state');
        return view('admin.import.index');
    }

    public function reset()
    {
        Session::forget('import_state');
        return redirect()->route('admin.import')->with('success', 'Import session cleared.');
    }

    public function upload(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|max:51200|mimes:xlsx,xls,csv',
            'category_id' => 'required|exists:categories,id',
        ]);

        $file     = $request->file('excel_file');
        $tmpPath  = $file->store('imports/tmp', 'local');
        $fullPath = storage_path("app/{$tmpPath}");

        try {
            $spreadsheet = IOFactory::load($fullPath);
            $sheetNames  = $spreadsheet->getSheetNames();

            // Preview first sheet
            $ws      = $spreadsheet->getActiveSheet();
            $allRows = $ws->toArray(null, true, true, false);
            $headers = $allRows[0] ?? [];
            $preview = array_slice($allRows, 1, 5);

            Session::put('import_state', [
                'step'        => 2,
                'tmp_path'    => $tmpPath,
                'category_id' => $request->input('category_id'),
                'sheet_names' => $sheetNames,
                'headers'     => $headers,
                'preview'     => $preview,
            ]);

            return view('admin.import.map', [
                'sheetNames'  => $sheetNames,
                'headers'     => $headers,
                'preview'     => $preview,
                'categoryId'  => $request->input('category_id'),
                'categories'  => Category::ordered()->get(),
                'state'       => Session::get('import_state'),
            ]);

        } catch (\Throwable $e) {
            return back()->with('error', 'Could not parse file: ' . $e->getMessage());
        }
    }

    public function map(Request $request)
    {
        $state = Session::get('import_state');
        if (!$state) {
            return redirect()->route('admin.import')->with('error', 'Session expired. Please start again.');
        }

        $request->validate([
            'sheet'          => 'required|string',
            'name_col'       => 'required|integer|min:0',
            'submission_col' => 'required|integer|min:0',
            'email_col'      => 'nullable|integer|min:0',
            'phone_col'      => 'nullable|integer|min:0',
            'dept_col'       => 'nullable|integer|min:0',
            'campus_col'     => 'nullable|integer|min:0',
            'batch_col'      => 'nullable|integer|min:0',
            'score_start'    => 'required|integer|min:0',
        ]);

        $fullPath = storage_path('app/' . $state['tmp_path']);
        $spreadsheet = IOFactory::load($fullPath);
        $ws   = $spreadsheet->getSheetByName($request->input('sheet'))
                ?? $spreadsheet->getActiveSheet();

        $allRows = $ws->toArray(null, true, true, false);
        $headers = $allRows[0] ?? [];
        $preview = array_slice($allRows, 1, 10);

        $mapping = $request->only([
            'sheet', 'name_col', 'submission_col', 'email_col', 'phone_col',
            'dept_col', 'campus_col', 'batch_col', 'score_start',
        ]);

        $state['step']    = 3;
        $state['mapping'] = $mapping;
        $state['headers'] = $headers;
        $state['preview'] = $preview;
        Session::put('import_state', $state);

        // Show preview with flag issues
        $rubricItems  = RubricItem::caac()->ordered()->get();
        $scoreColumns = config('rubric.excel_score_columns');
        $flaggedRows  = [];

        foreach ($preview as $rowIdx => $row) {
            $issues = [];
            $nameVal = $row[$mapping['name_col']] ?? null;
            $srVal   = $row[$mapping['submission_col']] ?? null;

            if (empty($nameVal)) $issues[] = 'Missing name';
            if (empty($srVal))   $issues[] = 'Missing submission ID';

            $scoreStart = (int) $mapping['score_start'];
            foreach ($scoreColumns as $idx => [$scoreRelIdx, $briefRelIdx, $rubricKey]) {
                $rubricItem = $rubricItems->firstWhere('sub_indicator_key', $rubricKey);
                if (!$rubricItem) continue;
                $scoreVal = $row[$scoreStart + $scoreRelIdx] ?? null;
                if (is_numeric($scoreVal) && (float)$scoreVal > $rubricItem->max_score) {
                    $issues[] = "{$rubricKey}: {$scoreVal} > max {$rubricItem->max_score}";
                }
            }

            if ($issues) {
                $flaggedRows[$rowIdx + 2] = $issues; // +2 for 1-indexed + header
            }
        }

        return view('admin.import.preview', compact(
            'headers', 'preview', 'mapping', 'flaggedRows', 'rubricItems', 'scoreColumns', 'state'
        ));
    }

    public function execute(Request $request)
    {
        $state = Session::get('import_state');
        if (!$state) {
            return redirect()->route('admin.import')->with('error', 'Session expired. Please start again.');
        }

        $mapping     = $state['mapping'];
        $categoryId  = $state['category_id'];
        $fullPath    = storage_path('app/' . $state['tmp_path']);
        $scoreColumns = config('rubric.excel_score_columns');
        $rubricItems = RubricItem::caac()->get()->keyBy('sub_indicator_key');
        $category    = Category::findOrFail($categoryId);

        try {
            $spreadsheet = IOFactory::load($fullPath);
            $ws   = $spreadsheet->getSheetByName($mapping['sheet'])
                    ?? $spreadsheet->getActiveSheet();

            $allRows = $ws->toArray(null, true, true, false);
            array_shift($allRows); // remove header

            $success = 0;
            $skipped = 0;
            $errors  = [];
            $chunk   = [];

            foreach ($allRows as $rowIndex => $row) {
                $submissionId = (int) ($row[$mapping['submission_col']] ?? 0);
                $name         = trim((string) ($row[$mapping['name_col']] ?? ''));

                if ($submissionId <= 0 || empty($name)) {
                    $skipped++;
                    continue;
                }

                $chunk[] = ['row' => $row, 'submission_id' => $submissionId, 'name' => $name];

                if (count($chunk) >= 50) {
                    [$s, $e] = $this->processChunk($chunk, $mapping, $scoreColumns, $rubricItems, $category);
                    $success += $s;
                    $errors   = array_merge($errors, $e);
                    $chunk    = [];
                }
            }

            // Process remaining
            if ($chunk) {
                [$s, $e] = $this->processChunk($chunk, $mapping, $scoreColumns, $rubricItems, $category);
                $success += $s;
                $errors   = array_merge($errors, $e);
            }

            ActivityLog::record('import_completed', null, [
                'category' => $category->name,
                'success'  => $success,
                'errors'   => count($errors),
            ]);

            Session::forget('import_state');

            return view('admin.import.result', compact('success', 'skipped', 'errors', 'category'));

        } catch (\Throwable $e) {
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    private function processChunk(array $chunk, array $mapping, array $scoreColumns, $rubricItems, $category): array
    {
        $success = 0;
        $errors  = [];

        DB::transaction(function () use ($chunk, $mapping, $scoreColumns, $rubricItems, $category, &$success, &$errors) {
            foreach ($chunk as $item) {
                try {
                    $row          = $item['row'];
                    $submissionId = $item['submission_id'];
                    $name         = $item['name'];

                    $student = Student::updateOrCreate(
                        ['submission_id' => $submissionId],
                        [
                            'name'        => $name,
                            'email'       => $this->cleanVal($row[$mapping['email_col'] ?? -1] ?? null),
                            'phone'       => $this->cleanVal($row[$mapping['phone_col'] ?? -1] ?? null),
                            'batch'       => $this->cleanVal($row[$mapping['batch_col'] ?? -1] ?? null),
                            'department'  => $this->cleanVal($row[$mapping['dept_col'] ?? -1] ?? null),
                            'campus'      => $this->cleanVal($row[$mapping['campus_col'] ?? -1] ?? null),
                            'category_id' => $category->id,
                        ]
                    );

                    $scoreStart = (int) $mapping['score_start'];
                    foreach ($scoreColumns as [$scoreRelIdx, $briefRelIdx, $rubricKey]) {
                        $rubricItem = $rubricItems->get($rubricKey);
                        if (!$rubricItem) continue;

                        $scoreVal = $row[$scoreStart + $scoreRelIdx] ?? null;
                        $briefVal = $row[$scoreStart + $briefRelIdx] ?? null;
                        $score    = is_numeric($scoreVal)
                            ? min(max((float)$scoreVal, 0), $rubricItem->max_score)
                            : 0.0;

                        SelfScore::updateOrCreate(
                            ['student_id' => $student->id, 'rubric_item_id' => $rubricItem->id],
                            ['score' => $score, 'remarks' => $briefVal ? trim((string)$briefVal) : null]
                        );
                    }

                    $success++;
                } catch (\Throwable $e) {
                    $errors[] = "Row {$item['submission_id']}: " . $e->getMessage();
                }
            }
        });

        return [$success, $errors];
    }

    private function cleanVal($value): ?string
    {
        if ($value === null || $value === '' || $value === 'NULL' || $value === 'False') return null;
        $str = trim((string) $value);
        $lines = array_filter(array_map('trim', explode("\n", $str)));
        return $lines ? reset($lines) : null;
    }
}

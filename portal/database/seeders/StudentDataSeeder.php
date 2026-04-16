<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\RubricItem;
use App\Models\SelfScore;
use App\Models\Student;
use App\Models\StudentFile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class StudentDataSeeder extends Seeder
{
    // Path to the data repo containing xlsx files and student folders
    private string $dataPath;

    public function run(): void
    {
        // Try common locations for the data files
        $candidates = [
            base_path('../'),        // one level up from portal/
            '/home/user/aea26/',
            base_path('../../aea26/'),
        ];

        $this->dataPath = '';
        foreach ($candidates as $path) {
            $normalized = realpath($path);
            if ($normalized && file_exists($normalized . '/Professional Achievement program wise.xlsx')) {
                $this->dataPath = $normalized . '/';
                break;
            }
        }

        if (!$this->dataPath) {
            $this->command->error('Cannot find data directory with xlsx files. Skipping student data import.');
            $this->command->line('Expected to find "Professional Achievement program wise.xlsx" in parent directory.');
            return;
        }

        $this->command->info("Data path: {$this->dataPath}");

        // Load rubric items keyed by sub_indicator_key
        $rubricItems = RubricItem::caac()->get()->keyBy('sub_indicator_key');

        if ($rubricItems->isEmpty()) {
            $this->command->error('No rubric items found. Run RubricItemSeeder first.');
            return;
        }

        // Load categories keyed by slug
        $categories = Category::all()->keyBy('slug');

        if ($categories->isEmpty()) {
            $this->command->error('No categories found. Run CategorySeeder first.');
            return;
        }

        $scoreColumns = config('rubric.excel_score_columns');
        $excelFiles   = config('rubric.excel_files');

        $totalStudents   = 0;
        $totalScores     = 0;
        $totalFilesLinked = 0;
        $errors          = [];

        foreach ($excelFiles as $fileConfig) {
            $filePath = $this->dataPath . $fileConfig['file'];

            if (!file_exists($filePath)) {
                $this->command->warn("File not found: {$filePath}");
                continue;
            }

            $category = $categories->get($fileConfig['category']);
            if (!$category) {
                $this->command->warn("Category not found: {$fileConfig['category']}");
                continue;
            }

            $this->command->line("Importing: {$fileConfig['file']}");

            try {
                $spreadsheet = IOFactory::load($filePath);
                $sheet       = $spreadsheet->getSheetByName($fileConfig['sheet'])
                               ?? $spreadsheet->getActiveSheet();

                $rows = $sheet->toArray(null, true, true, false);
                array_shift($rows); // remove header row

                $chunkStudents  = [];
                $chunkSelfScores = [];

                foreach ($rows as $rowIndex => $row) {
                    // Skip empty rows
                    if (empty($row[0]) && empty($row[$fileConfig['name_col']])) {
                        continue;
                    }

                    $submissionId = (int) ($row[0] ?? 0);
                    if ($submissionId <= 0) {
                        continue;
                    }

                    $name = trim((string) ($row[$fileConfig['name_col']] ?? ''));
                    if (empty($name)) {
                        continue;
                    }

                    // Build additional_info from extra cols
                    $extraParts = [];
                    foreach ($fileConfig['extra_info_cols'] as $colIdx) {
                        $val = trim((string) ($row[$colIdx] ?? ''));
                        if ($val && $val !== 'False' && $val !== 'NULL') {
                            $extraParts[] = $val;
                        }
                    }
                    $additionalInfo = implode(' | ', array_filter($extraParts));

                    DB::transaction(function () use (
                        $row, $submissionId, $name, $category, $fileConfig,
                        $rubricItems, $scoreColumns, $additionalInfo,
                        &$totalStudents, &$totalScores, &$errors
                    ) {
                        try {
                            $student = Student::updateOrCreate(
                                ['submission_id' => $submissionId],
                                [
                                    'name'            => $name,
                                    'email'           => $this->cleanValue($row[$fileConfig['email_col']] ?? null),
                                    'phone'           => $this->cleanValue($row[$fileConfig['phone_col']] ?? null),
                                    'batch'           => $this->cleanValue($row[$fileConfig['batch_col']] ?? null),
                                    'department'      => $this->cleanValue($row[$fileConfig['dept_col']] ?? null),
                                    'campus'          => $this->cleanValue($row[$fileConfig['campus_col']] ?? null),
                                    'category_id'     => $category->id,
                                    'additional_info' => $additionalInfo ?: null,
                                ]
                            );

                            $totalStudents++;

                            // Import self-scores
                            $scoreOffset = $fileConfig['score_start'];
                            foreach ($scoreColumns as [$scoreRelIdx, $briefRelIdx, $rubricKey]) {
                                $rubricItem = $rubricItems->get($rubricKey);
                                if (!$rubricItem) {
                                    continue;
                                }

                                $scoreVal = $row[$scoreOffset + $scoreRelIdx] ?? null;
                                $briefVal = $row[$scoreOffset + $briefRelIdx] ?? null;

                                $score = is_numeric($scoreVal)
                                    ? min((float) $scoreVal, $rubricItem->max_score)
                                    : 0.0;

                                SelfScore::updateOrCreate(
                                    [
                                        'student_id'    => $student->id,
                                        'rubric_item_id' => $rubricItem->id,
                                    ],
                                    [
                                        'score'   => $score,
                                        'remarks' => $this->cleanLongValue($briefVal),
                                    ]
                                );
                                $totalScores++;
                            }
                        } catch (\Throwable $e) {
                            $errors[] = "Row {$submissionId} ({$name}): " . $e->getMessage();
                        }
                    });
                }

                $this->command->line("  Done: {$fileConfig['file']}");

            } catch (\Throwable $e) {
                $this->command->error("Error reading {$fileConfig['file']}: " . $e->getMessage());
            }
        }

        // Link student files from category folders
        $totalFilesLinked = $this->linkStudentFiles($categories);

        $this->command->info("Import complete: {$totalStudents} students, {$totalScores} self-scores, {$totalFilesLinked} files linked.");

        if ($errors) {
            $this->command->warn('Errors encountered:');
            foreach (array_slice($errors, 0, 20) as $err) {
                $this->command->warn("  - {$err}");
            }
        }
    }

    private function linkStudentFiles($categories): int
    {
        $folderMap = [
            'professional-achievement'    => '1-Professional Achievement',
            'distinguished-young-alumni'  => '2-Distinguished Young Alumni',
            'innovation-entrepreneurship' => '3-Innovation & Entrepreneurship',
            'social-impact-community'     => '4-Social Impact & Community Service',
        ];

        $linked = 0;

        foreach ($folderMap as $slug => $folderName) {
            $folderPath = $this->dataPath . $folderName;
            if (!is_dir($folderPath)) {
                continue;
            }

            foreach (scandir($folderPath) as $studentDir) {
                if ($studentDir === '.' || $studentDir === '..') {
                    continue;
                }

                // Parse submission_id from folder name e.g. "1-Dr. Kalsoom Akhtar"
                if (!preg_match('/^(\d+)-(.+)$/', $studentDir, $matches)) {
                    continue;
                }

                $submissionId = (int) $matches[1];
                $student = Student::where('submission_id', $submissionId)->first();
                if (!$student) {
                    continue;
                }

                $studentFolderPath = $folderPath . '/' . $studentDir;
                if (!is_dir($studentFolderPath)) {
                    continue;
                }

                foreach (scandir($studentFolderPath) as $filename) {
                    if ($filename === '.' || $filename === '..') {
                        continue;
                    }

                    $filePath = $studentFolderPath . '/' . $filename;
                    if (!is_file($filePath)) {
                        continue;
                    }

                    // Determine file type from filename
                    $fileType = $this->guessFileType($filename);
                    $finfo    = new \finfo(FILEINFO_MIME_TYPE);
                    $mimeType = $finfo->file($filePath) ?: 'application/octet-stream';

                    StudentFile::firstOrCreate(
                        [
                            'student_id'    => $student->id,
                            'original_name' => $filename,
                        ],
                        [
                            'file_type'     => $fileType,
                            'file_path'     => $filePath, // absolute path for dev; change to relative for prod
                            'file_size'     => filesize($filePath),
                            'mime_type'     => $mimeType,
                            'uploaded_by'   => null,
                        ]
                    );
                    $linked++;

                    // Set cv_path / citation_path shortcuts on student
                    if ($fileType === 'cv' && !$student->cv_path) {
                        $student->cv_path = $filePath;
                        $student->save();
                    } elseif ($fileType === 'citation' && !$student->citation_path) {
                        $student->citation_path = $filePath;
                        $student->save();
                    }
                }
            }
        }

        return $linked;
    }

    private function guessFileType(string $filename): string
    {
        $lower = strtolower($filename);

        if (str_contains($lower, 'citation')) {
            return 'citation';
        }
        if (
            str_contains($lower, 'cv') ||
            str_contains($lower, 'resume') ||
            str_contains($lower, 'profile')
        ) {
            return 'cv';
        }
        return 'supporting';
    }

    private function cleanValue($value): ?string
    {
        if ($value === null || $value === '' || $value === 'NULL' || $value === 'False') {
            return null;
        }
        $str = trim((string) $value);
        // Take only first line for short fields (phone, email, etc.)
        $lines = array_filter(array_map('trim', explode("\n", $str)));
        return $lines ? reset($lines) : null;
    }

    private function cleanLongValue($value): ?string
    {
        if ($value === null || $value === '' || $value === 'NULL' || $value === 'False') {
            return null;
        }
        $str = trim((string) $value);
        return $str ?: null;
    }
}

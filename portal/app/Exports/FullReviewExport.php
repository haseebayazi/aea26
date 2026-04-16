<?php

namespace App\Exports;

use App\Models\RubricItem;
use App\Models\Student;
use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FullReviewExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    private $rubricItems;
    private $reviewers;

    public function __construct()
    {
        $this->rubricItems = RubricItem::caac()->ordered()->get();
        $this->reviewers   = User::reviewers()->active()->orderBy('name')->get();
    }

    public function headings(): array
    {
        $headers = [
            'Submission ID', 'Name', 'Category', 'Department', 'Campus',
            'Batch/RegNo', 'Email', 'Phone',
        ];

        // Self-score columns
        foreach ($this->rubricItems as $item) {
            $headers[] = "Self: {$item->sub_indicator_label}";
        }
        $headers[] = 'Self Total';

        // Per-reviewer columns
        foreach ($this->reviewers as $reviewer) {
            foreach ($this->rubricItems as $item) {
                $headers[] = "{$reviewer->name}: {$item->sub_indicator_label}";
            }
            $headers[] = "{$reviewer->name}: TOTAL";
            $headers[] = "{$reviewer->name}: Overall Remarks";
        }

        // Avg columns
        foreach ($this->rubricItems as $item) {
            $headers[] = "Avg: {$item->sub_indicator_label}";
        }
        $headers[] = 'Avg Total';
        $headers[] = '#Completed Reviews';

        return $headers;
    }

    public function collection()
    {
        $students = Student::with([
            'category',
            'selfScores',
            'reviews' => fn($q) => $q->completed()->with('scores'),
        ])->orderBy('category_id')->orderBy('submission_id')->get();

        return $students->map(function ($student) {
            $row = [
                $student->submission_id,
                $student->name,
                $student->category->name,
                $student->department,
                $student->campus,
                $student->batch,
                $student->email,
                $student->phone,
            ];

            $selfMap = $student->selfScores->keyBy('rubric_item_id');
            $selfTotal = 0;
            foreach ($this->rubricItems as $item) {
                $score = $selfMap->get($item->id)?->score ?? 0;
                $row[] = $score;
                $selfTotal += $score;
            }
            $row[] = round($selfTotal, 2);

            // Per-reviewer scores
            $reviewMap = $student->reviews->keyBy('reviewer_id');
            foreach ($this->reviewers as $reviewer) {
                $review = $reviewMap->get($reviewer->id);
                $reviewerTotal = 0;
                foreach ($this->rubricItems as $item) {
                    if ($review) {
                        $score = $review->scores->firstWhere('rubric_item_id', $item->id)?->score ?? '';
                        $row[] = $score;
                        if (is_numeric($score)) $reviewerTotal += $score;
                    } else {
                        $row[] = '';
                    }
                }
                $row[] = $review ? round($reviewerTotal, 2) : '';
                $row[] = $review?->overall_remarks ?? '';
            }

            // Averages
            $completedReviews = $student->reviews->where('status', 'completed');
            $avgTotal = 0;
            foreach ($this->rubricItems as $item) {
                if ($completedReviews->isEmpty()) {
                    $row[] = '';
                } else {
                    $avg = $completedReviews->map(fn($r) => $r->scores->firstWhere('rubric_item_id', $item->id)?->score ?? 0)->avg();
                    $avgRound = round($avg, 2);
                    $row[] = $avgRound;
                    $avgTotal += $avgRound;
                }
            }
            $row[] = $completedReviews->isEmpty() ? '' : round($avgTotal, 2);
            $row[] = $completedReviews->count();

            return $row;
        });
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

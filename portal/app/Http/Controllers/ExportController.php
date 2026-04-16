<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Review;
use App\Models\ReviewScore;
use App\Models\RubricItem;
use App\Models\Student;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ExportController extends Controller
{
    public function index()
    {
        $totalStudents    = Student::count();
        $completedReviews = Review::completed()->count();

        return view('admin.export.index', compact('totalStudents', 'completedReviews'));
    }

    public function fullExcel()
    {
        ActivityLog::record('export_full_excel');

        $filename = 'aea26-full-export-' . now()->format('Ymd-His') . '.xlsx';

        return Excel::download(new \App\Exports\FullReviewExport(), $filename);
    }

    public function summaryCsv()
    {
        ActivityLog::record('export_summary_csv');

        $rubricItems  = RubricItem::caac()->ordered()->get();
        $reviewers    = User::reviewers()->active()->get();

        $students = Student::with([
            'category',
            'selfScores',
            'reviews' => fn($q) => $q->completed()->with(['reviewer', 'scores']),
        ])->orderBy('category_id')->orderBy('submission_id')->get();

        $headers  = ['Submission ID', 'Name', 'Category', 'Department', 'Campus', 'Self Total', 'Avg Reviewer Total', '#Completed Reviews', 'Rank in Category'];
        $rows     = [$headers];

        $byCategory = $students->groupBy('category_id');

        foreach ($byCategory as $catId => $catStudents) {
            $ranked = $catStudents->map(function ($student) {
                $selfTotal       = $student->selfScores->sum('score');
                $completedReviews = $student->reviews->where('status', 'completed');
                $reviewerAvg     = $completedReviews->isEmpty() ? null
                    : round($completedReviews->map(fn($r) => $r->scores->sum('score'))->avg(), 2);
                return [
                    'student'      => $student,
                    'self_total'   => round($selfTotal, 2),
                    'reviewer_avg' => $reviewerAvg,
                    'review_count' => $completedReviews->count(),
                ];
            })
            ->sortByDesc('reviewer_avg');

            $rank = 1;
            foreach ($ranked as $item) {
                $rows[] = [
                    $item['student']->submission_id,
                    $item['student']->name,
                    $item['student']->category->name,
                    $item['student']->department,
                    $item['student']->campus,
                    $item['self_total'],
                    $item['reviewer_avg'] ?? 'N/A',
                    $item['review_count'],
                    $rank++,
                ];
            }
        }

        $tmpHandle = fopen('php://temp', 'r+');
        foreach ($rows as $row) {
            fputcsv($tmpHandle, array_map('strval', $row));
        }
        rewind($tmpHandle);
        $csvContent = stream_get_contents($tmpHandle);
        fclose($tmpHandle);
        $filename = 'aea26-summary-' . now()->format('Ymd-His') . '.csv';

        return response($csvContent, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function winnersReport()
    {
        ActivityLog::record('export_winners_report');

        $categories = Category::ordered()->with([
            'students.selfScores',
            'students.reviews' => fn($q) => $q->completed()->with(['reviewer', 'scores']),
        ])->get();

        $winners = $categories->map(function ($category) {
            $ranked = $category->students->map(function ($student) {
                $completedReviews = $student->reviews->where('status', 'completed');
                $reviewerAvg = $completedReviews->isEmpty() ? 0
                    : $completedReviews->map(fn($r) => $r->scores->sum('score'))->avg();
                return ['student' => $student, 'avg' => round($reviewerAvg, 2)];
            })->sortByDesc('avg')->take(3)->values();

            return [
                'category' => $category,
                'top3'     => $ranked,
            ];
        });

        $pdf = Pdf::loadView('exports.winners-pdf', compact('winners'))
            ->setPaper('A4', 'portrait');

        ActivityLog::record('export_winners_pdf');

        return $pdf->download('aea26-winners-' . now()->format('Ymd') . '.pdf');
    }

    public function studentPdf(Student $student)
    {
        $student->load([
            'category',
            'selfScores.rubricItem',
            'reviews' => fn($q) => $q->completed()->with(['reviewer', 'scores.rubricItem']),
        ]);

        $rubricConfig = config('rubric.caac');
        $rubricItems  = RubricItem::caac()->ordered()->get()->keyBy('id');
        $selfScoreMap = $student->selfScores->keyBy('rubric_item_id');

        $pdf = Pdf::loadView('exports.student-pdf', compact(
            'student', 'rubricConfig', 'rubricItems', 'selfScoreMap'
        ))->setPaper('A4', 'portrait');

        ActivityLog::record('export_student_pdf', $student);

        return $pdf->download("student-{$student->submission_id}-{$student->name}.pdf");
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Review;
use App\Models\ReviewScore;
use App\Models\RubricItem;
use App\Models\SelfScore;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index()
    {
        $categories       = Category::ordered()->get();
        $reviewers        = User::reviewers()->active()->get();
        $totalStudents    = Student::count();
        $totalCompleted   = Review::completed()->distinct('student_id')->count();
        $totalReviews     = Review::count();
        $completedReviews = Review::completed()->count();

        return view('analytics.index', compact(
            'categories', 'reviewers', 'totalStudents', 'totalCompleted',
            'totalReviews', 'completedReviews'
        ));
    }

    public function chartData(Request $request, string $chart): JsonResponse
    {
        return match ($chart) {
            'category-distribution'  => $this->categoryDistribution(),
            'self-vs-reviewer'       => $this->selfVsReviewer($request),
            'top-candidates'         => $this->topCandidates($request),
            'dimension-averages'     => $this->dimensionAverages($request),
            'reviewer-agreement'     => $this->reviewerAgreement(),
            'student-radar'          => $this->studentRadar($request),
            default                  => response()->json(['error' => 'Unknown chart'], 404),
        };
    }

    private function categoryDistribution(): JsonResponse
    {
        $data = Category::ordered()->withCount('students')->get()
            ->map(fn($c) => [
                'label' => $c->name,
                'count' => $c->students_count,
                'color' => $c->color,
            ]);

        return response()->json($data);
    }

    private function selfVsReviewer(Request $request): JsonResponse
    {
        $categoryId = $request->integer('category_id', 0);

        $query = Student::with(['selfScores', 'reviews' => fn($q) => $q->completed()->with('scores')])
            ->when($categoryId, fn($q) => $q->where('category_id', $categoryId));

        $points = $query->get()
            ->map(function ($student) {
                $selfTotal = $student->selfScores->sum('score');
                $completedReviews = $student->reviews->where('status', 'completed');
                if ($completedReviews->isEmpty()) return null;
                $reviewerAvg = $completedReviews->map(fn($r) => $r->scores->sum('score'))->avg();
                return [
                    'x'    => round($selfTotal, 1),
                    'y'    => round($reviewerAvg, 1),
                    'name' => $student->name,
                    'id'   => $student->id,
                ];
            })
            ->filter()
            ->values();

        return response()->json($points);
    }

    private function topCandidates(Request $request): JsonResponse
    {
        $categoryId = $request->integer('category_id', 0);
        $limit      = 15;

        $students = Student::with(['selfScores', 'reviews' => fn($q) => $q->completed()->with('scores')])
            ->when($categoryId, fn($q) => $q->where('category_id', $categoryId))
            ->get()
            ->map(function ($student) {
                $selfTotal = $student->selfScores->sum('score');
                $completedReviews = $student->reviews->where('status', 'completed');
                $reviewerAvg = $completedReviews->isEmpty()
                    ? null
                    : $completedReviews->map(fn($r) => $r->scores->sum('score'))->avg();
                return [
                    'name'         => $student->name,
                    'id'           => $student->id,
                    'self_total'   => round($selfTotal, 1),
                    'reviewer_avg' => $reviewerAvg ? round($reviewerAvg, 1) : null,
                ];
            })
            ->sortByDesc('reviewer_avg')
            ->take($limit)
            ->values();

        return response()->json($students);
    }

    private function dimensionAverages(Request $request): JsonResponse
    {
        $categoryId = $request->integer('category_id', 0);

        $dimensions = array_keys(config('rubric.caac'));
        $categories = Category::ordered()->get();

        $result = [];
        foreach ($categories as $cat) {
            if ($categoryId && $cat->id !== $categoryId) continue;

            $studentIds = Student::where('category_id', $cat->id)->pluck('id');

            $dimAvgs = [];
            foreach ($dimensions as $dim) {
                $items = RubricItem::caac()->forDimension($dim)->get();
                $itemIds = $items->pluck('id');

                $avgScore = ReviewScore::join('reviews', 'review_scores.review_id', '=', 'reviews.id')
                    ->whereIn('review_scores.rubric_item_id', $itemIds)
                    ->whereIn('reviews.student_id', $studentIds)
                    ->where('reviews.status', 'completed')
                    ->avg('review_scores.score');

                $dimAvgs[$dim] = round((float) $avgScore, 1);
            }

            $result[] = [
                'category' => $cat->name,
                'color'    => $cat->color,
                'averages' => $dimAvgs,
            ];
        }

        return response()->json($result);
    }

    private function reviewerAgreement(): JsonResponse
    {
        // Load all students with completed reviews and filter in PHP
        // (SQLite doesn't support HAVING on virtual withCount columns)
        $students = Student::with(['reviews' => fn($q) => $q->completed()->with('scores'), 'category'])
            ->get()
            ->filter(fn($s) => $s->reviews->count() >= 2);

        $rows = $students->map(function ($student) {
            $reviews = $student->reviews->where('status', 'completed');
            $totals  = $reviews->map(fn($r) => $r->scores->whereNotNull('score')->sum('score'));

            $mean = $totals->avg();
            $variance = $totals->map(fn($t) => pow($t - $mean, 2))->avg();
            $stdDev  = sqrt($variance);

            return [
                'id'       => $student->id,
                'name'     => $student->name,
                'category' => $student->category->name,
                'scores'   => $totals->values()->toArray(),
                'mean'     => round($mean, 1),
                'std_dev'  => round($stdDev, 2),
                'flagged'  => $stdDev > 5,
            ];
        })
        ->sortByDesc('std_dev')
        ->values();

        return response()->json($rows);
    }

    private function studentRadar(Request $request): JsonResponse
    {
        $studentId = $request->integer('student_id');
        if (!$studentId) {
            return response()->json(['error' => 'student_id required'], 422);
        }

        $student    = Student::with('selfScores.rubricItem')->findOrFail($studentId);
        $dimensions = array_keys(config('rubric.caac'));
        $rubricConf = config('rubric.caac');

        $selfData     = [];
        $reviewerData = [];
        $labels       = [];
        $maxData      = [];

        $completedReviews = Review::where('student_id', $studentId)
            ->completed()
            ->with('scores')
            ->get();

        foreach ($dimensions as $dim) {
            $dimConfig = $rubricConf[$dim];
            $labels[]  = $dimConfig['label'];
            $maxData[] = $dimConfig['total'];

            $itemKeys  = array_column($dimConfig['items'], 'key');
            $rubricIds = RubricItem::caac()->forDimension($dim)->pluck('id');

            // Self total for this dimension
            $selfDimTotal = $student->selfScores
                ->whereIn('rubric_item_id', $rubricIds->toArray())
                ->sum('score');
            $selfData[] = round($selfDimTotal, 1);

            // Reviewer avg for this dimension
            if ($completedReviews->isEmpty()) {
                $reviewerData[] = null;
            } else {
                $reviewerDimAvg = $completedReviews->map(function ($review) use ($rubricIds) {
                    return $review->scores->whereIn('rubric_item_id', $rubricIds->toArray())->sum('score');
                })->avg();
                $reviewerData[] = round($reviewerDimAvg, 1);
            }
        }

        return response()->json([
            'labels'        => $labels,
            'self'          => $selfData,
            'reviewer_avg'  => $reviewerData,
            'max'           => $maxData,
            'student_name'  => $student->name,
        ]);
    }
}

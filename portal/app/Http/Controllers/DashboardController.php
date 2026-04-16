<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Review;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user->isAdmin()) {
            return $this->adminDashboard();
        }
        return $this->reviewerDashboard($user);
    }

    private function adminDashboard()
    {
        $totalStudents  = Student::count();
        $totalReviewers = User::reviewers()->active()->count();
        $totalReviews   = Review::count();
        $completedReviews = Review::completed()->count();
        $completionPct  = $totalReviews > 0 ? round(($completedReviews / $totalReviews) * 100) : 0;

        // Completion by reviewer
        $reviewerStats = User::with('reviews')->reviewers()->active()->get()
            ->map(fn($r) => [
                'name'      => $r->name,
                'completed' => $r->reviews->where('status', 'completed')->count(),
                'total'     => $r->reviews->count(),
            ]);

        // Completion by category
        $categoryStats = Category::ordered()->withCount([
            'students',
            'students as completed_count' => function ($q) {
                $q->whereHas('reviews', fn($r) => $r->where('status', 'completed'));
            },
        ])->get();

        // Recent activity
        $recentActivity = ActivityLog::with('user')
            ->latest()
            ->limit(20)
            ->get();

        // Self-score avg per category
        $selfScoreAvg = DB::table('students')
            ->join('self_scores', 'students.id', '=', 'self_scores.student_id')
            ->join('categories', 'students.category_id', '=', 'categories.id')
            ->select('categories.name', DB::raw('ROUND(SUM(self_scores.score), 1) as total_score'), 'students.id as sid')
            ->groupBy('categories.name', 'students.id')
            ->get()
            ->groupBy('name')
            ->map(fn($g) => round($g->avg('total_score'), 1));

        return view('dashboard.admin', compact(
            'totalStudents', 'totalReviewers', 'totalReviews', 'completedReviews',
            'completionPct', 'reviewerStats', 'categoryStats', 'recentActivity', 'selfScoreAvg'
        ));
    }

    private function reviewerDashboard($user)
    {
        $assignedCategories = $user->assignedCategories()->ordered()->get();
        $assignedCategoryIds = $assignedCategories->pluck('id');

        $assignedCount = Student::whereIn('category_id', $assignedCategoryIds)->count();
        $myReviews     = Review::forReviewer($user->id)->with('student.category')->latest()->get();
        $completedCount = $myReviews->where('status', 'completed')->count();
        $inProgressCount = $myReviews->where('status', 'in_progress')->count();
        $remainingCount = $assignedCount - $completedCount - $inProgressCount;

        $recentReviews = $myReviews->take(5);

        // Find the next student to review
        $nextStudent = Student::whereIn('category_id', $assignedCategoryIds)
            ->whereDoesntHave('reviews', fn($q) => $q->where('reviewer_id', $user->id)->where('status', 'completed'))
            ->first();

        return view('dashboard.reviewer', compact(
            'assignedCategories', 'assignedCount', 'completedCount',
            'inProgressCount', 'remainingCount', 'recentReviews', 'nextStudent'
        ));
    }
}

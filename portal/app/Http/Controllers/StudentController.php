<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Review;
use App\Models\RubricItem;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = Student::with(['category', 'selfScores'])
            ->withCount('reviews as total_reviews')
            ->withCount(['reviews as completed_reviews' => fn($q) => $q->where('status', 'completed')]);

        // Restrict to assigned categories for reviewers
        if (!$user->isAdmin()) {
            $assignedIds = $user->assignedCategories->pluck('id');
            $query->whereIn('category_id', $assignedIds);
        }

        $students = $query->orderBy('category_id')->orderBy('submission_id')->get();

        // Attach review status for current user
        $myReviewMap = Review::forReviewer($user->id)
            ->whereIn('student_id', $students->pluck('id'))
            ->pluck('status', 'student_id');

        $students->each(function ($student) use ($myReviewMap, $user) {
            $student->my_review_status = $myReviewMap->get($student->id, 'not_started');
            $student->self_score_total = $student->selfScores->sum('score');
        });

        $categories = $user->isAdmin()
            ? Category::ordered()->get()
            : $user->assignedCategories()->ordered()->get();

        // Stats
        $totalAssigned  = $students->count();
        $reviewedByMe   = $students->where('my_review_status', 'completed')->count();
        $inProgress     = $students->where('my_review_status', 'in_progress')->count();
        $pending        = $totalAssigned - $reviewedByMe - $inProgress;
        $avgScore       = Review::forReviewer($user->id)->completed()
            ->with('scores')
            ->get()
            ->map(fn($r) => $r->scores->sum('score'))
            ->avg();

        return view('students.index', compact(
            'students', 'categories', 'totalAssigned', 'reviewedByMe',
            'inProgress', 'pending', 'avgScore'
        ));
    }

    public function show(Student $student)
    {
        $user = Auth::user();

        // Authorization check for reviewers
        if (!$user->isAdmin() && !$user->canAccessCategory($student->category_id)) {
            abort(403, 'You are not assigned to this student\'s category.');
        }

        $student->load([
            'category',
            'selfScores.rubricItem',
            'files',
        ]);

        $rubricItems  = RubricItem::caac()->ordered()->get()->keyBy('sub_indicator_key');
        $rubricConfig = config('rubric.caac');

        // Self-scores keyed by rubric_item_id
        $selfScoreMap = $student->selfScores->keyBy('rubric_item_id');

        // My current review
        $myReview = Review::with('scores.rubricItem')
            ->forReviewer($user->id)
            ->where('student_id', $student->id)
            ->first();

        $myScoreMap = $myReview
            ? $myReview->scores->keyBy('rubric_item_id')
            : collect();

        // All reviews (admin view)
        $allReviews = null;
        if ($user->isAdmin()) {
            $allReviews = Review::with(['reviewer', 'scores'])
                ->where('student_id', $student->id)
                ->get();
        }

        // Prev/Next navigation within same category
        $categoryStudentIds = Student::where('category_id', $student->category_id)
            ->when(!$user->isAdmin(), function ($q) use ($user) {
                $q->whereIn('category_id', $user->assignedCategories->pluck('id'));
            })
            ->orderBy('submission_id')
            ->pluck('id')
            ->toArray();

        $currentIndex = array_search($student->id, $categoryStudentIds);
        $prevStudentId = $currentIndex > 0 ? $categoryStudentIds[$currentIndex - 1] : null;
        $nextStudentId = $currentIndex < count($categoryStudentIds) - 1 ? $categoryStudentIds[$currentIndex + 1] : null;
        $positionLabel = ($currentIndex + 1) . ' of ' . count($categoryStudentIds) . ' in ' . $student->category->name;

        return view('students.show', compact(
            'student', 'rubricItems', 'rubricConfig', 'selfScoreMap',
            'myReview', 'myScoreMap', 'allReviews',
            'prevStudentId', 'nextStudentId', 'positionLabel'
        ));
    }
}

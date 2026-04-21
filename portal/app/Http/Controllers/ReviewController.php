<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Review;
use App\Models\ReviewScore;
use App\Models\RubricItem;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    public function myReviews(Request $request)
    {
        $user = Auth::user();

        $reviews = Review::with(['student.category'])
            ->forReviewer($user->id)
            ->latest('updated_at')
            ->get();

        $stats = [
            'total'       => $reviews->count(),
            'completed'   => $reviews->where('status', 'completed')->count(),
            'in_progress' => $reviews->where('status', 'in_progress')->count(),
            'pending'     => $reviews->where('status', 'pending')->count(),
        ];

        return view('reviews.mine', compact('reviews', 'stats'));
    }

    public function store(Request $request, Student $student)
    {
        $this->authorizeStudentAccess($student);

        $review = Review::firstOrCreate(
            ['student_id' => $student->id, 'reviewer_id' => Auth::id()],
            ['status' => 'pending']
        );

        return $this->saveScores($request, $review, $student);
    }

    public function update(Request $request, Student $student)
    {
        $this->authorizeStudentAccess($student);

        $review = Review::where('student_id', $student->id)
            ->where('reviewer_id', Auth::id())
            ->firstOrFail();

        if ($review->isCompleted()) {
            return back()->with('error', 'This review has already been completed and cannot be edited.');
        }

        return $this->saveScores($request, $review, $student);
    }

    public function autosave(Request $request, Student $student)
    {
        $this->authorizeStudentAccess($student);

        $review = Review::firstOrCreate(
            ['student_id' => $student->id, 'reviewer_id' => Auth::id()],
            ['status' => 'in_progress', 'started_at' => now()]
        );

        if ($review->isPending()) {
            $review->update(['status' => 'in_progress', 'started_at' => now()]);
        }

        // Don't autosave over a completed review
        if ($review->isCompleted()) {
            return response()->json(['ok' => true, 'saved_at' => now()->format('H:i:s')]);
        }

        $rubricItems = RubricItem::caac()->get()->keyBy('id');
        $scores      = $request->input('scores', []);
        $remarks     = $request->input('remarks', []);

        DB::transaction(function () use ($review, $scores, $remarks, $rubricItems) {
            // Re-check under a row lock to block concurrent complete() requests
            $locked = Review::lockForUpdate()->find($review->id);
            if ($locked->isCompleted()) {
                return;
            }

            foreach ($scores as $rubricItemId => $score) {
                $rubricItem = $rubricItems->get($rubricItemId);
                if (!$rubricItem) continue;

                $scoreVal = is_numeric($score)
                    ? min(max((float) $score, 0), $rubricItem->max_score)
                    : null;

                ReviewScore::updateOrCreate(
                    ['review_id' => $review->id, 'rubric_item_id' => $rubricItemId],
                    ['score' => $scoreVal, 'remarks' => $remarks[$rubricItemId] ?? null]
                );
            }
        });

        if ($request->has('overall_remarks')) {
            $review->update(['overall_remarks' => $request->input('overall_remarks')]);
        }

        return response()->json(['ok' => true, 'saved_at' => now()->format('H:i:s')]);
    }

    public function complete(Request $request, Student $student)
    {
        $this->authorizeStudentAccess($student);

        $request->validate([
            'overall_remarks' => 'required|string|min:10|max:5000',
            'scores'          => 'required|array',
        ]);

        $review = Review::firstOrCreate(
            ['student_id' => $student->id, 'reviewer_id' => Auth::id()],
            ['status' => 'in_progress', 'started_at' => now()]
        );

        if ($review->isCompleted()) {
            return back()->with('error', 'This review is already completed.');
        }

        $rubricItems = RubricItem::caac()->get();
        $scores      = $request->input('scores', []);
        $remarks     = $request->input('remarks', []);

        // Validate all 16 scores are filled
        $missingScores = [];
        foreach ($rubricItems as $item) {
            if (!isset($scores[$item->id]) || !is_numeric($scores[$item->id])) {
                $missingScores[] = $item->sub_indicator_label;
            }
        }

        if (!empty($missingScores)) {
            return back()->with('error', 'Please fill all scores before completing. Missing: ' . implode(', ', array_slice($missingScores, 0, 3)) . (count($missingScores) > 3 ? '...' : ''));
        }

        DB::transaction(function () use ($review, $scores, $remarks, $rubricItems, $request) {
            // Lock the review row so concurrent autosave() requests block until we finish
            $locked = Review::lockForUpdate()->find($review->id);
            if ($locked->isCompleted()) {
                return;
            }

            foreach ($rubricItems as $item) {
                $scoreVal = min(max((float) $scores[$item->id], 0), $item->max_score);
                ReviewScore::updateOrCreate(
                    ['review_id' => $review->id, 'rubric_item_id' => $item->id],
                    ['score' => $scoreVal, 'remarks' => $remarks[$item->id] ?? null]
                );
            }

            $review->update([
                'status'          => 'completed',
                'overall_remarks' => $request->input('overall_remarks'),
                'completed_at'    => now(),
            ]);
        });

        // Reload to confirm the transaction committed before logging
        $review->refresh();

        if ($review->isCompleted()) {
            try {
                ActivityLog::record('review_completed', $review, [
                    'student_id'   => $student->id,
                    'student_name' => $student->name,
                    'total_score'  => $review->totalScore(),
                ]);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('ActivityLog failed after review completion', [
                    'review_id' => $review->id,
                    'error'     => $e->getMessage(),
                ]);
            }
        }

        return redirect()->route('students.index')
            ->with('success', "Review for {$student->name} completed successfully.");
    }

    private function saveScores(Request $request, Review $review, Student $student)
    {
        $rubricItems = RubricItem::caac()->get();
        $scores      = $request->input('scores', []);
        $remarks     = $request->input('remarks', []);

        if ($review->isPending()) {
            $review->update(['status' => 'in_progress', 'started_at' => now()]);
        }

        DB::transaction(function () use ($review, $scores, $remarks, $rubricItems) {
            foreach ($rubricItems as $item) {
                if (!isset($scores[$item->id])) continue;

                $scoreVal = is_numeric($scores[$item->id])
                    ? min(max((float) $scores[$item->id], 0), $item->max_score)
                    : null;

                ReviewScore::updateOrCreate(
                    ['review_id' => $review->id, 'rubric_item_id' => $item->id],
                    ['score' => $scoreVal, 'remarks' => $remarks[$item->id] ?? null]
                );
            }
        });

        if ($request->has('overall_remarks')) {
            $review->update(['overall_remarks' => $request->input('overall_remarks')]);
        }

        ActivityLog::record('review_saved', $review, ['student_name' => $student->name]);

        return redirect()->route('students.show', $student)
            ->with('success', 'Review saved as draft.');
    }

    private function authorizeStudentAccess(Student $student): void
    {
        $user = Auth::user();
        if (!$user->isAdmin() && !$user->canAccessCategory($student->category_id)) {
            abort(403, 'You are not assigned to this category.');
        }
    }
}

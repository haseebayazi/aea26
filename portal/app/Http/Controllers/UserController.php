<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Review;
use App\Models\ReviewerAssignment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('assignedCategories')->latest()->get();
        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        $categories = Category::ordered()->get();
        return view('admin.users.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:255',
            'email'      => 'required|email|unique:users,email',
            'password'   => 'nullable|string|min:8',
            'role'       => ['required', Rule::in(['admin', 'reviewer', 'viewer'])],
            'is_active'  => 'sometimes|boolean',
            'categories' => 'sometimes|array',
            'categories.*' => 'exists:categories,id',
        ]);

        $password = $data['password'] ?: Str::random(12);

        $user = User::create([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'password'  => Hash::make($password),
            'role'      => $data['role'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        // Assign categories
        if (!empty($data['categories'])) {
            $this->syncCategoryAssignments($user, $data['categories']);
        }

        ActivityLog::record('user_created', $user, ['email' => $user->email, 'role' => $user->role]);

        $msg = "User {$user->name} created.";
        if (!$request->filled('password')) {
            $msg .= " Generated password: <strong>{$password}</strong> — note it now, it won't be shown again.";
        }

        return redirect()->route('admin.users.index')->with('success', $msg);
    }

    public function show(User $user)
    {
        $user->load(['assignedCategories', 'reviews.student.category']);
        $categories = Category::ordered()->get();
        return view('admin.users.show', compact('user', 'categories'));
    }

    public function edit(User $user)
    {
        $categories = Category::ordered()->get();
        return view('admin.users.edit', compact('user', 'categories'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'role'      => ['required', Rule::in(['admin', 'reviewer', 'viewer'])],
            'is_active' => 'sometimes|boolean',
        ]);

        $user->update([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'role'      => $data['role'],
            'is_active' => $request->boolean('is_active', $user->is_active),
        ]);

        ActivityLog::record('user_updated', $user, ['changes' => $data]);

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $userName = $user->name;
        $user->delete();

        ActivityLog::record('user_deleted', null, ['name' => $userName]);

        return redirect()->route('admin.users.index')->with('success', "User {$userName} deleted.");
    }

    public function assignCategories(Request $request, User $user)
    {
        $request->validate([
            'categories'    => 'present|array',
            'categories.*'  => 'exists:categories,id',
        ]);

        $categoryIds = $request->input('categories', []);
        $this->syncCategoryAssignments($user, $categoryIds);

        ActivityLog::record('categories_assigned', $user, ['categories' => $categoryIds]);

        return back()->with('success', 'Category assignments updated.');
    }

    public function toggleActive(User $user)
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $user->update(['is_active' => !$user->is_active]);

        ActivityLog::record($user->is_active ? 'user_activated' : 'user_deactivated', $user);

        $status = $user->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "User {$user->name} {$status}.");
    }

    public function resetPassword(Request $request, User $user)
    {
        $request->validate([
            'password' => 'nullable|string|min:8',
        ]);

        $newPassword = $request->filled('password') ? $request->input('password') : Str::random(12);
        $user->update(['password' => Hash::make($newPassword)]);

        ActivityLog::record('password_reset', $user);

        return back()->with('success', "Password reset. New password: <strong>{$newPassword}</strong>");
    }

    private function syncCategoryAssignments(User $user, array $categoryIds): void
    {
        // Remove old assignments
        ReviewerAssignment::where('user_id', $user->id)
            ->whereNotIn('category_id', $categoryIds)
            ->delete();

        // Add new assignments
        foreach ($categoryIds as $categoryId) {
            ReviewerAssignment::firstOrCreate(
                ['user_id' => $user->id, 'category_id' => $categoryId],
                ['assigned_by' => Auth::id()]
            );

            // Create pending review records for all students in this category
            $students = Student::where('category_id', $categoryId)->get();
            foreach ($students as $student) {
                Review::firstOrCreate([
                    'student_id'  => $student->id,
                    'reviewer_id' => $user->id,
                ], ['status' => 'pending']);
            }
        }
    }
}

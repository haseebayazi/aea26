@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
<div class="mb-6">
    <h1 class="text-xl font-bold text-slate-800">Welcome back, {{ auth()->user()->name }}</h1>
    <p class="text-slate-500 text-sm mt-1">Here's your review progress for the Alumni Excellence Awards 2026.</p>
</div>

{{-- Stats --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <p class="text-2xl font-bold text-slate-800">{{ $assignedCount }}</p>
        <p class="text-xs text-slate-500 mt-1">Assigned Students</p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <p class="text-2xl font-bold text-green-600">{{ $completedCount }}</p>
        <p class="text-xs text-slate-500 mt-1">Completed Reviews</p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <p class="text-2xl font-bold text-yellow-600">{{ $inProgressCount }}</p>
        <p class="text-xs text-slate-500 mt-1">In Progress</p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <p class="text-2xl font-bold text-red-500">{{ $remainingCount }}</p>
        <p class="text-xs text-slate-500 mt-1">Remaining</p>
    </div>
</div>

{{-- Progress bar --}}
@if($assignedCount > 0)
<div class="bg-white rounded-xl border border-slate-200 p-5 mb-6">
    <div class="flex items-center justify-between mb-2">
        <span class="text-sm font-medium text-slate-700">Overall Progress</span>
        <span class="text-sm font-semibold text-slate-800">{{ $completedCount }}/{{ $assignedCount }}</span>
    </div>
    <div class="w-full bg-slate-100 rounded-full h-3">
        <div class="bg-green-500 h-3 rounded-full transition-all"
             style="width: {{ $assignedCount > 0 ? round(($completedCount / $assignedCount) * 100) : 0 }}%"></div>
    </div>
    <p class="text-xs text-slate-500 mt-1.5">
        {{ $assignedCount > 0 ? round(($completedCount / $assignedCount) * 100) : 0 }}% complete
    </p>
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    {{-- Assigned categories --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <h2 class="font-semibold text-slate-800 mb-4">Your Categories</h2>
        @forelse($assignedCategories as $cat)
        <div class="flex items-center gap-3 mb-3">
            <div class="w-3 h-3 rounded-full shrink-0" style="background:{{ $cat->color }}"></div>
            <div class="flex-1">
                <p class="text-sm font-medium text-slate-700">{{ $cat->name }}</p>
                @php $catCount = \App\Models\Student::where('category_id', $cat->id)->count(); @endphp
                <p class="text-xs text-slate-500">{{ $catCount }} students</p>
            </div>
            <a href="{{ route('students.index', ['category' => $cat->id]) }}"
               class="text-xs text-blue-600 hover:underline">View →</a>
        </div>
        @empty
        <p class="text-slate-400 text-sm">No categories assigned yet. Contact your administrator.</p>
        @endforelse
    </div>

    {{-- Recent reviews --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <h2 class="font-semibold text-slate-800 mb-4">Recent Reviews</h2>
        @forelse($recentReviews as $review)
        <div class="flex items-center gap-3 mb-3">
            <div class="w-2 h-2 rounded-full shrink-0
                @if($review->status === 'completed') bg-green-500
                @elseif($review->status === 'in_progress') bg-yellow-500
                @else bg-slate-300 @endif">
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-slate-700 truncate">{{ $review->student->name }}</p>
                <p class="text-xs text-slate-400">{{ $review->student->category->name }}</p>
            </div>
            <a href="{{ route('students.show', $review->student_id) }}"
               class="text-xs text-blue-600 hover:underline shrink-0">
                {{ $review->status === 'completed' ? 'View' : 'Continue' }} →
            </a>
        </div>
        @empty
        <p class="text-slate-400 text-sm">No reviews yet.</p>
        @endforelse
    </div>
</div>

@if($nextStudent)
<div class="mt-6">
    <a href="{{ route('students.show', $nextStudent) }}"
       class="inline-flex items-center gap-3 bg-blue-900 hover:bg-blue-800 text-white px-6 py-3 rounded-xl font-semibold transition-colors">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        Continue Reviewing: {{ $nextStudent->name }}
    </a>
</div>
@endif
@endsection

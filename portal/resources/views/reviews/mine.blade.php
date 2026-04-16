@extends('layouts.app')
@section('title', 'My Reviews')

@section('content')
<div class="mb-5">
    <h1 class="text-xl font-bold text-slate-800">My Reviews</h1>
    <p class="text-sm text-slate-500 mt-1">Track and continue your review assignments.</p>
</div>

{{-- Stats --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-5">
    <div class="bg-white rounded-xl border border-slate-200 px-4 py-3">
        <p class="text-xl font-bold text-slate-800">{{ $stats['total'] }}</p>
        <p class="text-xs text-slate-500">Total Assigned</p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 px-4 py-3">
        <p class="text-xl font-bold text-green-600">{{ $stats['completed'] }}</p>
        <p class="text-xs text-slate-500">Completed</p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 px-4 py-3">
        <p class="text-xl font-bold text-yellow-600">{{ $stats['in_progress'] }}</p>
        <p class="text-xs text-slate-500">In Progress</p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 px-4 py-3">
        <p class="text-xl font-bold text-red-500">{{ $stats['pending'] }}</p>
        <p class="text-xs text-slate-500">Not Started</p>
    </div>
</div>

<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead>
            <tr class="bg-slate-50 border-b border-slate-200">
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Student</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide hidden md:table-cell">Category</th>
                <th class="text-center px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Status</th>
                <th class="text-right px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide hidden sm:table-cell">My Score</th>
                <th class="text-right px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide hidden lg:table-cell">Updated</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody>
            @forelse($reviews as $review)
            <tr class="border-b border-slate-100 hover:bg-slate-50">
                <td class="px-4 py-3 font-medium text-slate-800">
                    {{ $review->student->name }}
                    <p class="text-xs text-slate-400">#{{ $review->student->submission_id }}</p>
                </td>
                <td class="px-4 py-3 hidden md:table-cell">
                    <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full text-white"
                          style="background:{{ $review->student->category->color }}">
                        {{ Str::limit($review->student->category->name, 20) }}
                    </span>
                </td>
                <td class="px-4 py-3 text-center">
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium
                        @if($review->status === 'completed') bg-green-50 text-green-700
                        @elseif($review->status === 'in_progress') bg-yellow-50 text-yellow-700
                        @else bg-red-50 text-red-600 @endif">
                        {{ ucfirst(str_replace('_', ' ', $review->status)) }}
                    </span>
                </td>
                <td class="px-4 py-3 text-right font-semibold text-slate-700 hidden sm:table-cell">
                    @if($review->isCompleted())
                    {{ number_format($review->totalScore(), 1) }}/100
                    @else
                    —
                    @endif
                </td>
                <td class="px-4 py-3 text-right text-slate-400 text-xs hidden lg:table-cell">
                    {{ $review->updated_at->diffForHumans() }}
                </td>
                <td class="px-4 py-3 text-right">
                    <a href="{{ route('students.show', $review->student_id) }}"
                       class="text-blue-600 hover:text-blue-800 text-xs font-medium">
                        {{ $review->isCompleted() ? 'View' : ($review->isInProgress() ? 'Continue' : 'Start') }} →
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center py-12 text-slate-400">
                    No reviews assigned yet. Contact your administrator.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection

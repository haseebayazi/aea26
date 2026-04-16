@extends('layouts.app')
@section('title', $user->name)

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="flex items-center justify-between mb-5">
        <h1 class="text-xl font-bold text-slate-800">{{ $user->name }}</h1>
        <div class="flex gap-2">
            <a href="{{ route('admin.users.edit', $user) }}" class="bg-slate-700 hover:bg-slate-600 text-white text-sm font-medium py-2 px-4 rounded-xl">Edit</a>
            <a href="{{ route('admin.users.index') }}" class="border border-slate-200 text-slate-600 text-sm font-medium py-2 px-4 rounded-xl hover:bg-slate-50">← Back</a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h2 class="font-semibold text-slate-700 mb-3 text-sm">Profile</h2>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">Email</dt><dd class="font-medium text-slate-700">{{ $user->email }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Role</dt><dd>
                    <span class="px-2.5 py-1 rounded-full text-xs font-medium
                        @if($user->role === 'admin') bg-red-100 text-red-700
                        @elseif($user->role === 'reviewer') bg-blue-100 text-blue-700
                        @else bg-slate-100 text-slate-600 @endif">{{ ucfirst($user->role) }}</span>
                </dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Status</dt><dd class="{{ $user->is_active ? 'text-green-600' : 'text-red-500' }} font-medium text-xs">{{ $user->is_active ? 'Active' : 'Inactive' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Last Login</dt><dd class="text-slate-600 text-xs">{{ $user->last_login_at?->format('d M Y H:i') ?? 'Never' }}</dd></div>
            </dl>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h2 class="font-semibold text-slate-700 mb-3 text-sm">Review Stats</h2>
            @php
            $reviews   = $user->reviews;
            $completed = $reviews->where('status', 'completed')->count();
            $total     = $reviews->count();
            $avgScore  = $reviews->where('status','completed')->map(fn($r) => $r->totalScore())->avg();
            @endphp
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">Assigned</dt><dd class="font-semibold text-slate-700">{{ $total }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Completed</dt><dd class="font-semibold text-green-600">{{ $completed }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Avg Score Given</dt><dd class="font-semibold text-slate-700">{{ $avgScore ? number_format($avgScore, 1) : '—' }}/100</dd></div>
            </dl>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <h2 class="font-semibold text-slate-700 mb-3 text-sm">Assigned Categories</h2>
        <div class="flex flex-wrap gap-2">
            @forelse($user->assignedCategories as $cat)
            <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-white text-xs font-medium" style="background:{{ $cat->color }}">
                {{ $cat->name }}
            </span>
            @empty
            <p class="text-slate-400 text-sm">No categories assigned.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection

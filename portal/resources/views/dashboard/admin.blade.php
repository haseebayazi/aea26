@extends('layouts.app')
@section('title', 'Admin Dashboard')

@section('content')
{{-- Stats row --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @php
    $statCards = [
        ['label'=>'Total Students',    'value'=>$totalStudents,      'icon'=>'👥', 'color'=>'blue'],
        ['label'=>'Active Reviewers',  'value'=>$totalReviewers,     'icon'=>'🔍', 'color'=>'purple'],
        ['label'=>'Completed Reviews', 'value'=>$completedReviews,   'icon'=>'✅', 'color'=>'green'],
        ['label'=>'Completion',        'value'=>$completionPct.'%',  'icon'=>'📊', 'color'=>'yellow'],
    ];
    $colorMap = ['blue'=>'bg-blue-50 text-blue-700','purple'=>'bg-purple-50 text-purple-700','green'=>'bg-green-50 text-green-700','yellow'=>'bg-yellow-50 text-yellow-700'];
    @endphp
    @foreach($statCards as $card)
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <div class="flex items-center justify-between mb-3">
            <span class="text-2xl">{{ $card['icon'] }}</span>
            <span class="text-xs font-medium px-2 py-1 rounded-full {{ $colorMap[$card['color']] }}">{{ $card['label'] }}</span>
        </div>
        <p class="text-2xl font-bold text-slate-800">{{ $card['value'] }}</p>
        <p class="text-xs text-slate-500 mt-1">{{ $card['label'] }}</p>
    </div>
    @endforeach
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    {{-- Completion by reviewer --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <h2 class="font-semibold text-slate-800 mb-4">Reviewer Progress</h2>
        <canvas id="reviewerChart" height="250"></canvas>
    </div>

    {{-- Completion by category --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <h2 class="font-semibold text-slate-800 mb-4">Students by Category</h2>
        <canvas id="categoryChart" height="250"></canvas>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    {{-- Category stats table --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <h2 class="font-semibold text-slate-800 mb-4">Category Overview</h2>
        <table class="w-full text-sm">
            <thead>
                <tr class="text-xs text-slate-500 border-b border-slate-100">
                    <th class="text-left pb-2">Category</th>
                    <th class="text-right pb-2">Students</th>
                    <th class="text-right pb-2">Avg Self</th>
                </tr>
            </thead>
            <tbody>
                @foreach($categoryStats as $cat)
                <tr class="border-b border-slate-50">
                    <td class="py-2">
                        <div class="flex items-center gap-2">
                            <div class="w-2.5 h-2.5 rounded-full" style="background:{{ $cat->color }}"></div>
                            <span class="text-slate-700">{{ $cat->name }}</span>
                        </div>
                    </td>
                    <td class="py-2 text-right text-slate-600">{{ $cat->students_count }}</td>
                    <td class="py-2 text-right text-slate-600">{{ $selfScoreAvg[$cat->name] ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Recent activity --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <h2 class="font-semibold text-slate-800 mb-4">Recent Activity</h2>
        <div class="space-y-2 max-h-64 overflow-y-auto">
            @forelse($recentActivity as $log)
            <div class="flex items-start gap-3 text-sm">
                <div class="w-7 h-7 rounded-full bg-slate-100 flex items-center justify-center text-xs font-semibold text-slate-600 shrink-0">
                    {{ $log->user ? strtoupper(substr($log->user->name, 0, 1)) : '?' }}
                </div>
                <div>
                    <span class="text-slate-700 font-medium">{{ $log->user?->name ?? 'System' }}</span>
                    <span class="text-slate-500"> {{ str_replace('_', ' ', $log->action) }}</span>
                    <p class="text-slate-400 text-xs">{{ $log->created_at->diffForHumans() }}</p>
                </div>
            </div>
            @empty
            <p class="text-slate-400 text-sm">No activity yet.</p>
            @endforelse
        </div>
    </div>
</div>

@push('scripts')
@php
$catStatsJs = $categoryStats->map(function($c) {
    return ['name'=>$c->name,'count'=>$c->students_count,'completed'=>$c->completed_count,'color'=>$c->color];
})->values();
@endphp
<script>
const reviewerData = @json($reviewerStats);
const categoryStats = {!! json_encode($catStatsJs) !!};

// Reviewer bar chart
new Chart(document.getElementById('reviewerChart'), {
    type: 'bar',
    data: {
        labels: reviewerData.map(r => r.name.split(' ')[0]),
        datasets: [
            { label: 'Completed', data: reviewerData.map(r => r.completed), backgroundColor: '#22c55e' },
            { label: 'Total', data: reviewerData.map(r => r.total - r.completed), backgroundColor: '#e2e8f0' }
        ]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom' } },
        scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true } }
    }
});

// Category doughnut
new Chart(document.getElementById('categoryChart'), {
    type: 'doughnut',
    data: {
        labels: categoryStats.map(c => c.name),
        datasets: [{ data: categoryStats.map(c => c.count), backgroundColor: categoryStats.map(c => c.color) }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { position: 'right' } }
    }
});
</script>
@endpush
@endsection

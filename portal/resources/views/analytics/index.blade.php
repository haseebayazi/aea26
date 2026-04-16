@extends('layouts.app')
@section('title', 'Analytics')

@section('content')
<div class="mb-5 flex items-center justify-between">
    <div>
        <h1 class="text-xl font-bold text-slate-800">Analytics Dashboard</h1>
        <p class="text-sm text-slate-500 mt-1">{{ $totalStudents }} students · {{ $completedReviews }}/{{ $totalReviews }} reviews completed</p>
    </div>
    <div class="flex gap-2">
        <select id="catFilter" class="border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="0">All Categories</option>
            @foreach($categories as $cat)
            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    {{-- Chart A: Category Distribution --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <h2 class="font-semibold text-slate-800 mb-4 text-sm">Category Distribution</h2>
        <div class="relative" style="height:280px">
            <canvas id="chartA"></canvas>
        </div>
    </div>

    {{-- Chart B: Self vs Reviewer Scatter --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <h2 class="font-semibold text-slate-800 mb-1 text-sm">Self vs. Reviewer Score</h2>
        <p class="text-xs text-slate-400 mb-4">Each point = one student. Diagonal = perfect agreement.</p>
        <div class="relative" style="height:280px">
            <canvas id="chartB"></canvas>
        </div>
    </div>

    {{-- Chart C: Top 15 Candidates --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <h2 class="font-semibold text-slate-800 mb-4 text-sm">Top Candidates by Reviewer Avg</h2>
        <div class="relative" style="height:320px">
            <canvas id="chartC"></canvas>
        </div>
    </div>

    {{-- Chart D: Dimension Averages --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <h2 class="font-semibold text-slate-800 mb-4 text-sm">Dimension Averages by Category</h2>
        <div class="relative" style="height:320px">
            <canvas id="chartD"></canvas>
        </div>
    </div>

    {{-- Chart E: Reviewer Agreement Table --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5 lg:col-span-2">
        <h2 class="font-semibold text-slate-800 mb-1 text-sm">Reviewer Agreement</h2>
        <p class="text-xs text-slate-400 mb-4">Students with ≥2 completed reviews. Flagged = std dev &gt; 5 points.</p>
        <div class="overflow-x-auto">
            <table class="w-full text-xs" id="agreementTable">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100">
                        <th class="text-left px-3 py-2 text-slate-500 font-semibold">Student</th>
                        <th class="text-left px-3 py-2 text-slate-500 font-semibold">Category</th>
                        <th class="text-right px-3 py-2 text-slate-500 font-semibold">Mean</th>
                        <th class="text-right px-3 py-2 text-slate-500 font-semibold">Std Dev</th>
                        <th class="text-left px-3 py-2 text-slate-500 font-semibold">Scores</th>
                        <th class="text-center px-3 py-2 text-slate-500 font-semibold">Flag</th>
                    </tr>
                </thead>
                <tbody id="agreementBody">
                    <tr><td colspan="6" class="text-center py-6 text-slate-400">Loading…</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Chart F: Per-student Radar --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5 lg:col-span-2">
        <div class="flex items-center gap-4 mb-4">
            <h2 class="font-semibold text-slate-800 text-sm">Individual Student Radar</h2>
            <select id="studentSelect" class="border border-slate-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 flex-1 max-w-xs">
                <option value="">Select a student…</option>
                @foreach(\App\Models\Student::orderBy('name')->get() as $s)
                <option value="{{ $s->id }}">#{{ $s->submission_id }} — {{ $s->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="relative mx-auto" style="max-width:420px; height:320px">
            <canvas id="chartF"></canvas>
        </div>
    </div>

</div>

@push('scripts')
<script>
const apiBase = '{{ route("analytics.chart", "__CHART__") }}'.replace('__CHART__', '');
const catFilter = document.getElementById('catFilter');

// Chart instances
let chartA, chartB, chartC, chartD, chartF;

async function fetchChart(name, params = {}) {
    const url = new URL(apiBase + name, window.location.origin);
    const catId = catFilter?.value || 0;
    if (catId) url.searchParams.set('category_id', catId);
    Object.entries(params).forEach(([k, v]) => url.searchParams.set(k, v));
    const res = await fetch(url.toString());
    return res.json();
}

// Chart A — Category Distribution
async function loadChartA() {
    const data = await fetchChart('category-distribution');
    if (chartA) chartA.destroy();
    chartA = new Chart(document.getElementById('chartA'), {
        type: 'doughnut',
        data: {
            labels: data.map(d => d.label),
            datasets: [{ data: data.map(d => d.count), backgroundColor: data.map(d => d.color), borderWidth: 2, borderColor: '#fff' }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right', labels: { font: { size: 11 } } } } }
    });
}

// Chart B — Self vs Reviewer Scatter
async function loadChartB() {
    const data = await fetchChart('self-vs-reviewer');
    const maxVal = Math.max(...data.map(d => Math.max(d.x, d.y)), 100);
    if (chartB) chartB.destroy();
    chartB = new Chart(document.getElementById('chartB'), {
        type: 'scatter',
        data: {
            datasets: [
                { label: 'Students', data: data, backgroundColor: '#3b82f680', pointRadius: 5 },
                { label: '45° line', data: [{x:0,y:0},{x:100,y:100}], type: 'line', borderColor: '#94a3b8', borderDash: [5,5], pointRadius: 0, fill: false }
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: {
                tooltip: { callbacks: { label: ctx => `${ctx.raw.name}: Self ${ctx.raw.x}, Rev ${ctx.raw.y}` } },
                legend: { display: false }
            },
            scales: {
                x: { title: { display: true, text: 'Self Score', font: { size: 11 } }, min: 0, max: 100 },
                y: { title: { display: true, text: 'Reviewer Avg', font: { size: 11 } }, min: 0, max: 100 }
            }
        }
    });
}

// Chart C — Top 15
async function loadChartC() {
    const data = await fetchChart('top-candidates');
    if (chartC) chartC.destroy();
    chartC = new Chart(document.getElementById('chartC'), {
        type: 'bar',
        data: {
            labels: data.map(d => d.name.split(' ').slice(0,2).join(' ')),
            datasets: [
                { label: 'Reviewer Avg', data: data.map(d => d.reviewer_avg), backgroundColor: '#3b82f6' },
                { label: 'Self Score', data: data.map(d => d.self_total), backgroundColor: '#e2e8f0' }
            ]
        },
        options: {
            indexAxis: 'y',
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } },
            scales: { x: { beginAtZero: true, max: 100 } }
        }
    });
}

// Chart D — Dimension Averages
async function loadChartD() {
    const data = await fetchChart('dimension-averages');
    const dims = ['impact', 'leadership_service', 'innovation_creativity', 'ethics_engagement'];
    const labels = ['Impact', 'Leadership', 'Innovation', 'Ethics'];
    const colors = ['#3b82f6', '#8b5cf6', '#059669', '#dc2626'];

    if (chartD) chartD.destroy();
    chartD = new Chart(document.getElementById('chartD'), {
        type: 'bar',
        data: {
            labels,
            datasets: data.map((cat, i) => ({
                label: cat.category,
                data: dims.map(d => cat.averages[d] || 0),
                backgroundColor: cat.color + 'cc',
                borderColor: cat.color,
                borderWidth: 1,
            }))
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { font: { size: 10 } } } },
            scales: { x: { stacked: false }, y: { beginAtZero: true } }
        }
    });
}

// Agreement table
async function loadAgreement() {
    const data = await fetchChart('reviewer-agreement');
    const tbody = document.getElementById('agreementBody');
    if (!data.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-6 text-slate-400">No students with multiple completed reviews yet.</td></tr>';
        return;
    }
    tbody.innerHTML = data.map(row => `
        <tr class="border-b border-slate-50 ${row.flagged ? 'bg-red-50' : ''}">
            <td class="px-3 py-2 font-medium text-slate-700">${row.name}</td>
            <td class="px-3 py-2 text-slate-500">${row.category}</td>
            <td class="px-3 py-2 text-right font-semibold text-slate-700">${row.mean}</td>
            <td class="px-3 py-2 text-right ${row.flagged ? 'text-red-600 font-bold' : 'text-slate-600'}">${row.std_dev}</td>
            <td class="px-3 py-2 text-slate-500">${row.scores.join(' / ')}</td>
            <td class="px-3 py-2 text-center">${row.flagged ? '<span class="text-red-600 font-bold">⚠ High variance</span>' : '<span class="text-green-600">✓</span>'}</td>
        </tr>
    `).join('');
}

// Chart F — Student Radar
let radarChart = null;
async function loadRadar(studentId) {
    const data = await fetchChart('student-radar', { student_id: studentId });
    if (radarChart) radarChart.destroy();
    radarChart = new Chart(document.getElementById('chartF'), {
        type: 'radar',
        data: {
            labels: data.labels,
            datasets: [
                { label: 'Self', data: data.self, backgroundColor: '#3b82f620', borderColor: '#3b82f6', pointBackgroundColor: '#3b82f6' },
                { label: 'Reviewer Avg', data: data.reviewer_avg, backgroundColor: '#dc262620', borderColor: '#dc2626', pointBackgroundColor: '#dc2626' }
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' }, title: { display: true, text: data.student_name, font: { size: 13 } } },
            scales: { r: { beginAtZero: true, ticks: { font: { size: 10 } } } }
        }
    });
}

document.getElementById('studentSelect')?.addEventListener('change', e => {
    if (e.target.value) loadRadar(e.target.value);
});

catFilter?.addEventListener('change', () => {
    loadChartB(); loadChartC(); loadChartD();
});

// Init
loadChartA(); loadChartB(); loadChartC(); loadChartD(); loadAgreement();
</script>
@endpush
@endsection

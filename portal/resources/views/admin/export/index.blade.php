@extends('layouts.app')
@section('title', 'Export')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <h1 class="text-xl font-bold text-slate-800">Export Data</h1>
        <p class="text-sm text-slate-500">{{ $totalStudents }} students · {{ $completedReviews }} completed reviews</p>
    </div>

    <div class="space-y-4">
        {{-- Full Excel --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5 flex items-start gap-4">
            <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <div class="flex-1">
                <h2 class="font-semibold text-slate-800 text-sm">Full Excel Export</h2>
                <p class="text-xs text-slate-500 mt-1">All students × (self-scores + all reviewers' scores + avg) per rubric item. Includes remarks.</p>
            </div>
            <a href="{{ route('admin.export.full') }}"
               class="bg-green-600 hover:bg-green-700 text-white text-sm font-medium py-2 px-4 rounded-xl transition-colors shrink-0">
                Download .xlsx
            </a>
        </div>

        {{-- Summary CSV --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5 flex items-start gap-4">
            <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
            </div>
            <div class="flex-1">
                <h2 class="font-semibold text-slate-800 text-sm">Summary CSV</h2>
                <p class="text-xs text-slate-500 mt-1">Submission ID, Name, Category, Self Total, Avg Reviewer Total, #Reviews, Rank in Category.</p>
            </div>
            <a href="{{ route('admin.export.summary') }}"
               class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium py-2 px-4 rounded-xl transition-colors shrink-0">
                Download .csv
            </a>
        </div>

        {{-- Winners Report --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5 flex items-start gap-4">
            <div class="w-10 h-10 bg-yellow-100 rounded-xl flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
            </div>
            <div class="flex-1">
                <h2 class="font-semibold text-slate-800 text-sm">Winners Report (PDF)</h2>
                <p class="text-xs text-slate-500 mt-1">Top 3 per category with detailed score breakdowns. Formatted for ceremony use.</p>
            </div>
            <a href="{{ route('admin.export.winners') }}"
               class="bg-yellow-500 hover:bg-yellow-600 text-white text-sm font-medium py-2 px-4 rounded-xl transition-colors shrink-0">
                Download PDF
            </a>
        </div>

        {{-- Per-student PDF --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <div class="flex items-start gap-4 mb-4">
                <div class="w-10 h-10 bg-red-100 rounded-xl flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                </div>
                <div>
                    <h2 class="font-semibold text-slate-800 text-sm">Individual Student PDF</h2>
                    <p class="text-xs text-slate-500 mt-1">Profile + self-scores + reviewer scores + remarks for a single student.</p>
                </div>
            </div>
            <div class="flex gap-3">
                <select id="pdfStudent" class="flex-1 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Select student…</option>
                    @foreach(\App\Models\Student::orderBy('submission_id')->get() as $s)
                    <option value="{{ $s->id }}">#{{ $s->submission_id }} — {{ $s->name }}</option>
                    @endforeach
                </select>
                <button onclick="downloadStudentPdf()"
                        class="bg-red-500 hover:bg-red-600 text-white text-sm font-medium py-2 px-4 rounded-xl transition-colors">
                    Download PDF
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function downloadStudentPdf() {
    const sel = document.getElementById('pdfStudent');
    const id = sel.value;
    if (!id) { alert('Please select a student first.'); return; }
    window.open('{{ url("/admin/export/student") }}/' + id + '/pdf', '_blank');
}
</script>
@endpush
@endsection

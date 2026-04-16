@extends('layouts.app')
@section('title', 'Students')

@section('content')
<div x-data="studentList()" x-init="init()">

    {{-- Top stats --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
        <div class="bg-white rounded-xl border border-slate-200 px-4 py-3">
            <p class="text-xl font-bold text-slate-800">{{ $totalAssigned }}</p>
            <p class="text-xs text-slate-500">Total Assigned</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 px-4 py-3">
            <p class="text-xl font-bold text-green-600">{{ $reviewedByMe }}</p>
            <p class="text-xs text-slate-500">Reviewed by Me</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 px-4 py-3">
            <p class="text-xl font-bold text-red-500">{{ $pending }}</p>
            <p class="text-xs text-slate-500">Pending</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 px-4 py-3">
            <p class="text-xl font-bold text-blue-600">{{ $avgScore ? number_format($avgScore, 1) : '—' }}</p>
            <p class="text-xs text-slate-500">My Avg Score</p>
        </div>
    </div>

    {{-- Filters toolbar --}}
    <div class="bg-white rounded-xl border border-slate-200 mb-4">
        {{-- Category tabs --}}
        <div class="flex overflow-x-auto border-b border-slate-100">
            <button @click="activeCategory = null"
                    :class="activeCategory === null ? 'border-b-2 border-blue-600 text-blue-600 font-semibold' : 'text-slate-500 hover:text-slate-700'"
                    class="px-4 py-3 text-sm whitespace-nowrap transition-colors shrink-0">
                All ({{ $totalAssigned }})
            </button>
            @foreach($categories as $cat)
            <button @click="activeCategory = '{{ $cat->id }}'"
                    :class="activeCategory === '{{ $cat->id }}' ? 'border-b-2 border-blue-600 text-blue-600 font-semibold' : 'text-slate-500 hover:text-slate-700'"
                    class="px-4 py-3 text-sm whitespace-nowrap transition-colors shrink-0 flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full inline-block" style="background:{{ $cat->color }}"></span>
                {{ $cat->name }}
            </button>
            @endforeach
        </div>

        {{-- Search + status filter --}}
        <div class="flex flex-col sm:flex-row gap-3 p-3">
            <div class="relative flex-1">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
                </svg>
                <input type="text" x-model="search" placeholder="Search name, ID, department…"
                       class="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <select x-model="activeStatus"
                    class="border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">All Statuses</option>
                <option value="not_started">Not Started</option>
                <option value="in_progress">In Progress</option>
                <option value="completed">Completed</option>
            </select>
            <button @click="sortDir = sortDir === 'asc' ? 'desc' : 'asc'"
                    class="px-3 py-2 border border-slate-200 rounded-lg text-sm text-slate-600 hover:bg-slate-50">
                Sort: <span x-text="sortCol === 'self_score' ? 'Self Score' : 'ID'"></span>
                <span x-text="sortDir === 'asc' ? '↑' : '↓'"></span>
            </button>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide cursor-pointer"
                            @click="setSort('submission_id')">#</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Name</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide hidden md:table-cell">Category</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide hidden lg:table-cell">Dept/Campus</th>
                        <th class="text-right px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide cursor-pointer"
                            @click="setSort('self_score')">Self Score</th>
                        <th class="text-center px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">My Status</th>
                        @auth @if(auth()->user()->isAdmin())
                        <th class="text-center px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide hidden lg:table-cell">Reviews</th>
                        @endif @endauth
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($students as $student)
                    <tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors student-row"
                        data-category="{{ $student->category_id }}"
                        data-status="{{ $student->my_review_status }}"
                        data-search="{{ strtolower($student->submission_id . ' ' . $student->name . ' ' . $student->department . ' ' . $student->campus) }}"
                        data-self-score="{{ $student->self_score_total }}"
                        data-submission-id="{{ $student->submission_id }}">
                        <td class="px-4 py-3 text-slate-500 text-xs">{{ $student->submission_id }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('students.show', $student) }}"
                               class="font-medium text-slate-800 hover:text-blue-700">{{ $student->name }}</a>
                            @if($student->batch)
                            <p class="text-xs text-slate-400">{{ $student->batch }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 hidden md:table-cell">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium text-white"
                                  style="background:{{ $student->category->color }}">
                                {{ Str::limit($student->category->name, 20) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 hidden lg:table-cell text-slate-500 text-xs">
                            {{ $student->department }}<br>{{ $student->campus }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <span class="font-semibold text-slate-700">{{ number_format($student->self_score_total, 1) }}</span>
                            <span class="text-slate-400 text-xs">/100</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($student->my_review_status === 'completed')
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-green-50 text-green-700 rounded-full text-xs font-medium">
                                <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span> Completed
                            </span>
                            @elseif($student->my_review_status === 'in_progress')
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-yellow-50 text-yellow-700 rounded-full text-xs font-medium">
                                <span class="w-1.5 h-1.5 bg-yellow-500 rounded-full"></span> In Progress
                            </span>
                            @else
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-red-50 text-red-600 rounded-full text-xs font-medium">
                                <span class="w-1.5 h-1.5 bg-red-400 rounded-full"></span> Not Started
                            </span>
                            @endif
                        </td>
                        @auth @if(auth()->user()->isAdmin())
                        <td class="px-4 py-3 text-center text-xs text-slate-500 hidden lg:table-cell">
                            {{ $student->completed_reviews }}/{{ $student->total_reviews }}
                        </td>
                        @endif @endauth
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('students.show', $student) }}"
                               class="text-blue-600 hover:text-blue-800 text-xs font-medium whitespace-nowrap">
                                {{ $student->my_review_status === 'completed' ? 'View →' : 'Review →' }}
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Empty state --}}
        <div id="emptyState" class="hidden text-center py-12">
            <p class="text-slate-400">No students match your filters.</p>
        </div>
    </div>

    {{-- Row count --}}
    <p class="text-xs text-slate-500 mt-2 px-1">
        Showing <span id="visibleCount">{{ $students->count() }}</span> of {{ $students->count() }} students
    </p>
</div>

@push('scripts')
<script>
function studentList() {
    return {
        activeCategory: null,
        activeStatus: '',
        search: '',
        sortCol: 'submission_id',
        sortDir: 'asc',
        rows: [],

        init() {
            this.rows = Array.from(document.querySelectorAll('.student-row'));
            this.$watch('activeCategory', () => this.filter());
            this.$watch('activeStatus', () => this.filter());
            this.$watch('search', () => this.filter());
            this.$watch('sortDir', () => this.sortRows());
        },

        setSort(col) {
            if (this.sortCol === col) {
                this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortCol = col;
                this.sortDir = 'asc';
            }
            this.sortRows();
        },

        filter() {
            const search = this.search.toLowerCase();
            let visible = 0;

            this.rows.forEach(row => {
                const cat    = row.dataset.category;
                const status = row.dataset.status;
                const text   = row.dataset.search;

                const catOk    = !this.activeCategory || cat === this.activeCategory;
                const statusOk = !this.activeStatus || status === this.activeStatus;
                const searchOk = !search || text.includes(search);

                const show = catOk && statusOk && searchOk;
                row.style.display = show ? '' : 'none';
                if (show) visible++;
            });

            document.getElementById('visibleCount').textContent = visible;
            const empty = document.getElementById('emptyState');
            if (empty) empty.classList.toggle('hidden', visible > 0);
        },

        sortRows() {
            const tbody = document.querySelector('tbody');
            const sorted = [...this.rows].sort((a, b) => {
                let va, vb;
                if (this.sortCol === 'self_score') {
                    va = parseFloat(a.dataset.selfScore);
                    vb = parseFloat(b.dataset.selfScore);
                } else {
                    va = parseInt(a.dataset.submissionId);
                    vb = parseInt(b.dataset.submissionId);
                }
                return this.sortDir === 'asc' ? va - vb : vb - va;
            });
            sorted.forEach(row => tbody.appendChild(row));
        }
    }
}
</script>
@endpush
@endsection

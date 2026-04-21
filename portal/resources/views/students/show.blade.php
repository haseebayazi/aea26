@extends('layouts.app')
@section('title', $student->name)

@section('content')
<div x-data="reviewForm()" x-init="init()" @keydown.left.window="prevStudent()" @keydown.right.window="nextStudent()">

    {{-- Navigation --}}
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-3 text-sm">
            @if($prevStudentId)
            <a href="{{ route('students.show', $prevStudentId) }}"
               class="flex items-center gap-1 text-slate-500 hover:text-slate-700">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Previous
            </a>
            @endif
            <span class="text-slate-400 text-xs px-3 py-1 bg-slate-100 rounded-full">{{ $positionLabel }}</span>
            @if($nextStudentId)
            <a href="{{ route('students.show', $nextStudentId) }}"
               class="flex items-center gap-1 text-slate-500 hover:text-slate-700">
                Next
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
            @endif
        </div>
        <a href="{{ route('students.index') }}" class="text-xs text-slate-500 hover:text-slate-700">← Back to List</a>
    </div>

    {{-- Main two-column layout --}}
    <div class="flex flex-col lg:flex-row gap-6">

        {{-- LEFT: Profile Card (40%) --}}
        <div class="lg:w-2/5 space-y-4">

            {{-- Profile header --}}
            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <div class="flex items-start gap-4 mb-4">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white font-bold text-lg shrink-0"
                         style="background:{{ $student->category->color }}">
                        {{ strtoupper(substr($student->name, 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <h1 class="text-lg font-bold text-slate-800 leading-tight">{{ $student->name }}</h1>
                        <div class="flex flex-wrap items-center gap-2 mt-1.5">
                            <span class="text-xs bg-slate-100 text-slate-600 px-2 py-0.5 rounded-full">
                                #{{ $student->submission_id }}
                            </span>
                            <span class="text-xs px-2 py-0.5 rounded-full text-white text-xs font-medium"
                                  style="background:{{ $student->category->color }}">
                                {{ $student->category->name }}
                            </span>
                            @if($myReview)
                            <span class="text-xs px-2 py-0.5 rounded-full font-medium
                                @if($myReview->status === 'completed') bg-green-100 text-green-700
                                @elseif($myReview->status === 'in_progress') bg-yellow-100 text-yellow-700
                                @else bg-slate-100 text-slate-500 @endif">
                                {{ ucfirst(str_replace('_', ' ', $myReview->status)) }}
                            </span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Personal info --}}
                <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                    @if($student->department)
                    <div><span class="text-slate-400 text-xs">Department</span><p class="text-slate-700 font-medium text-xs mt-0.5">{{ $student->department }}</p></div>
                    @endif
                    @if($student->campus)
                    <div><span class="text-slate-400 text-xs">Campus</span><p class="text-slate-700 font-medium text-xs mt-0.5">{{ $student->campus }}</p></div>
                    @endif
                    @if($student->batch)
                    <div><span class="text-slate-400 text-xs">Reg No.</span><p class="text-slate-700 font-medium text-xs mt-0.5">{{ $student->batch }}</p></div>
                    @endif
                    @if($student->email)
                    <div><span class="text-slate-400 text-xs">Email</span><p class="text-slate-700 font-medium text-xs mt-0.5 truncate">{{ $student->email }}</p></div>
                    @endif
                    @if($student->phone)
                    <div><span class="text-slate-400 text-xs">Phone</span><p class="text-slate-700 font-medium text-xs mt-0.5">{{ $student->phone }}</p></div>
                    @endif
                </div>

                @if($student->additional_info)
                <div class="mt-3 p-3 bg-slate-50 rounded-lg text-xs text-slate-500">
                    {{ $student->additional_info }}
                </div>
                @endif
            </div>

            {{-- Citation --}}
            @if($student->citation)
            <div class="bg-white rounded-xl border border-slate-200" x-data="{ open: false }">
                <button @click="open = !open" class="w-full flex items-center justify-between px-5 py-3 text-sm font-medium text-slate-700">
                    <span>Citation / Profile Summary</span>
                    <svg class="w-4 h-4 text-slate-400 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" x-transition class="px-5 pb-4 text-sm text-slate-600 leading-relaxed whitespace-pre-wrap">{{ $student->citation }}</div>
            </div>
            @endif

            {{-- Files --}}
            @auth
            @php $isAdmin = auth()->user()->isAdmin(); @endphp
            @endauth
            @if($student->files->isNotEmpty() || $student->cv_path || $student->citation_path || (isset($isAdmin) && $isAdmin))
            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <h3 class="text-sm font-semibold text-slate-700 mb-3">Documents</h3>
                @if($student->files->isNotEmpty())
                <div class="space-y-2">
                    @foreach($student->files as $file)
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-red-50 rounded-lg flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-medium text-slate-700 truncate">{{ $file->original_name }}</p>
                            <p class="text-xs text-slate-400">{{ ucfirst($file->file_type) }} · {{ $file->humanSize() }}</p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            @if(in_array($file->mime_type, ['application/pdf', 'image/jpeg', 'image/png', 'image/gif', 'image/webp'])
                                || str_ends_with(strtolower($file->original_name), '.pdf'))
                            <a href="{{ route('files.view', $file) }}" target="_blank"
                               class="text-xs text-emerald-600 hover:underline">View</a>
                            @endif
                            <a href="{{ route('files.download', $file) }}"
                               class="text-xs text-blue-600 hover:underline">Download</a>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
                @auth @if(auth()->user()->isAdmin())
                <div class="{{ $student->files->isNotEmpty() ? 'mt-3 pt-3 border-t border-slate-100' : '' }}">
                    <form action="{{ route('students.files.upload', $student) }}" method="POST" enctype="multipart/form-data" class="flex gap-2">
                        @csrf
                        <input type="file" name="file" class="text-xs border border-slate-200 rounded p-1 flex-1 min-w-0">
                        <select name="file_type" class="text-xs border border-slate-200 rounded px-2">
                            <option value="cv">CV</option>
                            <option value="citation">Citation</option>
                            <option value="supporting">Supporting</option>
                            <option value="other">Other</option>
                        </select>
                        <button type="submit" class="text-xs bg-blue-600 text-white px-3 py-1 rounded">Upload</button>
                    </form>
                </div>
                @endif @endauth
            </div>
            @endif

            {{-- Self-Score Summary --}}
            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <h3 class="text-sm font-semibold text-slate-700 mb-3">Self-Assessment Summary</h3>
                @php
                $rubricConf = config('rubric.caac');
                $dimColors = ['impact'=>'bg-blue-500','leadership_service'=>'bg-purple-500','innovation_creativity'=>'bg-green-500','ethics_engagement'=>'bg-yellow-500'];
                @endphp
                @foreach($rubricConf as $dimKey => $dim)
                @php
                    $dimItems = \App\Models\RubricItem::caac()->forDimension($dimKey)->pluck('id');
                    $dimSelfTotal = $selfScoreMap->whereIn('rubric_item_id', $dimItems->toArray())->sum('score');
                    $pct = $dim['total'] > 0 ? ($dimSelfTotal / $dim['total']) * 100 : 0;
                @endphp
                <div class="mb-3">
                    <div class="flex justify-between text-xs mb-1">
                        <span class="text-slate-600 font-medium">{{ $dim['label'] }}</span>
                        <span class="text-slate-500">{{ number_format($dimSelfTotal, 1) }}/{{ $dim['total'] }}</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-2">
                        <div class="{{ $dimColors[$dimKey] ?? 'bg-blue-500' }} h-2 rounded-full"
                             style="width: {{ min($pct, 100) }}%"></div>
                    </div>
                </div>
                @endforeach
                <div class="flex justify-between text-sm font-semibold text-slate-700 mt-3 pt-3 border-t border-slate-100">
                    <span>Self Total</span>
                    <span>{{ number_format($selfScoreMap->sum('score'), 1) }}/100</span>
                </div>
            </div>

        </div>{{-- end left --}}

        {{-- RIGHT: Review Form (60%) --}}
        <div class="lg:w-3/5">

            @if($myReview && $myReview->isCompleted())
            {{-- Read-only completed view --}}
            <div class="bg-green-50 border border-green-200 rounded-xl px-5 py-3 mb-4 flex items-center gap-3">
                <svg class="w-5 h-5 text-green-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div>
                    <p class="text-sm font-semibold text-green-800">Review completed</p>
                    <p class="text-xs text-green-600">Completed {{ $myReview->completed_at?->format('d M Y H:i') }}</p>
                </div>
            </div>
            @endif

            <form id="reviewForm"
                  action="{{ $myReview
                    ? route('reviews.update', $student)
                    : route('reviews.store', $student) }}"
                  method="POST">
                @csrf
                @if($myReview) @method('PUT') @endif

                @php $totalMyScore = 0; @endphp

                {{-- Dimension accordions --}}
                @foreach($rubricConf as $dimKey => $dim)
                @php
                    $dimRubricItems = $rubricItems->filter(fn($i) => $i->dimension === $dimKey)->values();
                    $dimMyTotal = 0;
                    $dimSelfTotal = 0;
                    foreach($dimRubricItems as $item) {
                        $myScore = $myScoreMap->get($item->id)?->score;
                        $selfScore = $selfScoreMap->get($item->id)?->score ?? 0;
                        if (is_numeric($myScore)) $dimMyTotal += $myScore;
                        $dimSelfTotal += $selfScore;
                    }
                    $totalMyScore += $dimMyTotal;
                @endphp
                <div class="bg-white rounded-xl border border-slate-200 mb-4 overflow-hidden"
                     x-data="{ open: {{ $loop->first ? 'true' : 'false' }} }">
                    <button type="button" @click="open = !open"
                            class="w-full flex items-center justify-between px-5 py-4 text-left">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg {{ $dimColors[$dimKey] ?? 'bg-blue-500' }} flex items-center justify-center text-white text-xs font-bold">
                                {{ $dim['weight'] }}%
                            </div>
                            <div>
                                <p class="font-semibold text-slate-800 text-sm">{{ $dim['label'] }}</p>
                                <p class="text-xs text-slate-400">{{ $dim['total'] }} points max</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="text-right hidden sm:block">
                                <p class="text-sm font-bold text-slate-800">{{ number_format($dimMyTotal, 1) }}/{{ $dim['total'] }}</p>
                                <p class="text-xs text-slate-400">Self: {{ number_format($dimSelfTotal, 1) }}</p>
                            </div>
                            <svg class="w-4 h-4 text-slate-400 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                    </button>

                    <div x-show="open" x-transition class="border-t border-slate-100">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="bg-slate-50 text-xs text-slate-500">
                                        <th class="text-left px-4 py-2 font-medium">Indicator (max)</th>
                                        <th class="text-center px-3 py-2 font-medium w-16">Self</th>
                                        <th class="text-center px-3 py-2 font-medium w-24">Your Score</th>
                                        <th class="text-center px-3 py-2 font-medium w-14">Δ</th>
                                        <th class="text-left px-3 py-2 font-medium">Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($dimRubricItems as $item)
                                    @php
                                        $selfScore  = $selfScoreMap->get($item->id)?->score ?? 0;
                                        $myScore    = $myScoreMap->get($item->id)?->score;
                                        $myRemarks  = $myScoreMap->get($item->id)?->remarks ?? '';
                                        $selfBrief  = $selfScoreMap->get($item->id)?->remarks ?? '';
                                        $delta      = (is_numeric($myScore) && is_numeric($selfScore)) ? ($myScore - $selfScore) : null;
                                        $pctOfMax   = $item->max_score > 0 && is_numeric($myScore) ? ($myScore / $item->max_score) * 100 : null;
                                        $scoreColor = $pctOfMax === null ? '' : ($pctOfMax >= 70 ? 'ring-green-300 bg-green-50' : ($pctOfMax >= 40 ? 'ring-yellow-300 bg-yellow-50' : 'ring-red-300 bg-red-50'));
                                    @endphp
                                    <tr class="border-b border-slate-50">
                                        <td class="px-4 py-3">
                                            <p class="font-medium text-slate-700 text-xs">{{ $item->sub_indicator_label }}</p>
                                            <p class="text-slate-400 text-xs">max {{ $item->max_score }}</p>
                                            @if($selfBrief)
                                            <div x-data="{ exp: false }" class="mt-1">
                                                <p class="text-slate-500 text-xs italic leading-relaxed"
                                                   :class="exp ? '' : 'line-clamp-2'">{{ $selfBrief }}</p>
                                                @if(strlen($selfBrief) > 100)
                                                <button type="button" @click.stop="exp = !exp"
                                                        class="text-blue-500 text-xs hover:underline mt-0.5 focus:outline-none">
                                                    <span x-text="exp ? 'Show less ▲' : 'Read more ▼'"></span>
                                                </button>
                                                @endif
                                            </div>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3 text-center">
                                            <span class="font-semibold text-slate-600 text-sm">{{ $selfScore }}</span>
                                        </td>
                                        <td class="px-3 py-3 text-center">
                                            @if($myReview && $myReview->isCompleted())
                                            <span class="font-bold text-slate-800">{{ $myScore ?? '—' }}</span>
                                            @else
                                            <input type="number"
                                                   name="scores[{{ $item->id }}]"
                                                   value="{{ $myScore !== null ? $myScore : '' }}"
                                                   min="0" max="{{ $item->max_score }}" step="0.5"
                                                   class="w-20 text-center border rounded-lg px-2 py-1.5 text-sm font-semibold ring-1 {{ $scoreColor ?: 'ring-slate-200' }} focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                   @change="updateScoreColor(this, {{ $item->max_score }}); scheduleAutosave()"
                                                   data-rubric="{{ $item->id }}"
                                                   data-max="{{ $item->max_score }}">
                                            @endif
                                        </td>
                                        <td class="px-3 py-3 text-center">
                                            @if($delta !== null)
                                            <span class="text-xs font-semibold {{ $delta > 0 ? 'text-green-600' : ($delta < 0 ? 'text-red-500' : 'text-slate-400') }}">
                                                {{ $delta > 0 ? '+' : '' }}{{ number_format($delta, 1) }}
                                            </span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3">
                                            @if($myReview && $myReview->isCompleted())
                                            <p class="text-xs text-slate-500">{{ $myRemarks ?: '—' }}</p>
                                            @else
                                            <textarea name="remarks[{{ $item->id }}]"
                                                      rows="2"
                                                      placeholder="Optional remarks…"
                                                      class="w-full text-xs border border-slate-200 rounded-lg px-2 py-1.5 resize-none focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                      @input="scheduleAutosave()">{{ $myRemarks }}</textarea>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @endforeach

                {{-- Grand total --}}
                <div class="bg-slate-800 rounded-xl px-5 py-4 text-white mb-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-slate-400 mb-1">Score Summary</p>
                            <div class="flex items-center gap-4">
                                <div class="text-center">
                                    <p class="text-lg font-bold text-white" id="myTotal">{{ number_format($totalMyScore, 1) }}</p>
                                    <p class="text-xs text-slate-400">My Score</p>
                                </div>
                                <span class="text-slate-500">/</span>
                                <div class="text-center">
                                    <p class="text-lg font-bold text-slate-300">100</p>
                                    <p class="text-xs text-slate-400">Max</p>
                                </div>
                                <span class="text-slate-500">|</span>
                                <div class="text-center">
                                    <p class="text-lg font-bold text-yellow-400">{{ number_format($selfScoreMap->sum('score'), 1) }}</p>
                                    <p class="text-xs text-slate-400">Self Score</p>
                                </div>
                                @php $grandDelta = $totalMyScore - $selfScoreMap->sum('score'); @endphp
                                <div class="text-center">
                                    <p class="text-lg font-bold {{ $grandDelta > 0 ? 'text-green-400' : ($grandDelta < 0 ? 'text-red-400' : 'text-slate-300') }}">
                                        {{ $grandDelta > 0 ? '+' : '' }}{{ number_format($grandDelta, 1) }}
                                    </p>
                                    <p class="text-xs text-slate-400">Δ</p>
                                </div>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-slate-400" id="autosaveStatus">
                                @if($myReview) Last saved: {{ $myReview->updated_at->format('H:i:s') }} @endif
                            </p>
                        </div>
                    </div>
                </div>

                @if(!($myReview && $myReview->isCompleted()))
                {{-- Overall remarks --}}
                <div class="bg-white rounded-xl border border-slate-200 p-5 mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Overall Remarks <span class="text-red-500">*</span>
                        <span class="text-xs font-normal text-slate-400">(required for completion)</span>
                    </label>
                    <textarea name="overall_remarks" id="overallRemarks" rows="4"
                              placeholder="Provide your overall assessment of this candidate, including strengths, areas for improvement, and recommendation…"
                              class="w-full border border-slate-200 rounded-lg px-4 py-3 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-blue-500"
                              @input="scheduleAutosave()" required minlength="10">{{ $myReview?->overall_remarks }}</textarea>
                </div>

                {{-- Action buttons --}}
                <div class="flex flex-col sm:flex-row gap-3">
                    <button type="submit"
                            class="flex-1 bg-slate-700 hover:bg-slate-600 text-white font-semibold py-3 px-5 rounded-xl transition-colors flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                        Save Draft
                    </button>

                    <button type="button" @click="confirmComplete()"
                            class="flex-1 bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-5 rounded-xl transition-colors flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Mark as Completed
                    </button>
                </div>
                @endif

            </form>

            {{-- All Reviews panel (admin only) --}}
            @if(auth()->user()->isAdmin() && $allReviews && $allReviews->isNotEmpty())
            <div class="bg-white rounded-xl border border-slate-200 mt-6 overflow-hidden">
                <div class="px-5 py-3 border-b border-slate-100">
                    <h3 class="font-semibold text-slate-800 text-sm">All Reviewer Scores</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="bg-slate-50">
                                <th class="text-left px-4 py-2 text-slate-500">Reviewer</th>
                                <th class="text-center px-3 py-2 text-slate-500">Status</th>
                                <th class="text-right px-4 py-2 text-slate-500">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($allReviews as $rev)
                            <tr class="border-b border-slate-50">
                                <td class="px-4 py-2 font-medium text-slate-700">{{ $rev->reviewer->name }}</td>
                                <td class="px-3 py-2 text-center">
                                    <span class="px-2 py-0.5 rounded-full
                                        @if($rev->status === 'completed') bg-green-100 text-green-700
                                        @elseif($rev->status === 'in_progress') bg-yellow-100 text-yellow-700
                                        @else bg-slate-100 text-slate-500 @endif">
                                        {{ ucfirst(str_replace('_', ' ', $rev->status)) }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-right font-semibold text-slate-700">
                                    {{ $rev->status === 'completed' ? number_format($rev->totalScore(), 1) : '—' }}
                                </td>
                            </tr>
                            @endforeach
                            @if($allReviews->where('status', 'completed')->count() > 1)
                            @php $avgTotal = $allReviews->where('status','completed')->map(fn($r)=>$r->totalScore())->avg(); @endphp
                            <tr class="bg-blue-50">
                                <td class="px-4 py-2 font-bold text-blue-800">Average</td>
                                <td></td>
                                <td class="px-4 py-2 text-right font-bold text-blue-800">{{ number_format($avgTotal, 1) }}</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

        </div>{{-- end right --}}
    </div>

    {{-- Complete confirmation modal --}}
    <div x-show="showConfirm" x-cloak
         class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl p-6 max-w-md w-full shadow-2xl" @click.stop>
            <h3 class="text-lg font-bold text-slate-800 mb-2">Complete this review?</h3>
            <p class="text-sm text-slate-500 mb-6">
                This will mark your review as <strong>completed</strong> and cannot be undone.
                Make sure all 16 scores are filled and your overall remarks are provided.
            </p>
            <div class="flex gap-3">
                <button @click="showConfirm = false"
                        class="flex-1 border border-slate-200 text-slate-600 font-medium py-2.5 px-4 rounded-xl hover:bg-slate-50">
                    Cancel
                </button>
                <button @click="submitComplete()"
                        class="flex-1 bg-green-600 hover:bg-green-700 text-white font-semibold py-2.5 px-4 rounded-xl transition-colors">
                    Yes, Complete
                </button>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
function reviewForm() {
    return {
        showConfirm: false,
        autosaveTimer: null,
        autosaveUrl: '{{ route("reviews.autosave", $student) }}',
        csrfToken: '{{ csrf_token() }}',

        init() {},

        prevStudent() {
            @if($prevStudentId)
            window.location.href = '{{ route("students.show", $prevStudentId) }}';
            @endif
        },

        nextStudent() {
            @if($nextStudentId)
            window.location.href = '{{ route("students.show", $nextStudentId) }}';
            @endif
        },

        updateScoreColor(input, max) {
            const val = parseFloat(input.value);
            if (isNaN(val)) {
                input.className = input.className.replace(/ring-(green|yellow|red)-300 bg-(green|yellow|red)-50/g, 'ring-slate-200');
                return;
            }
            const pct = (val / max) * 100;
            let cls = pct >= 70 ? 'ring-green-300 bg-green-50' : (pct >= 40 ? 'ring-yellow-300 bg-yellow-50' : 'ring-red-300 bg-red-50');
            input.className = input.className
                .replace(/ring-(green|yellow|red|slate)-\d+ bg-(green|yellow|red|slate)-\d+/g, '')
                .trim() + ' ' + cls;
            this.updateTotal();
        },

        updateTotal() {
            let total = 0;
            document.querySelectorAll('input[name^="scores["]').forEach(inp => {
                const v = parseFloat(inp.value);
                if (!isNaN(v)) total += v;
            });
            const el = document.getElementById('myTotal');
            if (el) el.textContent = total.toFixed(1);
        },

        scheduleAutosave() {
            clearTimeout(this.autosaveTimer);
            this.autosaveTimer = setTimeout(() => this.autosave(), 30000);
        },

        async autosave() {
            const form = document.getElementById('reviewForm');
            const data = new FormData(form);
            const payload = { _token: this.csrfToken, scores: {}, remarks: {} };

            for (let [key, val] of data.entries()) {
                const scMatch = key.match(/^scores\[(\d+)\]$/);
                const rmMatch = key.match(/^remarks\[(\d+)\]$/);
                if (scMatch) payload.scores[scMatch[1]] = val;
                if (rmMatch) payload.remarks[rmMatch[1]] = val;
            }
            payload.overall_remarks = document.getElementById('overallRemarks')?.value;

            try {
                const res = await fetch(this.autosaveUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrfToken },
                    body: JSON.stringify(payload),
                });
                const json = await res.json();
                const el = document.getElementById('autosaveStatus');
                if (el) el.textContent = 'Auto-saved at ' + (json.saved_at || '');
            } catch (e) {
                console.warn('Autosave failed', e);
            }
        },

        confirmComplete() {
            // Validate all 16 scores filled
            let missing = [];
            document.querySelectorAll('input[name^="scores["]').forEach(inp => {
                if (inp.value === '' || isNaN(parseFloat(inp.value))) {
                    missing.push(inp.dataset.rubric);
                }
            });
            if (missing.length > 0) {
                alert(`Please fill all ${missing.length} missing score(s) before completing.`);
                return;
            }
            const remarks = document.getElementById('overallRemarks')?.value?.trim();
            if (!remarks || remarks.length < 10) {
                alert('Please provide overall remarks (minimum 10 characters) before completing.');
                return;
            }
            this.showConfirm = true;
        },

        submitComplete() {
            this.showConfirm = false;
            // Change form action to complete endpoint
            const form = document.getElementById('reviewForm');
            form.action = '{{ route("reviews.complete", $student) }}';
            // Remove method override if present
            const methodInput = form.querySelector('input[name="_method"]');
            if (methodInput) methodInput.remove();
            form.submit();
        }
    }
}
</script>
@endpush
@endsection

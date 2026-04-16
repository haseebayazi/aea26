@extends('layouts.app')
@section('title', 'Preview Import')

@section('content')
<div class="max-w-5xl mx-auto">
    <div class="mb-5">
        <h1 class="text-xl font-bold text-slate-800">Preview Import</h1>
        <p class="text-sm text-slate-500">First 10 rows — review before executing.</p>
    </div>

    @if($flaggedRows)
    <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 mb-5">
        <p class="text-sm font-semibold text-yellow-800 mb-2">⚠ {{ count($flaggedRows) }} rows have issues:</p>
        @foreach($flaggedRows as $rowNum => $issues)
        <p class="text-xs text-yellow-700">Row {{ $rowNum }}: {{ implode(' | ', $issues) }}</p>
        @endforeach
    </div>
    @endif

    <div class="bg-white rounded-xl border border-slate-200 mb-5 overflow-hidden">
        <div class="overflow-x-auto text-xs">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-slate-50">
                        <th class="border border-slate-200 px-2 py-2 text-slate-500">Sr.no</th>
                        <th class="border border-slate-200 px-2 py-2 text-slate-500">Name</th>
                        <th class="border border-slate-200 px-2 py-2 text-slate-500">Email</th>
                        <th class="border border-slate-200 px-2 py-2 text-slate-500">Dept</th>
                        <th class="border border-slate-200 px-2 py-2 text-slate-500">Campus</th>
                        @foreach($rubricItems as $item)
                        <th class="border border-slate-200 px-2 py-2 text-slate-500 whitespace-nowrap">{{ $item->sub_indicator_label }}</th>
                        @endforeach
                        <th class="border border-slate-200 px-2 py-2 text-slate-500">Issues</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($preview as $rowIdx => $row)
                    @php $issues = $flaggedRows[$rowIdx + 2] ?? []; @endphp
                    <tr class="{{ $issues ? 'bg-yellow-50' : 'hover:bg-slate-50' }}">
                        <td class="border border-slate-100 px-2 py-1.5">{{ $row[$mapping['submission_col']] ?? '' }}</td>
                        <td class="border border-slate-100 px-2 py-1.5 font-medium">{{ $row[$mapping['name_col']] ?? '' }}</td>
                        <td class="border border-slate-100 px-2 py-1.5 text-slate-400 max-w-24 truncate">{{ $mapping['email_col'] ? ($row[$mapping['email_col']] ?? '') : '' }}</td>
                        <td class="border border-slate-100 px-2 py-1.5 text-slate-400">{{ $mapping['dept_col'] ? ($row[$mapping['dept_col']] ?? '') : '' }}</td>
                        <td class="border border-slate-100 px-2 py-1.5 text-slate-400">{{ $mapping['campus_col'] ? ($row[$mapping['campus_col']] ?? '') : '' }}</td>
                        @foreach($scoreColumns as [$scoreRelIdx, $briefRelIdx, $rubricKey])
                        @php $val = $row[(int)$mapping['score_start'] + $scoreRelIdx] ?? null; @endphp
                        <td class="border border-slate-100 px-2 py-1.5 text-center {{ is_numeric($val) ? 'text-slate-700' : 'text-slate-300' }}">{{ $val }}</td>
                        @endforeach
                        <td class="border border-slate-100 px-2 py-1.5 text-yellow-600">{{ implode(', ', $issues) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <form action="{{ route('admin.import.execute') }}" method="POST" class="flex gap-3">
        @csrf
        <a href="{{ route('admin.import') }}" class="flex-1 border border-slate-200 text-slate-600 font-medium py-3 px-5 rounded-xl text-center hover:bg-slate-50">
            ← Start Over
        </a>
        <button type="submit"
                class="flex-1 bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-5 rounded-xl transition-colors">
            Execute Import →
        </button>
    </form>
</div>
@endsection

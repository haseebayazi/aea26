@extends('layouts.app')
@section('title', 'Map Columns')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-5">
        <h1 class="text-xl font-bold text-slate-800">Map Columns</h1>
        <p class="text-sm text-slate-500">Select which Excel columns correspond to each field.</p>
    </div>

    {{-- Sheet selection & preview --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5 mb-5">
        <h2 class="font-semibold text-slate-700 mb-3 text-sm">File Preview (first 5 rows)</h2>
        <div class="overflow-x-auto text-xs">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-slate-50">
                        @foreach($headers as $i => $h)
                        <th class="border border-slate-200 px-2 py-1.5 text-left font-medium text-slate-600 whitespace-nowrap">
                            Col {{ $i+1 }}: {{ $h ?? '—' }}
                        </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($preview as $row)
                    <tr class="hover:bg-slate-50">
                        @foreach($row as $cell)
                        <td class="border border-slate-200 px-2 py-1 text-slate-500 max-w-32 truncate" title="{{ $cell }}">
                            {{ Str::limit((string)$cell, 30) }}
                        </td>
                        @endforeach
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <form action="{{ route('admin.import.map') }}" method="POST">
        @csrf

        <div class="bg-white rounded-xl border border-slate-200 p-5 mb-5">
            <h2 class="font-semibold text-slate-700 mb-4 text-sm">Column Mapping</h2>

            <div class="mb-4">
                <label class="block text-xs font-medium text-slate-600 mb-1">Sheet</label>
                <select name="sheet" class="border border-slate-200 rounded-lg px-3 py-2 text-sm w-full max-w-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @foreach($sheetNames as $sn)
                    <option value="{{ $sn }}">{{ $sn }}</option>
                    @endforeach
                </select>
            </div>

            @php
            $fields = [
                ['name'=>'submission_col', 'label'=>'Submission ID (Sr.no)', 'req'=>true],
                ['name'=>'name_col',       'label'=>'Student Name',          'req'=>true],
                ['name'=>'email_col',      'label'=>'Email',                 'req'=>false],
                ['name'=>'phone_col',      'label'=>'Phone',                 'req'=>false],
                ['name'=>'dept_col',       'label'=>'Department',            'req'=>false],
                ['name'=>'campus_col',     'label'=>'Campus',                'req'=>false],
                ['name'=>'batch_col',      'label'=>'Reg No. / Batch',       'req'=>false],
                ['name'=>'score_start',    'label'=>'Score Columns Start (0-indexed col of first score)', 'req'=>true],
            ];
            @endphp

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @foreach($fields as $field)
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">
                        {{ $field['label'] }}
                        @if($field['req'])<span class="text-red-500">*</span>@endif
                    </label>
                    <select name="{{ $field['name'] }}" {{ $field['req'] ? 'required' : '' }}
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @if(!$field['req'])<option value="">— skip —</option>@endif
                        @foreach($headers as $i => $h)
                        <option value="{{ $i }}">Col {{ $i+1 }}: {{ $h }}</option>
                        @endforeach
                    </select>
                </div>
                @endforeach
            </div>

            <div class="mt-4 p-3 bg-blue-50 rounded-lg text-xs text-blue-700">
                <strong>Score Start:</strong> Enter the 0-indexed column number where "Career Score" begins.
                For "Professional Achievement", this is column index 16 (col 17).
                For all other files, it's column index 14 (col 15).
                Subsequent score/brief pairs follow in order.
            </div>
        </div>

        <div class="flex gap-3">
            <a href="{{ route('admin.import') }}" class="flex-1 border border-slate-200 text-slate-600 font-medium py-3 px-5 rounded-xl text-center hover:bg-slate-50">
                ← Back
            </a>
            <button type="submit" class="flex-1 bg-blue-900 hover:bg-blue-800 text-white font-semibold py-3 px-5 rounded-xl">
                Preview Import →
            </button>
        </div>
    </form>
</div>
@endsection

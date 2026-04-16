@extends('layouts.app')
@section('title', 'Import Data')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <h1 class="text-xl font-bold text-slate-800">Import Student Data</h1>
        <p class="text-sm text-slate-500 mt-1">Upload an Excel/CSV file to import students and self-scores.</p>
    </div>

    {{-- Steps indicator --}}
    <div class="flex items-center justify-between mb-8 px-4">
        @foreach(['Upload', 'Map Columns', 'Preview', 'Execute', 'Results'] as $i => $step)
        <div class="flex items-center {{ $loop->last ? '' : 'flex-1' }}">
            <div class="flex flex-col items-center">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold
                    {{ $i === 0 ? 'bg-blue-600 text-white' : 'bg-slate-200 text-slate-400' }}">
                    {{ $i + 1 }}
                </div>
                <span class="text-xs mt-1 {{ $i === 0 ? 'text-blue-600 font-medium' : 'text-slate-400' }}">{{ $step }}</span>
            </div>
            @if(!$loop->last)
            <div class="flex-1 h-0.5 bg-slate-200 mx-2 mb-5"></div>
            @endif
        </div>
        @endforeach
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <form action="{{ route('admin.import.upload') }}" method="POST" enctype="multipart/form-data"
              x-data="{ dragging: false, fileName: '' }">
            @csrf

            <div class="mb-5">
                <label class="block text-sm font-medium text-slate-700 mb-2">Category</label>
                <select name="category_id" required
                        class="w-full border border-slate-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Select category…</option>
                    @foreach(\App\Models\Category::ordered()->get() as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-slate-700 mb-2">Excel File (.xlsx, .xls, .csv)</label>
                <div class="border-2 border-dashed rounded-xl p-8 text-center transition-colors cursor-pointer"
                     :class="dragging ? 'border-blue-400 bg-blue-50' : 'border-slate-300 hover:border-blue-300'"
                     @dragover.prevent="dragging = true"
                     @dragleave="dragging = false"
                     @drop.prevent="dragging = false; $refs.fileInput.files = $event.dataTransfer.files; fileName = $event.dataTransfer.files[0]?.name"
                     @click="$refs.fileInput.click()">
                    <svg class="w-10 h-10 mx-auto text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <p class="text-sm text-slate-500" x-show="!fileName">Drag & drop or <span class="text-blue-600 font-medium">browse</span></p>
                    <p class="text-sm font-medium text-blue-700" x-show="fileName" x-text="fileName"></p>
                    <p class="text-xs text-slate-400 mt-1">Max 50MB · xlsx, xls, csv</p>
                    <input type="file" name="excel_file" required accept=".xlsx,.xls,.csv"
                           class="hidden" x-ref="fileInput"
                           @change="fileName = $event.target.files[0]?.name">
                </div>
                @error('excel_file') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <button type="submit"
                    class="w-full bg-blue-900 hover:bg-blue-800 text-white font-semibold py-3 px-5 rounded-xl transition-colors">
                Upload & Continue →
            </button>
        </form>
    </div>

    <div class="mt-4 p-4 bg-blue-50 rounded-xl text-sm text-blue-700">
        <strong>Tip:</strong> The existing 4 Excel files have already been seeded via the database seeder.
        Use this wizard to re-import or import additional data.
    </div>
</div>
@endsection

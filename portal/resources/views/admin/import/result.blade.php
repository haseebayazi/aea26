@extends('layouts.app')
@section('title', 'Import Complete')

@section('content')
<div class="max-w-lg mx-auto text-center">
    <div class="bg-white rounded-2xl border border-slate-200 p-8">
        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <h1 class="text-xl font-bold text-slate-800 mb-2">Import Complete!</h1>
        <p class="text-slate-500 text-sm mb-6">Category: <strong>{{ $category->name }}</strong></p>

        <div class="grid grid-cols-3 gap-4 mb-6">
            <div class="bg-green-50 rounded-xl p-4">
                <p class="text-2xl font-bold text-green-700">{{ $success }}</p>
                <p class="text-xs text-green-600 mt-1">Imported</p>
            </div>
            <div class="bg-yellow-50 rounded-xl p-4">
                <p class="text-2xl font-bold text-yellow-700">{{ $skipped }}</p>
                <p class="text-xs text-yellow-600 mt-1">Skipped</p>
            </div>
            <div class="bg-red-50 rounded-xl p-4">
                <p class="text-2xl font-bold text-red-700">{{ count($errors) }}</p>
                <p class="text-xs text-red-600 mt-1">Errors</p>
            </div>
        </div>

        @if($errors)
        <div class="bg-red-50 rounded-xl p-4 mb-6 text-left">
            <p class="text-xs font-semibold text-red-700 mb-2">Errors (showing first 10):</p>
            @foreach(array_slice($errors, 0, 10) as $err)
            <p class="text-xs text-red-600">· {{ $err }}</p>
            @endforeach
        </div>
        @endif

        <div class="flex gap-3">
            <a href="{{ route('admin.import') }}"
               class="flex-1 border border-slate-200 text-slate-600 font-medium py-2.5 px-4 rounded-xl text-center hover:bg-slate-50 text-sm">
                Import Another
            </a>
            <a href="{{ route('students.index') }}"
               class="flex-1 bg-blue-900 text-white font-semibold py-2.5 px-4 rounded-xl text-center hover:bg-blue-800 text-sm">
                View Students →
            </a>
        </div>
    </div>
</div>
@endsection

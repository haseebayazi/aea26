@extends('layouts.app')
@section('title', 'Add User')

@section('content')
<div class="max-w-xl mx-auto">
    <div class="mb-5">
        <h1 class="text-xl font-bold text-slate-800">Add User</h1>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <form action="{{ route('admin.users.store') }}" method="POST" x-data="{ autoGen: true }">
            @csrf
            <div class="space-y-5">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           class="w-full px-4 py-2.5 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('name') border-red-300 @enderror">
                    @error('name') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                           class="w-full px-4 py-2.5 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('email') border-red-300 @enderror">
                    @error('email') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Password</label>
                    <div class="flex items-center gap-3 mb-2">
                        <input type="checkbox" id="autoGen" x-model="autoGen" class="w-4 h-4 text-blue-600">
                        <label for="autoGen" class="text-sm text-slate-600">Auto-generate password</label>
                    </div>
                    <input type="password" name="password" x-bind:disabled="autoGen" x-bind:required="!autoGen"
                           placeholder="Min 8 characters"
                           class="w-full px-4 py-2.5 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:bg-slate-100">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Role</label>
                    <select name="role" required class="w-full border border-slate-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="reviewer" {{ old('role') === 'reviewer' ? 'selected' : '' }}>Reviewer</option>
                        <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="viewer" {{ old('role') === 'viewer' ? 'selected' : '' }}>Viewer</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Assign Categories</label>
                    <div class="space-y-2">
                        @foreach($categories as $cat)
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" name="categories[]" value="{{ $cat->id }}"
                                   class="w-4 h-4 text-blue-600 rounded border-slate-300">
                            <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background:{{ $cat->color }}"></span>
                            <span class="text-sm text-slate-700">{{ $cat->name }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <input type="checkbox" id="is_active" name="is_active" value="1" checked class="w-4 h-4 text-blue-600">
                    <label for="is_active" class="text-sm text-slate-700">Active (can log in)</label>
                </div>
            </div>

            <div class="flex gap-3 mt-6">
                <a href="{{ route('admin.users.index') }}"
                   class="flex-1 border border-slate-200 text-slate-600 font-medium py-2.5 px-4 rounded-xl text-center text-sm hover:bg-slate-50">Cancel</a>
                <button type="submit"
                        class="flex-1 bg-blue-900 hover:bg-blue-800 text-white font-semibold py-2.5 px-4 rounded-xl text-sm">Create User</button>
            </div>
        </form>
    </div>
</div>
@endsection

@extends('layouts.app')
@section('title', 'Edit User')

@section('content')
<div class="max-w-xl mx-auto">
    <div class="mb-5 flex items-center justify-between">
        <h1 class="text-xl font-bold text-slate-800">Edit: {{ $user->name }}</h1>
        <a href="{{ route('admin.users.index') }}" class="text-sm text-slate-500 hover:text-slate-700">← Back</a>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-6 mb-5">
        <form action="{{ route('admin.users.update', $user) }}" method="POST">
            @csrf @method('PUT')
            <div class="space-y-5">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Name</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                           class="w-full px-4 py-2.5 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @error('name') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                           class="w-full px-4 py-2.5 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @error('email') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Role</label>
                    <select name="role" required class="w-full border border-slate-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @foreach(['reviewer','admin','viewer'] as $r)
                        <option value="{{ $r }}" {{ $user->role === $r ? 'selected' : '' }}>{{ ucfirst($r) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-center gap-3">
                    <input type="checkbox" id="is_active" name="is_active" value="1" {{ $user->is_active ? 'checked' : '' }} class="w-4 h-4 text-blue-600">
                    <label for="is_active" class="text-sm text-slate-700">Active</label>
                </div>
            </div>
            <button type="submit" class="w-full mt-6 bg-blue-900 hover:bg-blue-800 text-white font-semibold py-2.5 px-4 rounded-xl text-sm">Save Changes</button>
        </form>
    </div>

    {{-- Category assignment --}}
    <div class="bg-white rounded-xl border border-slate-200 p-6 mb-5">
        <h2 class="font-semibold text-slate-700 mb-4 text-sm">Category Assignments</h2>
        <form action="{{ route('admin.users.assign', $user) }}" method="POST">
            @csrf
            <div class="space-y-2 mb-4">
                @foreach($categories as $cat)
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="categories[]" value="{{ $cat->id }}"
                           {{ $user->assignedCategories->contains('id', $cat->id) ? 'checked' : '' }}
                           class="w-4 h-4 text-blue-600 rounded border-slate-300">
                    <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background:{{ $cat->color }}"></span>
                    <span class="text-sm text-slate-700">{{ $cat->name }}</span>
                    @php $count = \App\Models\Student::where('category_id', $cat->id)->count(); @endphp
                    <span class="text-xs text-slate-400 ml-auto">{{ $count }} students</span>
                </label>
                @endforeach
            </div>
            <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-2.5 px-4 rounded-xl text-sm">
                Update Assignments
            </button>
        </form>
    </div>

    {{-- Reset password --}}
    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <h2 class="font-semibold text-slate-700 mb-4 text-sm">Reset Password</h2>
        <form action="{{ route('admin.users.reset-password', $user) }}" method="POST" class="flex gap-3">
            @csrf
            <input type="text" name="password" placeholder="New password (blank = auto-generate)"
                   class="flex-1 px-4 py-2.5 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <button type="submit" class="bg-yellow-500 hover:bg-yellow-600 text-white font-semibold py-2.5 px-4 rounded-xl text-sm">
                Reset
            </button>
        </form>
    </div>
</div>
@endsection

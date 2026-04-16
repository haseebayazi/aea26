@extends('layouts.app')
@section('title', 'Users')

@section('content')
<div class="flex items-center justify-between mb-5">
    <div>
        <h1 class="text-xl font-bold text-slate-800">User Management</h1>
        <p class="text-sm text-slate-500">{{ $users->count() }} users total</p>
    </div>
    <a href="{{ route('admin.users.create') }}"
       class="bg-blue-900 hover:bg-blue-800 text-white font-semibold py-2 px-4 rounded-xl text-sm transition-colors">
        + Add User
    </a>
</div>

<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead>
            <tr class="bg-slate-50 border-b border-slate-200">
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Name</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase hidden sm:table-cell">Email</th>
                <th class="text-center px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Role</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase hidden lg:table-cell">Categories</th>
                <th class="text-center px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Status</th>
                <th class="text-right px-4 py-3 text-xs font-semibold text-slate-500 uppercase hidden md:table-cell">Last Login</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody>
            @foreach($users as $user)
            <tr class="border-b border-slate-100 hover:bg-slate-50">
                <td class="px-4 py-3">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-blue-600 flex items-center justify-center text-white text-xs font-semibold shrink-0">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                        <span class="font-medium text-slate-800">{{ $user->name }}</span>
                        @if($user->id === auth()->id())
                        <span class="text-xs bg-blue-100 text-blue-600 px-1.5 py-0.5 rounded">You</span>
                        @endif
                    </div>
                </td>
                <td class="px-4 py-3 text-slate-500 hidden sm:table-cell">{{ $user->email }}</td>
                <td class="px-4 py-3 text-center">
                    <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium
                        @if($user->role === 'admin') bg-red-100 text-red-700
                        @elseif($user->role === 'reviewer') bg-blue-100 text-blue-700
                        @else bg-slate-100 text-slate-600 @endif">
                        {{ ucfirst($user->role) }}
                    </span>
                </td>
                <td class="px-4 py-3 hidden lg:table-cell">
                    <div class="flex flex-wrap gap-1">
                        @foreach($user->assignedCategories as $cat)
                        <span class="text-xs px-2 py-0.5 rounded-full text-white" style="background:{{ $cat->color }}">
                            {{ Str::limit($cat->name, 15) }}
                        </span>
                        @endforeach
                        @if($user->assignedCategories->isEmpty())
                        <span class="text-slate-300 text-xs">None</span>
                        @endif
                    </div>
                </td>
                <td class="px-4 py-3 text-center">
                    <form action="{{ route('admin.users.toggle', $user) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit"
                                class="text-xs px-2.5 py-1 rounded-full font-medium transition-colors
                                    {{ $user->is_active ? 'bg-green-50 text-green-700 hover:bg-red-50 hover:text-red-700' : 'bg-red-50 text-red-700 hover:bg-green-50 hover:text-green-700' }}">
                            {{ $user->is_active ? 'Active' : 'Inactive' }}
                        </button>
                    </form>
                </td>
                <td class="px-4 py-3 text-right text-slate-400 text-xs hidden md:table-cell">
                    {{ $user->last_login_at?->diffForHumans() ?? 'Never' }}
                </td>
                <td class="px-4 py-3 text-right">
                    <div class="flex items-center justify-end gap-2">
                        <a href="{{ route('admin.users.show', $user) }}" class="text-blue-600 hover:text-blue-800 text-xs">View</a>
                        <a href="{{ route('admin.users.edit', $user) }}" class="text-slate-500 hover:text-slate-700 text-xs">Edit</a>
                        @if($user->id !== auth()->id())
                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST" onsubmit="return confirm('Delete user {{ addslashes($user->name) }}?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-500 hover:text-red-700 text-xs">Delete</button>
                        </form>
                        @endif
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection

<div x-cloak x-show="sidebarOpen" x-transition.opacity @click="sidebarOpen = false" class="fixed inset-0 z-40 bg-slate-950/50 lg:hidden" aria-hidden="true"></div>
<aside id="app-sidebar" :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" @keydown.escape.window="sidebarOpen = false" class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col border-r border-slate-200 bg-white transition-transform duration-200 lg:translate-x-0" aria-label="Main navigation">
    <div class="flex h-20 items-center justify-between border-b border-slate-100 px-6">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 text-xl font-bold tracking-tight text-slate-900">
            StudentVault.
        </a>
        <button type="button" @click="sidebarOpen = false" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 lg:hidden" aria-label="Close navigation">
            <i class="fa-solid fa-xmark text-lg" aria-hidden="true"></i>
        </button>
    </div>
    <nav class="flex-1 space-y-7 overflow-y-auto px-4 py-6" aria-label="StudentVault">
        <form method="GET" action="{{ route('search.index') }}" class="pb-1">
            <label for="navigation-search" class="sr-only">Search your vault</label>
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400" aria-hidden="true">⌕</span>
                <input
                    id="navigation-search"
                    type="search"
                    name="q"
                    value="{{ request()->routeIs('search.*') && is_string(request('q')) ? request('q') : '' }}"
                    placeholder="Search vault"
                    class="w-full rounded-xl border-slate-200 bg-slate-50 py-2 pl-9 pr-3 text-sm text-slate-700 shadow-sm focus:border-primary-700 focus:ring-primary-700"
                >
            </div>
        </form>

        <div>
            <a href="{{ route('dashboard') }}" @class(['flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition', 'bg-primary-50 text-primary-700' => request()->routeIs('dashboard'), 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' => ! request()->routeIs('dashboard')]) @if(request()->routeIs('dashboard')) aria-current="page" @endif><i class="fa-solid fa-house w-5 text-center" aria-hidden="true"></i> Dashboard</a>
        </div>
        <div class="space-y-1">
            <p class="px-4 pb-2 text-xs font-bold uppercase tracking-widest text-slate-400">My Space</p>
            <a href="{{ route('notes.index') }}" @class(['flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium transition', 'bg-primary-50 text-primary-700' => request()->routeIs('notes.*'), 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' => ! request()->routeIs('notes.*')]) @if(request()->routeIs('notes.*')) aria-current="page" @endif><i class="fa-solid fa-note-sticky w-5 text-center" aria-hidden="true"></i> Notes</a>
            <a href="{{ route('vault.index') }}" @class(['flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium transition', 'bg-primary-50 text-primary-700' => request()->routeIs('vault.*'), 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' => ! request()->routeIs('vault.*')]) @if(request()->routeIs('vault.*')) aria-current="page" @endif><i class="fa-solid fa-folder-closed w-5 text-center" aria-hidden="true"></i> Files</a>
            <a href="{{ route('courses.index') }}" @class(['flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium transition', 'bg-primary-50 text-primary-700' => request()->routeIs('courses.*'), 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' => ! request()->routeIs('courses.*')]) @if(request()->routeIs('courses.*')) aria-current="page" @endif><i class="fa-solid fa-graduation-cap w-5 text-center" aria-hidden="true"></i> Courses</a>
            <a href="{{ route('starred.index') }}" @class(['flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium transition', 'bg-primary-50 text-primary-700' => request()->routeIs('starred.*'), 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' => ! request()->routeIs('starred.*')]) @if(request()->routeIs('starred.*')) aria-current="page" @endif><i class="fa-solid fa-star w-5 text-center" aria-hidden="true"></i> Starred</a>
        </div>
        <div class="space-y-1">
            <p class="px-4 pb-2 text-xs font-bold uppercase tracking-widest text-slate-400">Personal</p>
            <span class="flex items-center justify-between rounded-xl px-4 py-3 text-sm text-slate-400"><span>Personal Vault</span><span class="text-xs">Soon</span></span>
            <a href="{{ route('trash.index') }}" @class(['flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium transition', 'bg-primary-50 text-primary-700' => request()->routeIs('trash.*'), 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' => ! request()->routeIs('trash.*')]) @if(request()->routeIs('trash.*')) aria-current="page" @endif><i class="fa-solid fa-trash-can w-5 text-center" aria-hidden="true"></i> Trash</a>
        </div>
        <div class="space-y-1">
            <p class="px-4 pb-2 text-xs font-bold uppercase tracking-widest text-slate-400">Account</p>
            <a href="{{ route('profile.edit') }}" @class(['flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium transition', 'bg-primary-50 text-primary-700' => request()->routeIs('profile.*'), 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' => ! request()->routeIs('profile.*')]) @if(request()->routeIs('profile.*')) aria-current="page" @endif><i class="fa-solid fa-user w-5 text-center" aria-hidden="true"></i> Profile</a>
        </div>
    </nav>
    <div class="border-t border-slate-100 p-4">
        <p class="truncate px-3 text-sm font-semibold text-slate-800">{{ Auth::user()->name }}</p>
        <p class="truncate px-3 text-xs text-slate-500">{{ Auth::user()->email }}</p>
        <form method="POST" action="{{ route('logout') }}" class="mt-3">
            @csrf
            <button type="submit" class="w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-900">Log out</button>
        </form>
    </div>
</aside>

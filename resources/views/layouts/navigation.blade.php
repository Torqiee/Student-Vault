<div x-cloak x-show="sidebarOpen" x-transition.opacity @click="sidebarOpen = false" class="fixed inset-0 z-40 bg-slate-950/50 lg:hidden" aria-hidden="true"></div>
<aside id="app-sidebar" :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" @keydown.escape.window="sidebarOpen = false" class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col border-r border-slate-200 bg-white transition-transform duration-200 lg:translate-x-0" aria-label="Main navigation">
    <div class="flex h-20 items-center justify-between border-b border-slate-100 px-6">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 text-xl font-bold tracking-tight text-slate-900">
            StudentVault.
        </a>
        <button type="button" @click="sidebarOpen = false" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 lg:hidden" aria-label="Close navigation">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18" /></svg>
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
                    class="w-full rounded-xl border-slate-200 bg-slate-50 py-2 pl-9 pr-3 text-sm text-slate-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
            </div>
        </form>

        <div>
            <a href="{{ route('dashboard') }}" @class(['flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition', 'bg-indigo-50 text-indigo-700' => request()->routeIs('dashboard'), 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' => ! request()->routeIs('dashboard')]) @if(request()->routeIs('dashboard')) aria-current="page" @endif><span aria-hidden="true">◫</span> Dashboard</a>
        </div>
        <div class="space-y-1">
            <p class="px-4 pb-2 text-xs font-bold uppercase tracking-widest text-slate-400">My Space</p>
            <a href="{{ route('notes.index') }}" @class(['flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium transition', 'bg-indigo-50 text-indigo-700' => request()->routeIs('notes.*'), 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' => ! request()->routeIs('notes.*')]) @if(request()->routeIs('notes.*')) aria-current="page" @endif><span aria-hidden="true">▤</span> Notes</a>
            <a href="{{ route('vault.index') }}" @class(['flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium transition', 'bg-indigo-50 text-indigo-700' => request()->routeIs('vault.*'), 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' => ! request()->routeIs('vault.*')]) @if(request()->routeIs('vault.*')) aria-current="page" @endif><span aria-hidden="true">▣</span> Files</a>
            <a href="{{ route('courses.index') }}" @class(['flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium transition', 'bg-indigo-50 text-indigo-700' => request()->routeIs('courses.*'), 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' => ! request()->routeIs('courses.*')]) @if(request()->routeIs('courses.*')) aria-current="page" @endif><span aria-hidden="true">▦</span> Courses</a>
            <a href="{{ route('starred.index') }}" @class(['flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium transition', 'bg-indigo-50 text-indigo-700' => request()->routeIs('starred.*'), 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' => ! request()->routeIs('starred.*')]) @if(request()->routeIs('starred.*')) aria-current="page" @endif><span aria-hidden="true">★</span> Starred</a>
        </div>
        <div class="space-y-1">
            <p class="px-4 pb-2 text-xs font-bold uppercase tracking-widest text-slate-400">Personal</p>
            <span class="flex items-center justify-between rounded-xl px-4 py-3 text-sm text-slate-400"><span>Personal Vault</span><span class="text-xs">Soon</span></span>
            <span class="flex items-center justify-between rounded-xl px-4 py-3 text-sm text-slate-400"><span>Trash</span><span class="text-xs">Soon</span></span>
        </div>
        <div class="space-y-1">
            <p class="px-4 pb-2 text-xs font-bold uppercase tracking-widest text-slate-400">Account</p>
            <a href="{{ route('profile.edit') }}" @class(['flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium transition', 'bg-indigo-50 text-indigo-700' => request()->routeIs('profile.*'), 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' => ! request()->routeIs('profile.*')]) @if(request()->routeIs('profile.*')) aria-current="page" @endif><span aria-hidden="true">◯</span> Profile</a>
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

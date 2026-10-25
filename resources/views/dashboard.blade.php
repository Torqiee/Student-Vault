<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Dashboard</h1>
                <p class="mt-1 text-sm text-slate-500">Welcome back, {{ Auth::user()->name }}. Here's what's in your vault.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('notes.create') }}" class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">+ New Note</a>
                <a href="{{ route('vault.create') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">↑ Upload File</a>
                <a href="{{ route('courses.create') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">+ New Course</a>
            </div>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-8 px-4 py-8 sm:px-6 lg:px-8">
        <section aria-label="Vault statistics" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between"><p class="text-sm font-medium text-slate-500">Total Notes</p><span class="rounded-lg bg-indigo-50 px-2.5 py-1 text-indigo-600" aria-hidden="true">▤</span></div>
                <p class="mt-4 text-3xl font-bold tracking-tight text-slate-900">{{ number_format($notesCount) }}</p>
                <p class="mt-1 text-sm text-slate-500">Private notes</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between"><p class="text-sm font-medium text-slate-500">Total Files</p><span class="rounded-lg bg-sky-50 px-2.5 py-1 text-sky-600" aria-hidden="true">▣</span></div>
                <p class="mt-4 text-3xl font-bold tracking-tight text-slate-900">{{ number_format($filesCount) }}</p>
                <p class="mt-1 text-sm text-slate-500">Encrypted uploads</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between"><p class="text-sm font-medium text-slate-500">Storage Usage</p><span class="rounded-lg bg-emerald-50 px-2.5 py-1 text-emerald-600" aria-hidden="true">◈</span></div>
                <p class="mt-4 text-3xl font-bold tracking-tight text-slate-900">{{ $storageBytes >= 1048576 ? number_format($storageBytes / 1048576, 1).' MB' : ($storageBytes >= 1024 ? number_format($storageBytes / 1024, 1).' KB' : number_format($storageBytes).' B') }}</p>
                <p class="mt-1 text-sm text-slate-500">Original file sizes</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between"><p class="text-sm font-medium text-slate-500">Courses</p><span class="rounded-lg bg-amber-50 px-2.5 py-1 text-amber-600" aria-hidden="true">▦</span></div>
                <p class="mt-4 text-3xl font-bold tracking-tight text-slate-900">{{ number_format($coursesCount) }}</p>
                <p class="mt-1 text-sm text-slate-500">Subjects and projects</p>
            </div>
        </section>

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="min-w-0 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="recent-notes-heading">
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">
                    <h2 id="recent-notes-heading" class="text-lg font-semibold text-slate-900">Recent Notes</h2>
                    <a href="{{ route('notes.index') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-800">View all</a>
                </div>
                @forelse ($recentNotes as $note)
                    <a href="{{ route('notes.show', $note) }}" class="flex items-center justify-between gap-4 border-b border-slate-100 px-6 py-4 last:border-0 hover:bg-slate-50">
                        <span class="min-w-0 truncate text-sm font-medium text-slate-800">{{ $note->title }}</span>
                        <span class="shrink-0 text-xs text-slate-500">{{ $note->created_at->diffForHumans() }}</span>
                    </a>
                @empty
                    <div class="px-6 py-12 text-center">
                        <p class="font-medium text-slate-800">No notes yet.</p>
                        <p class="mt-1 text-sm text-slate-500">Start capturing your study ideas.</p>
                        <a href="{{ route('notes.create') }}" class="mt-4 inline-flex text-sm font-semibold text-indigo-600 hover:text-indigo-800">Create a note</a>
                    </div>
                @endforelse
            </section>

            <section class="min-w-0 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="recent-files-heading">
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">
                    <h2 id="recent-files-heading" class="text-lg font-semibold text-slate-900">Recent Files</h2>
                    <a href="{{ route('vault.index') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-800">View all</a>
                </div>
                @forelse ($recentFiles as $file)
                    <a href="{{ route('vault.download', $file) }}" class="flex items-center justify-between gap-4 border-b border-slate-100 px-6 py-4 last:border-0 hover:bg-slate-50">
                        <span class="min-w-0 truncate text-sm font-medium text-slate-800">{{ $file->original_name }}</span>
                        <span class="shrink-0 text-xs text-slate-500">{{ $file->created_at->diffForHumans() }}</span>
                    </a>
                @empty
                    <div class="px-6 py-12 text-center">
                        <p class="font-medium text-slate-800">Your vault is empty.</p>
                        <p class="mt-1 text-sm text-slate-500">Keep your academic files secure.</p>
                        <a href="{{ route('vault.create') }}" class="mt-4 inline-flex text-sm font-semibold text-indigo-600 hover:text-indigo-800">Upload a file</a>
                    </div>
                @endforelse
            </section>
        </div>
    </div>
</x-app-layout>

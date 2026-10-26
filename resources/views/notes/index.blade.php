<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                    {{ $courseFilter === 'general' ? 'General Notes' : 'My Notes' }}
                </h1>
                <p class="mt-1 text-sm text-slate-500">Capture and revisit your study notes.</p>
            </div>

            <a href="{{ route('notes.create') }}" class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                + New Note
            </a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        @if (session('success'))
            <div class="rounded-lg bg-green-100 p-4 text-green-700" role="status">{{ session('success') }}</div>
        @endif

        <form method="GET" action="{{ route('notes.index') }}" class="flex flex-wrap items-end gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            @if ($courseFilter !== null)
                <input type="hidden" name="course" value="{{ $courseFilter }}">
            @endif

            <div class="min-w-40">
                <label for="note-sort" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Sort by</label>
                <select id="note-sort" name="sort" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="newest" @selected($sort === 'newest')>Newest first</option>
                    <option value="oldest" @selected($sort === 'oldest')>Oldest first</option>
                    <option value="title" @selected($sort === 'title')>Title A–Z</option>
                </select>
            </div>

            <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Apply</button>

            @if ($sort !== 'newest')
                <a href="{{ route('notes.index', $courseFilter !== null ? ['course' => $courseFilter] : []) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Reset</a>
            @endif
        </form>

        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            @forelse ($notes as $note)
                <div class="relative rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:shadow-md">
                    <x-star-button :action="route('notes.star', $note)" :starred="$note->starred_at !== null" :label="$note->title" class="absolute right-4 top-4" />

                    <a href="{{ route('notes.show', $note) }}" class="block">
                        <h2 class="pr-8 font-semibold text-slate-900">{{ $note->title }}</h2>

                        @if ($note->content)
                            <p class="mt-2 line-clamp-3 text-sm text-slate-500">{{ $note->content }}</p>
                        @endif

                        <div class="mt-4 flex flex-wrap items-center justify-between gap-2 text-xs">
                            <span class="rounded-full bg-indigo-50 px-2.5 py-1 font-medium text-indigo-700">{{ $note->course?->name ?? 'General' }}</span>
                            <span class="text-slate-400">{{ $note->updated_at->diffForHumans() }}</span>
                        </div>
                    </a>
                </div>
            @empty
                <div class="col-span-full rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center">
                    <p class="font-semibold text-slate-800">No notes yet.</p>
                    <p class="mt-2 text-sm text-slate-500">Create your first note to start documenting your studies.</p>
                    <a href="{{ route('notes.create') }}" class="mt-4 inline-flex text-sm font-semibold text-indigo-600 hover:text-indigo-800">Create a note</a>
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>

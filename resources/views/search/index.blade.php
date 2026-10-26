<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Search</h1>
            <p class="mt-1 text-sm text-slate-500">Find notes, files, and courses in your vault.</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <form method="GET" action="{{ route('search.index') }}" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
            <label for="search-query" class="block text-sm font-semibold text-slate-700">Search your vault</label>
            <div class="mt-2 flex flex-col gap-3 sm:flex-row">
                <input
                    id="search-query"
                    type="search"
                    name="q"
                    value="{{ $query }}"
                    placeholder="Search notes, file names, categories, or courses..."
                    class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-primary-700 focus:ring-primary-700"
                    autofocus
                >
                <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-primary-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-800 focus:outline-none focus:ring-2 focus:ring-primary-700 focus:ring-offset-2">
                    Search
                </button>
                @if ($query !== '')
                    <a href="{{ route('search.index') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Clear
                    </a>
                @endif
            </div>
        </form>

        @if ($query === '')
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center">
                <p class="font-semibold text-slate-800">Search your vault.</p>
                <p class="mt-2 text-sm text-slate-500">Find notes by title or content, files by name or category, and courses by name or description.</p>
            </div>
        @else
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-lg font-semibold text-slate-900">Results for “{{ $query }}”</h2>
                <p class="text-sm text-slate-500">
                    {{ $notes->count() }} notes · {{ $files->count() }} files · {{ $courses->count() }} courses
                </p>
            </div>

            @if ($notes->isEmpty() && $files->isEmpty() && $courses->isEmpty())
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center">
                    <p class="font-semibold text-slate-800">No results found.</p>
                    <p class="mt-2 text-sm text-slate-500">Try a different title, file name, category, or course.</p>
                </div>
            @else
                @if ($notes->isNotEmpty())
                    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="search-notes-heading">
                        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">
                            <h2 id="search-notes-heading" class="text-lg font-semibold text-slate-900">Notes</h2>
                            <span class="rounded-full bg-primary-50 px-2.5 py-1 text-xs font-semibold text-primary-700">{{ $notes->count() }}</span>
                        </div>
                        @foreach ($notes as $note)
                            <a href="{{ route('notes.show', $note) }}" class="block border-b border-slate-100 px-6 py-4 last:border-0 hover:bg-slate-50">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <h3 class="font-semibold text-slate-900">{{ $note->title }}</h3>
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">{{ $note->course?->name ?? 'General' }}</span>
                                </div>
                                @if ($note->content)
                                    <p class="mt-1 line-clamp-2 text-sm text-slate-500">{{ $note->content }}</p>
                                @endif
                            </a>
                        @endforeach
                    </section>
                @endif

                @if ($files->isNotEmpty())
                    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="search-files-heading">
                        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">
                            <h2 id="search-files-heading" class="text-lg font-semibold text-slate-900">Files</h2>
                            <span class="rounded-full bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-700">{{ $files->count() }}</span>
                        </div>
                        @foreach ($files as $file)
                            <a href="{{ route('vault.download', $file) }}" class="flex items-center justify-between gap-4 border-b border-slate-100 px-6 py-4 last:border-0 hover:bg-slate-50">
                                <div class="min-w-0">
                                    <p class="truncate font-medium text-slate-900">{{ $file->original_name }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ ucfirst($file->category) }} · {{ $file->course?->name ?? 'General' }}</p>
                                </div>
                                <span class="shrink-0 text-xs font-semibold text-primary-700">Download</span>
                            </a>
                        @endforeach
                    </section>
                @endif

                @if ($courses->isNotEmpty())
                    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="search-courses-heading">
                        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">
                            <h2 id="search-courses-heading" class="text-lg font-semibold text-slate-900">Courses</h2>
                            <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">{{ $courses->count() }}</span>
                        </div>
                        @foreach ($courses as $course)
                            <a href="{{ route('courses.show', $course) }}" class="block border-b border-slate-100 px-6 py-4 last:border-0 hover:bg-slate-50">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <h3 class="font-semibold text-slate-900">{{ $course->name }}</h3>
                                    <span class="text-xs text-slate-500">{{ $course->notes_count }} notes · {{ $course->vault_files_count }} files</span>
                                </div>
                                @if ($course->description)
                                    <p class="mt-1 line-clamp-2 text-sm text-slate-500">{{ $course->description }}</p>
                                @endif
                            </a>
                        @endforeach
                    </section>
                @endif
            @endif
        @endif
    </div>
</x-app-layout>

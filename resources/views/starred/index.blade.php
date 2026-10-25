<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Starred</h1>
            <p class="mt-1 text-sm text-slate-500">Everything you marked important, in one place.</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        @if (session('success'))
            <div class="rounded-lg bg-green-100 p-4 text-green-700" role="status">{{ session('success') }}</div>
        @endif

        @if ($notes->isEmpty() && $files->isEmpty())
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center">
                <p class="text-4xl text-amber-400" aria-hidden="true">☆</p>
                <p class="mt-3 font-semibold text-slate-800">No starred resources yet.</p>
                <p class="mt-2 text-sm text-slate-500">Star a note or file to keep it close at hand.</p>
                <div class="mt-5 flex flex-wrap justify-center gap-3">
                    <a href="{{ route('notes.index') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Browse notes</a>
                    <a href="{{ route('vault.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Browse files</a>
                </div>
            </div>
        @else
            @if ($notes->isNotEmpty())
                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="starred-notes-heading">
                    <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">
                        <h2 id="starred-notes-heading" class="text-lg font-semibold text-slate-900">Starred Notes</h2>
                        <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700">{{ $notes->count() }}</span>
                    </div>

                    @foreach ($notes as $note)
                        <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-4 last:border-0">
                            <a href="{{ route('notes.show', $note) }}" class="min-w-0">
                                <h3 class="truncate font-semibold text-slate-900">{{ $note->title }}</h3>
                                @if ($note->content)
                                    <p class="mt-1 line-clamp-2 text-sm text-slate-500">{{ $note->content }}</p>
                                @endif
                                <p class="mt-2 text-xs text-slate-500">{{ $note->course?->name ?? 'General' }} · Updated {{ $note->updated_at->diffForHumans() }}</p>
                            </a>
                            <x-star-button :action="route('notes.star', $note)" :starred="true" :label="$note->title" class="shrink-0" />
                        </div>
                    @endforeach
                </section>
            @endif

            @if ($files->isNotEmpty())
                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="starred-files-heading">
                    <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">
                        <h2 id="starred-files-heading" class="text-lg font-semibold text-slate-900">Starred Files</h2>
                        <span class="rounded-full bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-700">{{ $files->count() }}</span>
                    </div>

                    @foreach ($files as $file)
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-6 py-4 last:border-0">
                            <div class="min-w-0">
                                <a href="{{ route('vault.download', $file) }}" class="truncate font-semibold text-slate-900 hover:text-indigo-700">{{ $file->original_name }}</a>
                                <p class="mt-1 text-xs text-slate-500">{{ ucfirst($file->category) }} · {{ $file->course?->name ?? 'General' }} · {{ number_format($file->original_size / 1024, 2) }} KB</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <x-star-button :action="route('vault.star', $file)" :starred="true" :label="$file->original_name" />
                                <a href="{{ route('vault.download', $file) }}" class="rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Download</a>
                            </div>
                        </div>
                    @endforeach
                </section>
            @endif
        @endif
    </div>
</x-app-layout>

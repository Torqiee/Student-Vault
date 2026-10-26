<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                    {{ $courseFilter === 'general' ? 'General Notes' : 'My Notes' }}
                </h1>
                <p class="mt-1 text-sm text-slate-500">Capture and revisit your study notes.</p>
            </div>

            <a href="{{ route('notes.create') }}" class="inline-flex items-center justify-center rounded-lg bg-primary-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-800">
                <i class="fa-solid fa-plus mr-1" aria-hidden="true"></i> New Note
            </a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        @if (session('success'))
            <div class="rounded-lg bg-green-100 p-4 text-green-700" role="status">{{ session('success') }}</div>
        @endif

        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            @forelse ($notes as $note)
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:shadow-md">

                    <a href="{{ route('notes.show', $note) }}" class="block">
                        <h2 class="font-semibold text-slate-900">{{ $note->title }}</h2>

                        @if ($note->content)
                            <p class="mt-2 line-clamp-3 text-sm text-slate-500">{{ $note->content }}</p>
                        @endif

                        <div class="mt-4 flex flex-wrap items-center justify-between gap-2 text-xs">
                            <span class="rounded-full bg-primary-50 px-2.5 py-1 font-medium text-primary-700">{{ $note->course?->name ?? 'General' }}</span>
                            <span class="text-slate-400">{{ $note->updated_at->diffForHumans() }}</span>
                        </div>
                    </a>
                </div>
            @empty
                <div class="col-span-full rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center">
                    <p class="font-semibold text-slate-800">No notes yet.</p>
                    <p class="mt-2 text-sm text-slate-500">Create your first note to start documenting your studies.</p>
                    <a href="{{ route('notes.create') }}" class="mt-4 inline-flex text-sm font-semibold text-primary-700 hover:text-primary-800">Create a note</a>
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>

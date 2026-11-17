<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-primary-700">Your workspace</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Trash</h1>
                <p class="mt-1 text-sm text-slate-500">Restore an item or delete it forever.</p>
            </div>
            <a href="{{ route('dashboard') }}" class="text-sm font-semibold text-primary-700 hover:text-primary-800"><i class="fa-solid fa-arrow-left mr-1" aria-hidden="true"></i> Dashboard</a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        @if (session('success'))
            <div class="rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-800" role="status">{{ session('success') }}</div>
        @endif
        @if ($errors->has('file'))
            <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700" role="alert">{{ $errors->first('file') }}</div>
        @endif

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="min-w-0 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="trash-notes-heading">
                <div class="flex items-center gap-3 border-b border-slate-100 px-6 py-5">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary-50 text-primary-700" aria-hidden="true"><i class="fa-solid fa-note-sticky"></i></span>
                    <h2 id="trash-notes-heading" class="text-lg font-semibold text-slate-900">Deleted Notes</h2>
                    <span class="ml-auto rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">{{ $notes->count() }}</span>
                </div>
                @forelse ($notes as $note)
                    <div class="flex flex-col gap-3 border-b border-slate-100 px-6 py-4 last:border-0 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-slate-800">{{ $note->title }}</p>
                            <p class="mt-1 text-xs text-slate-500">Deleted {{ $note->deleted_at->diffForHumans() }}</p>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <form method="POST" action="{{ route('trash.notes.restore', $note->id) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="rounded-lg border border-primary-200 px-3 py-2 text-sm font-semibold text-primary-700 hover:bg-primary-50">Restore</button>
                            </form>
                            <x-confirm-action :name="'delete-note-forever-'.$note->id" :action="route('trash.notes.destroy', $note->id)" title="Delete this note forever?" message="This note will be permanently removed. This action cannot be undone." confirm-label="Delete Forever" trigger-class="rounded-lg px-3 py-2 text-sm font-semibold text-red-600 hover:bg-red-50" />
                        </div>
                    </div>
                @empty
                    <div class="px-6 py-12 text-center">
                        <i class="fa-regular fa-note-sticky text-2xl text-slate-300" aria-hidden="true"></i>
                        <p class="mt-3 font-medium text-slate-800">No deleted notes.</p>
                        <p class="mt-1 text-sm text-slate-500">Notes you move to Trash will appear here.</p>
                    </div>
                @endforelse
            </section>

            <section class="min-w-0 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="trash-files-heading">
                <div class="flex items-center gap-3 border-b border-slate-100 px-6 py-5">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary-50 text-primary-700" aria-hidden="true"><i class="fa-solid fa-folder-closed"></i></span>
                    <h2 id="trash-files-heading" class="text-lg font-semibold text-slate-900">Deleted Files</h2>
                    <span class="ml-auto rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">{{ $files->count() }}</span>
                </div>
                @forelse ($files as $file)
                    <div class="flex flex-col gap-3 border-b border-slate-100 px-6 py-4 last:border-0 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-slate-800">{{ $file->original_name }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ ucfirst($file->category) }} · Deleted {{ $file->deleted_at->diffForHumans() }}</p>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <form method="POST" action="{{ route('trash.files.restore', $file->id) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="rounded-lg border border-primary-200 px-3 py-2 text-sm font-semibold text-primary-700 hover:bg-primary-50">Restore</button>
                            </form>
                            <x-confirm-action :name="'delete-file-forever-'.$file->id" :action="route('trash.files.destroy', $file->id)" title="Delete this file forever?" message="This file will be permanently removed. This action cannot be undone." confirm-label="Delete Forever" trigger-class="rounded-lg px-3 py-2 text-sm font-semibold text-red-600 hover:bg-red-50" />
                        </div>
                    </div>
                @empty
                    <div class="px-6 py-12 text-center">
                        <i class="fa-regular fa-folder-open text-2xl text-slate-300" aria-hidden="true"></i>
                        <p class="mt-3 font-medium text-slate-800">No deleted files.</p>
                        <p class="mt-1 text-sm text-slate-500">Files you move to Trash will appear here.</p>
                    </div>
                @endforelse
            </section>
        </div>
    </div>
</x-app-layout>

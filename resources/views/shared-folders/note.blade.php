<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h1 class="text-xl font-semibold text-slate-900">{{ $note->title }}</h1>
            <a href="{{ route('shared-folders.show', $sharedFolder) }}" class="text-sm font-semibold text-primary-700 hover:text-primary-800"><i class="fa-solid fa-arrow-left mr-1" aria-hidden="true"></i> {{ $sharedFolder->name }}</a>
        </div>
    </x-slot>
    <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
        <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <p class="mb-6 text-sm text-slate-500">Shared by {{ $note->user->name }}</p>
            <div class="whitespace-pre-wrap break-words text-slate-800">{{ $note->content }}</div>
        </article>
    </div>
</x-app-layout>

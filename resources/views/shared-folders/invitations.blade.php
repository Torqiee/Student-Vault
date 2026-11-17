<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h1 class="text-2xl font-bold text-slate-900">Shared folder invitations</h1>
            <a href="{{ route('shared-folders.index') }}" class="text-sm font-semibold text-primary-700 hover:text-primary-800"><i class="fa-solid fa-arrow-left mr-1" aria-hidden="true"></i> Shared Folders</a>
        </div>
    </x-slot>
    <div class="mx-auto max-w-4xl space-y-4 px-4 py-8 sm:px-6 lg:px-8">
        @if (session('success')) <div class="rounded-lg bg-green-50 p-4 text-sm text-green-800" role="status">{{ session('success') }}</div> @endif
        @forelse ($invitations as $invitation)
            <article class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-primary-700">Invitation</p>
                    <h2 class="mt-1 font-semibold text-slate-900">{{ $invitation->folder->name }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ $invitation->folder->owner->name }} invited you to collaborate.</p>
                </div>
                <div class="flex gap-2">
                    <form method="POST" action="{{ route('shared-folders.invitations.decline', $invitation) }}">@csrf<button type="submit" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Decline</button></form>
                    <form method="POST" action="{{ route('shared-folders.invitations.accept', $invitation) }}">@csrf<button type="submit" class="rounded-lg bg-primary-700 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-800">Accept invitation</button></form>
                </div>
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center">
                <i class="fa-regular fa-envelope text-2xl text-slate-300" aria-hidden="true"></i>
                <p class="mt-3 font-medium text-slate-800">No pending invitations.</p>
            </div>
        @endforelse
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-primary-700">Work together, privately</p>
                <h1 class="mt-1 text-2xl font-bold text-slate-900">Shared Folders</h1>
                <p class="mt-1 text-sm text-slate-500">Share selected notes and files with invited people.</p>
            </div>
            <a href="{{ route('shared-folders.create') }}" class="rounded-lg bg-primary-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-primary-800"><i class="fa-solid fa-plus mr-2" aria-hidden="true"></i>Create folder</a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-8 px-4 py-8 sm:px-6 lg:px-8">
        @if (session('success')) <div class="rounded-lg bg-green-50 p-4 text-sm text-green-800" role="status">{{ session('success') }}</div> @endif

        @if ($pendingInvitations->isNotEmpty())
            <section class="rounded-2xl border border-primary-200 bg-primary-50 p-5 sm:p-6" aria-labelledby="pending-heading">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div><h2 id="pending-heading" class="font-semibold text-slate-900">Invitations waiting for you</h2><p class="mt-1 text-sm text-slate-600">Choose which folders to join.</p></div>
                    <a href="{{ route('shared-folders.invitations.index') }}" class="text-sm font-semibold text-primary-700 hover:text-primary-800">View invitations <i class="fa-solid fa-arrow-right ml-1" aria-hidden="true"></i></a>
                </div>
            </section>
        @endif

        <section aria-labelledby="owned-heading">
            <h2 id="owned-heading" class="mb-4 text-lg font-semibold text-slate-900">Folders you own</h2>
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($ownedFolders as $folder)
                    <a href="{{ route('shared-folders.show', $folder) }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-primary-200 hover:shadow-md">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary-50 text-primary-700" aria-hidden="true"><i class="fa-solid fa-folder-closed"></i></span>
                        <h3 class="mt-4 truncate font-semibold text-slate-900">{{ $folder->name }}</h3>
                        <p class="mt-1 line-clamp-2 text-sm text-slate-500">{{ $folder->description ?: 'Invite friends and share selected resources.' }}</p>
                        <p class="mt-4 text-xs text-slate-500">{{ $folder->members_count }} members · {{ $folder->notes_count }} notes · {{ $folder->files_count }} files</p>
                    </a>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500 md:col-span-2 xl:col-span-3">You have not created a shared folder yet.</div>
                @endforelse
            </div>
        </section>

        <section aria-labelledby="joined-heading">
            <h2 id="joined-heading" class="mb-4 text-lg font-semibold text-slate-900">Folders shared with you</h2>
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($joinedFolders as $folder)
                    <a href="{{ route('shared-folders.show', $folder) }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-primary-200 hover:shadow-md">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary-50 text-primary-700" aria-hidden="true"><i class="fa-solid fa-users"></i></span>
                        <h3 class="mt-4 truncate font-semibold text-slate-900">{{ $folder->name }}</h3>
                        <p class="mt-1 text-sm text-slate-500">Owned by {{ $folder->owner->name }}</p>
                        <p class="mt-4 text-xs text-slate-500">{{ $folder->members_count }} members · {{ $folder->notes_count }} notes · {{ $folder->files_count }} files</p>
                    </a>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500 md:col-span-2 xl:col-span-3">You have not joined a shared folder yet.</div>
                @endforelse
            </div>
        </section>
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <a href="{{ route('shared-folders.index') }}" class="text-sm font-semibold text-primary-700 hover:text-primary-800"><i class="fa-solid fa-arrow-left mr-1" aria-hidden="true"></i> Shared Folders</a>
                <h1 class="mt-2 text-2xl font-bold text-slate-900">{{ $sharedFolder->name }}</h1>
                <p class="mt-1 text-sm text-slate-500">Owned by {{ $sharedFolder->owner->name }} · {{ $sharedFolder->members->count() + 1 }} people</p>
            </div>
            @if ($sharedFolder->owner_id === auth()->id())
                <div class="flex items-center gap-2">
                    <a href="{{ route('shared-folders.edit', $sharedFolder) }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Edit folder</a>
                    <x-confirm-action :name="'delete-shared-folder-'.$sharedFolder->id" :action="route('shared-folders.destroy', $sharedFolder)" title="Delete this shared folder?" message="Members will lose access. Original notes and files will remain with their owners." confirm-label="Delete Folder" trigger-label="Delete folder" />
                </div>
            @endif
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        @if (session('success')) <div class="rounded-lg bg-green-50 p-4 text-sm text-green-800" role="status">{{ session('success') }}</div> @endif
        @if ($errors->any()) <div class="rounded-lg bg-red-50 p-4 text-sm text-red-700" role="alert">{{ $errors->first() }}</div> @endif
        @if ($sharedFolder->description) <p class="rounded-2xl border border-slate-200 bg-white p-5 text-sm text-slate-700 shadow-sm">{{ $sharedFolder->description }}</p> @endif

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="shared-notes-heading">
                <div class="border-b border-slate-100 px-6 py-5"><h2 id="shared-notes-heading" class="text-lg font-semibold text-slate-900"><i class="fa-solid fa-note-sticky mr-2 text-primary-700" aria-hidden="true"></i> Shared Notes</h2></div>
                <div class="divide-y divide-slate-100">
                    @forelse ($notes as $note)
                        <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4">
                            <div class="min-w-0"><a href="{{ route('shared-folders.notes.show', [$sharedFolder, $note]) }}" class="break-words font-semibold text-primary-700 hover:text-primary-800">{{ $note->title }}</a><p class="mt-1 text-xs text-slate-500">Shared by {{ $note->user->name }}</p></div>
                            @if ($sharedFolder->owner_id === auth()->id() || $note->user_id === auth()->id())
                                <x-confirm-action :name="'remove-shared-note-'.$note->id" :action="route('shared-folders.notes.destroy', [$sharedFolder, $note])" title="Remove this note from the folder?" message="The note will remain in its owner's private Notes." confirm-label="Remove Note" trigger-label="Remove" trigger-class="rounded-lg px-3 py-2 text-sm font-semibold text-red-600 hover:bg-red-50" />
                            @endif
                        </div>
                    @empty
                        <p class="px-6 py-8 text-sm text-slate-500">No notes shared yet.</p>
                    @endforelse
                </div>
                @if ($availableNotes->isNotEmpty())
                    <form method="POST" action="{{ route('shared-folders.notes.store', $sharedFolder) }}" class="flex flex-wrap gap-2 border-t border-slate-100 p-5">
                        @csrf
                        <label for="note_id" class="sr-only">Choose your note</label>
                        <select id="note_id" name="note_id" required class="min-w-0 flex-1 rounded-lg border-slate-300 text-sm focus:border-primary-700 focus:ring-primary-700"><option value="">Choose your note</option>@foreach ($availableNotes as $note)<option value="{{ $note->id }}">{{ $note->title }}</option>@endforeach</select>
                        <button type="submit" class="rounded-lg bg-primary-700 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-800">Add note</button>
                    </form>
                @endif
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="shared-files-heading">
                <div class="border-b border-slate-100 px-6 py-5"><h2 id="shared-files-heading" class="text-lg font-semibold text-slate-900"><i class="fa-solid fa-folder-closed mr-2 text-primary-700" aria-hidden="true"></i> Shared Files</h2></div>
                <div class="divide-y divide-slate-100">
                    @forelse ($files as $file)
                        <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4">
                            <div class="min-w-0"><a href="{{ route('shared-folders.files.download', [$sharedFolder, $file]) }}" class="break-all font-semibold text-primary-700 hover:text-primary-800">{{ $file->original_name }}</a><p class="mt-1 text-xs text-slate-500">Shared by {{ $file->user->name }} · {{ ucfirst($file->category) }}</p></div>
                            @if ($sharedFolder->owner_id === auth()->id() || $file->user_id === auth()->id())
                                <x-confirm-action :name="'remove-shared-file-'.$file->id" :action="route('shared-folders.files.destroy', [$sharedFolder, $file])" title="Remove this file from the folder?" message="The file will remain in its owner's private Files." confirm-label="Remove File" trigger-label="Remove" trigger-class="rounded-lg px-3 py-2 text-sm font-semibold text-red-600 hover:bg-red-50" />
                            @endif
                        </div>
                    @empty
                        <p class="px-6 py-8 text-sm text-slate-500">No files shared yet.</p>
                    @endforelse
                </div>
                @if ($availableFiles->isNotEmpty())
                    <form method="POST" action="{{ route('shared-folders.files.store', $sharedFolder) }}" class="flex flex-wrap gap-2 border-t border-slate-100 p-5">
                        @csrf
                        <label for="vault_file_id" class="sr-only">Choose your file</label>
                        <select id="vault_file_id" name="vault_file_id" required class="min-w-0 flex-1 rounded-lg border-slate-300 text-sm focus:border-primary-700 focus:ring-primary-700"><option value="">Choose your file</option>@foreach ($availableFiles as $file)<option value="{{ $file->id }}">{{ $file->original_name }}</option>@endforeach</select>
                        <button type="submit" class="rounded-lg bg-primary-700 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-800">Add file</button>
                    </form>
                @endif
            </section>
        </div>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm" aria-labelledby="members-heading">
            <h2 id="members-heading" class="text-lg font-semibold text-slate-900">People with access</h2>
            <div class="mt-4 divide-y divide-slate-100">
                <div class="flex items-center justify-between gap-3 py-3"><div><p class="font-medium text-slate-800">{{ $sharedFolder->owner->name }}</p><p class="text-xs text-slate-500">{{ $sharedFolder->owner->email }}</p></div><span class="rounded-full bg-primary-50 px-3 py-1 text-xs font-semibold text-primary-700">Owner</span></div>
                @foreach ($sharedFolder->members as $member)
                    <div class="flex flex-wrap items-center justify-between gap-3 py-3"><div><p class="font-medium text-slate-800">{{ $member->name }}</p><p class="text-xs text-slate-500">{{ $member->email }}</p></div>
                        @if ($sharedFolder->owner_id === auth()->id() || $member->id === auth()->id())
                            <x-confirm-action :name="'remove-member-'.$member->id" :action="route('shared-folders.members.destroy', [$sharedFolder, $member])" :title="$member->id === auth()->id() ? 'Leave this shared folder?' : 'Remove this member?'" message="Their contributed items will be removed from the folder. Original notes and files remain theirs." :confirm-label="$member->id === auth()->id() ? 'Leave Folder' : 'Remove Member'" :trigger-label="$member->id === auth()->id() ? 'Leave folder' : 'Remove'" trigger-class="rounded-lg px-3 py-2 text-sm font-semibold text-red-600 hover:bg-red-50" />
                        @else
                            <span class="text-xs text-slate-500">Member</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>

        @if ($sharedFolder->owner_id === auth()->id())
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm" aria-labelledby="invite-heading">
                <h2 id="invite-heading" class="text-lg font-semibold text-slate-900">Invite a friend</h2>
                <p class="mt-1 text-sm text-slate-500">Enter the email address of a registered StudentVault user. They choose whether to join.</p>
                <form method="POST" action="{{ route('shared-folders.invitations.store', $sharedFolder) }}" class="mt-4 flex flex-wrap gap-2">
                    @csrf
                    <label for="email" class="sr-only">Friend's email address</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required class="min-w-0 flex-1 rounded-lg border-slate-300 text-sm focus:border-primary-700 focus:ring-primary-700" placeholder="friend@university.edu">
                    <button type="submit" class="rounded-lg bg-primary-700 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-800">Send invitation</button>
                </form>
                @if ($pendingInvitations->isNotEmpty())
                    <h3 class="mt-6 text-sm font-semibold text-slate-800">Pending invitations</h3>
                    <div class="mt-2 divide-y divide-slate-100">
                        @foreach ($pendingInvitations as $invitation)
                            <div class="flex flex-wrap items-center justify-between gap-3 py-3"><span class="text-sm text-slate-600">{{ $invitation->invitee->email }}</span><x-confirm-action :name="'revoke-invitation-'.$invitation->id" :action="route('shared-folders.invitations.revoke', [$sharedFolder, $invitation])" title="Revoke this invitation?" message="This person will no longer be able to accept it." confirm-label="Revoke Invitation" trigger-label="Revoke" trigger-class="rounded-lg px-3 py-2 text-sm font-semibold text-red-600 hover:bg-red-50" /></div>
                        @endforeach
                    </div>
                @endif
            </section>
        @endif
    </div>
</x-app-layout>

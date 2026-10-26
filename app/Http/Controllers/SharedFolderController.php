<?php

namespace App\Http\Controllers;

use App\Models\SharedFolder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SharedFolderController extends Controller
{
    public function index(Request $request): View
    {
        $ownedFolders = $request->user()->ownedSharedFolders()->withCount(['members', 'notes', 'files'])->latest()->get();
        $joinedFolders = $request->user()->sharedFolders()->with('owner')->withCount(['members', 'notes', 'files'])->latest()->get();
        $pendingInvitations = $request->user()->sharedFolderInvitations()
            ->where('status', 'pending')->with('folder.owner')->latest()->get();

        return view('shared-folders.index', compact('ownedFolders', 'joinedFolders', 'pendingInvitations'));
    }

    public function create(): View
    {
        return view('shared-folders.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $folder = $request->user()->ownedSharedFolders()->create($validated);

        return redirect()->route('shared-folders.show', $folder)->with('success', 'Shared folder created.');
    }

    public function show(Request $request, SharedFolder $sharedFolder): View
    {
        abort_unless($sharedFolder->isAccessibleBy($request->user()), 403);

        $sharedFolder->load(['owner', 'members']);
        $activeContributorIds = $sharedFolder->members->pluck('id')->push($sharedFolder->owner_id);
        $notes = $sharedFolder->notes()->with('user')->whereIn('notes.user_id', $activeContributorIds)->latest('notes.created_at')->get();
        $files = $sharedFolder->files()->with('user')->whereIn('vault_files.user_id', $activeContributorIds)->latest('vault_files.created_at')->get();
        $availableNotes = $request->user()->notes()->whereNotIn('id', $notes->where('user_id', $request->user()->id)->pluck('id'))->latest()->get();
        $availableFiles = $request->user()->vaultFiles()->whereNotIn('id', $files->where('user_id', $request->user()->id)->pluck('id'))->latest()->get();
        $pendingInvitations = $sharedFolder->owner_id === $request->user()->id
            ? $sharedFolder->invitations()->where('status', 'pending')->with('invitee')->latest()->get()
            : collect();

        return view('shared-folders.show', compact('sharedFolder', 'notes', 'files', 'availableNotes', 'availableFiles', 'pendingInvitations'));
    }

    public function edit(Request $request, SharedFolder $sharedFolder): View
    {
        abort_unless($sharedFolder->owner_id === $request->user()->id, 403);

        return view('shared-folders.edit', compact('sharedFolder'));
    }

    public function update(Request $request, SharedFolder $sharedFolder): RedirectResponse
    {
        abort_unless($sharedFolder->owner_id === $request->user()->id, 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $sharedFolder->update($validated);

        return redirect()->route('shared-folders.show', $sharedFolder)->with('success', 'Shared folder updated.');
    }

    public function destroy(Request $request, SharedFolder $sharedFolder): RedirectResponse
    {
        abort_unless($sharedFolder->owner_id === $request->user()->id, 403);

        $sharedFolder->delete();

        return redirect()->route('shared-folders.index')->with('success', 'Shared folder deleted. Original notes and files are safe.');
    }
}

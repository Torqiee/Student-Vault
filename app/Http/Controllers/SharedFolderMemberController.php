<?php

namespace App\Http\Controllers;

use App\Models\SharedFolder;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SharedFolderMemberController extends Controller
{
    public function destroy(Request $request, SharedFolder $sharedFolder, User $member): RedirectResponse
    {
        abort_unless($sharedFolder->owner_id === $request->user()->id || $member->id === $request->user()->id, 403);
        abort_if($member->id === $sharedFolder->owner_id, 403);
        abort_unless($sharedFolder->members()->whereKey($member->id)->exists(), 404);

        DB::transaction(function () use ($sharedFolder, $member): void {
            SharedFolder::query()->whereKey($sharedFolder->id)->lockForUpdate()->firstOrFail();
            $noteIds = $sharedFolder->notes()->where('notes.user_id', $member->id)->pluck('notes.id');
            $fileIds = $sharedFolder->files()->where('vault_files.user_id', $member->id)->pluck('vault_files.id');
            $sharedFolder->notes()->detach($noteIds);
            $sharedFolder->files()->detach($fileIds);
            $sharedFolder->members()->detach($member->id);
        });

        return $member->id === $request->user()->id
            ? redirect()->route('shared-folders.index')->with('success', 'You left the shared folder. Your notes and files remain yours.')
            : redirect()->route('shared-folders.show', $sharedFolder)->with('success', 'Member removed from the shared folder.');
    }
}

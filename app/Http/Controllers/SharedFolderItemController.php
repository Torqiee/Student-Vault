<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Models\SharedFolder;
use App\Models\VaultFile;
use App\Services\Vault\FileVaultService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SharedFolderItemController extends Controller
{
    public function storeNote(Request $request, SharedFolder $sharedFolder): RedirectResponse
    {
        $validated = $request->validate(['note_id' => ['required', 'integer']]);

        DB::transaction(function () use ($request, $sharedFolder, $validated): void {
            SharedFolder::query()->whereKey($sharedFolder->id)->lockForUpdate()->firstOrFail();
            abort_unless($sharedFolder->isAccessibleBy($request->user()), 403);
            $note = $request->user()->notes()->findOrFail($validated['note_id']);
            $sharedFolder->notes()->syncWithoutDetaching([$note->id]);
        });

        return redirect()->route('shared-folders.show', $sharedFolder)->with('success', 'Note added to the shared folder.');
    }

    public function storeFile(Request $request, SharedFolder $sharedFolder): RedirectResponse
    {
        $validated = $request->validate(['vault_file_id' => ['required', 'integer']]);

        DB::transaction(function () use ($request, $sharedFolder, $validated): void {
            SharedFolder::query()->whereKey($sharedFolder->id)->lockForUpdate()->firstOrFail();
            abort_unless($sharedFolder->isAccessibleBy($request->user()), 403);
            $file = $request->user()->vaultFiles()->findOrFail($validated['vault_file_id']);
            $sharedFolder->files()->syncWithoutDetaching([$file->id]);
        });

        return redirect()->route('shared-folders.show', $sharedFolder)->with('success', 'File added to the shared folder.');
    }

    public function showNote(Request $request, SharedFolder $sharedFolder, Note $note): View
    {
        $this->authorizeSharedNote($request, $sharedFolder, $note);
        $note->load('user');

        return view('shared-folders.note', compact('sharedFolder', 'note'));
    }

    public function downloadFile(Request $request, SharedFolder $sharedFolder, VaultFile $vaultFile, FileVaultService $vault): StreamedResponse|RedirectResponse
    {
        $this->authorizeSharedFile($request, $sharedFolder, $vaultFile);

        try {
            $stream = $vault->decryptToStream($vaultFile);
        } catch (RuntimeException $exception) {
            report($exception);

            return redirect()->route('shared-folders.show', $sharedFolder)->withErrors(['file' => 'Unable to open this file securely.']);
        }

        return response()->streamDownload(function () use ($stream): void {
            try {
                fpassthru($stream);
            } finally {
                fclose($stream);
            }
        }, $vaultFile->original_name, ['Content-Type' => $vaultFile->mime_type]);
    }

    public function destroyNote(Request $request, SharedFolder $sharedFolder, Note $note): RedirectResponse
    {
        abort_unless($sharedFolder->isAccessibleBy($request->user()), 403);
        abort_unless($sharedFolder->notes()->whereKey($note->id)->exists(), 404);
        abort_unless($sharedFolder->owner_id === $request->user()->id || $note->user_id === $request->user()->id, 403);

        $sharedFolder->notes()->detach($note->id);

        return redirect()->route('shared-folders.show', $sharedFolder)->with('success', 'Note removed from the shared folder.');
    }

    public function destroyFile(Request $request, SharedFolder $sharedFolder, VaultFile $vaultFile): RedirectResponse
    {
        abort_unless($sharedFolder->isAccessibleBy($request->user()), 403);
        abort_unless($sharedFolder->files()->whereKey($vaultFile->id)->exists(), 404);
        abort_unless($sharedFolder->owner_id === $request->user()->id || $vaultFile->user_id === $request->user()->id, 403);

        $sharedFolder->files()->detach($vaultFile->id);

        return redirect()->route('shared-folders.show', $sharedFolder)->with('success', 'File removed from the shared folder.');
    }

    private function authorizeSharedNote(Request $request, SharedFolder $sharedFolder, Note $note): void
    {
        abort_unless($sharedFolder->isAccessibleBy($request->user()), 403);
        abort_unless($sharedFolder->notes()->whereKey($note->id)->exists(), 404);
        abort_unless($sharedFolder->hasActiveContributor($note->user_id), 403);
    }

    private function authorizeSharedFile(Request $request, SharedFolder $sharedFolder, VaultFile $vaultFile): void
    {
        abort_unless($sharedFolder->isAccessibleBy($request->user()), 403);
        abort_unless($sharedFolder->files()->whereKey($vaultFile->id)->exists(), 404);
        abort_unless($sharedFolder->hasActiveContributor($vaultFile->user_id), 403);
    }
}

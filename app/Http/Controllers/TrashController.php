<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Models\VaultFile;
use App\Services\Vault\FileVaultService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class TrashController extends Controller
{
    public function index(Request $request): View
    {
        $notes = $request->user()->notes()->onlyTrashed()->latest('deleted_at')->get();
        $files = $request->user()->vaultFiles()->onlyTrashed()->latest('deleted_at')->get();

        return view('trash.index', compact('notes', 'files'));
    }

    public function restoreNote(Request $request, string $note): RedirectResponse
    {
        $trashedNote = Note::onlyTrashed()->findOrFail($note);
        abort_unless($trashedNote->user_id === $request->user()->id, 403);

        $trashedNote->restore();

        return redirect()->route('trash.index')->with('success', 'Note restored.');
    }

    public function destroyNote(Request $request, string $note): RedirectResponse
    {
        $trashedNote = Note::onlyTrashed()->findOrFail($note);
        abort_unless($trashedNote->user_id === $request->user()->id, 403);

        $trashedNote->forceDelete();

        return redirect()->route('trash.index')->with('success', 'Note permanently deleted.');
    }

    public function restoreFile(Request $request, string $vaultFile, FileVaultService $vault): RedirectResponse
    {
        $trashedFile = VaultFile::onlyTrashed()->findOrFail($vaultFile);
        abort_unless($trashedFile->user_id === $request->user()->id, 403);

        try {
            $vault->restore($trashedFile);
        } catch (RuntimeException $exception) {
            report($exception);

            return redirect()->route('trash.index')->withErrors(['file' => 'Unable to restore this file securely.']);
        }

        return redirect()->route('trash.index')->with('success', 'File restored.');
    }

    public function destroyFile(Request $request, string $vaultFile, FileVaultService $vault): RedirectResponse
    {
        $trashedFile = VaultFile::onlyTrashed()->findOrFail($vaultFile);
        abort_unless($trashedFile->user_id === $request->user()->id, 403);

        try {
            $vault->permanentlyDelete($trashedFile);
        } catch (RuntimeException $exception) {
            report($exception);

            return redirect()->route('trash.index')->withErrors(['file' => 'Unable to delete this file securely.']);
        }

        return redirect()->route('trash.index')->with('success', 'File permanently deleted.');
    }
}

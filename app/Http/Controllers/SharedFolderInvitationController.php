<?php

namespace App\Http\Controllers;

use App\Mail\SharedFolderInvitationMail;
use App\Models\SharedFolder;
use App\Models\SharedFolderInvitation;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class SharedFolderInvitationController extends Controller
{
    public function index(Request $request): View
    {
        $invitations = $request->user()->sharedFolderInvitations()
            ->where('status', 'pending')->with('folder.owner')->latest()->get();

        return view('shared-folders.invitations', compact('invitations'));
    }

    public function store(Request $request, SharedFolder $sharedFolder): RedirectResponse
    {
        abort_unless($sharedFolder->owner_id === $request->user()->id, 403);

        $validated = $request->validate(['email' => ['required', 'email', 'max:255']]);
        $email = Str::lower(trim($validated['email']));
        $invitee = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        if ($invitee === null || $invitee->id === $request->user()->id) {
            throw ValidationException::withMessages(['email' => 'Enter the email of another registered StudentVault user.']);
        }

        DB::transaction(function () use ($sharedFolder, $invitee, $email): void {
            SharedFolder::query()->whereKey($sharedFolder->id)->lockForUpdate()->firstOrFail();

            if ($sharedFolder->members()->whereKey($invitee->id)->exists()
                || $sharedFolder->invitations()->where('invitee_id', $invitee->id)->where('status', 'pending')->exists()) {
                throw ValidationException::withMessages(['email' => 'This user is already a member or has a pending invitation.']);
            }

            $sharedFolder->invitations()->create([
                'invitee_id' => $invitee->id,
                'email' => $email,
                'status' => 'pending',
            ]);
        });

        try {
            Mail::to($invitee->email)->send(new SharedFolderInvitationMail($sharedFolder->name, $request->user()->name));
        } catch (Throwable $exception) {
            report($exception);
        }

        return redirect()->route('shared-folders.show', $sharedFolder)->with('success', 'Invitation created. It is also visible in the recipient account.');
    }

    public function accept(Request $request, SharedFolderInvitation $invitation): RedirectResponse
    {
        $this->authorizeRecipient($request, $invitation);

        DB::transaction(function () use ($invitation, $request): void {
            $pending = SharedFolderInvitation::query()->whereKey($invitation->id)->lockForUpdate()->firstOrFail();
            abort_unless($pending->status === 'pending', 403);

            $pending->folder->members()->syncWithoutDetaching([$request->user()->id]);
            $pending->update(['status' => 'accepted', 'responded_at' => now()]);
        });

        return redirect()->route('shared-folders.show', $invitation->shared_folder_id)->with('success', 'Invitation accepted.');
    }

    public function decline(Request $request, SharedFolderInvitation $invitation): RedirectResponse
    {
        $this->authorizeRecipient($request, $invitation);

        DB::transaction(function () use ($invitation): void {
            $pending = SharedFolderInvitation::query()->whereKey($invitation->id)->lockForUpdate()->firstOrFail();
            abort_unless($pending->status === 'pending', 403);
            $pending->update(['status' => 'declined', 'responded_at' => now()]);
        });

        return redirect()->route('shared-folders.invitations.index')->with('success', 'Invitation declined.');
    }

    public function revoke(Request $request, SharedFolder $sharedFolder, SharedFolderInvitation $invitation): RedirectResponse
    {
        abort_unless($sharedFolder->owner_id === $request->user()->id, 403);
        abort_unless($invitation->shared_folder_id === $sharedFolder->id, 404);

        DB::transaction(function () use ($invitation): void {
            $pending = SharedFolderInvitation::query()->whereKey($invitation->id)->lockForUpdate()->firstOrFail();
            abort_unless($pending->status === 'pending', 404);
            $pending->update(['status' => 'revoked', 'responded_at' => now()]);
        });

        return redirect()->route('shared-folders.show', $sharedFolder)->with('success', 'Invitation revoked.');
    }

    private function authorizeRecipient(Request $request, SharedFolderInvitation $invitation): void
    {
        abort_unless($invitation->invitee_id === $request->user()->id, 403);
        abort_unless(hash_equals(Str::lower($invitation->email), Str::lower($request->user()->email)), 403);
        abort_unless($invitation->status === 'pending', 403);
    }
}

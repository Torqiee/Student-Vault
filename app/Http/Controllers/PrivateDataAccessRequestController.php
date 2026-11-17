<?php

namespace App\Http\Controllers;

use App\Models\PersonalVaultEntry;
use App\Models\PrivateDataAccessRequest;
use App\Models\User;
use App\Models\VaultFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PrivateDataAccessRequestController extends Controller
{
    public function index(Request $request): View
    {
        $incoming = PrivateDataAccessRequest::query()->where('owner_id', $request->user()->id)
            ->with('requester:id,name,email')->latest()->get();
        $outgoing = PrivateDataAccessRequest::query()->where('requester_id', $request->user()->id)
            ->with('owner:id,name,email')->latest()->get();

        return view('private-access.index', compact('incoming', 'outgoing'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'owner_email' => ['required', 'email:rfc', 'max:255'],
            'resource_type' => ['required', Rule::in(['personal_data', 'vault_file'])],
            'resource_id' => ['required', 'integer', 'min:1'],
        ]);

        $owner = User::query()->where('email', $validated['owner_email'])->first();
        if (! $owner || $owner->is($request->user()) || ! $this->resourceExists($validated['resource_type'], (int) $validated['resource_id'], $owner->id)) {
            return back()->withErrors(['resource_id' => 'The requested resource is unavailable.']);
        }

        $alreadyOpen = PrivateDataAccessRequest::query()
            ->where('owner_id', $owner->id)->where('requester_id', $request->user()->id)
            ->where('resource_type', $validated['resource_type'])->where('resource_id', $validated['resource_id'])
            ->whereIn('status', ['pending', 'approved'])->exists();
        if ($alreadyOpen) {
            return back()->withErrors(['resource_id' => 'You already have an open request for this resource.']);
        }

        PrivateDataAccessRequest::query()->create([
            'owner_id' => $owner->id,
            'requester_id' => $request->user()->id,
            'resource_type' => $validated['resource_type'],
            'resource_id' => $validated['resource_id'],
            'status' => 'pending',
        ]);

        return redirect()->route('private-access.index')->with('success', 'Access request sent to the owner.');
    }

    public function approve(Request $request, PrivateDataAccessRequest $accessRequest): RedirectResponse
    {
        $this->decide($request, $accessRequest, 'approved');

        return back()->with('success', 'Request approved. A symmetric key was generated for this resource.');
    }

    public function decline(Request $request, PrivateDataAccessRequest $accessRequest): RedirectResponse
    {
        $this->decide($request, $accessRequest, 'declined');

        return back()->with('success', 'Request declined.');
    }

    public function revoke(Request $request, PrivateDataAccessRequest $accessRequest): RedirectResponse
    {
        abort_unless($accessRequest->owner_id === $request->user()->id, 404);

        DB::transaction(function () use ($accessRequest): void {
            $locked = PrivateDataAccessRequest::query()->lockForUpdate()->findOrFail($accessRequest->id);
            abort_unless($locked->status === 'approved', 404);
            $locked->update(['status' => 'revoked', 'encrypted_symmetric_key' => null, 'responded_at' => now()]);
        });

        return back()->with('success', 'Access revoked.');
    }

    private function decide(Request $request, PrivateDataAccessRequest $accessRequest, string $decision): void
    {
        abort_unless($accessRequest->owner_id === $request->user()->id, 404);

        DB::transaction(function () use ($accessRequest, $decision): void {
            $locked = PrivateDataAccessRequest::query()->lockForUpdate()->findOrFail($accessRequest->id);
            abort_unless($locked->status === 'pending', 404);
            abort_unless($this->resourceExists($locked->resource_type, $locked->resource_id, $locked->owner_id), 404);

            $locked->update([
                'status' => $decision,
                'encrypted_symmetric_key' => $decision === 'approved' ? Crypt::encryptString(base64_encode(random_bytes(32))) : null,
                'responded_at' => now(),
            ]);
        });
    }

    private function resourceExists(string $type, int $id, int $ownerId): bool
    {
        return match ($type) {
            'personal_data' => PersonalVaultEntry::query()->whereKey($id)->where('user_id', $ownerId)->exists(),
            'vault_file' => VaultFile::query()->whereKey($id)->where('user_id', $ownerId)->exists(),
            default => false,
        };
    }
}

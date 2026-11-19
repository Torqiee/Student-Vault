<?php

namespace App\Http\Controllers;

use App\Mail\PrivateDataKeyMail;
use App\Models\PersonalVaultEntry;
use App\Models\PrivateDataAccessRequest;
use App\Models\User;
use App\Models\VaultFile;
use App\Services\Encryption\UserKeyPairService;
use App\Services\Vault\PrivateDataShareService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class PrivateDataAccessRequestController extends Controller
{
    public function __construct(
        private PrivateDataShareService $privateDataShareService,
        private UserKeyPairService $userKeyPairService
    ) {}

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
        abort_unless($accessRequest->owner_id === $request->user()->id, 404);

        // Generate the requester's RSA key pair before locking the request row; key generation is slow.
        $this->userKeyPairService->createFor($accessRequest->requester);

        try {
            $approved = $this->decide($accessRequest, 'approved');
        } catch (Throwable $exception) {
            if ($exception instanceof HttpExceptionInterface) {
                throw $exception;
            }

            report($exception);

            return back()->withErrors(['private_access' => 'Unable to prepare this item securely. Nothing was shared.']);
        }

        try {
            Mail::to($approved->requester->email)->send(new PrivateDataKeyMail(
                $request->user()->name,
                $this->resourceDescription($approved),
                $approved->encrypted_symmetric_key
            ));
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('success', 'Request approved, but the key email could not be sent. Share the encrypted key below through another channel.');
        }

        return back()->with('success', 'Request approved. The encrypted key was emailed to the requester; you can also share it below through another channel.');
    }

    public function decline(Request $request, PrivateDataAccessRequest $accessRequest): RedirectResponse
    {
        abort_unless($accessRequest->owner_id === $request->user()->id, 404);

        $this->decide($accessRequest, 'declined');

        return back()->with('success', 'Request declined.');
    }

    public function revoke(Request $request, PrivateDataAccessRequest $accessRequest): RedirectResponse
    {
        abort_unless($accessRequest->owner_id === $request->user()->id, 404);

        DB::transaction(function () use ($accessRequest): void {
            $locked = PrivateDataAccessRequest::query()->lockForUpdate()->findOrFail($accessRequest->id);
            abort_unless($locked->status === 'approved', 404);
            $locked->update(['status' => 'revoked', 'encrypted_symmetric_key' => null, 'responded_at' => now()]);

            $this->privateDataShareService->releaseUnusedShare($locked);
        });

        return back()->with('success', 'Access revoked.');
    }

    private function decide(PrivateDataAccessRequest $accessRequest, string $decision): PrivateDataAccessRequest
    {
        return DB::transaction(function () use ($accessRequest, $decision): PrivateDataAccessRequest {
            $locked = PrivateDataAccessRequest::query()->lockForUpdate()->findOrFail($accessRequest->id);
            abort_unless($locked->status === 'pending', 404);
            abort_unless($this->resourceExists($locked->resource_type, $locked->resource_id, $locked->owner_id), 404);

            $locked->update([
                'status' => $decision,
                'encrypted_symmetric_key' => $decision === 'approved'
                    ? $this->privateDataShareService->shareKeyWithRequester($locked)
                    : null,
                'responded_at' => now(),
            ]);

            return $locked;
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

    private function resourceDescription(PrivateDataAccessRequest $accessRequest): string
    {
        return ($accessRequest->resource_type === 'personal_data' ? 'Personal Vault entry' : 'File').' #'.$accessRequest->resource_id;
    }
}

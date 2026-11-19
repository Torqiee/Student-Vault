<?php

namespace App\Http\Controllers;

use App\Models\PersonalVaultEntry;
use App\Models\PrivateDataAccessRequest;
use App\Models\VaultFile;
use App\Services\Vault\FileVaultService;
use App\Services\Vault\PersonalVaultService;
use App\Services\Vault\PrivateDataAccessAuthorizer;
use App\Services\Encryption\PrivateDataKeyService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class PrivateDataResourceController extends Controller
{
    public function __construct(
        private PrivateDataAccessAuthorizer $authorizer,
        private PrivateDataKeyService $privateDataKeyService,
        private PersonalVaultService $personalVaultService,
        private FileVaultService $fileVaultService
    ) {}

    /**
     * Display a Personal Vault entry for an approved requester.
     */
    public function showPersonalData(
        Request $request,
        int $accessRequest
    ): View {
        $requester = $request->user();

        $approvedRequest = $this->findApprovedRequest(
            $requester->id,
            $accessRequest,
            PersonalVaultService::RESOURCE_TYPE
        );

        abort_unless(
            $this->authorizer->allowsRequester(
                $requester,
                PersonalVaultService::RESOURCE_TYPE,
                (int) $approvedRequest->resource_id
            ),
            404
        );

        $entry = PersonalVaultEntry::query()
            ->whereKey($approvedRequest->resource_id)
            ->where('user_id', $approvedRequest->owner_id)
            ->firstOrFail();

        try {
            $resourceKey = $this->privateDataKeyService
                ->decryptForRequester($approvedRequest, $requester);
        } catch (Throwable $exception) {
            report($exception);
            abort(404);
        }

        $entryData = $this->personalVaultService
            ->presentForRequester($entry, $resourceKey);

        abort_if($entryData['error'], 404);

        return view('private-access.personal-data', [
            'entry' => $entryData,
            'ownerName' => $approvedRequest->owner->name,
            'fieldLabels' => PersonalVaultService::fieldLabels(),
            'categories' => PersonalVaultService::categories(),
        ]);
    }

    /**
     * Download a file shared with an approved requester.
     */
    public function downloadFile(
        Request $request,
        int $accessRequest
    ): StreamedResponse {
        $requester = $request->user();

        $approvedRequest = $this->findApprovedRequest(
            $requester->id,
            $accessRequest,
            FileVaultService::RESOURCE_TYPE
        );

        abort_unless(
            $this->authorizer->allowsRequester(
                $requester,
                FileVaultService::RESOURCE_TYPE,
                (int) $approvedRequest->resource_id
            ),
            404
        );

        $file = VaultFile::query()
            ->whereKey($approvedRequest->resource_id)
            ->where('user_id', $approvedRequest->owner_id)
            ->firstOrFail();

        try {
            $resourceKey = $this->privateDataKeyService
                ->decryptForRequester($approvedRequest, $requester);

            $stream = $this->fileVaultService
                ->decryptRecipientCopyToStream($file, $resourceKey);
        } catch (Throwable $exception) {
            report($exception);
            abort(404);
        }

        return response()->streamDownload(
            function () use ($stream): void {
                try {
                    fpassthru($stream);
                } finally {
                    fclose($stream);
                }
            },
            $file->original_name,
            [
                'Content-Type' => $file->mime_type,
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }

    /**
     * Always retrieve the current approved request from the database.
     */
    private function findApprovedRequest(
        int $requesterId,
        int $accessRequestId,
        string $resourceType
    ): PrivateDataAccessRequest {
        return PrivateDataAccessRequest::query()
            ->whereKey($accessRequestId)
            ->where('requester_id', $requesterId)
            ->where('resource_type', $resourceType)
            ->where('status', 'approved')
            ->whereNotNull('encrypted_symmetric_key')
            ->firstOrFail();
    }
}

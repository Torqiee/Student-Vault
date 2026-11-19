<?php

namespace App\Services\Vault;

use App\Models\PersonalVaultEntry;
use App\Models\PrivateDataAccessRequest;
use App\Models\VaultFile;
use App\Services\Encryption\PrivateDataKeyService;
use RuntimeException;

/**
 * Prepares an approved private resource for sharing and wraps its key for the requester.
 */
class PrivateDataShareService
{
    public function __construct(
        private PersonalVaultService $personalVaultService,
        private FileVaultService $fileVaultService,
        private PrivateDataKeyService $privateDataKeyService
    ) {}

    /**
     * Make sure the resource is encrypted with its own key and return that key encrypted for the requester.
     *
     * @return string Base64 RSA-OAEP ciphertext of the resource key.
     */
    public function shareKeyWithRequester(PrivateDataAccessRequest $accessRequest): string
    {
        $symmetricKey = match ($accessRequest->resource_type) {
            PersonalVaultService::RESOURCE_TYPE => $this->personalVaultService->ensureResourceKeyEncryption(
                PersonalVaultEntry::query()
                    ->whereKey($accessRequest->resource_id)
                    ->where('user_id', $accessRequest->owner_id)
                    ->firstOrFail()
            ),
            FileVaultService::RESOURCE_TYPE => $this->fileVaultService->ensureRecipientCopy(
                VaultFile::query()
                    ->whereKey($accessRequest->resource_id)
                    ->where('user_id', $accessRequest->owner_id)
                    ->firstOrFail()
            ),
            default => throw new RuntimeException('Unsupported private resource type.'),
        };

        return $this->privateDataKeyService->wrapKeyForRecipient($symmetricKey, $accessRequest->requester);
    }

    /**
     * Remove a file's shared copy and key once no approved request still needs them.
     *
     * Personal Vault keys are kept because they also encrypt the owner's own entry.
     */
    public function releaseUnusedShare(PrivateDataAccessRequest $accessRequest): void
    {
        if ($accessRequest->resource_type !== FileVaultService::RESOURCE_TYPE) {
            return;
        }

        $stillApproved = PrivateDataAccessRequest::query()
            ->where('owner_id', $accessRequest->owner_id)
            ->where('resource_type', $accessRequest->resource_type)
            ->where('resource_id', $accessRequest->resource_id)
            ->where('status', 'approved')
            ->exists();

        if (! $stillApproved) {
            $this->privateDataKeyService->forgetResourceKey(
                $accessRequest->owner_id,
                $accessRequest->resource_type,
                $accessRequest->resource_id
            );
        }
    }
}

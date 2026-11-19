<?php

namespace App\Services\Vault;

use App\Models\PersonalVaultEntry;
use App\Models\PrivateDataAccessRequest;
use App\Models\User;
use App\Models\VaultFile;

class PrivateDataAccessAuthorizer
{
    public function allowsRequester(
        User $requester,
        string $resourceType,
        int $resourceId
    ): bool {
        $ownerId = match ($resourceType) {
            'personal_data' => PersonalVaultEntry::query()
                ->whereKey($resourceId)
                ->value('user_id'),

            'vault_file' => VaultFile::query()
                ->whereKey($resourceId)
                ->value('user_id'),

            default => null,
        };

        if (
            $ownerId === null ||
            (int) $ownerId === (int) $requester->id
        ) {
            return false;
        }

        return PrivateDataAccessRequest::query()
            ->where('owner_id', $ownerId)
            ->where('requester_id', $requester->id)
            ->where('resource_type', $resourceType)
            ->where('resource_id', $resourceId)
            ->where('status', 'approved')
            ->whereNotNull('encrypted_symmetric_key')
            ->exists();
    }
}

<?php

namespace App\Services\Encryption;

use App\Models\PrivateDataAccessRequest;
use App\Models\PrivateResourceKey;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use phpseclib4\Crypt\PublicKeyLoader;
use phpseclib4\Crypt\RSA;
use RuntimeException;
use Throwable;

/**
 * Manages per-resource AES-256 keys and their RSA-OAEP wrapping for approved requesters.
 *
 * Resource keys are stored encrypted at rest. A key leaves the system only after it has been
 * encrypted with the requester's RSA public key, so it can travel through another channel
 * (email, WhatsApp) without exposing the raw key.
 */
class PrivateDataKeyService
{
    public const SHARED_COPY_DIRECTORY = 'vault/shared/';

    private const KEY_LENGTH = 32;

    public function __construct(
        private UserKeyPairService $userKeyPairService
    ) {}

    public function getOrCreateResourceKey(int $ownerId, string $resourceType, int $resourceId): string
    {
        $resourceKey = PrivateResourceKey::query()->createOrFirst(
            [
                'owner_id' => $ownerId,
                'resource_type' => $resourceType,
                'resource_id' => $resourceId,
            ],
            [
                'encrypted_symmetric_key' => Crypt::encryptString(base64_encode(random_bytes(self::KEY_LENGTH))),
            ]
        );

        return $this->unsealResourceKey($resourceKey);
    }

    public function findResourceKey(int $ownerId, string $resourceType, int $resourceId): ?string
    {
        $resourceKey = $this->resourceKeyRecord($ownerId, $resourceType, $resourceId);

        return $resourceKey ? $this->unsealResourceKey($resourceKey) : null;
    }

    public function resourceKeyRecord(int $ownerId, string $resourceType, int $resourceId): ?PrivateResourceKey
    {
        return PrivateResourceKey::query()
            ->where('owner_id', $ownerId)
            ->where('resource_type', $resourceType)
            ->where('resource_id', $resourceId)
            ->first();
    }

    /**
     * Encrypt a raw resource key with the recipient's RSA public key.
     *
     * @return string Base64 RSA-OAEP-SHA256 ciphertext that is safe to send through another channel.
     */
    public function wrapKeyForRecipient(string $symmetricKey, User $recipient): string
    {
        $this->assertKeyLength($symmetricKey);

        $keyPair = $this->userKeyPairService->createFor($recipient);

        $publicKey = PublicKeyLoader::load($keyPair->public_key)
            ->withPadding(RSA::ENCRYPTION_OAEP)
            ->withHash('sha256')
            ->withMGFHash('sha256');

        return base64_encode($publicKey->encrypt($symmetricKey));
    }

    /**
     * Recover the raw resource key for the approved requester using their RSA private key.
     */
    public function decryptForRequester(
        PrivateDataAccessRequest $accessRequest,
        User $requester
    ): string {
        if (
            $accessRequest->status !== 'approved' ||
            (int) $accessRequest->requester_id !== (int) $requester->id ||
            ! $accessRequest->encrypted_symmetric_key
        ) {
            throw new RuntimeException('Access to this key is not authorized.');
        }

        $keyPair = $requester->cryptoKey;

        if (! $keyPair) {
            throw new RuntimeException('User cryptographic key pair not found.');
        }

        $encryptedKey = base64_decode($accessRequest->encrypted_symmetric_key, true);

        if ($encryptedKey === false) {
            throw new RuntimeException('Invalid encrypted symmetric key.');
        }

        try {
            $privateKey = PublicKeyLoader::load(Crypt::decryptString($keyPair->encrypted_private_key))
                ->withPadding(RSA::ENCRYPTION_OAEP)
                ->withHash('sha256')
                ->withMGFHash('sha256');

            $symmetricKey = $privateKey->decrypt($encryptedKey);
        } catch (Throwable $exception) {
            throw new RuntimeException('Unable to decrypt the symmetric key.', previous: $exception);
        }

        $this->assertKeyLength($symmetricKey);

        return $symmetricKey;
    }

    public function recordSharedCopy(int $ownerId, string $resourceType, int $resourceId, string $storedName): void
    {
        PrivateResourceKey::query()
            ->where('owner_id', $ownerId)
            ->where('resource_type', $resourceType)
            ->where('resource_id', $resourceId)
            ->update(['shared_stored_name' => $storedName]);
    }

    /**
     * Delete a resource key and any ciphertext copy that was encrypted with it.
     */
    public function forgetResourceKey(int $ownerId, string $resourceType, int $resourceId): void
    {
        $resourceKey = $this->resourceKeyRecord($ownerId, $resourceType, $resourceId);

        if (! $resourceKey) {
            return;
        }

        if ($resourceKey->shared_stored_name) {
            Storage::disk('local')->delete(self::SHARED_COPY_DIRECTORY.$resourceKey->shared_stored_name);
        }

        $resourceKey->delete();
    }

    private function unsealResourceKey(PrivateResourceKey $resourceKey): string
    {
        $symmetricKey = base64_decode(Crypt::decryptString($resourceKey->encrypted_symmetric_key), true);

        if ($symmetricKey === false) {
            throw new RuntimeException('Invalid resource encryption key.');
        }

        $this->assertKeyLength($symmetricKey);

        return $symmetricKey;
    }

    private function assertKeyLength(string $symmetricKey): void
    {
        if (strlen($symmetricKey) !== self::KEY_LENGTH) {
            throw new RuntimeException('Invalid symmetric key length.');
        }
    }
}

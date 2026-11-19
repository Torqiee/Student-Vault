<?php

namespace App\Services\Vault;

use App\Models\User;
use App\Models\VaultFile;
use App\Services\Encryption\EncryptionManager;
use App\Services\Encryption\EncryptionPolicy;
use App\Services\Encryption\PrivateDataKeyService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class FileVaultService
{
    public const RESOURCE_TYPE = 'vault_file';

    private const CHUNK_SIZE = 1048576;

    private const RECIPIENT_COPY_HEADER = "SVR1\n";

    public function __construct(
        private EncryptionManager $encryptionManager,
        private PrivateDataKeyService $privateDataKeyService
    ) {}

    public function store(User $user, UploadedFile $file, string $category, ?int $courseId = null): VaultFile
    {
        $source = fopen($file->getRealPath(), 'rb');
        $encryptedStream = fopen('php://temp/maxmemory:1048576', 'w+b');

        if ($source === false || $encryptedStream === false) {
            throw new RuntimeException('Unable to open uploaded file.');
        }

        $cipher = EncryptionPolicy::cipherFor($category);
        $hash = hash_init('sha256');
        $size = 0;
        $storedName = Str::uuid()->toString().'.vault';
        $path = 'vault/'.$storedName;

        try {
            fwrite($encryptedStream, "SV2\n");

            while (! feof($source)) {
                $chunk = fread($source, self::CHUNK_SIZE);

                if ($chunk === false) {
                    throw new RuntimeException('Unable to read uploaded file.');
                }

                if ($chunk === '') {
                    continue;
                }

                hash_update($hash, $chunk);
                $size += strlen($chunk);
                fwrite($encryptedStream, $this->encryptionManager->encrypt($chunk, $cipher)."\n");
            }

            rewind($encryptedStream);

            if (! Storage::disk('local')->put($path, $encryptedStream)) {
                throw new RuntimeException('Unable to store encrypted file.');
            }

            return $user->vaultFiles()->create([
                'original_name' => $file->getClientOriginalName(),
                'stored_name' => $storedName,
                'category' => $category,
                'cipher' => $cipher,
                'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
                'original_size' => $size,
                'original_hash' => hash_final($hash),
                'course_id' => $courseId,
            ]);
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);

            throw $exception;
        } finally {
            fclose($source);
            fclose($encryptedStream);
        }
    }

    /** @return resource */
    public function decryptToStream(VaultFile $vaultFile)
    {
        $encryptedStream = Storage::disk('local')->readStream('vault/'.$vaultFile->stored_name);

        if ($encryptedStream === false) {
            throw new RuntimeException('Encrypted file does not exist.');
        }

        $plaintextStream = fopen('php://temp/maxmemory:1048576', 'w+b');
        $hash = hash_init('sha256');
        $size = 0;

        try {
            $header = fgets($encryptedStream, 5);

            if ($header === "SV2\n") {
                while (($line = fgets($encryptedStream, 5000000)) !== false) {
                    if (! str_ends_with($line, "\n")) {
                        throw new RuntimeException('Invalid encrypted file format.');
                    }

                    $chunk = $this->encryptionManager->decrypt(rtrim($line, "\r\n"));
                    hash_update($hash, $chunk);
                    $size += strlen($chunk);
                    fwrite($plaintextStream, $chunk);
                }
            } else {
                rewind($encryptedStream);
                $plaintext = $this->encryptionManager->decrypt(stream_get_contents($encryptedStream));
                hash_update($hash, $plaintext);
                $size = strlen($plaintext);
                fwrite($plaintextStream, $plaintext);
                unset($plaintext);
            }

            if ($size !== $vaultFile->original_size || ! hash_equals($vaultFile->original_hash, hash_final($hash))) {
                throw new RuntimeException('File integrity verification failed.');
            }

            rewind($plaintextStream);

            return $plaintextStream;
        } catch (\Throwable $exception) {
            fclose($plaintextStream);

            throw $exception;
        } finally {
            fclose($encryptedStream);
        }
    }

    public function decrypt(VaultFile $vaultFile): string
    {
        $stream = $this->decryptToStream($vaultFile);

        try {
            return stream_get_contents($stream);
        } finally {
            fclose($stream);
        }
    }

    public function delete(VaultFile $vaultFile): void
    {
        DB::transaction(function () use ($vaultFile): void {
            $vaultFile->sharedFolders()->detach();
            $vaultFile->delete();
            $this->privateDataKeyService->forgetResourceKey($vaultFile->user_id, self::RESOURCE_TYPE, $vaultFile->id);
        });
    }

    public function restore(VaultFile $vaultFile): void
    {
        if (! Storage::disk('local')->exists('vault/'.$vaultFile->stored_name)) {
            throw new RuntimeException('Encrypted file does not exist.');
        }

        $vaultFile->restore();
    }

    public function permanentlyDelete(VaultFile $vaultFile): void
    {
        $path = 'vault/'.$vaultFile->stored_name;
        $disk = Storage::disk('local');

        if ($disk->exists($path) && ! $disk->delete($path)) {
            throw new RuntimeException('Unable to delete encrypted file.');
        }

        $this->privateDataKeyService->forgetResourceKey($vaultFile->user_id, self::RESOURCE_TYPE, $vaultFile->id);
        $vaultFile->forceDelete();
    }

    /**
     * Make sure an approved file has a ciphertext copy encrypted with its own resource key.
     *
     * The original file stays encrypted under the category policy; the copy lets the shared
     * resource key open the approved file without exposing the environment keys.
     *
     * @return string The file's raw resource key.
     */
    public function ensureRecipientCopy(VaultFile $vaultFile): string
    {
        $key = $this->privateDataKeyService->getOrCreateResourceKey($vaultFile->user_id, self::RESOURCE_TYPE, $vaultFile->id);
        $resourceKey = $this->privateDataKeyService->resourceKeyRecord($vaultFile->user_id, self::RESOURCE_TYPE, $vaultFile->id);
        $disk = Storage::disk('local');

        if (
            $resourceKey?->shared_stored_name
            && $disk->exists(PrivateDataKeyService::SHARED_COPY_DIRECTORY.$resourceKey->shared_stored_name)
        ) {
            return $key;
        }

        $storedName = Str::uuid()->toString().'.vault';
        $path = PrivateDataKeyService::SHARED_COPY_DIRECTORY.$storedName;
        $plaintextStream = $this->decryptToStream($vaultFile);
        $encryptedStream = fopen('php://temp/maxmemory:1048576', 'w+b');

        if ($encryptedStream === false) {
            fclose($plaintextStream);

            throw new RuntimeException('Unable to prepare encrypted copy.');
        }

        try {
            fwrite($encryptedStream, self::RECIPIENT_COPY_HEADER);

            while (! feof($plaintextStream)) {
                $chunk = fread($plaintextStream, self::CHUNK_SIZE);

                if ($chunk === false) {
                    throw new RuntimeException('Unable to read decrypted file.');
                }

                if ($chunk !== '') {
                    fwrite($encryptedStream, $this->encryptionManager->encryptWithKey($chunk, $key)."\n");
                }
            }

            rewind($encryptedStream);

            if (! $disk->put($path, $encryptedStream)) {
                throw new RuntimeException('Unable to store encrypted copy.');
            }

            $this->privateDataKeyService->recordSharedCopy($vaultFile->user_id, self::RESOURCE_TYPE, $vaultFile->id, $storedName);

            if ($resourceKey?->shared_stored_name) {
                $disk->delete(PrivateDataKeyService::SHARED_COPY_DIRECTORY.$resourceKey->shared_stored_name);
            }

            return $key;
        } catch (\Throwable $exception) {
            $disk->delete($path);

            throw $exception;
        } finally {
            fclose($plaintextStream);
            fclose($encryptedStream);
        }
    }

    /**
     * Decrypt an approved file's copy with its resource key and verify integrity.
     *
     * @return resource
     */
    public function decryptRecipientCopyToStream(VaultFile $vaultFile, string $key)
    {
        $resourceKey = $this->privateDataKeyService->resourceKeyRecord($vaultFile->user_id, self::RESOURCE_TYPE, $vaultFile->id);

        if (! $resourceKey?->shared_stored_name) {
            throw new RuntimeException('Encrypted copy does not exist.');
        }

        $encryptedStream = Storage::disk('local')->readStream(PrivateDataKeyService::SHARED_COPY_DIRECTORY.$resourceKey->shared_stored_name);

        if ($encryptedStream === false) {
            throw new RuntimeException('Encrypted copy does not exist.');
        }

        $plaintextStream = fopen('php://temp/maxmemory:1048576', 'w+b');
        $hash = hash_init('sha256');
        $size = 0;

        try {
            if (fgets($encryptedStream, 6) !== self::RECIPIENT_COPY_HEADER) {
                throw new RuntimeException('Invalid encrypted copy format.');
            }

            while (($line = fgets($encryptedStream, 5000000)) !== false) {
                if (! str_ends_with($line, "\n")) {
                    throw new RuntimeException('Invalid encrypted copy format.');
                }

                $chunk = $this->encryptionManager->decryptWithKey(rtrim($line, "\r\n"), $key);
                hash_update($hash, $chunk);
                $size += strlen($chunk);
                fwrite($plaintextStream, $chunk);
            }

            if ($size !== $vaultFile->original_size || ! hash_equals($vaultFile->original_hash, hash_final($hash))) {
                throw new RuntimeException('File integrity verification failed.');
            }

            rewind($plaintextStream);

            return $plaintextStream;
        } catch (\Throwable $exception) {
            fclose($plaintextStream);

            throw $exception;
        } finally {
            fclose($encryptedStream);
        }
    }
}

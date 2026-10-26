<?php

namespace App\Services\Vault;

use App\Models\User;
use App\Models\VaultFile;
use App\Services\Encryption\EncryptionManager;
use App\Services\Encryption\EncryptionPolicy;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class FileVaultService
{
    private const CHUNK_SIZE = 1048576;

    public function __construct(private EncryptionManager $encryptionManager) {}

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
        $vaultFile->delete();
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

        $vaultFile->forceDelete();
    }
}

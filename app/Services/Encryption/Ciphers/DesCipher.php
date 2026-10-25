<?php

namespace App\Services\Encryption\Ciphers;

use App\Services\Encryption\Contracts\CipherInterface;
use phpseclib4\Crypt\DES;
use RuntimeException;

class DesCipher implements CipherInterface
{
    private string $key;

    public function __construct()
    {
        $encodedKey = config('studentvault.encryption.des.key');

        if (! $encodedKey) {
            throw new RuntimeException(
                'STUDENTVAULT_DES_KEY is not configured.'
            );
        }

        $key = base64_decode($encodedKey, true);

        if ($key === false || strlen($key) !== 8) {
            throw new RuntimeException(
                'DES key must be a Base64 encoded 8-byte key.'
            );
        }

        $this->key = $key;
    }

    public function identifier(): string
    {
        return 'des';
    }

    public function name(): string
    {
        return 'DES-CBC';
    }

    public function encrypt(string $plaintext): array
    {
        $iv = random_bytes(8);

        try {
            $des = new DES('cbc');

            $des->setKey($this->key);
            $des->setIV($iv);

            $ciphertext = $des->encrypt($plaintext);

            return [
                'iv' => base64_encode($iv),
                'ciphertext' => base64_encode($ciphertext),
            ];
        } catch (\Throwable $exception) {
            throw new RuntimeException(
                'DES encryption failed.',
                previous: $exception
            );
        }
    }

    public function decrypt(array $payload): string
    {
        if (
            ! isset(
                $payload['iv'],
                $payload['ciphertext']
            )
        ) {
            throw new RuntimeException(
                'Invalid DES encrypted payload.'
            );
        }

        $iv = base64_decode($payload['iv'], true);
        $ciphertext = base64_decode(
            $payload['ciphertext'],
            true
        );

        if (
            $iv === false ||
            strlen($iv) !== 8 ||
            $ciphertext === false
        ) {
            throw new RuntimeException(
                'Invalid DES payload encoding.'
            );
        }

        try {
            $des = new DES('cbc');

            $des->setKey($this->key);
            $des->setIV($iv);

            return $des->decrypt($ciphertext);

        } catch (\Throwable $exception) {
            throw new RuntimeException(
                'DES decryption failed.',
                previous: $exception
            );
        }
    }
}

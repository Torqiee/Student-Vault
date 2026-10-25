<?php

namespace App\Services\Encryption\Ciphers;

use App\Services\Encryption\Contracts\CipherInterface;
use RuntimeException;

class AesCipher implements CipherInterface
{
    private string $cipher = 'aes-256-gcm';

    private string $key;

    public function __construct()
    {
        $encodedKey = config('studentvault.encryption.aes.key');

        if (! $encodedKey) {
            throw new RuntimeException(
                'STUDENTVAULT_AES_KEY is not configured.'
            );
        }

        $key = base64_decode($encodedKey, true);

        if ($key === false || strlen($key) !== 32) {
            throw new RuntimeException(
                'AES key must be a Base64 encoded 32-byte key.'
            );
        }

        $this->key = $key;
    }

    public function identifier(): string
    {
        return 'aes';
    }

    public function name(): string
    {
        return 'AES-256-GCM';
    }

    public function encrypt(string $plaintext): array
    {
        $ivLength = openssl_cipher_iv_length($this->cipher);

        $iv = random_bytes($ivLength);

        $tag = '';

        $ciphertext = openssl_encrypt(
            $plaintext,
            $this->cipher,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($ciphertext === false) {
            throw new RuntimeException('AES encryption failed.');
        }

        return [
            'iv' => base64_encode($iv),
            'tag' => base64_encode($tag),
            'ciphertext' => base64_encode($ciphertext),
        ];
    }

    public function decrypt(array $payload): string
    {
        if (
            ! isset(
                $payload['iv'],
                $payload['tag'],
                $payload['ciphertext']
            )
        ) {
            throw new RuntimeException(
                'Invalid AES encrypted payload.'
            );
        }

        $iv = base64_decode($payload['iv'], true);
        $tag = base64_decode($payload['tag'], true);
        $ciphertext = base64_decode(
            $payload['ciphertext'],
            true
        );

        if (
            $iv === false ||
            $tag === false ||
            $ciphertext === false
        ) {
            throw new RuntimeException(
                'Invalid AES payload encoding.'
            );
        }

        $plaintext = openssl_decrypt(
            $ciphertext,
            $this->cipher,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($plaintext === false) {
            throw new RuntimeException(
                'AES authentication/decryption failed.'
            );
        }

        return $plaintext;
    }
}

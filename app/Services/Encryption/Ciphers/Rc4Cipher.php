<?php

namespace App\Services\Encryption\Ciphers;

use App\Services\Encryption\Contracts\CipherInterface;
use phpseclib4\Crypt\RC4;
use RuntimeException;

class Rc4Cipher implements CipherInterface
{
    private string $key;

    public function __construct()
    {
        $encodedKey = config('studentvault.encryption.rc4.key');

        if (! $encodedKey) {
            throw new RuntimeException(
                'STUDENTVAULT_RC4_KEY is not configured.'
            );
        }

        $key = base64_decode($encodedKey, true);

        if ($key === false || strlen($key) < 16) {
            throw new RuntimeException(
                'RC4 key must be a valid Base64 encoded key.'
            );
        }

        $this->key = $key;
    }

    public function identifier(): string
    {
        return 'rc4';
    }

    public function name(): string
    {
        return 'RC4';
    }

    public function encrypt(string $plaintext): array
    {
        try {
            $rc4 = new RC4;

            $rc4->setKey($this->key);

            $ciphertext = $rc4->encrypt($plaintext);

            return [
                'ciphertext' => base64_encode($ciphertext),
            ];

        } catch (\Throwable $exception) {
            throw new RuntimeException(
                'RC4 encryption failed.',
                previous: $exception
            );
        }
    }

    public function decrypt(array $payload): string
    {
        if (! isset($payload['ciphertext'])) {
            throw new RuntimeException(
                'Invalid RC4 encrypted payload.'
            );
        }

        $ciphertext = base64_decode(
            $payload['ciphertext'],
            true
        );

        if ($ciphertext === false) {
            throw new RuntimeException(
                'Invalid RC4 payload encoding.'
            );
        }

        try {
            $rc4 = new RC4;

            $rc4->setKey($this->key);

            return $rc4->decrypt($ciphertext);

        } catch (\Throwable $exception) {
            throw new RuntimeException(
                'RC4 decryption failed.',
                previous: $exception
            );
        }
    }
}

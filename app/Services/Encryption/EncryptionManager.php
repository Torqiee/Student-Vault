<?php

namespace App\Services\Encryption;

use App\Services\Encryption\Ciphers\AesCipher;
use App\Services\Encryption\Ciphers\DesCipher;
use App\Services\Encryption\Ciphers\Rc4Cipher;
use App\Services\Encryption\Contracts\CipherInterface;
use InvalidArgumentException;
use JsonException;
use RuntimeException;

class EncryptionManager
{
    private const RESOURCE_KEY_VERSION = 2;

    /**
     * @var array<string, CipherInterface>
     */
    private array $ciphers;

    public function __construct()
    {
        $aes = new AesCipher;
        $des = new DesCipher;
        $rc4 = new Rc4Cipher;

        $this->ciphers = [
            $aes->identifier() => $aes,
            $des->identifier() => $des,
            $rc4->identifier() => $rc4,
        ];
    }

    public function encrypt(
        string $plaintext,
        string $cipher = 'aes'
    ): string {
        $cipherService = $this->cipher($cipher);

        $envelope = [
            'version' => 1,
            'cipher' => $cipherService->identifier(),
            'data' => $cipherService->encrypt($plaintext),
        ];

        return $this->encodeEnvelope($envelope);
    }

    public function decrypt(string $encryptedPayload): string
    {
        $envelope = $this->decodeEnvelope($encryptedPayload);

        if ($envelope['version'] !== 1) {
            throw new RuntimeException(
                'Unsupported encryption payload version.'
            );
        }

        return $this->cipher($envelope['cipher'])->decrypt($envelope['data']);
    }

    /**
     * Encrypt with a per-resource AES-256 key instead of the environment key.
     */
    public function encryptWithKey(
        string $plaintext,
        string $key
    ): string {
        $envelope = [
            'version' => self::RESOURCE_KEY_VERSION,
            'cipher' => 'aes',
            'key_type' => 'resource',
            'data' => $this->aes()->encryptWithKey($plaintext, $key),
        ];

        return $this->encodeEnvelope($envelope);
    }

    public function decryptWithKey(
        string $encryptedPayload,
        string $key
    ): string {
        $envelope = $this->decodeEnvelope($encryptedPayload);

        if (
            $envelope['version'] !== self::RESOURCE_KEY_VERSION ||
            $envelope['cipher'] !== 'aes' ||
            ($envelope['key_type'] ?? null) !== 'resource'
        ) {
            throw new RuntimeException(
                'Unsupported resource encryption payload.'
            );
        }

        return $this->aes()->decryptWithKey($envelope['data'], $key);
    }

    /**
     * Determine whether a payload was encrypted with a per-resource key.
     */
    public function usesResourceKey(string $encryptedPayload): bool
    {
        try {
            $envelope = $this->decodeEnvelope($encryptedPayload);
        } catch (RuntimeException) {
            return false;
        }

        return $envelope['version'] === self::RESOURCE_KEY_VERSION
            && ($envelope['key_type'] ?? null) === 'resource';
    }

    public function cipher(string $identifier): CipherInterface
    {
        if (! isset($this->ciphers[$identifier])) {
            throw new InvalidArgumentException(
                "Unsupported cipher: {$identifier}"
            );
        }

        return $this->ciphers[$identifier];
    }

    private function aes(): AesCipher
    {
        $aes = $this->cipher('aes');

        if (! $aes instanceof AesCipher) {
            throw new RuntimeException('AES cipher is unavailable.');
        }

        return $aes;
    }

    /**
     * @param  array<string, mixed>  $envelope
     */
    private function encodeEnvelope(array $envelope): string
    {
        return base64_encode(
            json_encode($envelope, JSON_THROW_ON_ERROR)
        );
    }

    /**
     * @return array{version: int, cipher: string, data: array<string, string>, key_type?: string}
     */
    private function decodeEnvelope(string $encryptedPayload): array
    {
        $decoded = base64_decode($encryptedPayload, true);

        if ($decoded === false) {
            throw new RuntimeException('Invalid encrypted payload.');
        }

        try {
            $envelope = json_decode(
                $decoded,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException $exception) {
            throw new RuntimeException(
                'Invalid encrypted payload format.',
                previous: $exception
            );
        }

        if (
            ! is_array($envelope) ||
            ! isset(
                $envelope['version'],
                $envelope['cipher'],
                $envelope['data']
            )
        ) {
            throw new RuntimeException(
                'Incomplete encrypted payload.'
            );
        }

        return $envelope;
    }
}

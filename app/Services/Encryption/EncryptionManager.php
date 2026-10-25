<?php

namespace App\Services\Encryption;

use App\Services\Encryption\Ciphers\AesCipher;
use App\Services\Encryption\Ciphers\DesCipher;
use App\Services\Encryption\Ciphers\Rc4Cipher;
use App\Services\Encryption\Contracts\CipherInterface;
use InvalidArgumentException;
use RuntimeException;

class EncryptionManager
{
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

        $encryptedData = $cipherService->encrypt($plaintext);

        $envelope = [
            'version' => 1,
            'cipher' => $cipherService->identifier(),
            'data' => $encryptedData,
        ];

        return base64_encode(
            json_encode(
                $envelope,
                JSON_THROW_ON_ERROR
            )
        );
    }

    public function decrypt(string $encryptedPayload): string
    {
        $decoded = base64_decode(
            $encryptedPayload,
            true
        );

        if ($decoded === false) {
            throw new RuntimeException(
                'Invalid encrypted payload.'
            );
        }

        try {
            $envelope = json_decode(
                $decoded,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (\JsonException $exception) {
            throw new RuntimeException(
                'Invalid encrypted payload format.',
                previous: $exception
            );
        }

        if (
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

        if ($envelope['version'] !== 1) {
            throw new RuntimeException(
                'Unsupported encryption payload version.'
            );
        }

        $cipher = $this->cipher(
            $envelope['cipher']
        );

        return $cipher->decrypt(
            $envelope['data']
        );
    }

    public function cipher(
        string $identifier
    ): CipherInterface {
        if (! isset($this->ciphers[$identifier])) {
            throw new InvalidArgumentException(
                "Unsupported cipher: {$identifier}"
            );
        }

        return $this->ciphers[$identifier];
    }

    public function availableCiphers(): array
    {
        return array_keys($this->ciphers);
    }
}

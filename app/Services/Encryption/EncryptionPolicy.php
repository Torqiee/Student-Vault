<?php

namespace App\Services\Encryption;

use InvalidArgumentException;

class EncryptionPolicy
{
    public const NOTES = 'aes';

    public const DOCUMENTS = 'aes';

    public const IDENTITY = 'aes';

    public const ASSIGNMENTS = 'des';

    public const VIDEOS = 'rc4';

    public static function cipherFor(string $category): string
    {
        return match ($category) {
            'notes' => self::NOTES,
            'documents' => self::DOCUMENTS,
            'identity' => self::IDENTITY,
            'assignments' => self::ASSIGNMENTS,
            'videos' => self::VIDEOS,

            default => throw new InvalidArgumentException(
                "Unsupported vault category: {$category}"
            ),
        };
    }
}

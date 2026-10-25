<?php

namespace App\Services\Encryption\Contracts;

interface CipherInterface
{
    public function encrypt(string $plaintext): array;

    public function decrypt(array $payload): string;

    public function name(): string;

    public function identifier(): string;
}

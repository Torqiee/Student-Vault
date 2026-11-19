<?php

namespace App\Services\Encryption;

use App\Models\User;
use App\Models\UserCryptoKey;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class UserKeyPairService
{
    public function createFor(User $user): UserCryptoKey
    {
        return DB::transaction(function () use ($user): UserCryptoKey {
            $lockedUser = User::query()
                ->whereKey($user->id)
                ->lockForUpdate()
                ->firstOrFail();

            $existingKey = $lockedUser->cryptoKey;

            if ($existingKey) {
                return $existingKey;
            }

            $keyPair = openssl_pkey_new([
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
                'private_key_bits' => 3072,
            ]);

            if ($keyPair === false) {
                throw new RuntimeException('Unable to generate RSA key pair.');
            }

            $privateKey = '';

            if (! openssl_pkey_export($keyPair, $privateKey)) {
                throw new RuntimeException('Unable to export RSA private key.');
            }

            $details = openssl_pkey_get_details($keyPair);

            if ($details === false || ! isset($details['key'])) {
                throw new RuntimeException('Unable to retrieve RSA public key.');
            }

            return $lockedUser->cryptoKey()->create([
                'public_key' => $details['key'],
                'encrypted_private_key' => Crypt::encryptString($privateKey),
                'algorithm' => 'RSA-OAEP-SHA256',
            ]);
        });
    }
}

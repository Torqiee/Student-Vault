<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCryptoKey extends Model
{
    protected $fillable = [
        'user_id',
        'public_key',
        'encrypted_private_key',
        'algorithm',
    ];

    protected $hidden = [
        'encrypted_private_key',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

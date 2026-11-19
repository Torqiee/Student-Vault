<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrivateResourceKey extends Model
{
    protected $fillable = [
        'owner_id',
        'resource_type',
        'resource_id',
        'encrypted_symmetric_key',
        'shared_stored_name',
    ];

    protected $hidden = [
        'encrypted_symmetric_key',
        'shared_stored_name',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}

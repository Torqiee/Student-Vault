<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrivateDataAccessRequest extends Model
{
    protected $fillable = ['owner_id', 'requester_id', 'resource_type', 'resource_id', 'status', 'encrypted_symmetric_key', 'responded_at'];

    protected $hidden = ['encrypted_symmetric_key'];

    protected function casts(): array
    {
        return ['responded_at' => 'datetime'];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }
}

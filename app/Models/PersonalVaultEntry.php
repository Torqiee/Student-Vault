<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonalVaultEntry extends Model
{
    protected static function booted(): void
    {
        static::deleting(function (self $entry): void {
            PrivateDataAccessRequest::query()->where('resource_type', 'personal_data')
                ->where('resource_id', $entry->id)->where('owner_id', $entry->user_id)
                ->whereIn('status', ['pending', 'approved'])
                ->update(['status' => 'revoked', 'encrypted_symmetric_key' => null, 'responded_at' => now()]);
        });
    }

    protected $table = 'personal_data';

    protected $fillable = [
        'encrypted_label',
        'category',
        'encrypted_fields',
        'identity_file_id',
    ];

    protected $hidden = [
        'encrypted_label',
        'encrypted_fields',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function identityFile(): BelongsTo
    {
        return $this->belongsTo(VaultFile::class, 'identity_file_id');
    }
}

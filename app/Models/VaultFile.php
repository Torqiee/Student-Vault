<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class VaultFile extends Model
{
    use SoftDeletes;

    protected static function booted(): void
    {
        static::deleting(function (self $file): void {
            PrivateDataAccessRequest::query()->where('resource_type', 'vault_file')
                ->where('resource_id', $file->id)->where('owner_id', $file->user_id)
                ->whereIn('status', ['pending', 'approved'])
                ->update(['status' => 'revoked', 'encrypted_symmetric_key' => null, 'responded_at' => now()]);
        });
    }

    protected $fillable = [
        'original_name',
        'stored_name',
        'category',
        'cipher',
        'mime_type',
        'original_size',
        'original_hash',
        'course_id',
        'starred_at',
    ];

    protected function casts(): array
    {
        return ['starred_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function sharedFolders(): BelongsToMany
    {
        return $this->belongsToMany(SharedFolder::class, 'shared_folder_files')->withTimestamps();
    }
}

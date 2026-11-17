<?php

namespace App\Models;

use Database\Factories\SharedFolderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SharedFolder extends Model
{
    /** @use HasFactory<SharedFolderFactory> */
    use HasFactory;

    protected $fillable = ['name', 'description'];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'shared_folder_memberships')->withTimestamps();
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(SharedFolderInvitation::class);
    }

    public function notes(): BelongsToMany
    {
        return $this->belongsToMany(Note::class, 'shared_folder_notes')->withTimestamps();
    }

    public function files(): BelongsToMany
    {
        return $this->belongsToMany(VaultFile::class, 'shared_folder_files')->withTimestamps();
    }

    public function isAccessibleBy(User $user): bool
    {
        return $this->owner_id === $user->id
            || $this->members()->whereKey($user->id)->exists();
    }

    public function hasActiveContributor(int $userId): bool
    {
        return $this->owner_id === $userId
            || $this->members()->whereKey($userId)->exists();
    }
}

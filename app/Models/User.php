<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    public function vaultFiles(): HasMany
    {
        return $this->hasMany(VaultFile::class);
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    public function personalVaultEntries(): HasMany
    {
        return $this->hasMany(PersonalVaultEntry::class);
    }

    public function ownedSharedFolders(): HasMany
    {
        return $this->hasMany(SharedFolder::class, 'owner_id');
    }

    public function sharedFolders(): BelongsToMany
    {
        return $this->belongsToMany(SharedFolder::class, 'shared_folder_memberships')->withTimestamps();
    }

    public function sharedFolderInvitations(): HasMany
    {
        return $this->hasMany(SharedFolderInvitation::class, 'invitee_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}

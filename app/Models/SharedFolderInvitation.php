<?php

namespace App\Models;

use Database\Factories\SharedFolderInvitationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SharedFolderInvitation extends Model
{
    /** @use HasFactory<SharedFolderInvitationFactory> */
    use HasFactory;

    protected $fillable = ['invitee_id', 'email', 'status', 'responded_at'];

    protected function casts(): array
    {
        return ['responded_at' => 'datetime'];
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(SharedFolder::class, 'shared_folder_id');
    }

    public function invitee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invitee_id');
    }
}

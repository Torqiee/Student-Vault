<?php

namespace App\Models;

use App\Services\Encryption\EncryptionManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Note extends Model
{
    protected $fillable = [
        'title',
        'content',
        'course_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function setTitleAttribute(string $value): void
    {
        $this->attributes['title'] = app(
            EncryptionManager::class
        )->encrypt($value);
    }

    public function getTitleAttribute(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return app(
            EncryptionManager::class
        )->decrypt($value);
    }

    public function setContentAttribute(?string $value): void
    {
        if ($value === null) {
            $this->attributes['content'] = null;

            return;
        }

        $this->attributes['content'] = app(
            EncryptionManager::class
        )->encrypt($value);
    }

    public function getContentAttribute(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return app(
            EncryptionManager::class
        )->decrypt($value);
    }
}

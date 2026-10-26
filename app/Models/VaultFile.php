<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VaultFile extends Model
{
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
        return [
            'starred_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}

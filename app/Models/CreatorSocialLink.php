<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreatorSocialLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'creator_id',
        'platform',
        'url',
        'handle',
        'follower_count',
    ];

    protected $casts = [
        'follower_count' => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Creator::class);
    }
}

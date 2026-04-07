<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Trend extends Model
{
    protected $fillable = [
        'title',
        'summary',
        'category',
        'country',
        'country_code',
        'source_label',
        'source_url',
        'trend_date',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'trend_date' => 'date',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where('expires_at', '>', now());
    }

    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('trend_date', today());
    }

    public function flagEmoji(): string
    {
        if (! $this->country_code) {
            return '';
        }

        $code = strtoupper($this->country_code);

        if (strlen($code) !== 2 || ! ctype_alpha($code)) {
            return '';
        }

        return mb_chr(ord($code[0]) - ord('A') + 0x1F1E6, 'UTF-8')
            . mb_chr(ord($code[1]) - ord('A') + 0x1F1E6, 'UTF-8');
    }
}

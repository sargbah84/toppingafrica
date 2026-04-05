<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SearchConsoleData extends Model
{
    protected $table = 'search_console_data';

    protected $fillable = [
        'query',
        'page',
        'country',
        'device',
        'date',
        'clicks',
        'impressions',
        'ctr',
        'position',
    ];

    protected $casts = [
        'date' => 'date',
        'clicks' => 'integer',
        'impressions' => 'integer',
        'ctr' => 'decimal:4',
        'position' => 'decimal:2',
    ];
}

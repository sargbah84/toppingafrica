<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    protected static function booted(): void
    {
        // get() caches forever, so any mutation must invalidate. saved/deleted
        // covers model-driven inserts, updates, and deletes — including the
        // updateOrCreate() path used by set().
        static::saved(fn (Setting $s) => Cache::forget("setting.{$s->key}"));
        static::deleted(fn (Setting $s) => Cache::forget("setting.{$s->key}"));
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::rememberForever("setting.{$key}", function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            return $setting?->value ?? $default;
        });
    }

    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public static function forget(string $key): void
    {
        // Use first()->delete() (not where()->delete()) so the deleted model
        // event fires and our cache invalidation hook in booted() runs.
        static::where('key', $key)->first()?->delete();
        Cache::forget("setting.{$key}");
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class PromotionRequest extends Model implements HasMedia
{
    use InteractsWithMedia;

    public const STATUSES = [
        'inquiry' => 'New inquiry',
        'invoiced' => 'Invoice sent',
        'pending_payment' => 'Checkout not completed',
        'paid' => 'Paid — new',
        'in_production' => 'In production',
        'live' => 'Live',
        'completed' => 'Completed',
        'declined' => 'Declined / refunded',
        'cancelled' => 'Cancelled',
    ];

    protected $fillable = [
        'user_id',
        'package',
        'addons',
        'amount',
        'currency',
        'name',
        'email',
        'phone',
        'promo_type',
        'subject_name',
        'title',
        'description',
        'primary_url',
        'links',
        'preferred_date',
        'rights_confirmed',
        'status',
        'post_id',
        'deliverables',
        'metrics',
        'admin_notes',
    ];

    protected $casts = [
        'addons' => 'array',
        'links' => 'array',
        'deliverables' => 'array',
        'metrics' => 'array',
        'amount' => 'integer',
        'rights_confirmed' => 'boolean',
        'preferred_date' => 'date',
        'paid_at' => 'datetime',
        'live_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (PromotionRequest $request) {
            if (empty($request->reference)) {
                do {
                    $reference = 'TA-'.Str::upper(Str::random(6));
                } while (static::where('reference', $reference)->exists());

                $request->reference = $reference;
            }
        });
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('assets');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    public function getPackageNameAttribute(): string
    {
        return config("promotions.packages.{$this->package}.name", Str::headline($this->package));
    }

    /** @return array<int, string> */
    public function getAddonNamesAttribute(): array
    {
        return collect($this->addons ?? [])
            ->map(fn (string $key) => config("promotions.addons.{$key}.name", Str::headline($key)))
            ->all();
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? Str::headline($this->status);
    }

    public function getFormattedAmountAttribute(): string
    {
        return self::formatMoney($this->amount, $this->currency);
    }

    /** Signed link back to the payment page (Stripe cancel URL, retry links). */
    public function payUrl(): string
    {
        return URL::signedRoute('promote.pay', $this);
    }

    public function checkoutUrl(): string
    {
        return URL::signedRoute('promote.pay-now', $this);
    }

    public function isPaid(): bool
    {
        return $this->paid_at !== null;
    }

    public static function formatMoney(int $cents, ?string $currency = null): string
    {
        $currency = strtoupper($currency ?? config('promotions.currency'));
        $amount = $cents % 100 === 0 ? number_format($cents / 100) : number_format($cents / 100, 2);

        return $currency === 'USD' ? '$'.$amount : $amount.' '.$currency;
    }
}

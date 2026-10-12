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

    /** `promo_type` of an order where the buyer supplies the finished article. */
    public const SPONSORED_ARTICLE = 'sponsored_article';

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
        'article_body',
        'primary_url',
        'anchor_text',
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
        return config("promotions.packages.{$this->package}.name")
            ?? config("promotions.sponsored_packages.{$this->package}.name", Str::headline($this->package));
    }

    public function getPromoTypeLabelAttribute(): string
    {
        return $this->isSponsoredArticle()
            ? 'Sponsored article'
            : config("promotions.promo_types.{$this->promo_type}", Str::headline($this->promo_type));
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

    /** Approved or checked out, but not paid yet. */
    public function awaitingPayment(): bool
    {
        return ! $this->isPaid() && in_array($this->status, ['invoiced', 'pending_payment'], true);
    }

    public function isSponsoredArticle(): bool
    {
        return $this->promo_type === self::SPONSORED_ARTICLE;
    }

    /** Status as shown to the buyer in their dashboard. */
    public function getBuyerStatusAttribute(): string
    {
        return match ($this->status) {
            'inquiry' => 'In review',
            'invoiced', 'pending_payment' => 'Approved — awaiting payment',
            'paid', 'in_production' => 'Paid — being published',
            'live', 'completed' => 'Published',
            'declined' => 'Declined',
            'cancelled' => 'Cancelled',
            default => $this->status_label,
        };
    }

    /**
     * The submitted article as post HTML. The buyer's link goes on the first
     * occurrence of their anchor text, or in a closing paragraph if the text
     * doesn't appear in the article.
     */
    public function articleHtml(): string
    {
        $anchor = e((string) $this->anchor_text);
        $href = e($this->primary_url);
        $linked = false;

        $html = collect(preg_split('/\R{2,}/', trim((string) $this->article_body)))
            ->filter(fn (string $paragraph) => trim($paragraph) !== '')
            ->map(function (string $paragraph) use ($anchor, $href, &$linked) {
                $paragraph = nl2br(e(trim($paragraph)));

                if (! $linked && $anchor !== '') {
                    $paragraph = preg_replace_callback(
                        '/'.preg_quote($anchor, '/').'/i',
                        fn (array $match) => '<a href="'.$href.'">'.$match[0].'</a>',
                        $paragraph,
                        1,
                        $count,
                    );
                    $linked = $count > 0;
                }

                return "<p>{$paragraph}</p>";
            })
            ->implode('');

        return $linked ? $html : $html.'<p><a href="'.$href.'">'.($anchor ?: $href).'</a></p>';
    }

    public static function formatMoney(int $cents, ?string $currency = null): string
    {
        $currency = strtoupper($currency ?? config('promotions.currency'));
        $amount = $cents % 100 === 0 ? number_format($cents / 100) : number_format($cents / 100, 2);

        return $currency === 'USD' ? '$'.$amount : $amount.' '.$currency;
    }
}

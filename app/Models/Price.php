<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Models\cms\CountrycodeCurrencyMapping;

class Price extends Model
{
    use LogsActivity;

    const MAX_ZTORM_ID = 200000;

    protected $table = 'prices';

    protected $fillable = [
        'product_id',
        'source',
        'currency',
        'country_code',
        'price',
        'steam_price',
        'cost_estimate',
        'discount_valid_from',
        'discount_valid_to',
        'discount_percent',
        'discount_valid_from_2game',
        'discount_valid_to_2game',
        'discount_percent_2game',
        'is_active',
        'price_source',
        'concept_id',
        'scrape_url',
        'is_promo_active',
        'price_update_timestamp',
        'min_value',
        'max_value',
        'created_at',
        'updated_at',
        'allowed_countries',
        'title',
    ];

    protected $appends = [
        'discount_valid_from_formatted',
        'discount_valid_to_formatted',
        'discount_valid_from_2game_formatted',
        'discount_valid_to_2game_formatted',
        'discount_amount',
        'discount_percent_raw',
        'vat_rate',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'sku');
    }

    public function scopeZtorm($query)
    {
        return $query->where('product_id', '<', self::MAX_ZTORM_ID);
    }

    /**
     * Get an array of country codes for a specific product and currency.
     * bad practice, not related to model at all, yet another price query,
     * todo: should be moved to price repository, with cache to reduce disk reads
     */
    public static function getCountryCodesByCurrency(int $productId, string $currency): array
    {
        return self::where('product_id', $productId)
            ->where('currency', $currency)
            ->pluck('country_code')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Get grouped prices with concatenated country codes
     * matching raw SQL exactly.
     */
    public static function getGroupedPricesByProduct(int $productId): array
    {
        return self::query()
            ->selectRaw('
            product_id,
            currency,
            price,
            steam_price,
            cost_estimate,
            is_active,
            is_converted,
            GROUP_CONCAT(country_code) as country_codes
        ')
            ->where('product_id', $productId)
            ->groupBy('currency', 'price')
            ->get()
            ->toArray();
    }

    public function getDiscountValidFromFormattedAttribute(): ?string
    {
        if ($this->attributes['discount_valid_from']) {
            return date('Y-m-d H:i', $this->attributes['discount_valid_from']);
        }

        return null;
    }

    public function getDiscountValidToFormattedAttribute(): ?string
    {
        if ($this->attributes['discount_valid_to']) {
            return date('Y-m-d H:i', $this->attributes['discount_valid_to']);
        }

        return null;
    }

    public function getDiscountValidFrom2gameFormattedAttribute(): ?string
    {
        if ($this->attributes['discount_valid_from_2game']) {
            return Carbon::parse($this->attributes['discount_valid_from_2game'])->format('Y-m-d H:i');
        }

        return null;
    }

    public function getDiscountValidTo2gameFormattedAttribute(): ?string
    {
        if ($this->attributes['discount_valid_to_2game']) {
            return Carbon::parse($this->attributes['discount_valid_to_2game'])->format('Y-m-d H:i');
        }

        return null;
    }

    public function getDiscountAmountAttribute()
    {
        return $this->discountAmount() ?? 0;
    }

    public function discountAmount()
    {
        return $this->calculateDiscount();

        // need when 2game discount take count
        /*$discountPrice = 0;
        $discountActive = $this->isDiscountActive();
        $discountActiveTwogame = $this->isDiscountActiveTwogame();

        if ($discountActive) {
            $discountPrice = $this->calculateDiscount();
        }

        if (! $discountActiveTwogame) {
            return $discountPrice;
        }

        if ($discountActiveTwogame) {
            $price = $this->is_converted ? $this->steam_price : $this->price;

            $rrp = $discountPrice > 0 ? $discountPrice : $price;

            return $this->calculateDiscountTwogame($rrp);
        }

        return $discountPrice;*/
    }

    public function isDiscountActive()
    {
        $currentTimestamp = time();

        return $this->attributes['discount_percent'] > 0 &&
            $this->attributes['discount_valid_from'] > 0 &&
            $this->attributes['discount_valid_from'] <= $currentTimestamp &&
            ($currentTimestamp <= $this->attributes['discount_valid_to'] ||
                $this->attributes['discount_valid_to'] == 0);
    }

    /*public function isDiscountActiveTwogame()
    {
        $currentTimestamp = time();

        return $this->attributes['discount_percent_2game'] > 0 &&
            $this->attributes['discount_valid_from_2game'] > 0 &&
            $this->attributes['discount_valid_from_2game'] <= $currentTimestamp &&
            ($currentTimestamp <= $this->attributes['discount_valid_to_2game'] ||
            $this->attributes['discount_valid_to_2game'] == 0);
    }*/

    protected function calculateDiscount()
    {
        $price = ($this->is_converted && $this->steam_price > 0) ? $this->steam_price : $this->price;

        $discountAmount = ($price * $this->attributes['discount_percent']) / 100;
        $discountPrice = $price - $discountAmount;

        return number_format($discountPrice, 2, '.', '');
    }

    /*protected function calculateDiscountTwogame($discountPrice)
    {
        $discountAmount = ($discountPrice * $this->attributes['discount_percent_2game']) / 100;
        $discountPrice = $discountPrice - $discountAmount;

        return number_format($discountPrice, 2, '.', '');
    }*/

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'price',
                'discount_percent',
                'title',
                'price_source',
                'scrape_url',
                'concept_id',
                'is_active',
                'steam_price',
            ])
            ->logOnlyDirty() // only trigger on changes
            ->useLogName('price')
            ->dontSubmitEmptyLogs(); // prevent empty logs
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $productId = $this->product_id;

        $trackedFields = [
            'price',
            'discount_percent',
            'title',
            'price_source',
            'scrape_url',
            'concept_id',
            'is_active',
            'steam_price',
        ];

        $buildProperties = function () use ($trackedFields) {
            $new = $this->only($trackedFields);
            $old = [];

            foreach ($trackedFields as $field) {
                $old[$field] = $this->getOriginal($field);
            }

            return collect([
                'attributes' => $new,
                'old' => $old,
            ]);
        };

        // CREATED
        if ($eventName === 'created') {
            $activity->log_name = 'price_created';
            $activity->description = "Created price record for product_id: {$productId}";
            $activity->properties = $buildProperties();
            return;
        }

        // UPDATED
        if ($eventName === 'updated') {

            // Soft delete
            if ($this->isDirty('is_active') && $this->is_active == 0) {
                $activity->log_name = 'price_deleted';
                $activity->description = "Deleted price record for product_id: {$productId}";
                $activity->properties = $buildProperties();
                return;
            }

            // Reactivated
            if ($this->isDirty('is_active') && $this->is_active == 1) {
                $activity->log_name = 'price_reactivated';
                $activity->description = "Reactivated price record for product_id: {$productId}";
                $activity->properties = $buildProperties();
                return;
            }

            // Any other tracked field updated
            $dirtyTracked = array_intersect(array_keys($this->getDirty()), $trackedFields);

            if (!empty($dirtyTracked)) {
                $activity->log_name = 'price_updated';
                $activity->description = "Updated price record for product_id: {$productId}";
                $activity->properties = $buildProperties();
                return;
            }
        }
    }



    // attr
    /*public function getDiscountValidFromAttribute($value)
    {
        $discountActive = $this->isDiscountActive();
        if ($discountActive) {
            return $value;
        }

        $discountActiveTwogame = $this->isDiscountActiveTwogame();
        if ($discountActiveTwogame) {
            return $this->attributes['discount_valid_from_2game'];
        }

        return $value;
    }*/

    /*public function getDiscountValidToAttribute($value)
    {
        $discountActive = $this->isDiscountActive();
        if ($discountActive) {
            return $value;
        }

        $discountActiveTwogame = $this->isDiscountActiveTwogame();
        if ($discountActiveTwogame) {
            return $this->attributes['discount_valid_to_2game'];
        }

        return $value;
    }*/

    /*public function getDiscountPercentAttribute($value)
    {
        $discountActive = $this->isDiscountActive();
        $discountActiveTwogame = $this->isDiscountActiveTwogame();

        if ($discountActive && $discountActiveTwogame) {
            return $this->getTwogamePercent();
        }

        if ($discountActive && ! $discountActiveTwogame) {
            return $value;
        }

        if (! $discountActive && $discountActiveTwogame) {
            return $this->attributes['discount_percent_2game'];
        }

        if (! $discountActive && ! $discountActiveTwogame) {
            return $value;
        }

        return $value;
    }*/

    /*public function getTwogamePercent()
    {
        $price = $this->is_converted ? $this->steam_price : $this->price;
        if ($price <= 0) {
            return 0;
        }
        $discountPrice = $this->discountAmount();

        return number_format(100 - ($discountPrice / $price * 100), 3, '.', '');
    }*/

    public function getDiscountPercentRawAttribute()
    {
        return $this->attributes['discount_percent'];
    }

    public function countrycodeCurrencyMapping()
    {
        return $this->belongsTo(CountrycodeCurrencyMapping::class, 'country_code', 'country_code');
    }

    public function getVatRateAttribute()
    {
        return $this->countrycodeCurrencyMapping?->vat;
    }
}

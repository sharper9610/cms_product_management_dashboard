<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 0;

    const ZTORM_PRODUCT_ID_START = 1000;
    const ZTORM_PRODUCT_ID_END = 200000;

    const SOURCE_ZTORM = 1;
    const SOURCE_INCOMM = 2;

    protected $table = 'products';

    protected $appends = [
        'release_date_formatted',
        'drm_type_formatted',
        'download_date_formatted',
        'franchise_formatted',
        'status_formatted',
        'source_formatted',
        'supported_languages_formatted',
        'dlc_products_formatted',
        'supported_languages_raw',
        'developers_raw',
        'genres_raw',
        'allowed_countries',
        'allowed_currencies',
        'system_requirements_raw',
        'pegi_ratings_formatted',
    ];

    protected $fillable = [
        'sku',
        'name',
        'source',
        'auxiliary_field',
        'bundled_products',
        'classification',
        'community_discussion',
        'default_language',
        'developers',
        'dlc_products',
        'dlc_master_product_id',
        'is_dlc',
        'download_date',
        'drm_type',
        'face_value',
        'genres',
        'franchise',
        'platform',
        'publisher_id',
        'publisher_name',
        'product_type',
        'redemption',
        'redemption_field',
        'region_tag',
        'release_date',
        'status',
        'supported_languages',
        'systems',
        'system_requirements',
        'terms_and_conditions',
        'update_timestamp',
        'validade',
        'average_rating',
        'total_reviews',
        'skip_update',
        'min_value',
        'max_value',
        'created_at',
        'updated_at',
        'product_upc',
        'merchant_commission_percentage',
        'category',
        'bonus_cap_percent',
        'allowed_countries',
        'allowed_currencies',
        'editions',
        'pegi_ratings'
    ];

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    // HasMany relation to ProductMedia
    public function media()
    {
        return $this->hasMany(ProductMedia::class, 'product_id', 'sku');
    }

    // Has one rating
    public function rating()
    {
        return $this->hasOne(Rating::class, 'product_id', 'sku');
    }

    public function ratings()
    {
        return $this->hasMany(Rating::class, 'product_id', 'sku');
    }

    // Has many prices
    public function prices()
    {
        return $this->hasMany(Price::class, 'product_id', 'sku')->where('is_active', 1);
    }

    public function baseLocalizations()
    {
        return $this->hasMany(Localization::class, 'product_id', 'sku');
    }

    public function localizations()
    {
        return $this->baseLocalizations();
    }

    public function tagItems()
    {
        return $this->baseLocalizations()
            ->whereIn('locale', ['en', 'es-419', 'pt-br'])
            ->withTags();
    }

    public function systemRequirementItems()
    {
        return $this->baseLocalizations()
            ->withSystemRequirements()
            ->whereIn('locale', ['en', 'es-419', 'pt-br']);
    }

    public function legalTexts()
    {
        return $this->baseLocalizations()
            ->whereIn('locale', ['en', 'es-419', 'pt-br'])
            ->withLegalTexts();
    }
    public function supportLanguages()
    {
        return $this->baseLocalizations()
            ->whereIn('locale', ['en', 'es-419', 'pt-br'])
            ->withSupportedLanguages();
    }

    // Has many tags
    public function tags()
    {
        return $this->hasMany(Tag::class, 'product_id', 'sku');
    }

    public function getReleaseDateFormattedAttribute()
    {
        return $this->release_date
            ? Carbon::createFromTimestamp($this->release_date)->format('Y-m-d')
            : '';
    }

    public function getDownloadDateFormattedAttribute()
    {
        return $this->download_date
            ? Carbon::createFromTimestamp($this->download_date)->format('Y-m-d')
            : '';
    }

    public function getSourceFormattedAttribute()
    {
        // todo: ref it from config/service
        return $this->source == '1' ? 'ztorm' : ($this->source == '2' ? 'inComm' : '');
    }

    public function getStatusFormattedAttribute()
    {
        return $this->status == '0' ? 'inactive' : ($this->status == '1' ? 'active' : '');
    }

    public function getAllowedCountriesAttribute(): array
    {
        return $this->prices
            ->pluck('country_code')
            ->unique()
            ->values()
            ->all();
    }

    public function getAllowedCurrenciesAttribute(): array
    {
        return $this->prices
            ->pluck('currency')
            ->unique()
            ->values()
            ->all();
    }

    public function getGenresAttribute($value)
    {
        $genres = @unserialize($value);

        return is_array($genres) ? $genres : [];
    }

    public function getFranchiseFormattedAttribute($value)
    {
        $data = @unserialize($this->attributes['franchise']);

        return is_array($data) ? $data : [];
    }

    public function getGenresRawAttribute($value)
    {
        return $value ?? null;
    }

    public function getDevelopersAttribute($value)
    {
        $data = @unserialize($value);

        return is_array($data) ? $data : [];
    }

    public function getDevelopersRawAttribute()
    {
        return $this->attributes['developers'];
    }

    public function getSupportedLanguagesAttribute(): array
    {
        $html = $this->attributes['supported_languages'] ?? '';

        if (empty($html)) {
            return [];
        }

        $languageMap = config('languages');

        $text = html_entity_decode($html);

        $text = preg_replace('/(&lt;br\s*\/&gt;|<br\s*\/?>)/i', "\n", $text);

        $text = strip_tags($text);

        $lines = explode("\n", $text);

        $languages = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            if (strpos($line, ':') !== false) {
                [$key, $values] = explode(':', $line, 2);

                $key = \Illuminate\Support\Str::snake(trim($key));

                $valuesArray = array_filter(array_map('trim', explode(',', $values)));

                $codesArray = array_map(function ($lang) use ($languageMap) {
                    return $languageMap[$lang] ?? strtolower($lang);
                }, $valuesArray);

                if (isset($languages[$key])) {
                    $languages[$key] = array_unique(array_merge($languages[$key], $codesArray));
                } else {
                    $languages[$key] = $codesArray;
                }
            }
        }

        return $languages;
    }

    public function getSupportedLanguagesRawAttribute()
    {
        return $this->attributes['supported_languages'];
    }

    public function getSystemRequirementsAttribute()
    {
        $value = $this->attributes['system_requirements'] ?? '';
        if (empty($value)) {
            return null;
        }

        $dom = new \DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML($value);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);

        // Extract initial information (all <p> except ones containing "Minimum:" and "Recommended:")
        $intro = [];
        foreach ($xpath->query('//p') as $p) {
            $text = trim($p->textContent);
            if (! str_contains($text, 'Minimum:') && ! str_contains($text, 'Recommended:')) {
                $intro[] = $text;
            }
        }

        // Extract Minimum requirements (find <p><strong>Minimum:</strong></p> and its next <ul>)
        $minimum = [];
        $minNode = $xpath->query('//p[strong[contains(text(),"Minimum:")]]')->item(0);
        if ($minNode) {
            $nextUl = $minNode->nextSibling;
            while ($nextUl && $nextUl->nodeName !== 'ul') {
                $nextUl = $nextUl->nextSibling;
            }
            if ($nextUl) {
                foreach ($xpath->query('.//li', $nextUl) as $li) {
                    $minimum[] = trim($li->textContent);
                }
            }
        }

        // Extract Recommended requirements (find <p><strong>Recommended:</strong></p> and its next <ul>)
        $recommended = [];
        $recNode = $xpath->query('//p[strong[contains(text(),"Recommended:")]]')->item(0);
        if ($recNode) {
            $nextUl = $recNode->nextSibling;
            while ($nextUl && $nextUl->nodeName !== 'ul') {
                $nextUl = $nextUl->nextSibling;
            }
            if ($nextUl) {
                foreach ($xpath->query('.//li', $nextUl) as $li) {
                    $recommended[] = trim($li->textContent);
                }
            }
        }

        return [
            'requirements_info' => $intro,
            'minimum' => $minimum,
            'recommended' => $recommended,
        ];
    }

    public function getsystemRequirementsRawAttribute()
    {
        return $this->attributes['system_requirements'] ?? '';
    }

    public function getSupportedsLanguagesFormattedAttributeFirst()
    {
        $raw = $this->attributes['supported_languages'] ?? null;

        if (empty($raw)) {
            return [];
        }

        // Decode HTML entities
        $decoded = html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Normalize non-breaking spaces to regular spaces
        $decoded = str_replace("\xc2\xa0", ' ', $decoded); // UTF-8 NBSP
        $decoded = str_replace('&nbsp;', ' ', $decoded);   // In case it wasn't decoded

        // Remove <p> tags, keep <br>
        $decoded = strip_tags($decoded, '<br>');

        // Split by <br> or newline
        $lines = preg_split('/<br\s*\/?>|\r?\n/', $decoded, -1, PREG_SPLIT_NO_EMPTY);

        $result = [];

        foreach ($lines as $line) {
            // Normalize spaces again
            $line = preg_replace('/\s+/', ' ', $line);
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            // Split by first colon
            if (strpos($line, ':') !== false) {
                [$key, $value] = explode(':', $line, 2);

                // Normalize key
                $key = strtolower(trim(str_replace('-', '_', $key)));
                $key = preg_replace('/\s+/', '_', $key);

                // Split values by comma
                $items = array_filter(array_map(function ($v) {
                    $v = preg_replace('/\s+/', ' ', $v); // collapse spaces

                    return trim($v);
                }, explode(',', $value)));

                $result[$key] = $items;
            }
        }

        return $result;
    }

    public function getSupportedLanguagesFormattedAttribute()
    {
        $raw = $this->attributes['supported_languages'] ?? null;

        if (empty($raw)) {
            return [];
        }

        // Decode HTML entities
        $decoded = html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Normalize non-breaking spaces to regular spaces
        $decoded = str_replace("\xc2\xa0", ' ', $decoded); // UTF-8 NBSP
        $decoded = str_replace('&nbsp;', ' ', $decoded);   // Just in case

        // Remove <p> tags, keep <br>
        $decoded = strip_tags($decoded, '<br>');

        // Split by <br> or newline
        $lines = preg_split('/<br\s*\/?>|\r?\n/', $decoded, -1, PREG_SPLIT_NO_EMPTY);

        $result = [];

        foreach ($lines as $line) {
            // Normalize spaces
            $line = preg_replace('/\s+/', ' ', $line);
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            // Split by first colon
            if (strpos($line, ':') !== false) {
                [$key, $value] = explode(':', $line, 2);

                // Normalize key
                $key = strtolower(trim(str_replace('-', '_', $key)));
                $key = preg_replace('/\s+/', '_', $key);

                // ✅ Split by comma OR slash
                $items = array_filter(array_map(function ($v) {
                    $v = preg_replace('/\s+/', ' ', $v); // collapse spaces

                    return trim($v);
                }, preg_split('/[,\/]/', $value)));

                $result[$key] = $items;
            }
        }

        return $result;
    }

    public function getDlcProductsFormattedAttribute($value)
    {
        $data = @unserialize($this->attributes['dlc_products']);

        return is_array($data) ? $data : [];
    }

    public function getEditionsAttribute($value)
    {
        $data = @unserialize($value);

        return is_array($data) ? $data : [];
    }

    public function getDrmTypeFormattedAttribute()
    {
        $data = @unserialize($this->attributes['drm_type']);

        if (is_array($data) && isset($data['DRMType'])) {
            return $data['DRMType'];
        }

        return '';
    }

    public function getPegiRatingsFormattedAttribute()
    {
        $data = $this->pegi_ratings;

        // If data is serialized, unserialize it safely
        if (is_string($data) && @unserialize($data) !== false) {
            $data = unserialize($data);
        }

        // If data is empty or not an array, return empty array
        if (empty($data) || ! is_array($data)) {
            return [];
        }

        // Check if 'Rating' exists
        if (isset($data['Rating'])) {
            $ratings = $data['Rating'];

            // Wrap single rating in an array
            if (isset($ratings['Type'])) {
                $ratings = [$ratings];
            }

            // Format each rating
            $formatted = array_map(function ($rating) {
                return [
                    'type' => $rating['Type'] ?? null,
                    'text' => $rating['Text'] ?? null,
                    'logo' => $rating['Logo']['URL'] ?? null,
                ];
            }, $ratings);

            return $formatted;
        }

        return [];
    }

    public function skipUpdates()
    {
        return $this->hasMany(ProductsSkipUpdate::class, 'product_id', 'sku');
    }

    public static function getIncompleteProducts()
    {
        $requiredLocales = ['en', 'pt-br', 'es-419'];
        $requiredFields = [
            'short_description',
            'long_description',
            // 'system_requirements',
            // 'supported_languages',
            'seo_tags',
            'genre_tags',
            'franchise_tags',
            'community_tags',
            'title',
            'legal_texts'
        ];

        $checkFieldMissing = function ($query, $field) {
            if ($field === 'genre_tags') {
                $query->orWhere(function ($f) {
                    $f->where('source', '!=', 2) // todo: replace
                        ->whereNull('genre_tags')
                        ->orWhere('genre_tags', '');
                });
            } else {
                $query->orWhere(function ($f) use ($field) {
                    $f->whereNull($field)
                        ->orWhere($field, '');
                });
            }
        };

        return self::query()
            ->whereNull('average_rating')
            ->orWhere('average_rating', '')
            ->orWhereHas('localizations', fn($q) => $q->whereIn('locale', $requiredLocales), '<', count($requiredLocales))
            ->orWhereHas('localizations', function ($q) use ($requiredFields, $requiredLocales, $checkFieldMissing) {
                $q->whereIn('locale', $requiredLocales)
                    ->whereHas('product', function ($prodQ) use ($requiredFields, $checkFieldMissing) {
                        $prodQ->where(function ($fieldsQ) use ($requiredFields, $checkFieldMissing) {
                            foreach ($requiredFields as $field) {
                                $checkFieldMissing($fieldsQ, $field);
                            }
                        });
                    });
            })
            ->with('localizations')
            ->orderByDesc('id')
            ->get();
    }


    public function scopeCompleted(Builder $query): void
    {
        $requiredLocales = ['en', 'pt-br', 'es-419'];
        $requiredFields = [
            'short_description',
            'long_description',
            'system_requirements',
            'seo_tags',
            'community_tags',
            'franchise_tags',
            'title',
            'legal_texts',
        ];

        // The 'completed' logic (a big AND condition)
        $query->whereHas('media', fn($q) => $q->where('is_main', 1))
            ->whereHas('prices')
            ->whereHas('baseLocalizations', function ($q) use ($requiredLocales, $requiredFields) {
                $q->whereIn('locale', $requiredLocales);
                foreach ($requiredFields as $field) {
                    $q->whereNotNull($field)->where($field, '!=', '');
                }
            }, '=', count($requiredLocales))
            ->whereNotNull('average_rating')
            ->where('average_rating', '!=', '')
            ->where(function ($q) use ($requiredLocales) {
                $q->where('source', '!=', 1) // Not Ztorm OR
                    ->orWhere(function ($q2) use ($requiredLocales) {
                        $q2->where('source', 1) // Is Ztorm AND
                            ->whereHas('baseLocalizations', function ($q3) use ($requiredLocales) {
                                $q3->whereIn('locale', $requiredLocales)
                                    ->whereNotNull('genre_tags')
                                    ->where('genre_tags', '!=', '');
                            }, '=', count($requiredLocales)); // Has required genre tags
                    });
            });
    }
    public function scopeIncomplete(Builder $query): void
    {
        $requiredLocales = ['en', 'pt-br', 'es-419'];
        $requiredFields = [
            'short_description',
            'long_description',
            'system_requirements',
            'seo_tags',
            'community_tags',
            'franchise_tags',
            'title',
            'legal_texts',
        ];

        // The 'incomplete' logic (a big OR condition)
        $query->where(function ($q) use ($requiredLocales, $requiredFields) {
            // 1. Missing main media
            $q->orWhereDoesntHave('media', fn($q2) => $q2->where('is_main', 1))
                // 2. Missing prices
                ->orWhereDoesntHave('prices')
                // 3. Missing or incomplete localizations
                ->orWhere(function ($q3) use ($requiredLocales, $requiredFields) {
                    $q3->whereDoesntHave('baseLocalizations', function ($inner) use ($requiredLocales) {
                        $inner->whereIn('locale', $requiredLocales);
                    }, '=', count($requiredLocales))
                        ->orWhereHas('baseLocalizations', function ($inner) use ($requiredLocales, $requiredFields) {
                            $inner->whereIn('locale', $requiredLocales)
                                ->where(function ($innerSub) use ($requiredFields) {
                                    foreach ($requiredFields as $field) {
                                        $innerSub->orWhereNull($field)->orWhere($field, '');
                                    }
                                });
                        });
                })
                // 4. Missing rating
                ->orWhere(function ($q) {
                    $q->whereNull('average_rating')
                        ->orWhereRaw('TRIM(average_rating) = ""');
                })
                // 5. Source = 1 but missing genre_tags
                ->orWhere(function ($q4) use ($requiredLocales) {
                    $q4->where('source', 1)
                        ->whereDoesntHave('baseLocalizations', function ($q5) use ($requiredLocales) {
                            $q5->whereIn('locale', $requiredLocales)
                                ->whereNotNull('genre_tags')
                                ->where('genre_tags', '!=', '');
                        }, '=', count($requiredLocales));
                });
        });
    }


    public function shouldSkipUpdate()
    {
        return !! $this->skip_update;
    }

    public function conceptPricesRelation()
    {

        return $this->hasMany(Price::class, 'product_id', 'sku')
            ->whereNotNull('concept_id')
            ->where('is_active', self::STATUS_ACTIVE);
    }

    public function conceptPrices()
    {

        return $this->conceptPricesRelation->map(function ($p) {
            return [
                'title' => $p->title,
                'description' => $p->description,
                'source' => $p->price_source,
                'primary_image_url' => $p->primary_image_url,
                'platforms' => $p->platforms ? @unserialize($p->platforms) : [],
            ];
        })->values()->toArray();
    }

    public function scopeActive(Builder $query)
    {
        return $query->where('products.status', self::STATUS_ACTIVE);
    }

    public function scopeInactive(Builder $query)
    {
        return $query->where('products.status', self::STATUS_INACTIVE);
    }

    public function scopeZtorm(Builder $query)
    {
        return $query
            // ->where('products.source', self::SOURCE_ZTORM)
            ->where('products.sku', '>', self::ZTORM_PRODUCT_ID_START)
            ->where('products.sku', '<', self::ZTORM_PRODUCT_ID_END);
    }

    /**
     * Get bonus cap percent for this product
     */
    public function getBonusCapPercent(): int
    {
        if (isset($this->attributes['bonus_cap_percent'])) {
            return (int) $this->attributes['bonus_cap_percent'];
        }

        return $this->getDefaultBonusCapPercent();
    }

    /**
     * Get default bonus cap based on product type
     */
    private function getDefaultBonusCapPercent(): int
    {
        $productType = strtolower($this->product_type ?? '');

        if ($productType === 'gift_card' || $productType === 'giftcard') {
            return 5;
        }

        if ($productType === 'restricted' || $productType === 'no_bonus') {
            return 0;
        }

        return 100;
    }

    /**
     * Get maximum bonus amount that can be used for this product
     */
    public function getMaxBonusAmount(float $quantity = 1, ?float $price = null): float
    {
        if ($price === null) {
            $firstPrice = $this->prices()->first();
            $price = $firstPrice ? $firstPrice->price : 0;
        }

        $itemTotal = $price * $quantity;
        $capPercent = $this->getBonusCapPercent();
        $capDecimal = $capPercent / 100;

        return $itemTotal * $capDecimal;
    }

    /**
     * Check if bonus can be used for this product
     */
    public function canUseBonus(): bool
    {
        return $this->getBonusCapPercent() > 0;
    }

    /**
     * Check if full bonus (100%) is allowed
     */
    public function isFullBonusAllowed(): bool
    {
        return $this->getBonusCapPercent() >= 100;
    }

    /**
     * Calculate bonus allocation for this product
     */
    public function calculateBonusAllocation(float $quantity = 1, ?float $price = null): array
    {
        if ($price === null) {
            $firstPrice = $this->prices()->first();
            $price = $firstPrice ? $firstPrice->price : 0;
        }

        $itemTotal = $price * $quantity;
        $bonusCapPercent = $this->getBonusCapPercent();
        $maxBonusAllowed = ($itemTotal * $bonusCapPercent) / 100;
        $requiredCash = $itemTotal - $maxBonusAllowed;

        return [
            'sku' => $this->sku,
            'title' => $this->name,
            'quantity' => $quantity,
            'price' => $price,
            'item_total' => $itemTotal,
            'bonus_cap_percent' => $bonusCapPercent,
            'max_bonus_allowed' => $maxBonusAllowed,
            'required_cash' => max(0, $requiredCash),
            'can_use_full_bonus' => $bonusCapPercent >= 100,
        ];
    }

    /**
     * Scope: Filter by bonus cap percent
     */
    public function scopeWithBonusCap($query, ?int $minPercent = null, ?int $maxPercent = null)
    {
        if ($minPercent !== null) {
            $query->where('bonus_cap_percent', '>=', $minPercent);
        }

        if ($maxPercent !== null) {
            $query->where('bonus_cap_percent', '<=', $maxPercent);
        }

        return $query;
    }

    /**
     * Scope: Products that allow full bonus
     */
    public function scopeFullBonusAllowed($query)
    {
        return $query->where('bonus_cap_percent', '>=', 100);
    }

    /**
     * Scope: Products with bonus restrictions
     */
    public function scopeWithBonusRestriction($query)
    {
        return $query->where('bonus_cap_percent', '<', 100);
    }

    /**
     * Scope: Products with no bonus allowed
     */
    public function scopeNoBonusAllowed($query)
    {
        return $query->where('bonus_cap_percent', '=', 0);
    }
}

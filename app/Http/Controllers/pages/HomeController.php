<?php

namespace App\Http\Controllers\pages;

use App\Http\Controllers\Controller;
use App\Models\Option;
use App\Models\Price;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;


class HomeController extends Controller
{


    public function index()
    {





        $cacheKey = 'completed_product_counts';
        $cacheTtl = 15*60; // 15 minutes

        $counts = Cache::remember($cacheKey, $cacheTtl, function () {

            $completedQuery = Product::query()->completed();

            $completedCount = $completedQuery->count();
            $totalCount = Product::count();

            return [
                'completed' => $completedCount,
                'incompleted' => $totalCount - $completedCount,
                'total' => $totalCount,
            ];
        });

        $completedCount = $counts['completed'];
        $incompletedCount = $counts['incompleted'];
        $totalCount = $counts['total'];

        // ✅ Active & Inactive counts
        $activeCount = Product::where('status', 1)->count();
        $inactiveCount = Product::where('status', 0)->count();

        $completionRate = $totalCount > 0
          ? round(($completedCount / $totalCount) * 100, 2)
          : 0;

        $countries = array_keys(config('countries', []));

        $totalCountries = Price::whereIn('country_code', $countries)->distinct('country_code')->count('country_code');
        $totalProducts = Price::whereIn('country_code', $countries)->distinct('product_id')->count('product_id');

        $publishers = Cache::remember('publishers_list', 60 * 60 * 12, function () {
            return Product::select('publisher_id', 'publisher_name')
                ->whereNotNull('publisher_id')
                ->whereNotNull('publisher_name')
                ->where('publisher_name', '!=', '')
                ->whereRaw('id IN (SELECT MAX(id) FROM products WHERE publisher_id IS NOT NULL GROUP BY publisher_id)')
                ->orderBy('publisher_name')
                ->get();
        });

       $imortStatus= $this->getImportStatus();

        return view('content.pages.pages-home', [
            'totalCount' => $totalCount,
            'completedCount' => $completedCount,
            'incompletedCount' => $incompletedCount,
            'activeCount' => $activeCount,
            'inactiveCount' => $inactiveCount,
            'completionRate' => $completionRate,
            'totalCountries' => $totalCountries,
            'totalProducts' => $totalProducts,
            'publishers' => $publishers,
            'imortStatus' => $imortStatus,
        ]);
    }

    public function productList(Request $request)
    {
        $columns = [
            1 => 'sku',
            2 => 'name',
        ];

        $search = [
            1 => 'sku',
            2 => 'name',
            12 => 'publisher_id',
            13 => 'developers',
            14 => 'status',
            15 => 'source',
            16 => 'release_date',
            17 => 'completed_product',

        ];

        $query = Product::with(['localizations', 'systemRequirementItems', 'tags', 'prices', 'media']);

        // Apply search filters efficiently
        foreach ($search as $index => $field) {
            $searchValue = $request->input("columns.$index.search.value");
            if (empty($searchValue)) {
                continue;
            }

            if ($field === 'status') {
                $statusFilter = match ($searchValue) {
                    'active' => 1,
                    'inactive' => 0,
                    default => null,
                };
                if (! is_null($statusFilter)) {
                    $query->where($field, $statusFilter);
                }
            } elseif ($field === 'source') {
                $query->where($field, $searchValue);
            } elseif ($field === 'name') {
                $query->where('name', 'LIKE', "%{$searchValue}%");
            } elseif ($field === 'sku') {
                $query->where('sku', 'LIKE', "%{$searchValue}%");
            } elseif ($field === 'developers') {
                $query->where('developers', 'LIKE', "%{$searchValue}%");

            } elseif ($field == 'publisher_id') {
                if ($searchValue == 'null') {
                    $query->whereNull('publisher_id');
                } else {
                    $query->where('publisher_id', '=', $searchValue);
                }

            } elseif ($field == 'release_date') {
                $now = time(); // current Unix timestamp

                if ($searchValue == 'upcoming') {
                    $query->where('release_date', '>', $now);
                } elseif ($searchValue == 'new_release') {
                    $query->whereBetween('created_at', [
                        now()->subDays(7)->startOfDay(),
                        now(),
                    ]);
                }
            } elseif ($field === 'developers') {
                $query->where('developers', 'LIKE', "%{$searchValue}%");
            } elseif ($field === 'completed_product') {

                if ($searchValue === 'yes') {
                    $query->completed();
                } elseif ($searchValue === 'no') {
                    $query->incomplete();
                }
            } else {
                //        $query->where($field, 'LIKE', "%{$searchValue}%");
            }
        }

        $totalData = $query->count();
        $limit = $request->input('length', 10);
        $start = $request->input('start', 0);
        $order = $columns[$request->input('order.0.column')] ?? 'id';
        $dir = $request->input('order.0.dir', 'desc');

        // Only select needed columns
        $products = $query->select([
            'id', 'name', 'sku', 'average_rating', 'system_requirements',
        ])
            ->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir)
            ->get();

        $data = $products->map(function ($product, $index) use ($start) {
            $requiredLocales = ['pt-br', 'en', 'es-419'];
//            $localizationLocales = $product->localizations->pluck('locale')->toArray();
//            $hasAllRequiredLocales = collect($requiredLocales)->every(fn ($locale) => in_array($locale, $localizationLocales));


          $hasAllRequiredLocales = $product->localizations()
            ->whereIn('locale', $requiredLocales)
            ->where(function ($q) {
              $q->where(function ($q2) {
                // Short description check
                $q2->whereNotNull('short_description')
                  ->where('short_description', '!=', '');
              })
                ->Where(function ($q2) {
                  // Long description check
                  $q2->whereNotNull('long_description')
                    ->where('long_description', '!=', '');
                });
            })
            ->get()
            ->pluck('locale')
            ->unique()
            ->values()
            ->toArray();

// Check if all required locales exist with filled descriptions
          $hasAllRequiredLocales = collect($requiredLocales)
            ->every(fn($locale) => in_array($locale, $hasAllRequiredLocales));

            $requiredLocalesSystemReq = ['pt-br', 'es-419'];
            $systemRequirementItemsLocales = $product->systemRequirementItems->pluck('locale')->toArray();
            $hasAllRequiredsystemRequirement = collect($requiredLocalesSystemReq)->every(fn ($locale) => in_array($locale, $systemRequirementItemsLocales));

            $tagComplete = false;

            if ($product && $product->tagItems->isNotEmpty()) {
//                $tagItems = $product->tagItems;
                $tagItems = $product->tagItems->take(3);


              // Count how many have non-empty seo_tags
                $seoTagCount = $tagItems->whereNotNull('seo_tags')
                    ->where('seo_tags', '!=', '')
                    ->count() == 3;

                // Count how many have non-empty genre_tags
                $genreTagCount = $tagItems->whereNotNull('genre_tags')
                    ->where('genre_tags', '!=', '')
                    ->count() == 3;

                if ($product->source == 2) {
                    $genreTagCount = true;
                }

                // Count how many have non-empty franchise_tags
                $franchiseTagCount = $tagItems->whereNotNull('franchise_tags')
                    ->where('franchise_tags', '!=', '')
                    ->count() == 3;

                // Count how many have non-empty franchise_tags
                $communityTagCount = $tagItems->whereNotNull('community_tags')
                    ->where('community_tags', '!=', '')
                    ->count() == 3;

                $tagComplete = $seoTagCount && $genreTagCount && $franchiseTagCount && $communityTagCount;

            }

            $fields = [
                'is_media_main' => $product->media->contains('is_main', 1),
                'is_localizations' => $hasAllRequiredLocales,
                'is_tags' => $tagComplete,
                'is_prices' => $product->prices->isNotEmpty(),
                'is_countries' => $product->prices->isNotEmpty(), // assuming same as prices
                'is_rating' => ! is_null($product->average_rating) && $product->average_rating !== '',
                'is_system_requirements' => $hasAllRequiredsystemRequirement,
            ];

            // Count how many are true
            $completedCount = count(array_filter($fields));

            // Calculate percentage
            $completionPercent = round(($completedCount / count($fields)) * 100);
            $reqCompletionPercent = 100 - $completionPercent;
            $isCompletion = $reqCompletionPercent == 0 ? true : false;

            // 🔹 Mapping field keys to user-friendly labels
            $fieldLabels = [
                'is_media_main' => 'Main Image',
                'is_localizations' => 'Localizations',
                'is_tags' => 'Tags',
                'is_prices' => 'Prices',
                'is_countries' => 'Countries',
                'is_rating' => 'Rating',
                'is_system_requirements' => 'System Requirements',
            ];

            $missingItems = [];
            foreach ($fields as $field => $status) {
                if (! $status && isset($fieldLabels[$field])) {
                    $missingItems[] = $fieldLabels[$field];
                }
            }

            return [
                'id' => $product->id,
                'fake_id' => $start + $index + 1,
                'name' => $product->name,
                'sku' => $product->sku,

                // ✅ New fields

                'is_media' => $product->media->isNotEmpty(),

                'is_media_main' => $product->media->contains('is_main', 1),
                'is_localizations' => $hasAllRequiredLocales,
                'is_tags' => $tagComplete,
                'is_prices' => $product->prices->isNotEmpty(),
                'is_countries' => $product->prices->isNotEmpty(),
                'is_rating' => ! is_null($product->average_rating) && $product->average_rating !== '',
                'is_system_requirements' => $hasAllRequiredsystemRequirement,
                'completion' => $completionPercent,
                'req_completion' => $reqCompletionPercent,
                'is_completion' => $isCompletion,
                'missing_items' => $missingItems,

                //        'localization_needed' => collect($product->allowed_currencies)
                //          ->mapWithKeys(function ($currency) {
                //            $mapping = config("localization.currency_locales.$currency", []);
                //            return [$currency => $mapping];
                //          })
                //          ->toArray(),

            ];
        });

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $totalData,
            'recordsFiltered' => $totalData,
            'code' => 200,
            'data' => $data,
        ]);
    }

    public function countryWiseProductCount()
    {
        $countries = array_keys(config('countries', []));

        $countryMap = config('countries') ?? [];

        $countryProductCounts = Cache::remember('country_product_counts', 30 * 60, function () use ($countries, $countryMap) {
            return Price::select('country_code')
                ->selectRaw('COUNT(product_id) as product_count')
                ->whereIn('country_code', $countries)
                ->groupBy('country_code')
                ->orderBy('country_code')
                ->get()
                ->map(function ($item) use ($countryMap) {
                    return [
                        'country_code' => $item->country_code,
                        'country_name' => $countryMap[$item->country_code]['name'] ?? null,
                        'region' => $countryMap[$item->country_code]['region'] ?? null,
                        'currency_name' => $countryMap[$item->country_code]['currency_name'] ?? null,
                        'product_count' => $item->product_count,
                    ];
                });
        });

        return response()->json($countryProductCounts);
    }

    public function countryWiseProduct(Request $request, $code)
    {
        try {
            $prices = Price::with('product')
                ->where('country_code', $code)
                ->whereHas('product', function ($query) {
                    $query->whereIn('status', [0, 1]); // only active or inactive
                })
                ->get();

            // Group active publishers and count their products
            $activePublishers = $prices->filter(fn ($price) => $price->product->status == 1)
                ->groupBy('product.publisher_name')
                ->map(fn ($items, $publisher) => [
                    'publisher_name' => $publisher,
                    'count' => $items->count(),
                ])
                ->values();

            // Group inactive publishers and count their products
            $inactivePublishers = $prices->filter(fn ($price) => $price->product->status == 0)
                ->groupBy('product.publisher_name')
                ->map(fn ($items, $publisher) => [
                    'publisher_name' => $publisher,
                    'count' => $items->count(),
                ])
                ->values();

            // Totals (with duplicates, i.e. per product)
            $totalActive = $prices->filter(fn ($price) => $price->product->status == 1)->count();
            $totalInactive = $prices->filter(fn ($price) => $price->product->status == 0)->count();
            $total = $totalActive + $totalInactive;
            $totalCoverage = $total > 0 ? round(($totalActive / $total) * 100, 2) : 0;

            return response()->json([
                'success' => true,
                'total' => $totalActive + $totalInactive,
                'totalCoverage' => $totalCoverage,
                'active' => [
                    'count' => $totalActive,
                    'publishers' => $activePublishers,
                ],
                'inactive' => [
                    'count' => $totalInactive,
                    'publishers' => $inactivePublishers,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

  public function getImportStatus()
  {
    $options = Option::whereIn('key', [
      'ztorm_price_import',
      'ztorm_price_import_start',
      'ztorm_price_import_end',
      'ztorm_product_import',
      'ztorm_product_import_start',
      'ztorm_product_import_end',
      'incomm_price_import',
      'incomm_price_import_start',
      'incomm_price_import_end',
      'incomm_product_import',
      'incomm_product_import_start',
      'incomm_product_import_end',
    ])->get()
      ->pluck('value', 'key');
    // convert to key => value map

    // Helper function to validate and format timestamp
    $formatTime = function ($timestamp) {
      if (empty($timestamp)) {
        return '';
      }

      // Check if numeric (Unix timestamp)
      if (is_numeric($timestamp)) {
        $timestamp = (int)$timestamp;
        return date('Y-m-d H:i', $timestamp);
      }

      // Try to parse datetime string
      $time = strtotime($timestamp);
      if ($time !== false && $time > 0) {
        return date('Y-m-d H:i', $time);
      }

      return ''; // invalid timestamp
    };

    $sources = ['ztorm', 'incomm'];
    $types = ['price', 'product'];

    $result = [];

    foreach ($sources as $source) {
      foreach ($types as $type) {
        $keyBase = "{$source}_{$type}_import";

        $status = $options[$keyBase] ?? null;
        $startRaw = $options["{$keyBase}_start"] ?? null;
        $endRaw = $options["{$keyBase}_end"] ?? null;

        $start = $formatTime($startRaw);
        $end = $formatTime($endRaw);

        // Determine situation
        if ($status === null) {
          $situation = 'complete';
        } elseif ($status === '1' || strtolower($status) === 'running') {
          $situation = 'running';
        } elseif ($status === '0' || strtolower($status) === 'complete') {
          $situation = 'complete';
        } else {
          $situation = $status;
        }

        $result[$source][$type] = [
          'situation' => $situation,
          'start_time' => $start,
          'end_time' => $end,
          'start_t' => $startRaw,
          'end_t' => $endRaw,
        ];
      }
    }

    return $result;
  }


}

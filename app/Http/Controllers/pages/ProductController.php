<?php

namespace App\Http\Controllers\pages;

use App\Http\Controllers\Controller;
use App\Http\Traits\HttpResponses;
use App\Models\Localization;
use App\Models\Notice;
use App\Models\Option;
use App\Models\Price;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\ProductPublisher;
use App\Models\ProductsSkipUpdate;
use App\Models\Prompt;
use App\Models\Tag;
use App\Services\Openai\OpenAIService;
use App\Services\Openai\PromptService;
use App\Services\Openai\RatingSuggestionService;
use App\Services\Openai\TagSuggestionService;
use App\Services\Openai\TranslationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    use HttpResponses;

    public function list()
    {

        $this->checkPageAccess('product.view');

        $publishers = Cache::remember('publishers_list', 60 * 60 * 1, function () {
            return Product::select('publisher_id', 'publisher_name')
                ->whereNotNull('publisher_id')
                ->whereNotNull('publisher_name')
                ->where('publisher_name', '!=', '')
                ->whereRaw('id IN (SELECT MAX(id) FROM products WHERE publisher_id IS NOT NULL GROUP BY publisher_id)')
                ->orderBy('publisher_name')
                ->get();
        });

        // Get distinct countries from prices table
        $countries = Cache::remember('countries_list', 60 * 60 * 1, function () {
            return \App\Models\Price::distinct()
                ->whereNotNull('country_code')
                ->pluck('country_code')
                ->sort()
                ->values();
        });

        $data['publishers'] = $publishers;
        $data['countries'] = $countries;

        return view('content.pages.products', $data);

    }

    public function index(Request $request)
    {

        $columns = [
            1 => 'sku', 2 => 'publisher_name', 3 => 'developers', 4 => 'genres',
            5 => 'product_type', 6 => 'platform', 7 => 'source', 8 => 'status', 9 => 'release_date',
        ];

        $search = [
            1 => 'name', 2 => 'publisher_name', 3 => 'developers', 4 => 'sku',
            7 => 'source', 8 => 'status', 9 => 'release_date', 10 => 'completed_product',
            11 => 'missing_product', 12 => 'country_code',
        ];

        $query = Product::with(['tagItems']);

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
              if($searchValue == 999){
                $query->whereBetween('sku', [1000, 2000]);
              }else{
                $query->where($field, $searchValue);
              }
            } elseif (in_array($field, ['name', 'sku', 'developers'])) {
                $query->where($field, 'LIKE', "%{$searchValue}%");
            } elseif ($field == 'publisher_name') {
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
                    // Assuming 'created_at' is used to define "new release"
                    $query->whereBetween('created_at', [
                        now()->subDays(7)->startOfDay(),
                        now(),
                    ]);
                }
            } elseif ($field === 'completed_product') {

                if ($searchValue === 'yes') {
                    $query->completed();
                } elseif ($searchValue === 'no') {
                    $query->incomplete();
                }
            } elseif ($field === 'missing_product') {

                $allowedFields = ['name',
                    'release_date',
                    'download_date',
                    'publisher_name',
                    'platform',
                    'product_type',
                    'region_tag',
                    'developers',
                    'default_language',
                    'pegi_ratings',
                    'average_rating',
                    'total_reviews',

                    'auxiliary_field',
                    'bundled_products',
                    'classification',
                    'community_discussion',
                    'dlc_products',
                    'source',
                    'status',
                    'dlc_master_product_id',
                    'is_dlc',
                    'face_value',
                    'redemption',
                    'redemption_field',
                    'validade',
                ];
                $incommField = ['auxiliary_field',
                    'classification',
                    'redemption',
                    'redemption_field',
                    'validade',
                ];

                // Generic function for checking missing localization fields
                $checkLocalizationMissing = function ($query, $fieldName) {
                    $query->where(function ($q) use ($fieldName) {
                        // Count of related locales is less than 3 → missing
                        $q->whereHas('baseLocalizations', function ($q2) {
                            $q2->whereIn('locale', ['pt-br', 'es-419', 'en']);
                        }, '<', 3)
                          // OR any of the 3 locales has null/empty value
                            ->orWhereHas('baseLocalizations', function ($q3) use ($fieldName) {
                                $q3->whereIn('locale', ['pt-br', 'es-419', 'en'])
                                    ->where(function ($q4) use ($fieldName) {
                                        $q4->whereNull($fieldName)
                                            ->orWhere($fieldName, '');
                                    });
                            });
                    });
                };

                if (in_array($searchValue, $allowedFields)) {

                    $query->where(function ($q) use ($searchValue, $incommField) {
                        // Skip missing check if source = 1 and field is in skip list
                        $q->where(function ($sub) use ($searchValue, $incommField) {
                            if (in_array($searchValue, $incommField)) {
                                $sub->where(function ($x) use ($searchValue) {
                                    $x->where('source', '=', 2)
                                        ->where(function ($inner) use ($searchValue) {
                                            $inner->whereNull($searchValue)
                                                ->orWhere($searchValue, '');
                                        });
                                });
                            } else {
                                // Normal missing check
                                $sub->whereNull($searchValue)
                                    ->orWhere($searchValue, '');
                            }
                        });
                    });

                } elseif (in_array($searchValue, [
                    'seo_tags',
                    'community_tags',
                    'genre_tags',
                    'franchise_tags',
                    'legal_texts',
                    'long_description',
                    'short_description',
                    'system_requirements',
                    'supported_languages',

                ])) {
                    $checkLocalizationMissing($query, $searchValue);
                } elseif ($searchValue === 'prices') {
                    // Products with no active prices
                    $query->whereDoesntHave('prices');
                } elseif ($searchValue === 'media') {
                    // Products with no media
                    $query->whereDoesntHave('media');
                } elseif ($searchValue === 'media_main') {
                    // Products with no main media (is_main = 1)
                    $query->whereDoesntHave('media', function ($q) {
                        $q->where('is_main', 1);
                    });
                }

            } elseif ($field === 'country_code') {
                // Filter products by country_code through prices relationship
                $query->whereHas('prices', function ($priceQuery) use ($searchValue) {
                    $priceQuery->where('country_code', $searchValue);
                });
            } else {
                // General string search (already covered by the `in_array` above, but left as fallback)
                $query->where($field, 'LIKE', "%{$searchValue}%");
            }
        }

        // ... (rest of the code remains the same) ...

        // Get total counts in **one query** to reduce multiple DB hits
        $totals = Product::selectRaw('
            SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) AS total_active,
            SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) AS total_inactive,
            SUM(CASE WHEN source = 1 THEN 1 ELSE 0 END) AS total_ztorm,
            SUM(CASE WHEN source = 2 THEN 1 ELSE 0 END) AS total_incomm,
            SUM(CASE WHEN source = 3 THEN 1 ELSE 0 END) AS total_point_nexus,
            SUM(CASE WHEN source = 4 THEN 1 ELSE 0 END) AS total_genba
        ')->first();

        $totalData = $query->count();
        $limit = $request->input('length', 10);
        $start = $request->input('start', 0);
        $order = $columns[$request->input('order.0.column')] ?? 'id';
        $dir = $request->input('order.0.dir', 'desc');

        // Only select needed columns
        $products = $query->select([
            'id', 'name', 'sku', 'publisher_name', 'developers',
            'genres',
            'product_type',
            'platform',
            'source',
            'status',
            'release_date', // Added 'developers_raw' if it's used later
        ])
            ->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir)
            ->get();

        $data = $products->map(function ($product, $index) use ($start) {

            // Handle developers
            $developerValue = '';
            if (! empty($product->developers_raw) && is_string($product->developers_raw)) {
                $devData = @unserialize($product->developers_raw);
                if ($devData !== false && isset($devData['Developer'])) {
                    $developerValue = $devData['Developer'];
                }
            }

            $genres = [];
            $enTagItem = $product->tagItems->firstWhere('locale', 'en');

            if ($enTagItem && ! empty($enTagItem->genre_tags)) {
                $rawGenres = $enTagItem->genre_tags;

                // Check if unserialization is possible
                $isSerialized = @unserialize($rawGenres) !== false || $rawGenres === 'b:0;';

                if ($isSerialized) {
                    $genreTags = @unserialize($rawGenres);

                    if (is_array($genreTags)) {
                        // Take only first 2 genres
                        $genres = array_slice($genreTags, 0, 2);
                    }
                }
            }

            return [
                'id' => $product->id,
                'fake_id' => $start + $index + 1,
                'name' => $product->name,
                'sku' => $product->sku,
                'publisher_name' => $product->publisher_name,
                'developers' => $developerValue,
                'genres' => is_array($genres) ? implode(', ', $genres) : '',
                'product_type' => $product->product_type,
                'platform' => $product->platform,
                'source' => $product->source == 1 ? 'ztorm' : ($product->source == 2 ? 'incomm' : ($product->source == 3 ? 'point nexus' : ($product->source == 4 ? 'genba' : ''))),
                'status' => $product->status == 1 ? 'Active' : 'Inactive',
                'release_date' => $product->release_date_formatted ?? '',
                'allowed_countries' => $product->allowed_countries ?? '',
            ];
        });

        $blockPage = $this->isPageBlocked();

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $totalData,
            'recordsFiltered' => $totalData,
            'code' => 200,
            'data' => $data,
            'total_active' => $totals->total_active,
            'total_inactive' => $totals->total_inactive,
            'total_ztorm' => $totals->total_ztorm,
            'total_incomm' => $totals->total_incomm,
            'total_point_nexus' => $totals->total_point_nexus,
            'total_genba' => $totals->total_genba,
            'block_page' => $blockPage,
        ]);
    }

    public function productEdit(Request $request, $sku)
    {

        $this->checkPageAccess('product.view');

        // Find product
        $product = Product::where('sku', $sku)->first();

        if (! $product) {
            return redirect()->route('products')
                ->with('error', "Product with SKU {$sku} not found.");
        }

        if (
            $product->source === 2 &&
            ! empty($product->system_requirements_raw)
        ) {
            $localization = Localization::where('product_id', $sku)
                ->where('locale', 'en')
                ->first();

            if ($localization && empty($localization->system_requirements)) {
                $localization->update([
                    'system_requirements' => $product->system_requirements_raw,
                ]);
            }
        }

        $prompts = Prompt::where('is_active', 1)->take(50)->get();
        $publishers = ProductPublisher::where('source', 2)->take(100)->get();

        return view('content.pages.product-edit', compact('product', 'prompts', 'publishers'));
    }

    public function editAjax($id)
    {
        $product = Product::with([
            'skipUpdates',
            'media',
            //      'ratings',
            //      'legalTexts',
            'prices',
            'localizations',
            'systemRequirementItems',
            'supportLanguages',
            'tags',
        ])->where('id', $id)->first();

        // Calculate cost_estimate_sourcewise for each price
        $product->prices->each(function($price) use ($product) {
            if ($price->source == config('services.sources.incomm')) {
                $merchant_commission = $product->merchant_commission_percentage;
                $price->cost_estimate_sourcewise = $merchant_commission
                    ? $price->price - ($price->price * ($merchant_commission / 100))
                    : null;
            } else if ($price->source == config('services.sources.ztorm')) {
                $price->cost_estimate_sourcewise = $price->cost_estimate;
            } else {
                $price->cost_estimate_sourcewise = null;
            }
        });

        if (! $product) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        // required locales
        $requiredLocales = ['en', 'es-419', 'pt-br','zh-Hans','zh-Hant','ja','ko'];

        // Fetch and prepare all existing localizations
        $existingLocalizations = Localization::where('product_id', $product->sku)
            // ->whereIn('locale', $requiredLocales)
          // Select all tag fields to process them below
            ->get();

        // Map the fetched data and index it by locale for easy lookup
        $mappedLocalizations = $existingLocalizations->map(function ($loc) {
            // Helper function to safely unserialize and implode tags
            //      $formatTags = fn($tags) => $tags ? implode(',', (array)unserialize($tags)) : '';
            $formatTags = function ($tags) {
                if (empty($tags)) {
                    return '';
                }

                // 🔹 Step 1: Check if the string looks serialized
                if (@unserialize($tags) !== false || $tags === 'b:0;') {
                    return implode(',', (array) unserialize($tags));
                }

                // 🔹 Step 2: If not serialized, return as-is
                return $tags;
            };

            return [
                'id' => $loc->id,
                'locale' => $loc->locale,
                'franchise_tags' => $formatTags($loc->franchise_tags),
                'genre_tags' => $formatTags($loc->genre_tags),
                'community_tags' => $formatTags($loc->community_tags),
                'seo_tags' => $formatTags($loc->seo_tags), // Keep SEO tags here for now
                'legal_texts' => $loc->legal_texts ?? '',
                'supported_languages_formatted' => $loc->supported_languages_formatted ?? '',

            ];
        })
            ->keyBy('locale');

        // --- 1. Generate Franchise & Genre Tags Collection ---
        $franchiseGenreLocalizations = collect($requiredLocales)->map(function ($locale) use ($mappedLocalizations) {
            $data = $mappedLocalizations->get($locale, [
                'id' => 0,
                'locale' => $locale,
                'franchise_tags' => '',
                'genre_tags' => '',
                'community_tags' => '',
            ]);

            // Only include fields relevant for Franchise/Genre
            return [
                'id' => $data['id'],
                'locale' => $data['locale'],
                'franchise_tags' => $data['franchise_tags'],
                'genre_tags' => $data['genre_tags'],
                'community_tags' => $data['community_tags'],
            ];
        });

        // --- 2. Generate SEO Tags Collection ---
        $seoTagsLocalizations = collect($requiredLocales)->map(function ($locale) use ($mappedLocalizations) {
            $data = $mappedLocalizations->get($locale, [
                'id' => 0,
                'locale' => $locale,
                'seo_tags' => '', // Default for missing data
            ]);

            // Only include fields relevant for SEO
            return [
                'id' => $data['id'],
                'locale' => $data['locale'],
                'seo_tags' => $data['seo_tags'],
            ];
        });

        //  Legal texts ---
        $legalTextsLocalizations = collect($requiredLocales)->map(function ($locale) use ($mappedLocalizations) {
            $data = $mappedLocalizations->get($locale, [
                'id' => 0,
                'locale' => $locale,
                'legal_texts' => '',
            ]);

            // Include only if has value, or empty for missing locale
            return [
                'id' => $data['id'],
                'locale' => $data['locale'],
                'legal_texts' => $data['legal_texts'] ?? '',
            ];
        });
        //  Legal texts ---
        $supported_languages_Localizations = collect($requiredLocales)->map(function ($locale) use ($mappedLocalizations) {
            $data = $mappedLocalizations->get($locale, [
                'id' => 0,
                'locale' => $locale,
                'supported_languages' => '',
            ]);

            // Include only if has value, or empty for missing locale
            return [
                'id' => $data['id'],
                'locale' => $data['locale'],
                'supported_languages' => $data['supported_languages_formatted'] ?? '',
            ];
        });

        $blockPage = $this->isPageBlocked();

        return response()->json([
            'block_page' => $blockPage,
            'product' => $product,
            // Passes Franchise and Genre tags for required languages
            'franchise_genre_localizations' => $franchiseGenreLocalizations->values(),
            // Passes ONLY SEO tags for required languages
            'seo_localizations' => $seoTagsLocalizations->values(),
            'legal_texts_localizations' => $legalTextsLocalizations->values(),
            'supported_languages_Localizations' => $supported_languages_Localizations->values(),

        ]);
    }

    public function productUpdate(Request $request, $id)
    {
        try {

            $tab = $request->input('tab_name'); // determine which tab submitted

            switch ($tab) {
                case 'basic_info':
                    return $this->basicInfoTab($request, $id);

                case 'summary':
                    return $this->summaryTab($request, $id);

                case 'localization':
                    return $this->localizationTab($request, $id);

                case 'media':
                    return $this->mediaTab($request, $id);

                case 'media_delete':
                    return $this->mediaDelete($request, $id);

                case 'tag':
                    return $this->tagTab($request, $id);

                case 'rating':
                    return $this->ratingTab($request, $id);

                case 'systemReq':
                    return $this->systemReqTab($request, $id);

                case 'skip-update':
                    return $this->skipUpdateTab($request, $id);

                case 'price-update':
                    return $this->updatePrice($request, $id);
                case 'price-info-update':
                    return $this->updatePriceInfo($request, $id);

                case 'price_delete':
                    return $this->deletePrice($request, $id);

                case 'get-price':
                    return $this->getPrice($request, $id);

                case 'price-create':
                    return $this->createPrice($request, $id);

                case 'steam-price-update':
                    return $this->steamPriceUpdate($request, $id);

                case 'commission_update':
                    return $this->commissionUpdate($request, $id);

                case 'generate-tag':
                    return $this->generateTags($request, $id);

                case 'tag-suggest':
                    return $this->suggestTags($request, $id);

                case 'generate-rating':
                    return $this->generateRating($request, $id);

                case 'translate-system-req':
                    return $this->translateSystemReq($request, $id);

                case 'translate-terms-conditions':
                    return $this->translateTermsConditions($request, $id);

                case 'saveTag':
                    return $this->saveTag($request, $id);

                case 'upload-json':
                    return $this->uploadJson($request, $id);
                case 'check-json':
                    return $this->checkUploadJsonData($request, $id);

                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid tab selected.',
                    ]);
            }

        } catch (ValidationException $e) {
            // Return validation errors in a consistent JSON format
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(), // returns field-wise validation messages
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong.',
                'error' => $e->getMessage(),
            ], 500);
        }

    }

    public function localizationDestroy($id)
    {
        $localization = Localization::find($id);

        if (! $localization) {
            return response()->json([
                'success' => false,
                'message' => 'Localization not found.',
            ], 404);
        }

        if ($localization->locale == 'en'
          ||
          $localization->locale == 'es-419'
          || $localization->locale == 'pt-br') {
            $localization->short_description = null;
            $localization->long_description = null;
            $localization->save();

        } else {
            $localization->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Localization deleted successfully.',
        ]);
    }

    public function logProductUpdate($product, string $tabName): void
    {
        // Get changed fields (exclude updated_at)
        $changes = array_keys($product->getChanges());
        $changes = array_filter($changes, fn ($field) => $field !== 'updated_at');

        if (! empty($changes)) {
            activity('product')
                ->event('updated')
                ->withProperties([
                    'changed' => array_values($changes),
                ])
                ->log("{$tabName} updated for Product SKU {$product->sku}.");
        }
    }

    public function translate(Request $request)
    {
        $productId = $request->input('product_id');
        $locale = $request->input('locale');
        $source = $request->input('source', 'en'); // default 'en'

        if (empty($locale) || empty($productId)) {
            return response()->json([
                'success' => false,
                'message' => 'Locale or Product ID is missing.',
            ], 422);
        }
        // Find product
        $product = Product::find($productId);
        if (! $product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ]);
        }
        // Get English localization
        $enLocalization = Localization::where('product_id', $product->sku)
            ->where('locale', 'en')
            ->first();

        if (
            ! $enLocalization ||
            ((is_null($enLocalization->short_description) || $enLocalization->short_description === '') &&
              (is_null($enLocalization->long_description) || $enLocalization->long_description === ''))
        ) {
            return response()->json([
                'success' => false,
                'message' => 'No English localization available for this product.',
            ]);
        }

        try {
            $password = config('services.api.password');
            $url = config('services.api.url')."/products/translation/{$product->sku}?password={$password}";

            $response = Http::withHeaders([
                'accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post($url, [
                'password' => $password,
                'locale' => $locale,
                'source' => $source,
            ]);
            $data = $response->json();

            // Handle different error codes from API
            if (! $response->successful() || ($data['Response']['ErrorCode'] ?? null) !== '0') {
                return response()->json([
                    'success' => false,
                    'message' => $data['Response']['ErrorMsg'] ?? 'Translation API error',
                    'code' => $data['Response']['ErrorCode'] ?? $response->status(),
                ], $response->status());
            }

            // Success
            return response()->json([
                'success' => true,
                'message' => 'Translation successful.',
                'data' => $data['Response']['Value'] ?? [],
            ]);

        } catch (\Throwable $e) {
            // Catch unexpected exceptions (network issues, etc.)
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'server_message' => $e->getMessage(),
                //        'message' => 'Something went wrong: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function translateAllLang(Request $request)
    {
        $request->validate([
            'sku' => 'required|string',
        ]);

        try {

            $sku = $request->input('sku');

            $product = Product::where('sku', $sku)->first();

            if (! $product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found',
                ], 404);
            }

            $locale = 'en';
            if ($product->source === 2) {
                $locale = 'pt-br';
            }
            $enLocalization = Localization::where('product_id', $product->sku)
                ->where('locale', $locale)
                ->first();

            if (
                ! $enLocalization ||
                (is_null($enLocalization->long_description) || $enLocalization->long_description === '')
            ) {
                $language = $locale == 'en' ? 'English' : 'pt-br';

                return response()->json([
                    'success' => false,
                    'message' => 'No '.$language.' localization available for this product.',
                ]);
            }

            $this->removeRateLimit();
            $translateService = new TranslationService(new OpenAIService(new PromptService));
            $translateService->processProductTranslationBySKU($sku);

            $aiErrorMessage = $this->getAIErrorMessage();

            if ($aiErrorMessage) {
                return response()->json([
                    'success' => false,
                    'message' => $aiErrorMessage,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Product translated successfully',
                'product' => $product,
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while translating the product.',
                'serviceMessage' => $e->getTraceAsString(),
            ], 500);
        }
    }

    private function basicInfoTab(Request $request, $id)
    {

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'product_url_title' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products', 'seo_url_name')->ignore($id),
            ],
            'publisher' => 'nullable|string',
            // 'genres' => 'nullable|string',
            // 'franchise' => 'nullable|string',
            // 'interface' => 'nullable|string',
            // 'full_audio' => 'nullable|string',
            // 'subtitles' => 'nullable|string',
            'supplier' => 'required|integer|in:1,2,3,4',
            'release_date' => ['nullable', 'date_format:Y-m-d'], // nullable, must be Y-m-d if present
            // 'download_date' => ['nullable', 'date_format:Y-m-d'],   // same for order_date            'publisher' => 'nullable|string|max:255',
            'developers' => 'nullable|string',
            'product_type' => 'required|string|max:64',
            'status' => 'required|string|in:active,inactive',
            'default_language' => 'nullable|string|max:16',
            //            'skip_update' => 'nullable|boolean', // optional boolean
            'platform' => 'nullable|string|max:255',

        ]);

        $product = Product::find($id);

        if ($product) {

            // Map validated data to product fields
            $product->name = $validated['name'];
            $product->genres = $this->tagifyToSerialized($validated['genres'] ?? null);
            $product->franchise = $this->tagifyToSerialized($validated['franchise'] ?? null);
            // developers field format
            if (isset($validated['developers'])) {
                // Decode JSON from Tagify
                $developers = json_decode($validated['developers'], true);

                // Extract all "value" items
                $devNames = array_column($developers, 'value');

                // Unserialize existing data
                $existing = $product->developers_raw ? unserialize($product->developers_raw) : [];

                // Store all developers as array
                $existing['Developer'] = $devNames;

                // Save back
                $product->developers = serialize($existing);
            } else {
                // Store all developers as array
                $existing['Developer'] = [];
                // Save back
                $product->developers = serialize($existing);
            }

            if ($product->source === 2) {
                $releaseTimestamp = $validated['release_date']
                  ? Carbon::createFromFormat('Y-m-d', $validated['release_date'], config('app.timezone'))->timestamp
                  : 0;

                $product->release_date = $releaseTimestamp;
            }

            //            $product->source = $validated['supplier'];

            $publisherName = $validated['publisher'] ?? null;

            if ($product->source == 2 && $publisherName != $product->publisher_name) {

                if (empty($publisherName)) {
                    $product->publisher_id = null;
                } else {
                    $productPublisher = ProductPublisher::where('name', $publisherName)->first();
                    $product->publisher_id = $productPublisher?->id ?? null;
                }
            }
            $product->publisher_name = $publisherName;

            $product->default_language = $validated['default_language'] ?? '';
            $product->seo_url_name = $validated['product_url_title'] ?? '';
            $product->product_type = $validated['product_type'];
            $product->platform = $validated['platform'];
            $product->ignore_update = $request->boolean('ignore_update');
            // Status conversion: active -> 1, inactive -> 0
            $product->status = $validated['status'] === 'active' ? 1 : ($validated['status'] === 'inactive' ? 0 : '');
            //            $product->skip_update = $validated['skip_update'] ?? false;

            $product->save();

            $localizations = $request->input('localizations', []);
            if ($localizations) {
                foreach ($localizations as $loc) {
                    // Serialize tag arrays
                    //          $seoTags = !empty($loc['seo_tags']) ? $this->tagifyToSerialized($loc['seo_tags'] ?? null) : null;
                    $franchiseTags = ! empty($loc['franchise_tags']) ? $this->tagifyToSerialized($loc['franchise_tags'] ?? null) : null;
                    $genreTags = ! empty($loc['genre_tags']) ? $this->tagifyToSerialized($loc['genre_tags'] ?? null) : null;
                    $communityTags = ! empty($loc['community_tags']) ? $this->tagifyToSerialized($loc['community_tags'] ?? null) : null;
                    $title = $loc['title'] ?? null;

                    if ($loc['id'] == 0) {
                        $existLocalization = Localization::where('product_id', $product->sku)
                            ->where('locale', $loc['locale'])->first();

                        if ($existLocalization) {
                            //             $existLocalization->seo_tags=$seoTags;
                            $existLocalization->franchise_tags = $franchiseTags;
                            $existLocalization->genre_tags = $genreTags;
                            $existLocalization->community_tags = $communityTags;
                            if (isset($loc['title'])) {
                                $existLocalization->title = $title;
                            }

                            $existLocalization->save();
                        } else {
                            $newLocalize = new Localization;

                            $newLocalize->product_id = $product->sku;
                            $newLocalize->locale = $loc['locale'];
                            $newLocalize->title = $title ?? $product->name;
                            //             $newLocalize->seo_tags=$seoTags;
                            $newLocalize->franchise_tags = $franchiseTags;
                            $newLocalize->genre_tags = $genreTags;
                            $newLocalize->community_tags = $communityTags;
                            $newLocalize->save();

                        }

                    } else {
                        // Update existing localization
                        $updateData = [
                            //              'seo_tags' => $seoTags,
                            'franchise_tags' => $franchiseTags,
                            'genre_tags' => $genreTags,
                            'community_tags' => $communityTags,
                        ];
                        if (isset($loc['title'])) {
                            $updateData['title'] = $title;
                        }
                        Localization::where('id', $loc['id'])->where('product_id', $product->sku)->update($updateData);
                    }
                }
            }

            $this->logProductUpdate($product, 'Basic info');
        }

        return response()->json(['success' => true, 'message' => 'Basic info  updated successfully']);

    }

    private function summaryTab(Request $request, $id)
    {

        $validated = $request->validate([
            'dlc_products' => 'nullable|string',
            // 'auxiliary_field' => 'nullable|string',
            // 'bundled_products' => 'nullable|string',
            // 'classification' => 'nullable|string',
            // 'region_tag' => 'nullable|string',
            // 'community_discussion' => 'nullable|string',
            // 'drm_type' => 'nullable|string|max:255',
            // 'face_value' => 'nullable|string',
            // 'redemption' => 'nullable|string',
            // 'redemption_field' => 'nullable|string',
            // 'terms_and_conditions' => 'nullable|string',
            // 'validade' => 'nullable|string',
            // 'interface' => 'nullable|string',
            // 'full_audio' => 'nullable|string',
            // 'subtitles' => 'nullable|string',
            'is_dlc' => 'nullable|boolean',
            'dlc_master_product_id' => 'nullable|integer',

        ]);

        $product = Product::find($id);
        $supportedLanguages = $request->input('supported_languages', []);

        if ($product) {

            // Always update DRMType,
            $currentData = [];

            // Get current DRM data from database
            if (! empty($product->drm_type)) {
                $currentData = @unserialize($product->drm_type);
                if (! is_array($currentData)) {
                    $currentData = [];
                }
            }

            $product->auxiliary_field = $request->auxiliary_field;
            $product->bundled_products = $request->bundled_products;
            $product->classification = $request->classification;
            $product->community_discussion = $request->community_discussion;
            $product->face_value = $request->face_value;
            $product->redemption = $request->redemption;
            $product->redemption_field = $request->redemption_field;
            $product->terms_and_conditions = $request->terms_and_conditions;
            $product->validade = $request->validade;
            $product->is_dlc = $request->is_dlc ?? 0;
            $product->dlc_master_product_id = $request->dlc_master_product_id;
            $product->platform = $validated['drm_type'] ?? null;
            if ($product->source === 2) {
                $product->region_tag = $validated['region_tag'] ?? '';
            }

            if (isset($validated['dlc_products'])) {
                $dlc_products = json_decode($validated['dlc_products'], true);
                $productIds = array_column($dlc_products, 'value');

                // Handle both serialized string or array
                $existing = $product->dlc_products_formatted;

                if (is_string($existing)) {
                    $existing = unserialize($existing);
                }

                if (! is_array($existing)) {
                    $existing = [];
                }

                // Update or add ProductID key
                $existing['ProductID'] = $productIds;

                // Save serialized
                $product->dlc_products = serialize($existing);

            } else {
                $existing = $product->dlc_products_formatted;

                if (is_string($existing)) {
                    $existing = unserialize($existing);
                }

                if (! is_array($existing)) {
                    $existing = [];
                }

                $existing['ProductID'] = [];

                $product->dlc_products = serialize($existing);
            }

            $localizations = $request->input('localizations', []);
            if ($localizations && ! empty($localizations['locale'])) {
                foreach ($localizations['locale'] as $index => $locale) {
                    $id = $request->localizations['id'][$index] ?? null;
                    $legal_texts = $this->sanitizeEditorText($localizations['legal_texts'][$index] ?? null);

                    if ($id) {

                        Localization::where('id', $id)->where('product_id', $product->sku)->update([
                            'legal_texts' => $legal_texts,
                        ]);

                    } else {

                        $localization = Localization::where('product_id', $product->sku)->where('locale', $locale)->first();

                        if ($localization) {
                            $localization->legal_texts = $legal_texts;
                            $localization->save();
                        } else {
                            $newLocalize = new Localization;
                            $newLocalize->product_id = $product->sku;
                            $newLocalize->locale = $locale;
                            $newLocalize->title = $product->name;
                            $newLocalize->legal_texts = $legal_texts;
                            $newLocalize->save();

                        }

                    }
                }
            }

            $product->save();

            foreach ($supportedLanguages as $locale => $data) {
                $languageSupportString = $this->buildLanguageSupportString($data);

                // Skip if all empty
                if (! $languageSupportString || ! $locale) {
                    continue;
                }

                $localization = Localization::where('product_id', $product->sku)->
                where('locale', $locale)->first();
                if (! $localization) {
                    $localization = new Localization;
                    $localization->product_id = $product->sku;
                    $localization->locale = $locale;
                    $localization->supported_languages = $languageSupportString;
                }
                $localization->supported_languages = $languageSupportString;
                $localization->save();

            }

            $this->logProductUpdate($product, 'Summary');

        }

        return response()->json(['success' => true, 'message' => 'Summary  updated successfully']);

    }

    private function mediaTab(Request $request, $id)
    {

        $product = Product::findOrFail($id);

        // 1. Define base validation rules
        $rules = [
            'media' => 'sometimes|array',
            'media.*.type' => 'required|in:image,videos,boxshot,screenshot',
            'media.*.id' => 'nullable|exists:product_media,id',
            'media.*.image_orientation' => 'nullable',
            'media.*.main' => 'nullable|boolean',
        ];

        // 2. Add conditional validation rules for file/url based on type
        $mediaInput = $request->input('media', []);
        if (is_array($mediaInput)) {
            foreach ($mediaInput as $index => $item) {
                $isExisting = ! empty($item['id']);
                $type = $item['type'] ?? null;

                if ($type === 'image' || $type === 'boxshot' || $type === 'screenshot') {
                    $rules["media.$index.file"] = [
                        $isExisting ? 'nullable' : 'required', // Required only for new images
                        'file',
                        'mimes:jpg,jpeg,png,gif',
                        'max:10240', // 10MB
                    ];
                    $rules["media.$index.url"] = 'nullable|url';

                } else { // videos or videos_steam
                    $rules["media.$index.url"] = 'required|url';
                    $rules["media.$index.file"] = 'nullable';
                }

                if ($type === 'boxshot') {
                    $rules["media.$index.image_orientation"] = [
                        'required',
                        Rule::in([1, 2]), // 1 = landscape, 2 = portrait
                    ];
                } else {
                    $rules["media.$index.image_orientation"] = 'nullable';
                }
            }
        }

        // Custom messages for better feedback
        $messages = [
            'media.*.file.required' => 'The image file is required.',
            'media.*.url.required' => 'The video URL is required.',
            'media.*.type.required' => 'The media type is required.',

        ];

        $validatedData = $request->validate($rules, $messages);

        // --- Data Processing ---

        $processedMediaIds = [];

        if (isset($validatedData['media'])) {
            // Use a foreach loop for robustness. This is safer than a 'for' loop.
            foreach ($validatedData['media'] as $index => $itemData) {
                $isMain = isset($itemData['main']) && $itemData['main'] == '1';
                $image_orientation = isset($itemData['image_orientation']) ? $itemData['image_orientation'] : null;

                // Find existing or create a new instance
                $media = ! empty($itemData['id'])
                  ? ProductMedia::find($itemData['id'])
                  : new ProductMedia(['product_id' => $product->sku]);

                if (! $media) {
                    continue;
                } // Skip if ID was provided but not found

                $media->media_type = match ($itemData['type']) {
                    'videos' => 2,
                    'boxshot' => 3,
                    'screenshot' => 4,
                    default => 1,
                };
                $media->is_main = $isMain;
                $media->image_orientation = $image_orientation;

                try {
                    // Handle file upload for images
                    if (
                        ($itemData['type'] === 'image' ||
                          $itemData['type'] === 'boxshot' ||
                          $itemData['type'] === 'screenshot')
                        && $request->hasFile("media.$index.file")
                    ) {
                        $file = $request->file("media.$index.file");
                        $fileName = uniqid().'_'.preg_replace('/[^A-Za-z0-9\._-]/', '', $file->getClientOriginalName());
                        $s3Path = 'products_management/'.$fileName;

                        //

                        Storage::disk('s3')->put($s3Path, file_get_contents($file), 'public');
                        $media->url = 'https://images.2game.com/'.$s3Path;
                        $media->media_source = '3';
                    } // Handle URL for videos
                    elseif (in_array($itemData['type'], ['videos', 'videos_steam'])) {
                        $media->url = $itemData['url'];
                        $media->media_source = '3';
                    }

                    $media->save();
                    $processedMediaIds[] = $media->id;

                } catch (\Exception $e) {
                    Log::error("Media processing error for product ID {$product->id}: ".$e->getMessage());
                    // Optionally return an error response
                    // return response()->json(['success' => false, 'message' => 'An error occurred during file upload.'], 500);
                }
            }
        }

        // Optional: Delete media that were removed on the frontend
        // This deletes any media associated with the product that wasn't in the submission
        ProductMedia::where('product_id', $product->sku)
            ->whereNotIn('id', $processedMediaIds)
            ->delete();

        activity('product')
            ->event('updated')
            ->log("Media updates for Product SKU {$product->sku}: ");

        return response()->json([
            'success' => true,
            'message' => 'Media updated successfully!',
        ]);
    }

    public function mediaDelete(Request $request, $id)
    {
        $mediaId = $request->media_id;
        $productMedia = ProductMedia::find($mediaId);
        $product = Product::find($id);

        if (! $productMedia) {
            return response()->json([
                'success' => false,
                'message' => 'Media not found.',
            ], 404);
        }

        try {

            $productMedia->delete();

            activity('product media')
                ->event('deleted')
                ->withProperties([
                    'product_sku' => $product->sku,
                    'media_id' => $mediaId,
                    'url' => $productMedia->url ?? '',
                    'is_main' => $productMedia->is_main ?? '',
                    'media_source' => $productMedia->media_source ?? '',
                    'image_orientation' => $productMedia->image_orientation ?? '',
                    'media_type' => $productMedia->type ?? '',
                ])
                ->log("Deleted product media for Product SKU {$product->sku}");

            return response()->json([
                'success' => true,
                'message' => 'Media deleted successfully.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete media. '.$e->getMessage(),
            ], 500);
        }
    }

    private function tagTab(Request $request, $id)
    {

        $product = Product::find($id);
        if (! $product) {
            return response()->json(['success' => false, 'message' => 'Product not found']);
        }

        //    // Decode JSON string to array
        //    $tagsPayload = json_decode(request()->input('tags', '[]'), true);
        //
        //    // Extract values safely
        //    $tagValues = collect($tagsPayload)
        //      ->pluck('value')
        //      ->filter()
        //      ->toArray(); // ['ABCD', 'EFGH']
        //
        //    if (!empty($tagValues)) {
        //      // Delete tags that are not in payload
        //      Tag::where('product_id', $product->sku)
        //        ->whereNotIn('tag', $tagValues)
        //        ->delete();
        //
        //      // Create or update tags
        //      foreach ($tagValues as $tagValue) {
        //        Tag::updateOrCreate(
        //          ['product_id' => $product->sku, 'tag' => $tagValue],
        //          ['updated_at' => now()]
        //        );
        //      }
        //    } else {
        //      Tag::where('product_id', $product->sku)->delete();
        //    }
        //
        //
        //

        $localizations = $request->input('localizations', []);
        if ($localizations) {
            foreach ($localizations as $loc) {
                // Serialize tag arrays
                $seoTags = ! empty($loc['seo_tags']) ? $this->tagifyToSerialized($loc['seo_tags'] ?? null) : null;
                if ($loc['id'] == 0) {
                    $existLocalization = Localization::where('product_id', $product->sku)
                        ->where('locale', $loc['locale'])->first();

                    if ($existLocalization) {
                        $existLocalization->seo_tags = $seoTags;

                        $existLocalization->save();
                    } else {
                        $newLocalize = new Localization;

                        $newLocalize->product_id = $product->sku;
                        $newLocalize->locale = $loc['locale'];
                        $newLocalize->title = $product->name;
                        $newLocalize->seo_tags = $seoTags;
                        $newLocalize->save();

                    }

                } else {
                    // Update existing localization
                    Localization::where('id', $loc['id'])->where('product_id', $product->sku)->update([
                        'seo_tags' => $seoTags,
                    ]);
                }
            }
        }

        activity('product')
            ->event('updated')
            ->log("Tag updates for Product SKU {$product->sku}: ");

        return response()->json([
            'success' => true,
            'message' => 'Tags updated successfully',
        ]);
    }

    private function getPrice(Request $request, $id)
    {
        $product = Product::find($id);

        if (! $product) {
            return response()->json(['success' => false, 'message' => 'Product not found.']);
        }

        if (! $request->price_id) {
            return response()->json(['success' => false, 'message' => 'Price ID not found.']);
        }

        $price = Price::where('product_id', $product->sku)
            ->where('id', $request->price_id)
            ->first();

        if (! $price) {
            return response()->json(['success' => false, 'message' => 'Price not found.']);
        }
        $platforms = '';

        if (! empty($price->platforms)) {
            // Unserialize safely
            $unserialized = @unserialize($price->platforms);
            if ($unserialized !== false && is_array($unserialized)) {
                $platforms = implode(', ', $unserialized);
            } else {
                // Fallback in case it's not serialized
                $platforms = $price->platforms;
            }
        }

        return response()->json([
            'success' => true,
            'price' => [
                'title' => $price->title,
                'price' => $price->price,
                'discount_percent' => $price->discount_percent ?? '',
                'concept_id' => $price->concept_id ?? '',
                'scrape_url' => $price->scrape_url ?? '',
                'description' => $price->description ?? '',
                'platforms' => $platforms ?? '',
                'primary_image_url' => $price->primary_image_url ?? '',
                'discount_valid_from_formatted' => $price->discount_valid_from_formatted ?? '',
                'discount_valid_to_formatted' => $price->discount_valid_to_formatted ?? '',
            ],
            'message' => 'Price fetched successfully.',
        ]);
    }

    private function ratingTab(Request $request, $id)
    {

        $validated = $request->validate([
            // 'average_rating' => 'nullable|numeric|min:0|max:100',
            // 'total_reviews' => 'nullable|integer|min:0',
        ]);

        $product = Product::find($id);

        if ($product) {

            $product->average_rating = $request->average_rating;
            $product->total_reviews = $request->total_reviews;

            $product->save();

        }
        $this->logProductUpdate($product, 'Rating');

        return response()->json([
            'success' => true,
            'message' => 'Rating updated successfully!',
        ]);
    }

    private function systemReqTab(Request $request, $id)
    {
        // Validate the input
        $request->validate([
            //            'system_requirement' => 'required|string',
            'localizations.id.*' => 'nullable|integer|exists:localizations,id', //
            'localizations.system_requirements.*' => 'nullable|string',

        ]);

        // Find the product
        $product = Product::find($id);

        if (! $product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ], 404);
        }

        // Save system requirements
        //        $product->system_requirements = $request->system_requirement;
        //        $product->save();

        if (isset($request->localizations)) {
            foreach ($request->localizations['id'] as $index => $localizationId) {
                if ($localizationId) {
                    $localization = Localization::find($localizationId);
                    $system_req = $this->sanitizeEditorText($request->localizations['system_requirements'][$index] ?? null);

                    if ($localization) {
                        $localization->system_requirements = $system_req;
                        $localization->save();
                    }
                }
            }
        }

        //        $enLocalization = Localization::where('product_id', $product->sku)->where('locale', 'en')->first();
        //        if ($enLocalization) {
        //            $system_req_en = $this->sanitizeEditorText($request->system_requirement ?? null);
        //
        //            $enLocalization->system_requirements = $system_req_en;
        //            $enLocalization->save();
        //        }

        $this->logProductUpdate($product, 'System requirements');

        return response()->json([
            'success' => true,
            'message' => 'System requirements updated successfully!',
        ]);
    }

    private function skipUpdateTab(Request $request, $id)
    {
        $skipFields = $request->input('skip_fields', []); // array of field names

        // Find the product
        $product = Product::find($id);

        if (! $product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ], 404);
        }

        $productId = $product->sku; // as per your migration

        // Get existing skip updates for this product
        $existingFields = ProductsSkipUpdate::where('product_id', $productId)
            ->pluck('field_name')
            ->toArray();

        // Determine which fields to delete (no longer selected)
        $fieldsToDelete = array_diff($existingFields, $skipFields);

        // Determine which fields to add (newly selected)
        $fieldsToAdd = array_diff($skipFields, $existingFields);

        // Delete unselected fields
        if (! empty($fieldsToDelete)) {
            ProductsSkipUpdate::where('product_id', $productId)
                ->whereIn('field_name', $fieldsToDelete)
                ->delete();
        }

        // Add new selected fields
        foreach ($fieldsToAdd as $field) {
            ProductsSkipUpdate::updateOrCreate(
                [
                    'product_id' => $productId,
                    'field_name' => $field,
                ],
                [
                    'skip_update' => true,
                ]
            );
        }

        // ✅ Activity log with detailed info
        activity('product')
            ->event('updated')
            ->withProperties(['fields' => $skipFields])
            ->log("Skip update fields updated for Product SKU {$product->sku}");

        return response()->json([
            'success' => true,
            'message' => 'Skip update fields updated successfully!',
        ]);
    }

    private function localizationTab(Request $request, $id)
    {

        $validated = $request->validate([
            'localizations.id.*' => 'nullable|integer|exists:localizations,id', // existing IDs
            'localizations.language_code.*' => 'required|string|max:10',       // e.g., en, de
            'localizations.localized_name.*' => 'required|string|max:255',     // title
            'localizations.short_description.*' => 'nullable|string',
            'localizations.long_description.*' => 'nullable|string',
        ]);
        $product = Product::find($id);
        foreach ($request->localizations['language_code'] as $index => $locale) {
            $id = $request->localizations['id'][$index] ?? null;
            $short_description = $this->sanitizeEditorText($request->localizations['short_description'][$index] ?? null);
            $long_description = $this->sanitizeEditorText($request->localizations['long_description'][$index] ?? null);

            $data = [
                'locale' => $locale,
                'title' => $request->localizations['localized_name'][$index],
                'short_description' => $short_description,
                'long_description' => $long_description,
                'product_id' => $product->sku,
            ];

            if ($id) {
                // Update existing record
                Localization::find($id)->update($data);
            } else {

                $localization = Localization::where('product_id', $product->sku)->where('locale', $locale)->first();

                if ($localization) {
                    $localization->short_description = $short_description;
                    $localization->long_description = $long_description;
                    $localization->save();
                } else {
                    // Create new record
                    Localization::create($data);
                }

            }
        }

        activity('product')
            ->event('updated')
            ->log("Localization updates for Product SKU {$product->sku}: ");

        return response()->json([
            'success' => true,
            'message' => 'Localizations updated successfully!',
        ]);
    }

    private function updatePrice(Request $request, $id)
    {

        $priceValue = $request->price;
        $title = $request->title;
        $priceId = $request->price_id;

        $product = Product::find($id);
        if (! $product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ]);
        }

        $price = Price::find($priceId);
        if (! $price) {
            return response()->json([
                'success' => false,
                'message' => 'Price not found.',
            ]);
        }

        $min = (float) $product->min_value;
        $max = (float) $product->max_value;
        $enteredPrice = $priceValue; // keep as string first

        // Check if it's a valid number with up to 2 decimals
        if (! preg_match('/^\d+(\.\d{1,2})?$/', $enteredPrice)) {
            return response()->json([
                'success' => false,
                'message' => 'Please enter a valid price with up to 2 decimal places.',
            ]);
        }

        // Now convert to float
        $enteredPrice = (float) $enteredPrice;
        $min = round($min, 2);
        $max = round($max, 2);

        if ($enteredPrice < $min || $enteredPrice > $max) {
            return response()->json([
                'success' => false,
                'message' => 'Please enter a value between '.number_format($min, 2).' and '.number_format($max, 2).'.',
            ]);
        }

        $priceActive = Price::where('country_code', 'BR')
            ->where('currency', 'BRL')
            ->where('product_id', $product->sku)
            ->where('price', $priceValue)
            ->where('is_active', 1)
            ->whereNot('id', $priceId)
            ->first();

        if ($priceActive) {
            return response()->json([
                'success' => false,
                'message' => 'This price is already active for the product.',
            ]);
        }

        $priceCheckInActive = Price::where('country_code', 'BR')
            ->where('currency', 'BRL')
            ->where('product_id', $product->sku)
            ->where('price', $priceValue)
            ->where('is_active', 0)
            ->first();

        if ($priceCheckInActive) {
            $priceCheckInActive->is_active = 1;
            $priceCheckInActive->price_update_timestamp = time();
            $priceCheckInActive->title = $title;
            $priceCheckInActive->save();

            $price->is_active = 0;
            $price->save();

            return response()->json([
                'success' => true,
                'message' => 'Updated successfully.',
            ]);
        }

        $old_price = $price->price;

        //      // ✅ Update price if valid
        $price->price = $enteredPrice;
        $price->title = $title;
        $price->price_update_timestamp = time();
        $price->save();

        if ($old_price != $enteredPrice) {
            $product->skip_update = 1;
            $product->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Updated successfully.',
            'data' => $price,
        ]);
    }

    private function updatePriceInfo(Request $request, $id)
    {

        $priceId = $request->price_id;
        $title = $request->title;
        $priceValue = $request->price;
        $discount_percent = $request->discount_percent;
        $concept_id = $request->concept_id;

        $scrape_url = $request->scrape_url;
        $description = $request->description;
        $primaryImage = $request->primary_image;
        $discount_valid_from = $request->discount_valid_from;
        $discount_valid_to = $request->discount_valid_to;

        $discountFromTs = null;
        $discountToTs = null;

        if (! empty($discount_valid_from)) {
            $discountFromTs = strtotime($discount_valid_from);
            if ($discountFromTs === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid Discount Valid From date.',
                ]);
            }
        }

        if (! empty($discount_valid_to)) {
            $discountToTs = strtotime($discount_valid_to);
            if ($discountToTs === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid Discount Valid To date.',
                ]);
            }
        }

        // Only compare when BOTH exist
        if ($discountFromTs !== null && $discountToTs !== null) {
            if ($discountToTs <= $discountFromTs) {
                return response()->json([
                    'success' => false,
                    'message' => 'Discount Valid To must be after Discount Valid From.',
                ]);
            }
        }

        if (! empty($discount_percent)) {
            $discount_percent = (float) $discount_percent; // cast to float

            if ($discount_percent < 0 || $discount_percent > 100) {
                return response()->json([
                    'success' => false,
                    'message' => 'Discount percent must be between 0 and 100.',
                ]);
            }
        }

        $platforms = ! empty($request->platforms) ? $this->tagifyToSerialized($request->platforms ?? null) : null;

        if ($primaryImage) { // only check if file exists
            if (! $primaryImage->isValid() ||
              ! in_array($primaryImage->getClientMimeType(), ['image/jpeg', 'image/png', 'image/webp'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid image type. Only JPEG, PNG, or WEBP allowed.',
                ]);
            }
        }

        $product = Product::find($id);
        if (! $product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ]);
        }

        $price = Price::find($priceId);
        if (! $price) {
            return response()->json([
                'success' => false,
                'message' => 'Price not found.',
            ]);
        }

        if (! empty($price->concept_id) && empty($concept_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Concept ID is required for this product.',
            ]);
        }

        $min = (float) $product->min_value;
        $max = (float) $product->max_value;
        $enteredPrice = $priceValue; // keep as string first

        // Check if it's a valid number with up to 2 decimals
        if (! preg_match('/^\d+(\.\d{1,2})?$/', $enteredPrice)) {
            return response()->json([
                'success' => false,
                'message' => 'Please enter a valid price with up to 2 decimal places.',
            ]);
        }

        // Now convert to float
        $enteredPrice = (float) $enteredPrice;
        $min = round($min, 2);
        $max = round($max, 2);

        if ($enteredPrice < $min || $enteredPrice > $max) {
            return response()->json([
                'success' => false,
                'message' => 'Please enter a Price between '.number_format($min, 2).' and '.number_format($max, 2).'.',
            ]);
        }

        $activityLog = [];
        $message = "Price for Product SKU {$product->sku} updated successfully.";

        $priceActive = Price::where('country_code', 'BR')
            ->where('currency', 'BRL')
            ->where('product_id', $product->sku)
            ->where('price', $priceValue)
            ->where('is_active', 1)
            ->when(! empty($concept_id), function ($query) use ($concept_id) {
                return $query->where('concept_id', $concept_id);
            })->whereNot('id', $priceId)
            ->first();

        if ($priceActive) {
            return response()->json([
                'success' => false,
                'message' => 'This price is already active for the product.',
            ]);
        }

        // check any inactive price then update this with active
        $priceCheckInActive = Price::where('country_code', 'BR')
            ->where('currency', 'BRL')
            ->where('product_id', $product->sku)
            ->where('price', $priceValue)
            ->when(! empty($concept_id), function ($query) use ($concept_id) {
                return $query->where('concept_id', $concept_id);
            })
            ->where('is_active', 0)
            ->first();

        if ($priceCheckInActive) {
            $activityLog['before'] = $this->priceActivityLog($priceCheckInActive);
            $priceCheckInActive->is_active = 1;
            $priceCheckInActive->price_update_timestamp = time();
            $priceCheckInActive->title = $title;
            $priceCheckInActive->discount_percent = $discount_percent;
            $priceCheckInActive->concept_id = $concept_id;
            $priceCheckInActive->scrape_url = $scrape_url;
            $priceCheckInActive->platforms = $platforms;
            $priceCheckInActive->description = $description;
            if ($primaryImage) {
                $priceCheckInActive->primary_image_url = $this->uploadFileToS3($primaryImage);
            }
            $priceCheckInActive->disableLogging();
            $priceCheckInActive->discount_valid_from = $discountFromTs;
            $priceCheckInActive->discount_valid_to = $discountToTs;
            $priceCheckInActive->save();

            $price->is_active = 0;
            $price->disableLogging();
            $price->save();

            $activityLog['after'] = $this->priceActivityLog($priceCheckInActive);

            activity('price.update')
                ->withProperties($activityLog)
                ->log($message);

            return response()->json([
                'success' => true,
                'message' => $message,
            ]);

        }

        $activityLog['before'] = $this->priceActivityLog($price);

        $price->price = $enteredPrice;
        $price->title = $title;
        $price->discount_percent = $discount_percent;
        $price->concept_id = $concept_id;
        $price->scrape_url = $scrape_url;
        $price->platforms = $platforms;
        $price->description = $description;
        if ($primaryImage) {
            $price->primary_image_url = $this->uploadFileToS3($primaryImage);
        }

        $price->price_update_timestamp = time();
        $price->discount_valid_from = $discountFromTs;
        $price->discount_valid_to = $discountToTs;
        $price->disableLogging();
        $price->save();

        $activityLog['after'] = $this->priceActivityLog($price);

        activity('price.update')
            ->withProperties($activityLog)
            ->log($message);

        return response()->json([
            'success' => true,
            'message' => $message,
        ]);
    }

    private function createPrice(Request $request, $id)
    {

        $priceValue = $request->price;
        $title = $request->title;
        // Check if it's a valid number with up to 2 decimals
        if (! preg_match('/^\d+(\.\d{1,2})?$/', $priceValue)) {
            return response()->json([
                'success' => false,
                'message' => 'Please enter a valid price with up to 2 decimal places.',
            ]);
        }

        $product = Product::find($id);
        if (! $product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ]);
        }

        $priceCheckActive = Price::where('country_code', 'BR')
            ->where('currency', 'BRL')
            ->where('product_id', $product->sku)
            ->where('price', $priceValue)
            ->where('is_active', 1)
            ->first();

        if ($priceCheckActive) {
            return response()->json([
                'success' => false,
                'message' => 'This price is already active for the product.',
            ]);
        }

        $priceCheckInActive = Price::where('country_code', 'BR')
            ->where('currency', 'BRL')
            ->where('product_id', $product->sku)
            ->where('price', $priceValue)
            ->where('is_active', 0)
            ->first();

        if ($priceCheckInActive) {
            $priceCheckInActive->is_active = 1;
            $priceCheckInActive->title = $title;
            $priceCheckInActive->save();

            return response()->json([
                'success' => true,
                'message' => 'New price added successfully.',
            ]);
        }

        $min = (float) $product->min_value;
        $max = (float) $product->max_value;
        $enteredPrice = $priceValue; // keep as string first

        // Now convert to float
        $enteredPrice = (float) $enteredPrice;
        $min = round($min, 2);
        $max = round($max, 2);

        if ($enteredPrice < $min || $enteredPrice > $max) {
            return response()->json([
                'success' => false,
                'message' => 'Please enter a value between '.number_format($min, 2).' and '.number_format($max, 2).'.',
            ]);
        }
        $newPrice = new Price;
        $newPrice->product_id = $product->sku;
        $newPrice->source = $product->source;
        $newPrice->currency = 'BRL';
        $newPrice->country_code = 'BR';
        $newPrice->min_value = $product->min_value;
        $newPrice->max_value = $product->max_value;
        $newPrice->price_update_timestamp = time();
        $newPrice->price = $enteredPrice;
        $newPrice->title = $title;
        $newPrice->is_active = 1;
        $newPrice->save();
        $product->skip_update = 1;
        $product->save();

        return response()->json([
            'success' => true,
            'message' => 'New price added successfully.',
        ]);
    }

    private function steamPriceUpdate(Request $request, $id)
    {
        // Validate request
        $request->validate([
            'price_id' => 'required|integer',
            'steam_price' => 'required|numeric|min:0',
        ]);

        $priceId = $request->price_id;
        $steamPrice = $request->steam_price;

        // Find product
        $product = Product::find($id);
        if (! $product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ]);
        }

        // Check skip update permission
        $productSkipUpdate = ProductsSkipUpdate::where('product_id', $product->sku)
            ->where('field_name', 'steam_price')
            ->where('skip_update', 1)
            ->first();

        if (! $productSkipUpdate) {
            return response()->json([
                'success' => false,
                'message' => 'Steam price update is not allowed for this product.',
            ]);
        }

        // Find price row
        $price = Price::where('product_id', $product->sku)
            ->where('id', $priceId)
            ->first();

        if (! $price) {
            return response()->json([
                'success' => false,
                'message' => 'Price record not found.',
            ]);
        }

        $oldSteamPrice = $price->steam_price;

        // Prevent unnecessary update
        if ((float) $oldSteamPrice === (float) $steamPrice) {
            return response()->json([
                'success' => false,
                'message' => 'Steam price is already the same.',
            ]);
        }

        // Update steam price
        $price->disableLogging();
        $price->steam_price = $steamPrice;
        $price->save();

        // Activity log
        activity('price.updated')
            ->withProperties([
                'product_sku' => $product->sku,
                'price_id' => $priceId,
                'old_steam_price' => $oldSteamPrice,
                'new_steam_price' => $steamPrice,
            ])
            ->log('Steam price updated for product SKU: '.$product->sku);

        return response()->json([
            'success' => true,
            'message' => 'Steam price updated successfully.',
            'data' => [
                'old_steam_price' => $oldSteamPrice,
                'steam_price' => $steamPrice,
            ],
        ]);
    }

    private function commissionUpdate(Request $request, $id)
    {
        // Ensure float
        $commission_percentage = (float) $request->merchant_commission_percentage;

        $product = Product::find($id);
        if (! $product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ]);
        }

        if (! $product->product_upc) {
            return response()->json([
                'success' => false,
                'message' => 'Product UPC not found.',
            ]);
        }
        if ($product->merchant_commission_percentage == $commission_percentage) {
            return response()->json([
                'success' => false,
                'message' => 'The commission value is already set for this product.',
            ]);
        }

        try {
            $endpoint = config('services.incomm_product.endpoint');
            $password = config('services.incomm_product.password');
            $route = '/product/upsert';

            $url = $endpoint.$route.'?password='.urlencode($password);

            $payload = [
                'product_id' => $product->sku,
                'product_upc' => $product->product_upc,
                'merchant_commission' => $commission_percentage,
            ];

            $response = Http::timeout(10)->post($url, $payload);
            $data = $response->json();

            if ($response->status() === 201) {
                $oldCommission = $product->merchant_commission_percentage;

                // ✅ Check response data safely
                if (
                    isset($data['data']['merchant_commission']) &&
                    (float) $data['data']['merchant_commission'] === $commission_percentage
                ) {
                    $product->merchant_commission_percentage = $commission_percentage;
                    $product->save();
                }

                activity('product')
                    ->event('commission_updated')
                    ->withProperties([
                        'old_merchant_commission_percentage' => $oldCommission,
                        'new_merchant_commission_percentage' => $product->merchant_commission_percentage,
                    ])
                    ->log("updated commission for Product SKU {$product->sku}.");

                // 🔹 API response log
                Log::info('Incomm commission update response', [
                    'product_id' => $product->id,
                    'status' => $response->status(),
                    'response' => $data,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => $data['message'] ?? 'Commission updated successfully.',
                    'api_response' => $data,
                ]);

            } else {
                return response()->json([
                    'success' => false,
                    'message' => $data['message']
                      ?? 'Failed to update commission. API returned status: '.$response->status(),
                    'api_response' => $data,
                ]);
            }

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Commission update failed',
                'api_error' => $e->getMessage(),
            ]);
        }
    }

    private function deletePrice(Request $request, $id)
    {

        $priceId = $request->price_id;

        $product = Product::find($id);
        if (! $product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ]);
        }

        $price = Price::find($priceId);
        if (! $price) {
            return response()->json([
                'success' => false,
                'message' => 'Price not found.',
            ]);
        }

        $price->is_active = 0;
        $price->save();

        $product->skip_update = 1;
        $product->save();

        return response()->json([
            'success' => true,
            'message' => 'Price deleted successfully.',
        ]);
    }

    private function generateTags(Request $request, $id)
    {
        $product = Product::find($id);

        if (! $product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ]);
        }

        $locale = in_array($product->source, [1, 3, 4]) ? 'en' : 'pt-br';

        $localization = Localization::where('product_id', $product->sku)
            ->where('locale', $locale)
            ->first();

        if (! $localization) {
            return response()->json([
                'success' => false,
                'message' => "Localization not found for product id {$product->id} and locale {$locale}.",
            ]);
        }

        $tagSuggestionService = new TagSuggestionService(new OpenAIService(new PromptService));
        $tags = $tagSuggestionService->suggestTagsBySKU($product->sku);

        $createdTags = [];
        $skippedTags = [];

        foreach ($tags as $tagName) {
            // Check if tag already exists for this product
            $existingTag = Tag::where('product_id', $product->sku)
                ->where('tag', $tagName)
                ->first();

            if ($existingTag) {
                $skippedTags[] = $tagName; // already exists
            } else {
                $newTag = Tag::create([
                    'product_id' => $product->sku,
                    'tag' => $tagName,
                ]);
                $createdTags[] = $newTag->name;
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Tags generated successfully.',
            'created' => $createdTags,
            'skipped' => $skippedTags,
        ]);
    }

    private function suggestTags(Request $request, $id)
    {
        $product = Product::find($id);

        if (! $product) {
            return response()->json(['success' => false, 'message' => 'Product not found.'], 404);
        }

        $placeholders = [
            'Name' => $product->name,
            'DRM' => $product->drm_type_formatted,
        ];

        $lang = $request->lang;
        $type = $product->source == '1' ? 'game' : 'gift_card';

        $field = '';

        $prompt = Prompt::find($request->prompt);

        if ($prompt) {
            $name = $prompt->name;

            if (stripos($name, 'SEO') !== false) {
                $field = 'seo';
            } elseif (stripos($name, 'Genre') !== false) {
                $field = 'genre';
            } elseif (stripos($name, 'Franchise') !== false) {
                $field = 'franchise';
            } elseif (stripos($name, 'Community') !== false) {
                $field = 'community';
            }
        }

        $aiErrorMessage = $this->getAIErrorMessage();
        if ($aiErrorMessage) {
            return response()->json([
                'success' => false,
                'message' => $aiErrorMessage,
            ]);
        }

        $openAIService = new OpenAIService(new PromptService);
        $runPrompt = $openAIService->runPrompt($request->prompt, $placeholders, $lang, 5, $type);

        if (! $runPrompt || ! isset($runPrompt['response_content'])) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get a response from the AI service.',
            ]);
        }

        // Return a structured data object for the frontend
        return response()->json([
            'success' => true,
            'data' => [
                'response_content' => $runPrompt['response_content'],
                'promptText' => $runPrompt['promptText'] ?? '',
                'usage' => $runPrompt['usage'] ?? '',
                'cost' => $runPrompt['cost'] ?? '',
                'model' => $runPrompt['model'] ?? '',
                'lang' => $lang,
                'field' => $field,
            ],
            'message' => 'Data generated successfully.',
        ]);
    }

    private function generateRating(Request $request, $id)
    {
        $product = Product::find($id);

        if (! $product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ]);
        }

        // Generate the rating using your service
        $ratingService = new RatingSuggestionService(new OpenAIService(new PromptService));
        $rating = $ratingService->getRatingBySKU($product->sku);

        // Validate rating is numeric
        if (! is_numeric($rating)) {
            return response()->json([
                'success' => false,
                'message' => 'Generated rating is invalid.',
            ]);
        }

        // Store the rating in product's average_rating field
        $product->average_rating = round($rating, 2); // round to 2 decimals
        $product->save();

        return response()->json([
            'success' => true,
            'message' => 'Generated successfully.',
        ]);
    }

    private function translateTermsConditions(Request $request, $id)
    {
        $product = Product::find($id);

        if (! $product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ]);
        }
        $locale = 'en';
        if ($product->source === 2) {
            $locale = 'pt-br';
        }
        $localization = Localization::where('product_id', $product->sku)->where('locale', $locale)->first();

        if (! $localization || empty($localization->legal_texts)) {
            $language = $locale == 'en' ? 'English' : 'pt-br';

            return response()->json([
                'success' => false,
                'message' => 'Terms & Conditions are missing in '.$language.'. Please add them in English before translating.',
            ]);
        }

        $localizationCount = Localization::where('product_id', $product->sku)
            ->whereNotNull('legal_texts')
            ->where(function ($query) {
                $query->where('legal_texts', '<>', ''); // ensures not empty
            })
            ->whereIn('locale', ['en', 'es-419', 'pt-br'])
            ->count();

        if ($localizationCount == 3) {
            return response()->json([
                'success' => false,
                'message' => 'Terms & Conditions already exist for all locales: en, es-419, and pt-br.',
            ]);
        }

        $this->removeRateLimit();
        $service = new TranslationService(new OpenAIService(new PromptService));
        // Process system requirements translation for the given SKU
        $service->processLegalTextTranslation((int) $product->sku);

        $aiErrorMessage = $this->getAIErrorMessage();

        if ($aiErrorMessage) {
            return response()->json([
                'success' => false,
                'message' => $aiErrorMessage,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Terms & Conditions translated successfully!.',
        ]);
    }

    private function translateSystemReq(Request $request, $id)
    {

        $product = Product::find($id);
        if (! $product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ]);
        }
        $locale = 'en';
        if ($product->source === 2) {
            $locale = 'pt-br';
        }
        $localization = Localization::where('product_id', $product->sku)->where('locale', $locale)->first();
        if (! $localization || $localization->system_requirements == null || $localization->system_requirements == '') {

            $language = $locale == 'en' ? 'English' : 'pt-br';

            return response()->json([
                'success' => false,
                'message' => 'System requirements are missing in '.$language.'. Please add them in English before translating.',
            ]);
        }

        $this->removeRateLimit();
        $service = new TranslationService(new OpenAIService(new PromptService));
        // Process system requirements translation for the given SKU
        $service->processSystemRequirementsTranslation((int) $product->sku);

        $aiErrorMessage = $this->getAIErrorMessage();

        if ($aiErrorMessage) {
            return response()->json([
                'success' => false,
                'message' => $aiErrorMessage,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'System requirements translated successfully!.',
        ]);
    }

    private function saveTags(Request $request, $id)
    {
        $product = Product::find($id);

        if (! $product) {
            return response()->json(['success' => false, 'message' => 'Product not found.']);
        }

        $field = $request->field;
        $locale = $request->lang;
        $response_content = $request->response_content;
        $tags = ! empty($response_content) ? $this->tagifyToSerialized($response_content ?? null) : null;

        $localization = Localization::where('product_id', $product->sku)->where('locale', $locale)->first();
        if (empty($localization)) {
            $localization = new Localization;
            $localization->product_id = $product->sku;
            $localization->locale = $locale;
        }

        return response()->json([
            'success' => true,
            'message' => 'System requirements translated successfully!.',
        ]);
    }

    private function saveTag(Request $request, $id)
    {
        $product = Product::find($id);

        if (! $product) {
            return response()->json(['success' => false, 'message' => 'Product not found.']);
        }

        $field = $request->field;
        $locale = $request->lang;
        $response_content = $request->response_content;

        // Convert new tagify data to serialized format
        $newTagsSerialized = ! empty($response_content) ? $this->tagifyToSerialized($response_content ?? null) : null;

        // Decode new tags (to merge later if needed)
        $newTags = [];
        if (! empty($newTagsSerialized)) {
            $newTags = @unserialize($newTagsSerialized);
            if (! is_array($newTags)) {
                $newTags = [];
            }
        }

        // Find or create localization
        $localization = Localization::where('product_id', $product->sku)
            ->where('locale', $locale)
            ->first();

        // Determine which DB field to use
        $dbField = match ($field) {
            'seo' => 'seo_tags',
            'genre' => 'genre_tags',
            'franchise' => 'franchise_tags',
            'community' => 'community_tags',
            default => null,
        };

        if (! $dbField) {
            return response()->json(['success' => false, 'message' => 'Invalid field type.']);
        }

        // If no localization exists → create new and just save new tags
        if (empty($localization)) {
            $localization = new Localization;
            $localization->title = $product->name;
            $localization->product_id = $product->sku;
            $localization->locale = $locale;
            $localization->$dbField = serialize($newTags);
        } else {
            // Merge with old tags if old data exists
            $oldTags = [];
            if (! empty($localization->$dbField)) {
                $oldTags = @unserialize($localization->$dbField);
                if (! is_array($oldTags)) {
                    $oldTags = [];
                }
            }

            // Merge old + new, remove duplicates
            $mergedTags = array_unique(array_merge($oldTags, $newTags));

            // Save merged data
            $localization->$dbField = serialize($mergedTags);
        }

        $localization->save();

        return response()->json([
            'success' => true,
            'message' => ucfirst($field).' tags saved successfully!',
        ]);
    }

    private function tagifyToSerialized(?string $json): ?string
    {
        if (empty($json)) {
            return null;
        }

        $decoded = json_decode($json, true);
        if (! is_array($decoded)) {
            return null;
        }

        $values = array_column($decoded, 'value');

        return serialize($values);
    }

    private function sanitizeEditorText(?string $html): ?string
    {
        if (empty($html)) {
            return null;
        }

        // Remove whitespace and newlines
        $trimmed = trim($html);

        // Check if it's visually empty (only <p><br></p>, <p></p>, &nbsp; etc.)
        $isEmpty = preg_match('/^(<p>(\s|&nbsp;|<br\s*\/?>)*<\/p>)*$/i', $trimmed)
          || trim(strip_tags($trimmed)) === '';

        return $isEmpty ? null : $html;
    }

    public function uploadJson(Request $request)
    {
        $data = $request->json_data;

        if (empty($data) || ! is_array($data)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid JSON payload',
            ]);
        }

        // REQUIRED FIELDS
        $required = [
            'scrape_url', 'product_id', 'region', 'source',
            'concept_id', 'title', 'description',
            'platforms', 'primary_image_url', 'pricing',
        ];

        $missing = [];
        foreach ($required as $field) {
            if (! array_key_exists($field, $data)) {
                $missing[] = $field;
            }
        }

        if (! empty($missing)) {
            return response()->json([
                'success' => false,
                'message' => 'Missing required fields: '.implode(', ', $missing),
            ]);
        }

        // BASIC INFO
        $scrapeUrl = $data['scrape_url'];
        $productId = $data['product_id'];
        $region = $data['region'];
        $source = $data['source'];
        $conceptId = $data['concept_id'];
        $title = $data['title'];
        $description = $data['description'];

        // ✅ Check if concept_id is null
        if (empty($conceptId)) {
            return response()->json([
                'success' => false,
                'message' => 'Concept ID is missing in your JSON payload. Please provide a valid concept_id.',
            ]);
        }

        // Platforms (convert array → JSON)
        $platforms = $data['platforms'] ?? [];

        $platformsSerialized = is_array($platforms)
          ? serialize($platforms)
          : serialize([$platforms]);

        $primary_image_url = $data['primary_image_url'];

        // Check valid Product
        $product = Product::where('sku', $productId)->first();

        if (! $product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ]);
        }
        if ($product->source === 1 || $product->source === 3 || $product->source === 4) {
            return response()->json([
                'success' => false,
                'message' => 'This product is not a incomm product.',
            ]);
        }

        // PRICING
        $pricing = $data['pricing'];
        $currency = $pricing['currency'] ?? null;
        $priceOriginal = $pricing['price_original'] ?? null;
        $priceCurrent = $pricing['price_current'] ?? null;
        $discountPercent = $pricing['discount_percent'] ?? null;
        $promoActive = $pricing['promo_active'] ?? false;

        // VALIDATE PRICE RANGE
        $min = (float) $product->min_value;
        $max = (float) $product->max_value;
        $enteredPrice = (float) $priceOriginal;

        if ($enteredPrice < $min || $enteredPrice > $max) {
            return response()->json([
                'success' => false,
                'message' => 'Please enter a price_original between '.number_format($min, 2).' and '.number_format($max, 2).'.',
            ]);
        }

        // ============================
        // IMAGE DOWNLOAD & S3 UPLOAD
        // ============================
        $imageUrl = null;

        if (! empty($primary_image_url) && filter_var($primary_image_url, FILTER_VALIDATE_URL)) {
            try {
                $ch = curl_init($primary_image_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

                $imageContents = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
                curl_close($ch);

                if ($httpCode !== 200 || empty($imageContents)) {
                    throw new \Exception("Image download failed: HTTP $httpCode");
                }

                if (! in_array($contentType, ['image/jpeg', 'image/png', 'image/webp'])) {
                    throw new \Exception("Invalid image type: $contentType");
                }

                if (! @getimagesizefromstring($imageContents)) {
                    throw new \Exception('Downloaded file is not a valid image');
                }

                $baseUrl = 'https://images.2game.com/';
                $fileName = uniqid().'_'.basename(parse_url($primary_image_url, PHP_URL_PATH));
                $fileName = preg_replace('/[^A-Za-z0-9\._-]/', '', $fileName);

                $s3Path = 'products_management/'.$fileName;
                Storage::disk('s3')->put($s3Path, $imageContents, 'public');

                $imageUrl = $baseUrl.$s3Path;

            } catch (\Exception $e) {
                Log::error("Image Upload Failed for Product $productId : ".$e->getMessage());
            }
        }

        $activityLog = [];
        $activityLog['json_data'] = $data;
        // ============================
        // PRICE SAVE / UPDATE
        // ============================
        $price = Price::where('product_id', $productId)
            ->where('currency', $currency)
            ->where('price', $priceOriginal)
            ->where('concept_id', $conceptId)
            ->first();

        if ($price && $price->is_active == 0) {
            $price->is_active = 1;
        }

        if (! $price) {
            $price = new Price;
            $price->source = 2;
            $price->product_id = $productId;
            $price->currency = $currency;
            $price->country_code = strtoupper($region ?? 'BR');
            $price->price = $priceOriginal;

            $message = "Price for Product SKU {$product->sku} created successfully.";
        } else {
            $activityLog['before'] = $this->priceActivityLog($price);
            $message = "Price for Product SKU {$product->sku} updated successfully.";
        }
        $price->disableLogging();

        $price->concept_id = $conceptId;
        $price->scrape_url = $scrapeUrl;
        $price->title = $title;
        $price->description = $description;
        $price->discount_percent = $discountPercent;
        $price->is_promo_active = $promoActive;
        $price->platforms = $platformsSerialized;
        $price->primary_image_url = $imageUrl;
        $price->price_source = $source;
        $price->discount_valid_from = Carbon::now('Europe/Stockholm')->timestamp;
        $price->price_update_timestamp = time();
        $price->save();

        $activityLog['after'] = $this->priceActivityLog($price);

        activity('price.upload')
            ->withProperties($activityLog)
            ->log($message);

        return response()->json([
            'success' => true,
            'message' => $message,
        ]);
    }

    private function priceActivityLog($price)
    {
        $platforms = $price->platforms ?? '';

        if (! empty($platforms)) {
            $unserialized = @unserialize($platforms);
            if ($unserialized !== false && is_array($unserialized)) {
                $platforms = $unserialized;
            }
            // else keep $platforms as-is
        }

        return [
            'id' => $price->id ?? '',
            'price' => $price->price ?? '',
            'discount_percent' => $price->discount_percent ?? '',
            'discount_valid_from' => $price->discount_valid_from_formatted ?? '',
            'discount_valid_to' => $price->discount_valid_to_formatted ?? '',
            'price_source' => $price->price_source ?? '',
            'is_promo_active' => $price->is_promo_active ?? '',
            'currency' => $price->currency ?? '',
            'country_code' => $price->country_code ?? '',
            'scrape_url' => $price->scrape_url ?? '',
            'description' => $price->description ?? '',
            'platforms' => $platforms,
            'primary_image_url' => $price->primary_image_url ?? '',
            'concept_id' => $price->concept_id ?? '',
        ];
    }

    public function checkUploadJsonData(Request $request)
    {
        $item = $request->item;

        $productId = $item['product_id'] ?? null;
        $conceptId = $item['concept_id'] ?? null;
        $price_original = $item['pricing']['price_original'] ?? null;

        $exists = false;
        if ($productId) {
            $exists = Product::where('sku', $productId)->exists();
        }

        $conceptIdMessage = null;
        $enteredPrice = null;

        if (empty($conceptId)) {
            $conceptIdMessage = 'Concept ID is required in your JSON.';
        } elseif ($productId && $conceptId) {
            // Initialize enteredPrice as null

            // Convert price_original to float with 2 decimals if numeric
            if (is_numeric($price_original)) {
                $enteredPrice = round((float) $price_original, 2);
            }

            // Build query for Price
            $priceQuery = Price::where('concept_id', $conceptId)
                ->where('product_id', $productId)
                ->where('is_active', 1);

            // Add price condition if enteredPrice is valid
            if (! is_null($enteredPrice)) {
                $priceQuery->where('price', $enteredPrice)->first();
            }

            // Execute query
            $price = $priceQuery->first();

            // Set message if price record exists
            if ($price) {
                $conceptIdMessage = 'This Concept ID already exists for this product — uploading will update the existing data.';
            }
        }

        return response()->json([
            'exists' => $exists,
            'product_id' => $productId,
            'concept_id_message' => $conceptIdMessage,
            'enteredPrice' => $enteredPrice,
            'price_original' => $price_original,
            'conceptId' => $conceptId,
        ]);
    }

    private function uploadFileToS3(?UploadedFile $file): ?string
    {
        if (! $file || ! $file->isValid()) {
            return null;
        }

        // Allow only image MIME types
        if (! in_array($file->getMimeType(), ['image/jpeg', 'image/png', 'image/webp', 'image/gif'])) {
            Log::warning('Upload failed: File is not a valid image.');

            return null;
        }

        try {
            $folder = 'products_management'; // static folder
            $fileName = uniqid().'_'.preg_replace('/[^A-Za-z0-9\._-]/', '', $file->getClientOriginalName());
            $s3Path = $folder.'/'.$fileName;

            // Upload to S3
            Storage::disk('s3')->put($s3Path, file_get_contents($file), 'public');

            // Return public URL
            return 'https://images.2game.com/'.$s3Path;

        } catch (\Exception $e) {
            Log::error('S3 Upload Error: '.$e->getMessage());

            return null;
        }
    }

    private function buildLanguageSupportString(array $data): ?string
    {
        $sections = [];

        $interface = $this->extractValues($data['interface'] ?? null);
        $fullAudio = $this->extractValues($data['full_audio'] ?? null);
        $subtitles = $this->extractValues($data['subtitles'] ?? null);
        $sections[] = 'Language Support:';
        if ($interface) {
            $sections[] = 'Interface: '.implode(', ', $interface);
        }

        if ($fullAudio) {
            $sections[] = 'Full Audio: '.implode(', ', $fullAudio);
        }

        if ($subtitles) {
            $sections[] = 'Subtitles: '.implode(', ', $subtitles);
        }

        return empty($sections) ? null : implode("\n", $sections);
    }

    private function extractValues($value): array
    {
        if (empty($value)) {
            return [];
        }

        // If Tagify sends JSON string
        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        if (! is_array($value)) {
            return [];
        }

        return array_values(
            array_filter(
                array_map(fn ($v) => $v['value'] ?? null, $value)
            )
        );
    }

    private function removeRateLimit()
    {
        Notice::where('type', 'openai_rate_limit')->delete();
    }

    private function getAIErrorMessage()
    {
        return Notice::where('type', 'openai_rate_limit')
            ->where('status', 'active')
            ->value('title'); // returns string or null
    }

    private function isPageBlocked(): bool
    {
        if (auth()->user()?->hasRole('Super-Admin')) {
            return false;
        }

        return Option::get('ztorm_price_import') === 'running';
    }
}

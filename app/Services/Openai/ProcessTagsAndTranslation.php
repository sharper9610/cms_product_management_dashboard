<?php

namespace App\Services\Openai;

use App\Models\Product;
use App\Models\Localization;
use Illuminate\Support\Facades\Log;

class ProcessTagsAndTranslation
{
    protected OpenAIService $openAIService;
    protected TranslationService $translationService;
    protected RatingSuggestionService $ratingSuggestionService;
    protected PegiRatingSuggestionService $pegiRatingSuggestionService;
    protected ProductTitleLocalizationService $productTitleLocalizationService;

    public function __construct(
        OpenAIService $openAIService, 
        TranslationService $translationService, 
        RatingSuggestionService $ratingSuggestionService, 
        PegiRatingSuggestionService $pegiRatingSuggestionService,
        ProductTitleLocalizationService $productTitleLocalizationService
    )
    {
        $this->openAIService = $openAIService;
        $this->translationService = $translationService;
        $this->ratingSuggestionService = $ratingSuggestionService;
        $this->pegiRatingSuggestionService = $pegiRatingSuggestionService;
        $this->productTitleLocalizationService = $productTitleLocalizationService;

    }

    /**
     * Process all translations and tag generations for a given product SKU
     */
    public function processAllForProduct(int $productSku): void
    {
        Log::info("🔄 Starting all translations & tag generations", ['sku' => $productSku]);

        $product = Product::where('sku', $productSku)->first();

        if (!$product) {
            Log::warning("❌ Product not found", ['sku' => $productSku]);
            return;
        }

       

        // try {
        //     $this->generateAllTags($product);
        //     Log::info("✅ Completed tag generation for SKU {$product->sku}");
        // } catch (\Throwable $e) {
        //     Log::error("❌ Tag generation failed for SKU {$product->sku}: {$e->getMessage()}");
        // }


         try {
            $this->productTitleLocalizationService->localizeProduct($product->sku);
            Log::info("✅ Completed product title translation generation for SKU {$product->sku}");
        } catch (\Throwable $e) {
            Log::error("❌ product title translation generation failed for SKU {$product->sku}: {$e->getMessage()}");
        }

        // try {
        //     $this->processSystemRequirementsTranslation($product);
        //     Log::info("✅ Completed system requirements translation for SKU {$product->sku}");
        // } catch (\Throwable $e) {
        //     Log::error("❌ System requirements translation failed for SKU {$product->sku}: {$e->getMessage()}");
        // }

        // try {
        //     $this->processSupportedLanguagesTranslation($product);
        //     Log::info("✅ Completed Supported language translation for SKU {$product->sku}");
        // } catch (\Throwable $e) {
        //     Log::error("❌ Supported language translation failed for SKU {$product->sku}: {$e->getMessage()}");
        // }

        // try {
        //     $this->translationService->processProductTranslationBySKU($product->sku);
        //     Log::info("✅ Completed product translations for SKU {$product->sku}");
        // } catch (\Throwable $e) {
        //     Log::error("❌ Product translation failed for SKU {$product->sku}: {$e->getMessage()}");
        // }

        // try {
        //     $this->ratingSuggestionService->getRatingBySKU($product->sku);
        //     Log::info("✅ Completed product rating generation for SKU {$product->sku}");
        // } catch (\Throwable $e) {
        //     Log::error("❌ Product rating generation for SKU {$product->sku}: {$e->getMessage()}");
        // }

        try {
            $this->pegiRatingSuggestionService->getPegiRatingBySKU($product->sku);
            Log::info("✅ Completed product pegi rating generation for SKU {$product->sku}");
        } catch (\Throwable $e) {
            Log::error("❌ Product pegi rating generation for SKU {$product->sku}: {$e->getMessage()}");
        }

        // try {
        //     $this->translationService->processLegalTextTranslation($product->sku);
        //     Log::info("✅ Completed product legal text translations for SKU {$product->sku}");
        // } catch (\Throwable $e) {
        //     Log::error("❌ Product legal text translation failed for SKU {$product->sku}: {$e->getMessage()}");
        // }

        // try{
        //     $this->openAIService->processDlcProductIdsGeneration($product->sku);
        //     Log::info("✅ Completed dlc product ids generation for SKU {$product->sku}");
        // }catch(\Throwable $e){
        //    Log::error("❌ Product dlc product ids generation failed for SKU {$product->sku}: {$e->getMessage()}");
        // }


        Log::info("✅ Completed all localization for SKU {$productSku}");
    }

    /**
     * Generate SEO, Genre, and Franchise tags
     */
    protected function generateAllTags(Product $product): void
    {
        // if ($product->source == 1) {
        //     $this->translateOrGenerateGenreTags($product);
        // }

        $languages = ['en', 'pt-br', 'es-419'];
        $tagTypes = [
            // 'seo_tags'       => 1,
            'genre_tags'     => 2,
            'franchise_tags' => 3,
            // 'community_tags' => 4,
        ];

        $drm = $product->getDrmTypeFormattedAttribute() ?: ($product->source == 1 ? 'Game' : 'Gift Card');

        foreach ($languages as $locale) {
            Log::info("🌐 Processing SKU {$product->sku} ({$product->name}) — Locale: {$locale}");

            foreach ($tagTypes as $field => $promptId) {
                $this->generateTagsForType($product, $locale, $drm, $field, $promptId);
                usleep(2_500_000); // 2.5s delay
            }
        }
    }


    public function translateOrGenerateGenreTags(Product $product): void
    {
        $baseLocale = 'en';
        $targetLocales = ['pt-br', 'es-419'];
        $promptId = 2;
        $drm = $product->getDrmTypeFormattedAttribute() ?: ($product->source == 1 ? 'Game' : 'Gift Card');

        try {
            $enLocalization = Localization::where('product_id', $product->sku)
                ->where('locale', $baseLocale)
                ->first();

            if (!$enLocalization || empty($enLocalization->genre_tags)) {
                Log::info("Generating missing genre_tags for SKU {$product->sku} ({$product->name}) — Locale: {$baseLocale}");

                $this->generateGenreTagsForType($product, $baseLocale, $drm, 'genre_tags', $promptId);
                usleep(2_500_000);

                $enLocalization = Localization::where('product_id', $product->sku)
                    ->where('locale', $baseLocale)
                    ->first();
            }

            if (!$enLocalization || empty($enLocalization->genre_tags)) {
                Log::warning("Could not generate or find genre_tags for SKU {$product->sku}");
                return;
            }

            $genreTags = @unserialize($enLocalization->genre_tags);
            if ($genreTags === false || !is_array($genreTags) || empty($genreTags)) {
                Log::warning("Invalid or empty serialized genre_tags for SKU {$product->sku}");
                return;
            }

            foreach ($targetLocales as $locale) {
                $localeLocalization = Localization::where('product_id', $product->sku)
                    ->where('locale', $locale)
                    ->first();

                if ($localeLocalization && !empty($localeLocalization->genre_tags)) {
                    Log::info("Skipping translation for SKU {$product->sku} — Locale {$locale} already has genre_tags");
                    continue;
                }

                Log::info("Translating genre_tags for SKU {$product->sku} from {$baseLocale} → {$locale}");

                $translatedTags = [];
                foreach ($genreTags as $tag) {
                    try {
                        $translated = $this->translationService->translate($tag, $baseLocale, $locale);
                        $translatedTags[] = $translated;
                    } catch (\Exception $e) {
                        Log::error("Failed to translate tag '{$tag}' ({$baseLocale}→{$locale}): " . $e->getMessage());
                        $translatedTags[] = $tag;
                    }
                }

                // Save translated tags for this locale
                Localization::updateOrCreate(
                    ['product_id' => $product->sku, 'locale' => $locale],
                    ['genre_tags' => serialize($translatedTags), 'title' => $product->name]
                );

                Log::info("Stored translated genre_tags for SKU {$product->sku} — Locale: {$locale}");
                usleep(500_000);
            }
        } catch (\Exception $e) {
            Log::error("translateOrGenerateGenreTags failed for SKU {$product->sku}: " . $e->getMessage());
        }
    }

    public function translateOrGenerateGenreTagsWithOverride(Product $product): void
    {
        $baseLocale = 'en';
        $targetLocales = ['pt-br', 'es-419'];
        $promptId = 2;
        $drm = $product->getDrmTypeFormattedAttribute() ?: ($product->source == 1 ? 'Game' : 'Gift Card');

        try {
            $enLocalization = Localization::where('product_id', $product->sku)
                ->where('locale', $baseLocale)
                ->first();

            // if (!$enLocalization || empty($enLocalization->genre_tags)) {
            //     Log::info("Generating missing genre_tags for SKU {$product->sku} ({$product->name}) — Locale: {$baseLocale}");

            //     $this->generateGenreTagsForTypeWithOverride($product, $baseLocale, $drm, 'genre_tags', $promptId);
            //     usleep(2_500_000);

            //     $enLocalization = Localization::where('product_id', $product->sku)
            //         ->where('locale', $baseLocale)
            //         ->first();
            // }

            $this->generateGenreTagsForTypeWithOverride($product, $baseLocale, $drm, 'genre_tags', $promptId);
            usleep(2_500_000);

            $enLocalization = Localization::where('product_id', $product->sku)
                ->where('locale', $baseLocale)
                ->first();



            if (!$enLocalization || empty($enLocalization->genre_tags)) {
                Log::warning("Could not generate or find genre_tags for SKU {$product->sku}");
                return;
            }

            $genreTags = @unserialize($enLocalization->genre_tags);
            if ($genreTags === false || !is_array($genreTags) || empty($genreTags)) {
                Log::warning("Invalid or empty serialized genre_tags for SKU {$product->sku}");
                return;
            }

            foreach ($targetLocales as $locale) {
                $localeLocalization = Localization::where('product_id', $product->sku)
                    ->where('locale', $locale)
                    ->first();

                // if ($localeLocalization && !empty($localeLocalization->genre_tags)) {
                //     Log::info("Skipping translation for SKU {$product->sku} — Locale {$locale} already has genre_tags");
                //     continue;
                // }

                Log::info("Translating genre_tags for SKU {$product->sku} from {$baseLocale} → {$locale}");

                $translatedTags = [];
                foreach ($genreTags as $tag) {
                    try {
                        $translated = $this->translationService->translate($tag, $baseLocale, $locale);
                        $translatedTags[] = $translated;
                    } catch (\Exception $e) {
                        Log::error("Failed to translate tag '{$tag}' ({$baseLocale}→{$locale}): " . $e->getMessage());
                        $translatedTags[] = $tag;
                    }
                }

                // Save translated tags for this locale
                Localization::updateOrCreate(
                    ['product_id' => $product->sku, 'locale' => $locale],
                    ['genre_tags' => serialize($translatedTags), 'title' => $product->name]
                );

                Log::info("Stored translated genre_tags for SKU {$product->sku} — Locale: {$locale}");
                usleep(500_000);
            }
        } catch (\Exception $e) {
            Log::error("translateOrGenerateGenreTags failed for SKU {$product->sku}: " . $e->getMessage());
        }
    }





    /**
     * Generate a single tag type for product + locale
     */
    protected function generateTagsForType(Product $product, string $locale, string $drm, string $field, int $promptId): void
    {
        if ($product->source == 2 && $promptId == 2) {
            Log::info("Skipping SEO tags for gift cards sku {$product->sku}");
            return;
        }

        $localization = Localization::where([
            'product_id' => $product->sku,
            'locale'     => $locale,
        ])->first();

        if ($localization && !empty($localization->{$field})) {
            Log::info("⏭️ Skipping {$field} (already exists)");
            return;
        }


        $attempts = 0;
        $maxAttempts = 5;
        $delay = 5;

        while ($attempts < $maxAttempts) {
            try {
                $response = $this->openAIService->runPrompt(
                    promptId: $promptId,
                    placeholders: [
                        'Name' => $product->name,
                        'DRM'  => $drm,
                    ],
                    language: $locale,
                    type: $product->source == 1 ? 'game' : 'gift_card'
                );

                if (!$response || empty($response['response_content'])) {
                    Log::warning("No response for {$field}");
                    break;
                }

                $newTags = array_map('trim', explode(',', $response['response_content']));

                if (!$localization) {
                    $localization = new Localization();
                    $localization->product_id = $product->sku;
                    $localization->locale = $locale;
                    $localization->title = $product->name;
                }

                $existingTags = $localization->{$field} ? @unserialize($localization->{$field}) : [];
                $existingTags = is_array($existingTags) ? $existingTags : [];

                $localization->{$field} = serialize(array_unique(array_filter(array_merge($existingTags, $newTags))));
                $localization->save();

                Log::info("✅ Saved {$field} for {$locale}");
                return;
            } catch (\Throwable $e) {
                $attempts++;
                Log::warning("Error generating {$field} (attempt {$attempts}): {$e->getMessage()}");
                sleep($delay);
                $delay *= 2;
            }
        }

        Log::error("❌ Failed to generate {$field} after {$maxAttempts} attempts");
    }

    protected function generateGenreTagsForType(Product $product, string $locale, string $drm, string $field, int $promptId): void
    {
        if ($product->source == 2 && $promptId == 2) {
            Log::info("Skipping SEO tags for gift cards sku {$product->sku}");
            return;
        }

        $localization = Localization::where([
            'product_id' => $product->sku,
            'locale'     => $locale,
        ])->first();

        $enlocalization = Localization::where([
            'product_id' => $product->sku,
            'locale'     => 'en',
        ])->first();

        if ($localization && !empty($localization->{$field})) {
            Log::info("⏭️ Skipping {$field} (already exists)");
            return;
        }

        $attempts = 0;
        $maxAttempts = 5;
        $delay = 5;

        while ($attempts < $maxAttempts) {
            try {
                $response = $this->openAIService->runGenrePrompt(
                    promptId: $promptId,
                    placeholders: [
                        'Name' => $product->name,
                        'DRM'  => $drm,
                        'Description' => isset($enlocalization) ? $enlocalization->long_description : '',
                    ],
                    language: $locale,
                    type: $product->source == 1 ? 'game' : 'gift_card'
                );

                if (!$response || empty($response['response_content'])) {
                    Log::warning("No response for {$field}");
                    break;
                }

                $newTags = array_map('trim', explode(',', $response['response_content']));

                if (!$localization) {
                    $localization = new Localization();
                    $localization->product_id = $product->sku;
                    $localization->locale = $locale;
                    $localization->title = $product->name;
                }

                $existingTags = $localization->{$field} ? @unserialize($localization->{$field}) : [];
                $existingTags = is_array($existingTags) ? $existingTags : [];

                $localization->{$field} = serialize(array_unique(array_filter(array_merge($existingTags, $newTags))));
                $localization->save();

                Log::info("✅ Saved {$field} for {$locale}");
                return;
            } catch (\Throwable $e) {
                $attempts++;
                Log::warning("Error generating {$field} (attempt {$attempts}): {$e->getMessage()}");
                sleep($delay);
                $delay *= 2;
            }
        }

        Log::error("❌ Failed to generate {$field} after {$maxAttempts} attempts");
    }

    protected function generateGenreTagsForTypeWithOverride(Product $product, string $locale, string $drm, string $field, int $promptId): void
    {
        if ($product->source == 2 && $promptId == 2) {
            Log::info("Skipping SEO tags for gift cards sku {$product->sku}");
            return;
        }

        $localization = Localization::where([
            'product_id' => $product->sku,
            'locale'     => $locale,
        ])->first();

        $enlocalization = Localization::where([
            'product_id' => $product->sku,
            'locale'     => 'en',
        ])->first();

        // if ($localization && !empty($localization->{$field})) {
        //     Log::info("⏭️ Skipping {$field} (already exists)");
        //     return;
        // }

        $attempts = 0;
        $maxAttempts = 5;
        $delay = 5;

        while ($attempts < $maxAttempts) {
            try {
                $response = $this->openAIService->runGenrePrompt(
                    promptId: $promptId,
                    placeholders: [
                        'Name' => $product->name,
                        'DRM'  => $drm,
                        'Description' => isset($enlocalization) ? $enlocalization->long_description : '',
                    ],
                    language: $locale,
                    type: $product->source == 1 ? 'game' : 'gift_card'
                );

                if (!$response || empty($response['response_content'])) {
                    Log::warning("No response for {$field}");
                    break;
                }

                $newTags = array_map('trim', explode(',', $response['response_content']));

                if (!$localization) {
                    $localization = new Localization();
                    $localization->product_id = $product->sku;
                    $localization->locale = $locale;
                    $localization->title = $product->name;
                }

                // $existingTags = $localization->{$field} ? @unserialize($localization->{$field}) : [];
                // $existingTags = is_array($existingTags) ? $existingTags : [];

                $localization->{$field} = serialize($newTags);
                $localization->save();

                Log::info("✅ Saved {$field} for {$locale}");
                return;
            } catch (\Throwable $e) {
                $attempts++;
                Log::warning("Error generating {$field} (attempt {$attempts}): {$e->getMessage()}");
                sleep($delay);
                $delay *= 2;
            }
        }

        Log::error("❌ Failed to generate {$field} after {$maxAttempts} attempts");
    }

    /**
     * Translate system requirements and store in localization table
     */
    protected function processSystemRequirementsTranslation(Product $product): void
    {
        $productSku = $product->sku;

        if (!$product->system_requirements) {
            Log::debug("Product has no system_requirements", ['sku' => $productSku]);
            return;
        }

        $systemRequirementsRaw = $product->getsystemRequirementsRawAttribute();
        if (!$systemRequirementsRaw) {
            Log::debug("No raw system requirements found", ['sku' => $productSku]);
            return;
        }

        if ((int) $product->source === 2) {
            $sourceLocale = 'pt-br';
            $targetLocales = ['en', 'es-419'];
        } else {
            $sourceLocale = 'en';
            $targetLocales = ['pt-br', 'es-419'];
        }

        $existingLocalization = Localization::where('product_id', $productSku)
            ->where('locale', $sourceLocale)
            ->first();

        if ($existingLocalization) {
            return;
        } else {
            Localization::create([
                'product_id' => $productSku,
                'locale' => $sourceLocale,
                'title' => $product->name,
                'system_requirements' => $systemRequirementsRaw,
            ]);
        }

        foreach ($targetLocales as $targetLocale) {
            $existingLocalization = Localization::where('product_id', $productSku)
                ->where('locale', $targetLocale)
                ->first();

            if ($existingLocalization && $existingLocalization->system_requirements) {
                Log::debug("Skipping system requirements translation for {$targetLocale}", ['sku' => $productSku]);
                continue;
            }

            $translatedRequirements = $this->translationService->translate(
                $systemRequirementsRaw,
                $sourceLocale,
                $targetLocale
            );

            if ($translatedRequirements) {
                Localization::updateOrCreate(
                    ['product_id' => $productSku, 'locale' => $targetLocale],
                    [
                        'title' => $existingLocalization?->title ?? $product->name,
                        'system_requirements' => $translatedRequirements,
                    ]
                );

                Log::debug("System requirements translated for {$targetLocale}", ['sku' => $productSku]);
            } else {
                Log::warning("Translation returned empty for {$targetLocale}", ['sku' => $productSku]);
            }

            // Respect API rate limits
            usleep(500_000); // .5 seconds
        }
    }


    protected function processSupportedLanguagesTranslation(Product $product): void
    {

        $productSku = $product->sku;

        if (!$product->supported_languages) {
            Log::debug("Product has no supported_languages", ['sku' => $productSku]);
            return;
        }

        $supportedLanguagesRaw = $product->getSupportedLanguagesRawAttribute();
        if (!$supportedLanguagesRaw) {
            Log::debug("No raw supported languages found", ['sku' => $productSku]);
            return;
        }

        if ((int) $product->source === 2) {
            $sourceLocale = 'pt-br';
            $targetLocales = ['en', 'es-419'];
        } else {
            $sourceLocale = 'en';
            $targetLocales = ['pt-br', 'es-419'];
        }

        Localization::updateOrCreate(
            ['product_id' => $productSku, 'locale' => $sourceLocale],
            [
                'title' => $product->name,
                'supported_languages' => $supportedLanguagesRaw,
            ]
        );

        foreach ($targetLocales as $targetLocale) {
            $existingLocalization = Localization::where('product_id', $productSku)
                ->where('locale', $targetLocale)
                ->first();

            if ($existingLocalization && $existingLocalization->supported_languages) {
                Log::debug("Skipping supported_languages translation for {$targetLocale}", ['sku' => $productSku]);
                continue;
            }

            $translatedLanguages = $this->translationService->translate(
                $supportedLanguagesRaw,
                $sourceLocale,
                $targetLocale
            );

            if ($translatedLanguages) {
                Localization::updateOrCreate(
                    ['product_id' => $productSku, 'locale' => $targetLocale],
                    [
                        'title' => $existingLocalization?->title ?? $product->name,
                        'supported_languages' => $translatedLanguages,
                    ]
                );

                Log::debug("Supported languages translated for {$targetLocale}", ['sku' => $productSku]);
            } else {
                Log::warning("Translation returned empty for {$targetLocale}", ['sku' => $productSku]);
            }

            usleep(500_000);
        }
    }

    protected function generateProductTitleTranslation(Product $product): void
    {

        $productSku = $product->sku;

        if (!$product->name) {
            Log::debug("Product has no title", ['sku' => $productSku]);
            return;
        }

        $title = $product->name;
        if (!$title) {
            Log::debug("No raw title found", ['sku' => $productSku]);
            return;
        }

        if ((int) $product->source === 2) {
            $sourceLocale = 'pt-br';
            $targetLocales = ['en', 'es-419'];
        } else {
            $sourceLocale = 'en';
            $targetLocales = ['pt-br', 'es-419'];
        }

        Localization::updateOrCreate(
            ['product_id' => $productSku, 'locale' => $sourceLocale],
            [
                'title' => $product->name
            ]
        );

        foreach ($targetLocales as $targetLocale) {
            $existingLocalization = Localization::where('product_id', $productSku)
                ->where('locale', $targetLocale)
                ->first();

            if ($existingLocalization && $existingLocalization->name) {
                Log::debug("Skipping title translation for {$targetLocale}", ['sku' => $productSku]);
                continue;
            }

            $translatedTitle = $this->translationService->translate(
                $title,
                $sourceLocale,
                $targetLocale
            );

            if ($translatedTitle) {
                Localization::updateOrCreate(
                    ['product_id' => $productSku, 'locale' => $targetLocale],
                    [
                        'title' => $translatedTitle,
                    ]
                );

                Log::debug("title translated for {$targetLocale}", ['sku' => $productSku]);
            } else {
                Log::warning("Translation returned empty for {$targetLocale}", ['sku' => $productSku]);
            }

            usleep(500_000);
        }
    }
}

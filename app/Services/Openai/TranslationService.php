<?php

namespace App\Services\Openai;

use App\Models\Localization;
use App\Models\Product;
use Exception;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class TranslationService
{

    protected OpenAIService $openAIService;

    public function __construct(OpenAIService $openAIService)
    {
        $this->openAIService = $openAIService;
    }


    /**
     * Translate text from one language to another for a specific product id
     *
     * @return null
     */
    public function processProductTranslation()
    {
        $products = Product::all();

        foreach ($products as $product) {
            $localizationNeeded = collect($product->allowed_currencies)
                ->mapWithKeys(function ($currency) {
                    $mapping = config("localization.currency_locales.$currency", []);
                    return [$currency => $mapping];
                })
                ->toArray();

            $this->handleProductLocalization($product->sku, $localizationNeeded);
        }
    }

    public function processProductTranslationBySKU($sku)
    {
        $product = Product::where('sku', $sku)->first();

        if ($product) {
            $localizationNeeded = collect($product->allowed_currencies)
                ->mapWithKeys(function ($currency) {
                    $mapping = config("localization.currency_locales.$currency", []);
                    return [$currency => $mapping];
                })
                ->toArray();


            $this->handleProductLocalization($product->sku, $localizationNeeded);
        }
    }
    /**
     * Handle product localization result.
     *
     * @param int   $productSku
     * @param array $localizationNeeded
     * @return void
     */
    protected function handleProductLocalization(int $productSku, array $localizationNeeded): void
    {
        $product = Product::where('sku', $productSku)->first();
        if (!$product) {
            return;
        }

        if ((int) $product->source === 2) {
            $baseLocale = 'pt-br';
            $targetLocales = ['en', 'es-419'];
        } elseif ((int) $product->source === 1) {
            $baseLocale = 'en';
            $targetLocales = ['pt-br', 'es-419'];
        }

        // Fetch base localization
        $localization = Localization::where([
            'product_id' => $productSku,
            'locale' => $baseLocale,
        ])->first();

        if (!$localization) {
            return;
        }

        // Extract source fields
        $locale = $localization->locale;
        $title = $localization->title;
        $short_description = $localization->short_description;
        $long_description = $localization->long_description;
        if (empty($short_description) || $short_description === null) {
            $short_description = $this->generateShortDescription($long_description);
            if ($short_description) {
                $localization->short_description = $short_description;
                $localization->save();
            }
        }

        // Translate into each target locale
        foreach ($targetLocales as $targetLocale) {
            $this->processLocaleForProduct(
                $productSku,
                $targetLocale,
                $locale,
                $title,
                $short_description,
                $long_description
            );
        }
    }

    public function generateShortDescription(string $longDescription): ?string
    {
        try {
            $prompt = "Summarize the following product description into a concise, engaging short description (max 150 characters):\n\n" . $longDescription;

            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o-mini', // You can also use 'gpt-4-turbo' or 'gpt-3.5-turbo'
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a marketing assistant who writes short product summaries.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.7,
                'max_tokens' => 120,
            ]);
            $this->openAIService->deactivateRateLimitNotice();

            return trim($response->choices[0]->message->content ?? '');
        } catch (\OpenAI\Exceptions\RateLimitException $e) {
            $this->openAIService->activateRateLimitNotice($e);
        } catch (Exception $e) {
            Log::error("Failed to generate short description: " . $e->getMessage());
            return null;
        }
    }

    /**
     *  function to process translation/localization for a specific locale
     */
    public function processLocaleForProduct(
        int $productSku,
        string $targetLocale,
        string $source,
        ?string $title,
        ?string $shortDescription,
        ?string $longDescription
    ): void {

        $existingLocalization = Localization::where([
            'product_id' => $productSku,
            'locale' => $targetLocale,
        ])->first();

        $dataToUpdate = [];


        $product = Product::where('sku', $productSku)->first();

        if ($product) {
            $dataToUpdate['title'] = $product->name;
        }

        if (empty($existingLocalization?->short_description) && !empty($shortDescription)) {
            $dataToUpdate['short_description'] = $this->translate($shortDescription, $source, $targetLocale);
        }

        if (empty($existingLocalization?->long_description) && !empty($longDescription)) {
            $dataToUpdate['long_description'] = $this->translate($longDescription, $source, $targetLocale);
        }

        if (!empty($dataToUpdate)) {
            Localization::updateOrCreate(
                [

                    'product_id' => $productSku,
                    'locale' => $targetLocale,
                ],
                $dataToUpdate
            );
        }
    }




    public function processSystemRequirementsTranslation(int $productSku): void
    {
        Log::debug("Starting system requirements translation", [
            'product_sku' => $productSku,
        ]);

        $product = Product::where('sku', $productSku)->first();

        if (!$product) {
            Log::debug("Product not found", ['product_sku' => $productSku]);
            return;
        }

        $localization = Localization::where('product_id', $productSku)->where('locale', 'en')->first();
        if (! $localization || empty($localization->system_requirements)) {
            Log::debug("Product has no system_requirements field", ['product_sku' => $productSku]);
            return;
        }

        $source = 'en';
        //        $systemRequirementsRaw = $product->getsystemRequirementsRawAttribute();
        $systemRequirementsRaw = $localization->system_requirements;

        if (!$systemRequirementsRaw) {
            Log::debug("No raw system requirements found", ['product_sku' => $productSku]);
            return;
        }

        Log::debug("Saving source system requirements", [
            'product_sku' => $productSku,
            'locale' => $source,
        ]);

        Localization::updateOrCreate(
            [
                'product_id' => $productSku,
                'locale' => $source,
            ],
            [
                'title' => $product->name,
                'system_requirements' => $systemRequirementsRaw,
            ]
        );

        // Target locales
        $locales = ['pt-br', 'es-419'];

        foreach ($locales as $targetLocale) {
            Log::debug("Processing translation for target locale", [
                'product_sku' => $productSku,
                'target_locale' => $targetLocale,
            ]);

            $existingLocalization = Localization::where('product_id', $productSku)
                ->where('locale', $targetLocale)
                ->first();

            if ($existingLocalization && $existingLocalization->system_requirements) {
                Log::debug("Skipping translation, already exists", [
                    'product_sku' => $productSku,
                    'target_locale' => $targetLocale,
                ]);
                continue;
            }

            $translatedRequirements = $this->translate($systemRequirementsRaw, $source, $targetLocale);

            if ($translatedRequirements) {
                Log::debug("Translation successful", [
                    'product_sku' => $productSku,
                    'target_locale' => $targetLocale,
                    'length' => strlen($translatedRequirements),
                ]);

                Localization::updateOrCreate(
                    [
                        'product_id' => $productSku,
                        'locale' => $targetLocale,
                    ],
                    [
                        'title' => $existingLocalization?->title ?? $product->name,
                        'system_requirements' => $translatedRequirements,
                    ]
                );
            } else {
                Log::debug("Translation returned empty", [
                    'product_sku' => $productSku,
                    'target_locale' => $targetLocale,
                ]);
            }
            sleep(1);
        }

        Log::debug("System requirements translation process finished", [
            'product_sku' => $productSku,
        ]);
    }

    public function processLegalTextTranslation(int $productSku): void
    {
        Log::debug("Starting legal_texts translation", [
            'product_sku' => $productSku,
        ]);

        $product = Product::where('sku', $productSku)->first();

        if (!$product) {
            Log::debug("Product not found", ['product_sku' => $productSku]);
            return;
        }

        // Determine source and target locales based on product source
        if ($product->source == 1) {
            $sourceLocale = 'en';
            $targetLocales = ['pt-br', 'es-419'];
        } elseif ($product->source == 2) {
            $sourceLocale = 'pt-br';
            $targetLocales = ['en', 'es-419'];
        } else {
            Log::debug("Unsupported product source for translation", [
                'product_sku' => $productSku,
                'source' => $product->source,
            ]);
            return;
        }

        // Try to find or create/update source localization
        $sourceLocalization = Localization::where('product_id', $productSku)
            ->where('locale', $sourceLocale)
            ->first();

        if (!$sourceLocalization || empty($sourceLocalization->legal_texts)) {
            Log::debug("No valid source localization found — creating/updating from product terms_and_conditions", [
                'product_sku' => $productSku,
                'source_locale' => $sourceLocale,
            ]);

            if (empty($product->terms_and_conditions)) {
                Log::debug("Product has no terms_and_conditions to use as base for legal_texts", [
                    'product_sku' => $productSku,
                ]);

                $this->populateStaticLegalText($product->sku);
                return;
            }

            $sourceLocalization = Localization::updateOrCreate(
                [
                    'product_id' => $productSku,
                    'locale' => $sourceLocale,
                ],
                [
                    'title' => $product->name,
                    'legal_texts' => $product->terms_and_conditions,
                ]
            );

            Log::debug("Source localization updated/created from product terms_and_conditions", [
                'product_sku' => $productSku,
                'source_locale' => $sourceLocale,
            ]);
        }

        if (empty($sourceLocalization->legal_texts)) {
            Log::debug("No legal_texts found in source localization after updateOrCreate", [
                'product_sku' => $productSku,
                'source_locale' => $sourceLocale,
            ]);
            return;
        }

        $legalTextsRaw = $sourceLocalization->legal_texts;

        Log::debug("Using existing {$sourceLocale} legal_texts as source", [
            'product_sku' => $productSku,
        ]);

        foreach ($targetLocales as $targetLocale) {
            Log::debug("Processing legal_texts translation for target locale", [
                'product_sku' => $productSku,
                'target_locale' => $targetLocale,
            ]);

            $existingLocalization = Localization::where('product_id', $productSku)
                ->where('locale', $targetLocale)
                ->first();

            if ($existingLocalization && $existingLocalization->legal_texts) {
                Log::debug("Skipping translation, already exists", [
                    'product_sku' => $productSku,
                    'target_locale' => $targetLocale,
                ]);
                continue;
            }

            $translatedLegalTexts = $this->translate($legalTextsRaw, $sourceLocale, $targetLocale);

            if ($translatedLegalTexts) {
                Log::debug("Legal_texts translation successful", [
                    'product_sku' => $productSku,
                    'target_locale' => $targetLocale,
                    'length' => strlen($translatedLegalTexts),
                ]);

                Localization::updateOrCreate(
                    [
                        'product_id' => $productSku,
                        'locale' => $targetLocale,
                    ],
                    [
                        'title' => $existingLocalization?->title ?? $product->name,
                        'legal_texts' => $translatedLegalTexts,
                    ]
                );
            } else {
                Log::debug("Legal_texts translation returned empty", [
                    'product_sku' => $productSku,
                    'target_locale' => $targetLocale,
                ]);
            }

            sleep(1);
        }

        Log::debug("Legal_texts translation process finished", [
            'product_sku' => $productSku,
        ]);
    }

    protected function populateStaticLegalText(int $sku)
    {
        $localizations = [
            'en' => '© All rights reserved. All names, logos, and trademarks are the property of their respective owners. 2Game acts only as an authorized reseller of digital content.',
            'es-419' => '© Todos los derechos reservados. Todos los nombres, logotipos y marcas son propiedad de sus respectivos titulares. 2Game actúa únicamente como revendedor autorizado de contenido digital.',
            'pt-br' => '© Todos os direitos reservados. Todos os nomes, logotipos e marcas são de propriedade de seus respectivos detentores. A 2Game atua apenas como revendedora autorizada de conteúdo digital.'
        ];

        foreach ($localizations as $locale => $text) {
            $localization = Localization::firstOrNew([
                'product_id' => $sku,
                'locale' => $locale
            ]);

            if (empty($localization->legal_texts)) {
                $localization->legal_texts = $text;
                $localization->save();
            }
        }
    }





    public function translateTags(string $text, string $from, string $to): ?string
    {
        if (empty($text)) {
            return null;
        }

        try {
            $tags = array_filter(array_map('trim', explode(',', $text)));
            if (empty($tags)) {
                return null;
            }

            dd($text);

            $tagList = implode("\n- ", $tags);
            $prompt = <<<EOT
            Translate the following comma-separated tags from {$from} to {$to}.
            Return ONLY the translated tags as a comma-separated list, in the same order.

            Original Tags:
            - {$tagList}
            EOT;

            $maxRetries = 3;
            $retryDelayMs = 2000;
            $response = null;
            $attempt = 0;

            while ($attempt < $maxRetries) {
                try {
                    $attempt++;

                    $response = OpenAI::chat()->create([
                        'model' => 'gpt-4o',
                        'messages' => [
                            [
                                'role' => 'system',
                                'content' => 'You are a translation assistant. Translate each tag accurately and return them as a single comma-separated line.'
                            ],
                            ['role' => 'user', 'content' => $prompt],
                        ],
                    ]);
                    $this->openAIService->deactivateRateLimitNotice();

                    break;
                }catch (\OpenAI\Exceptions\RateLimitException $e) {
                    $this->openAIService->activateRateLimitNotice($e);
                } catch (\Exception $innerEx) {
                    Log::warning("Translation attempt {$attempt} failed: {$innerEx->getMessage()}");

                    if ($attempt >= $maxRetries) {
                        throw $innerEx;
                    }

                    usleep($retryDelayMs * 1000);
                }
            }

            $translatedText = trim($response->choices[0]->message->content ?? '');

            $translatedText = preg_replace('/\s*,\s*/', ', ', $translatedText);
            $translatedText = trim($translatedText, ", \t\n\r\0\x0B");

            $usage = $response->usage ?? null;
            $promptTokens = $usage->promptTokens ?? 0;
            $completionTokens = $usage->completionTokens ?? 0;
            $totalTokens = $usage->totalTokens ?? 0;

            $cost = ($promptTokens / 1000 * 0.03) + ($completionTokens / 1000 * 0.06);

            Log::info("Translation executed", [
                'from' => $from,
                'to' => $to,
                'tags_count' => count($tags),
                'prompt_tokens' => $promptTokens,
                'completion_tokens' => $completionTokens,
                'total_tokens' => $totalTokens,
                'cost_usd' => round($cost, 6),
            ]);

            $this->openAIService->deactivateRateLimitNotice();

            return $translatedText;
        } catch (\OpenAI\Exceptions\RateLimitException $e) {
            $this->openAIService->activateRateLimitNotice($e);
        } catch (\Exception $e) {
            Log::error("Translation failed: {$e->getMessage()}", [
                'from' => $from,
                'to' => $to,
                'text_length' => strlen($text),
            ]);
            return null;
        }
    }





    public function translate(string $text, string $from, string $to): ?string
    {
        if (empty($text)) {
            return null;
        }

        try {
            $prompt = <<<EOT
            Translate the following text from {$from} to {$to}.
            Return ONLY the translated text.
            Preserve all line breaks, bullet points, and formatting exactly as in the original.

            Text:
            {$text}
            EOT;

            $maxRetries = 3;
            $retryDelayMs = 2000;

            $response = null;
            $attempt = 0;

            while ($attempt < $maxRetries) {
                try {
                    $attempt++;

                    $response = OpenAI::chat()->create([
                        'model' => 'gpt-4o',
                        'messages' => [
                            ['role' => 'system', 'content' => 'You are a translation assistant. Respond with only the translated text.'],
                            ['role' => 'user', 'content' => $prompt],
                        ],
                    ]);
                    
                    $this->openAIService->deactivateRateLimitNotice();
                    break;
                } catch (\OpenAI\Exceptions\RateLimitException $e) {
                    $this->openAIService->activateRateLimitNotice($e);
                } catch (\Exception $innerEx) {
                    Log::warning("Translation attempt {$attempt} failed: {$innerEx->getMessage()}");

                    if ($attempt >= $maxRetries) {
                        throw $innerEx;
                    }

                    usleep($retryDelayMs * 1000);
                }
            }

            $translatedText = trim($response->choices[0]->message->content ?? '');

            $usage = $response->usage ?? null;
            $promptTokens = $usage->promptTokens ?? 0;
            $completionTokens = $usage->completionTokens ?? 0;
            $totalTokens = $usage->totalTokens ?? 0;

            $cost = ($promptTokens / 1000 * 0.03) + ($completionTokens / 1000 * 0.06);

            Log::info("Translation executed", [
                'from' => $from,
                'to' => $to,
                'prompt_tokens' => $promptTokens,
                'completion_tokens' => $completionTokens,
                'total_tokens' => $totalTokens,
                'cost' => $cost,
            ]);

            return $translatedText;
        } catch (\Exception $e) {
            Log::error("Translation failed: {$e->getMessage()}", [
                'from' => $from,
                'to' => $to,
                'text_length' => strlen($text),
            ]);
            return null;
        }
    }
}

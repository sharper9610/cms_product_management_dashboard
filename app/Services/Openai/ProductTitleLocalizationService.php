<?php

namespace App\Services\Openai;

use App\Models\Product;
use App\Models\Localization;
use App\Services\Openai\OpenAIService;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;
use Exception;

class ProductTitleLocalizationService
{
    private const LOCALE_MAP = [
        'CN' => 'zh-Hans',
        'TW' => 'zh-Hant',
        'HK' => 'zh-Hant',
        'JP' => 'ja',
        'KR' => 'ko',
    ];

    private const BATCH_SIZE = 10;

    public function __construct(
        private readonly OpenAIService $openAIService
    ) {}

    public function localizeProduct(int $sku): void
    {
        $product = Product::where('sku', $sku)->firstOrFail();


        if (!$this->needsLocalization($product, false)) {
            return;
        }

        $this->processProducts(collect([$product]));
    }

    public function localizeAll(bool $forceAll = false): void
    {
        Product::query()
            ->select(['id', 'sku', 'name', 'publisher_name', 'franchise'])
            ->chunkById(200, function ($products) use ($forceAll) {
                $filtered = $products->filter(fn($p) => $this->needsLocalization($p, $forceAll));
                $this->processProducts($filtered);
            });
    }

    private function needsLocalization(Product $product, bool $forceAll): bool
    {
        $locales = $this->getTargetLocales($product->allowed_countries);


        if (empty($locales)) {
            return false;
        }

        if ($forceAll) {
            return true;
        }

        $existing = Localization::where('product_id', $product->sku)
            ->whereIn('locale', $locales)
            ->whereNotNull('title')
            ->pluck('locale')
            ->toArray();

        return count(array_diff($locales, $existing)) > 0;
    }

    private function processProducts(\Illuminate\Support\Collection $products): void
    {
        $jobs = $products
            ->map(fn($p) => $this->buildJob($p))
            ->filter()
            ->values();

        if ($jobs->isEmpty()) {
            return;
        }

        foreach ($jobs->chunk(self::BATCH_SIZE) as $batch) {
            $results = $this->callOpenAI($batch->values()->all());
            $this->upsertTitles($results);
        }
    }

    private function buildJob(Product $product): ?array
    {
        // $locales = $this->getTargetLocales($product->getAllowedCountries());
        $locales = $this->getTargetLocales(['CN', 'TW', 'HK', 'JP', 'KR']);



        if (empty($locales)) {
            return null;
        }

        $existing = Localization::where('product_id', $product->id)
            ->whereIn('locale', $locales)
            ->whereNotNull('title')
            ->pluck('locale')
            ->toArray();

        $missing = array_values(array_diff($locales, $existing));


        if (empty($missing)) {
            return null;
        }

        return [
            'product_id'     => $product->sku,
            'title'          => $product->name,
            'publisher'      => $product->publisher_name ?? '',
            'franchise'      => $product->franchise ?? '',
            'target_locales' => $missing,
        ];
    }

    private function getTargetLocales(array $allowedCountries): array
    {
        $locales = [];
        $seen    = [];

        foreach ($allowedCountries as $country) {
            $locale = self::LOCALE_MAP[$country] ?? null;
            if ($locale && !isset($seen[$locale])) {
                $locales[]     = $locale;
                $seen[$locale] = true;
            }
        }

        return $locales;
    }

    private function callOpenAI(array $jobs): array
    {
        try {
            $response = OpenAI::chat()->create([
                'model'       => 'gpt-4o-mini',
                'temperature' => 0,
                'messages'    => [
                    [
                        'role'    => 'system',
                        'content' => $this->systemPrompt(),
                    ],
                    [
                        'role'    => 'user',
                        'content' => $this->buildBatchPrompt($jobs),
                    ],
                ],
            ]);

            $content = trim($response->choices[0]->message->content ?? '');

            return $this->parseResponse($content, $jobs);
        } catch (\OpenAI\Exceptions\RateLimitException $e) {
            $this->openAIService->activateRateLimitNotice($e);
            return [];
        } catch (Exception $e) {
            Log::error('ProductTitleLocalizationService: OpenAI error', [
                'message' => $e->getMessage(),
                'jobs'    => array_column($jobs, 'product_id'),
            ]);
            return [];
        }
    }

    private function systemPrompt(): string
    {
        return <<<PROMPT
        You are localizing video game titles for a global gaming storefront.

        Think like a gamer and a game store operator — NOT a translator.

        RULES
        1. Use real franchise names gamers in each market would recognize.
        2. Do not translate literally. Preserve franchise identity.
        3. If a market normally keeps the English title, keep it exactly.
        4. Preserve numbering, subtitles, and edition names.
        5. Only localize the listed target_locales per product.
        6. No explanations. No extra text.

        OUTPUT FORMAT — follow exactly:

        PRODUCT <number>
        <locale>: <title>
        PROMPT;
    }

    private function buildBatchPrompt(array $jobs): string
    {
        $lines = '';

        foreach ($jobs as $idx => $job) {
            $num     = $idx + 1;
            $locales = implode(', ', $job['target_locales']);

            $lines .= <<<TEXT

            PRODUCT {$num}
            title: {$job['title']}
            publisher: {$job['publisher']}
            franchise: {$job['franchise']}
            target_locales: {$locales}

            TEXT;
        }

        return trim($lines);
    }

    private function parseResponse(string $text, array $jobs): array
    {
        $results = [];
        $blocks  = preg_split('/PRODUCT\s+(\d+)/i', $text, -1, PREG_SPLIT_DELIM_CAPTURE);

        for ($i = 1; $i < count($blocks) - 1; $i += 2) {
            $jobIndex = (int) $blocks[$i] - 1;
            $content  = $blocks[$i + 1] ?? '';

            if (!isset($jobs[$jobIndex])) {
                continue;
            }

            $job    = $jobs[$jobIndex];
            $parsed = [];

            foreach (explode("\n", trim($content)) as $line) {
                if (preg_match('/^([\w-]+):\s*(.+)$/u', trim($line), $m)) {
                    $locale = trim($m[1]);
                    $title  = trim($m[2]);

                    if (in_array($locale, $job['target_locales'])) {
                        $parsed[$locale] = $title;
                    }
                }
            }

            if (!empty($parsed)) {
                $results[] = [
                    'product_id' => $job['product_id'],
                    'locales'    => $parsed,
                ];
            }
        }

        return $results;
    }

    private function upsertTitles(array $results): void
    {
        foreach ($results as $result) {
            $enLocalization = Localization::where(['product_id' => $result['product_id'], 'locale' => 'en'])->first();
            if (!$enLocalization) {
                $product = Product::where('sku', $result['product_id'])->first();
                if ($product) {
                    $baseLocal = $product->source == 1 ? 'en' : 'pt-br';
                    Localization::create([
                        'locale' => $baseLocal,
                        'product_id' => $result['product_id'],
                        'title' =>  $product->name,
                    ]);
                }
            }
            foreach ($result['locales'] as $locale => $title) {
                Localization::updateOrCreate(
                    ['product_id' => $result['product_id'], 'locale' => $locale],
                    ['title' => $title]
                );
            }
        }
    }
}

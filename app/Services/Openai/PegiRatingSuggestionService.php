<?php

namespace App\Services\Openai;

use App\Models\Product;
use Exception;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class PegiRatingSuggestionService
{
    protected OpenAIService $openAIService;

    public function __construct(OpenAIService $openAIService)
    {
        $this->openAIService = $openAIService;
    }
    /**
     * Get PEGI rating for a product by SKU
     *
     * @param string $sku
     * @return int|null
     */
    public function getPegiRatingBySKU(int $sku): ?int
    {
        try {
            $product = Product::where('sku', $sku)->first();

            if (!$product) {
                Log::warning("getPegiRatingBySKU: Product not found for SKU {$sku}.");
                return null;
            }

            if (!is_null($product->pegi_rating)) {
                return (int) $product->pegi_rating;
            }

            if ((int) $product->source !== 1) {
                Log::info("getPegiRatingBySKU: Skipped PEGI rating for non-game product [SKU: {$sku}].");
                return null;
            }

            $prompt = $this->buildPegiRatingPrompt($product->name);
            $rating = $this->getPegiRatingFromOpenAI($product->name, $prompt);


            $this->storeProductPegiRating($rating, $product->sku);

            return $rating;
        } catch (Exception $e) {
            Log::error("getPegiRatingBySKU: Unexpected error for SKU {$sku}: {$e->getMessage()}");
            return null;
        }
    }


    /**
     * Store PEGI rating in product table
     */
    /**
     * Store PEGI rating in product table using PHP serialize format
     */
    protected function storeProductPegiRating(?int $rating, int $sku): void
    {
        if ($rating !== null) {
            $pegiData = [
                "Rating" => [
                    [
                        "Type" => "pegi",
                        "Logo" => [
                            "URL" => "https://static.exertisztorm.net/logos/ratings/pegi_{$rating}.gif"
                        ],
                        "Text" => "PEGI {$rating}"
                    ]
                ]
            ];

            Product::where('sku', $sku)->update([
                'pegi_ratings' => serialize($pegiData)
            ]);

            Log::info("Stored PEGI rating (serialized)", [
                'sku' => $sku,
                'pegi_rating' => $pegiData
            ]);
        }
    }



    /**
     * Build prompt for PEGI rating prediction
     */
    protected function buildPegiRatingPrompt(string $title): string
    {
        return <<<EOT
            You are a PEGI rating assistant.

            Based on the game title provided, estimate the most likely official PEGI age rating used in Europe. 
            If an exact PEGI rating is widely known, use it; otherwise infer from franchise history, genre, themes, 
            typical content (violence, fear/horror, bad language, sex/nudity, drugs, gambling, online interactions), 
            and platform norms.

            Return only one of these numbers: 3, 7, 12, 16, or 18. No words, symbols, or extra text. 
            If uncertain, give your best estimate based on similar games.

            Game Title: "{$title}"
        EOT;
    }


    /**
     * Call OpenAI with a given prompt and return PEGI rating
     */
    protected function getPegiRatingFromOpenAI(string $productTitle, string $prompt): ?int
    {
        try {
            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a PEGI rating assistant. Respond only with a number (3, 7, 12, 16, or 18).'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ],
                ],
            ]);

            $content = trim($response->choices[0]->message->content ?? '');
            $rating = (int) filter_var($content, FILTER_SANITIZE_NUMBER_INT);

            if (in_array($rating, [3, 7, 12, 16, 18], true)) {
                return $rating;
            }

            Log::info("getPegiRatingFromOpenAI: Invalid PEGI rating returned for '{$productTitle}': '{$content}'");
            $this->openAIService->deactivateRateLimitNotice();
            return null;
        } catch (\OpenAI\Exceptions\RateLimitException $e) {
           $this->openAIService->activateRateLimitNotice($e);
        } catch (Exception $e) {
            Log::error("getPegiRatingFromOpenAI: OpenAI error for '{$productTitle}': {$e->getMessage()}");
            return null;
        }
    }
}

<?php

namespace App\Services\Openai;

use App\Models\Product;
use Exception;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class RatingSuggestionService
{
    protected OpenAIService $openAIService;

    public function __construct(OpenAIService $openAIService)
    {
        $this->openAIService = $openAIService;
    }
    /**
     * Get numeric rating for a product by SKU
     *
     * @param string $sku
     * @return float|null
     */
    public function getRatingBySKU(string $sku): ?float
    {
        try {
            $product = Product::where('sku', $sku)->first();

            if (!$product) {
                Log::warning("getRatingBySKU: Product not found for SKU {$sku}.");
                return null;
            }

            if (!is_null($product->average_rating)) {
                return (float) $product->average_rating;
            }

            $prompt = (int)$product->source === 1
                ? $this->buildGameRatingPrompt($product->name)
                : $this->buildGiftCardRatingPrompt($product->name);

            $rating = $this->getRatingFromOpenAI($product->name, $prompt);

            $this->storeProductAvgRating($rating, $product->sku);

            return $rating;
        } catch (Exception $e) {
            Log::error("getRatingBySKU: Unexpected error for SKU {$sku}: {$e->getMessage()}");
            return null;
        }
    }


    protected function storeProductAvgRating(?float $rating, string $sku): void
    {
        if ($rating !== null) {
            Product::where('sku', $sku)->update(['average_rating' => $rating]);
            Log::info("Stored average rating", ['sku' => $sku, 'rating' => $rating]);
        }
    }


    /**
     * Build prompt for GAME product rating
     */
    protected function buildGameRatingPrompt(string $title): string
    {
        return <<<EOT
            You are a video game rating assistant.

            Based on the game title below, try to estimate the **most likely average user rating** from popular platforms such as **Steam, Metacritic, IGN, or other reputable gaming review sites**. 
            Provide a numeric rating from 0 to 5 (where 0 = worst, 5 = best). 
            The rating can be decimal (e.g., 4.2). 
            If no information is available, provide your best guess based on similar games. 
            Return **only the number**, no extra text.

            Game Title: "{$title}"
            EOT;
    }

    /**
     * Build prompt for GIFT CARD product rating
     */
    protected function buildGiftCardRatingPrompt(string $title): string
    {
        return <<<EOT
            You are a digital gift card rating assistant.

            Based on the gift card title below, try to estimate the **most likely customer rating** from popular platforms such as **Amazon, PlayStation Store, Google Play, or other reputable online stores**. 
            Provide a numeric rating from 0 to 5 (where 0 = worst, 5 = best). 
            The rating can be decimal (e.g., 4.7). 
            If no exact rating is available, provide your best estimate based on similar products. 
            Return **only the number**, no extra text.

            Gift Card Title: "{$title}"
            EOT;
    }


    /**
     * Call OpenAI with a given prompt and return rating
     */
    protected function getRatingFromOpenAI(string $productTitle, string $prompt): ?float
    {
        try {
            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a product rating assistant. Respond only with a numeric rating 0-5.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ],
                ],
            ]);

            $content = trim($response->choices[0]->message->content ?? '');
            $rating = floatval($content);

            if ($rating >= 0 && $rating <= 5) {
                return $rating;
            }

            Log::info("getRatingFromOpenAI: Invalid rating returned for '{$productTitle}': '{$content}'");
            $this->openAIService->deactivateRateLimitNotice();
            return null;
        }catch (\OpenAI\Exceptions\RateLimitException $e) {
           $this->openAIService->activateRateLimitNotice($e);
        } 
        catch (Exception $e) {
            Log::error("getRatingFromOpenAI: OpenAI error for '{$productTitle}': {$e->getMessage()}");
            return null;
        }
    }
}

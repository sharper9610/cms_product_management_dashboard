<?php

namespace App\Services\Openai;

use App\Models\Localization;
use App\Models\Product;
use Exception;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class TagSuggestionService
{
    protected OpenAIService $openAIService;

    public function __construct(OpenAIService $openAIService)
    {
        $this->openAIService = $openAIService;
    }
    /**
     * Suggest tags for a single product by SKU
     *
     * @param string $sku
     * @return array
     */
    public function suggestTagsBySKU($sku): array
    {
        
        try {
            $product = Product::where('sku', $sku)->first();
            if (!$product) {
                Log::warning("suggestTagsBySKU: Product not found for SKU {$sku}.");
                return [];
            }

            $locale = $product->source === 1 ? 'en' : 'pt-br';

            $localization = Localization::where('product_id', $product->sku)
                ->where('locale', $locale)
                ->first();

            if (!$localization) {
                Log::warning("suggestTagsBySKU: Localization not found for product_id {$product->id} and locale {$locale}.");
                return [];
            }

            $textParts = [
                'title'       => $product->name,
                'short_desc'  => $localization->short_description ?? '',
                'long_desc'   => $localization->long_description ?? '',
            ];

            $textToAnalyze = collect($textParts)
                ->filter(fn($text) => !empty(trim($text)))
                ->implode("\n");
        

            if (empty($textToAnalyze)) {
                Log::info("suggestTagsBySKU: No text to analyze for SKU {$sku}.");
                return [];
            }

            try {
                if ((int) $product->source === 1) {
                    return $this->getGameTagsFromOpenAI($textToAnalyze);
                }

                return $this->getGiftCardTagsFromOpenAI($textToAnalyze);
            } catch (Exception $e) {
                Log::error("suggestTagsBySKU: OpenAI error for SKU {$sku}: {$e->getMessage()}");
                return [];
            }
        } catch (Exception $e) {
            Log::error("suggestTagsBySKU: Unexpected error for SKU {$sku}: {$e->getMessage()}");
            return [];
        }
    }




    /**
     * Generate tags specifically for a game product.
     *
     * @param string $text  // contains: title, short_desc, long_desc
     * @return array
     */
    protected function getGameTagsFromOpenAI(string $text): array
    {
        $prompt = <<<EOT
        You are a product tag suggestion assistant.

        The text below contains:
        - **Title** of the video game
        - A short description
        - A long description

        Using this information, suggest **20–30 short, single-word or hyphenated tags**
        that best describe the VIDEO GAME product.

        Focus on:
        • Game genre
        • Gameplay style or key mechanics
        • Platform(s)
        • Notable features or themes

        Return ONLY a comma-separated list of tags — no explanations, no numbering.

        Game Details:
        {$text}
        EOT;

        return $this->requestOpenAITags($prompt);
    }

    /**
     * Generate tags specifically for a gift-card product.
     *
     * @param string $text  // contains: title, short_desc, long_desc
     * @return array
     */
    protected function getGiftCardTagsFromOpenAI(string $text): array
    {
        $prompt = <<<EOT
            You are a product tag suggestion assistant.

            The text below includes:
            - **Title** of the gift card
            - A short description
            - A long description

            Using this information, suggest **15–20 short, single-word or hyphenated tags**
            that best describe the GIFT CARD product.

            Focus on:
            • Store or brand name (e.g., Amazon, PlayStation)
            • Region or country of use
            • Currency or denomination
            • Digital vs. physical delivery
            • Key usage context (e.g., gaming, shopping)

            Return ONLY a comma-separated list of tags — no explanations, no numbering.

            Gift Card Details:
            {$text}
            EOT;

        return $this->requestOpenAITags($prompt);
    }

    /**
     * Core OpenAI call (shared by game/gift card tag generators).
     *
     * @param string $prompt
     * @return array
     */
    private function requestOpenAITags(string $prompt): array
    {
        try {
            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a product tag suggestion assistant. Respond with only a comma-separated list of relevant tags.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ],
                ],
            ]);

            $content = $response->choices[0]->message->content ?? '';

            $this->openAIService->deactivateRateLimitNotice();

            return collect(explode(',', $content))
                ->map(fn($tag) => trim($tag))
                ->filter()
                ->unique()
                ->values()
                ->toArray();
        }catch (\OpenAI\Exceptions\RateLimitException $e) {
           $this->openAIService->activateRateLimitNotice($e);
        } 
        catch (\Throwable $e) {
            logger()->error("Tag suggestion failed: {$e->getMessage()}");
            return [];
        }
    }
}

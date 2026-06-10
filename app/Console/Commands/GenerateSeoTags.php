<?php

namespace App\Console\Commands;

use App\Models\Localization;
use App\Models\Product;
use App\Services\Openai\OpenAIService;
use Illuminate\Console\Command;

class GenerateSeoTags extends Command
{

    

    /**
     * The name and signature of the console command.
     */
    protected $signature = 'seo:generate-tags-and-store';

    /**
     * The console command description.
     */
    protected $description = 'Generate SEO tags for products using OpenAI and store it in localization table';

    /**
     * Execute the console command.
     */
    public function handle(OpenAIService $openAIService): int
    {
        if (config('app.env') !== 'production') {
            $this->warn("This command only runs in production environment.");
            return Command::SUCCESS;
        }

        $products = Product::where('sku', 4000000)->get();
        $languages = ['en', 'pt-br', 'es-419'];

        foreach ($products as $product) {
            $this->info("Generating SEO tags for Product #{$product->sku} ({$product->name})");

            $drm = $product->getDrmTypeFormattedAttribute();
            if (!isset($drm)) {
                $drm = $product->source == 1 ? 'Game' : 'Gift Card';
            }

            foreach ($languages as $locale) {
                $this->info("Generating tags for locale: {$locale}");

                $localization = Localization::where([
                    'product_id' => $product->sku,
                    'locale' => $locale
                ])->first();

                if (isset($localization) && !is_null($localization->seo_tags)) {
                    continue;
                }

                $attempts = 0;
                $maxAttempts = 5;
                $delay = 5;

                while ($attempts < $maxAttempts) {
                    try {
                        $response = $openAIService->runPrompt(
                            promptId: 1,
                            placeholders: [
                                'Name' => $product->name,
                                'DRM'  => $drm,
                            ],
                            language: $locale,
                            type: $product->source == 1 ? 'game' : 'gift_card',
                        );

                        sleep(3);
                        if (! $response || empty($response['response_content'])) {
                            $this->warn("No response for Product #{$product->id}, locale: {$locale}");
                            break;
                        }

                        $newTags = array_map('trim', explode(',', $response['response_content']));

                        if (! $localization) {
                            $localization = new Localization();
                            $localization->product_id = $product->sku;
                            $localization->locale = $locale;
                            $localization->title = $locale == 'en' ? $product->name : ' ';
                            $localization->seo_tags = serialize($newTags);
                            $localization->save();
                            $this->info("✅ Created localization for Product #{$product->id}, locale: {$locale}");
                            break;
                        }

                        $existingTags = $localization->seo_tags ? @unserialize($localization->seo_tags) : [];
                        if (!is_array($existingTags)) {
                            $existingTags = [];
                        }

                        $mergedTags = array_unique(array_filter(array_merge($existingTags, $newTags)));
                        $localization->seo_tags = serialize($mergedTags);
                        $localization->save();

                        $this->info("✅ Saved merged tags for Product #{$product->id}, locale: {$locale}");
                        break;
                    } catch (\Exception $e) {
                        $attempts++;
                        $this->warn("OpenAI request failed (attempt {$attempts}): " . $e->getMessage());
                        sleep($delay);
                        $delay *= 2;
                    }
                }

                usleep(2_000_000);
            }

            usleep(2_500_000);
        }

        return Command::SUCCESS;
    }
}

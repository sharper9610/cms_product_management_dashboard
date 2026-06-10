<?php

namespace App\Console\Commands;

use App\Models\Localization;
use App\Models\Product;
use App\Services\Openai\OpenAIService;
use Illuminate\Console\Command;

class GenerateFranciseTags extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'francise:generate-tags-and-store';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Francise tags for products using OpenAI and store it in localization table';

    /**
     * Execute the console command.
     */
    public function handle(OpenAIService $openAIService): int
    {
        if (config('app.env') !== 'production') {
            $this->warn("This command only runs in production environment.");
            return Command::SUCCESS;
        }

        $products = Product::all();
        $languages = ['en', 'pt-br', 'es-419'];

        foreach ($products as $product) {
            $this->info("Generating Franchise tags for Product #{$product->sku} ({$product->name})");

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

                if (isset($localization) && (!is_null($localization->franchise_tags) || !empty($localization->franchise_tags))) {
                    continue;
                }

                $attempts = 0;
                $maxAttempts = 5;
                $delay = 5;

                while ($attempts < $maxAttempts) {
                    try {
                        $response = $openAIService->runPrompt(
                            promptId: 3,
                            placeholders: [
                                'Name' => $product->name,
                                'DRM'  => $drm,
                            ],
                            language: $locale,
                            type: $product->source == 1 ? 'game' : 'gift_card',
                        );

                        if (!$response || empty($response['response_content'])) {
                            break;
                        }

                        $newTags = array_map('trim', explode(',', $response['response_content']));

                        if (!$localization) {
                            $localization = new Localization();
                            $localization->product_id = $product->sku;
                            $localization->locale = $locale;
                            $localization->title = $locale == 'en' ? $product->name : ' ';
                            $localization->franchise_tags = serialize($newTags);
                            $localization->save();
                            break;
                        }

                        $existingTags = $localization->franchise_tags ? @unserialize($localization->franchise_tags) : [];
                        if (!is_array($existingTags)) {
                            $existingTags = [];
                        }

                        $mergedTags = array_unique(array_filter(array_merge($existingTags, $newTags)));
                        $localization->franchise_tags = serialize($mergedTags);
                        $localization->save();

                        sleep($delay);
                        break;
                    } catch (\Exception $e) {
                        $attempts++;
                        sleep($delay);
                        $delay *= 2;
                    }
                }
            }

            usleep(2_500_000);
        }

        return Command::SUCCESS;
    }
}

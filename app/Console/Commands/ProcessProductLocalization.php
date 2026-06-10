<?php

namespace App\Console\Commands;

use App\Models\Option;
use App\Models\Product;
use App\Services\Openai\ProcessTagsAndTranslation;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessProductLocalization extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'product:localization
                        {sku? : Optional product SKU. If omitted, all products will be processed}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process all translations and tag generations for products using OpenAI';


    protected ProcessTagsAndTranslation $localizationService;

    public function __construct(ProcessTagsAndTranslation $localizationService)
    {
        parent::__construct();
        $this->localizationService = $localizationService;
    }


    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            if (config('app.env') !== 'production') {
                $this->warn("⚠️ This command can only be run in the production environment.");
                return Command::SUCCESS;
            }

            $sku = $this->argument('sku');

            if ($sku) {
                $this->info("🔄 Processing localization for SKU {$sku}");

                try {
                    $this->localizationService->processAllForProduct((int)$sku);
                } catch (Exception $e) {
                    Log::error("❌ Failed processing SKU {$sku}: " . $e->getMessage());
                    $this->error("Error processing SKU {$sku}: " . $e->getMessage());
                }
            } else {
                $this->info("🔄 Processing localization for all products");

                $products = Product::all();
                $totalCount = $products->count();
                $this->info("Found {$totalCount} products to process");

                foreach ($products as $product) {
                    $this->info("Processing SKU {$product->sku}");
                    try {
                        $this->localizationService->processAllForProduct($product->sku);
                    } catch (Exception $e) {
                        Log::error("❌ Failed processing SKU {$product->sku}: " . $e->getMessage());
                        $this->error("Error processing SKU {$product->sku}: " . $e->getMessage());
                    }

                    // Optional delay between products to reduce rate-limit issues
                    usleep(250_000); // 0.25s
                }
            }

            $this->info("✅ All products processed successfully.");
            return Command::SUCCESS;
        } catch (Exception $e) {
            Option::set('product_localization_end', time());
            Option::set('product_localization', 'complete');
            Log::critical("🚨 Unexpected error in localization command: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            $this->error("A critical error occurred: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

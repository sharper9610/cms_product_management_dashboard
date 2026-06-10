<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\Openai\ProcessTagsAndTranslation;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessGenreWithTranslation extends Command
{
    protected ProcessTagsAndTranslation $localizationService;

    public function __construct(ProcessTagsAndTranslation $localizationService)
    {
        parent::__construct();
        $this->localizationService = $localizationService;
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'product:process-genre-with-translation {sku? : Optional product SKU. If omitted, all products will be processed}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process product genre with localization';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // if (config('app.env') !== 'production') {
        //     $this->warn("⚠️ This command can only be run in the production environment.");
        //     return Command::SUCCESS;
        // }

        $sku = $this->argument('sku');

        if ($sku) {
            $this->info("🔄 Processing Genre localization for SKU {$sku}");
            $product = Product::where('sku', $sku)->first();
            $this->localizationService->translateOrGenerateGenreTagsWithOverride($product);
        } else {
            $this->info("🔄 Processing localization for all products");

            $products = Product::where('source', 1)->get();

            $totalCount = $products->count();
            $this->info("Found {$totalCount} products to Genre process");

            foreach ($products as $product) {
                $this->info("Genre Processing SKU {$product->sku}");
                try {
                    $this->localizationService->translateOrGenerateGenreTagsWithOverride($product);
                } catch (Exception $e) {
                    Log::error("Failed Genre processing SKU {$product->sku}: " . $e->getMessage());
                }

                // Optional delay between products to reduce rate-limit issues
                usleep(250_000); // 0.25s
            }
        }

        $this->info("✅ All products genre processed successfully.");
        return Command::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\Openai\PegiRatingSuggestionService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PegiRatingGenerate extends Command
{
    protected PegiRatingSuggestionService  $pegiRatingSuggestionService;

    public function __construct(PegiRatingSuggestionService  $pegiRatingSuggestionService)
    {
        parent::__construct();
        $this->pegiRatingSuggestionService = $pegiRatingSuggestionService;
    }


    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'product:pegi-rating-generate {sku? : Optional product SKU. If omitted, all products will be processed}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Product Pegi rating';

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
            $this->info("🔄 Processing Pegi Rating Genration for SKU {$sku}");
            $product = Product::where('sku', $sku)->first();
            $this->pegiRatingSuggestionService->getPegiRatingBySKU($product->sku);
        } else {
            $this->info("🔄 Processing pegi rating for all products");

            $products = Product::where(function ($query) {
                $query->whereNull('pegi_ratings')
                    ->orWhere('pegi_ratings', '');
            })
                ->where('source', 1)
                ->get();
            $totalCount = $products->count();
            $this->info("Found {$totalCount} products to pegi rating process");

            foreach ($products as $product) {
                $this->info("Pegi Processing SKU {$product->sku}");
                try {
                    $this->pegiRatingSuggestionService->getPegiRatingBySKU($product->sku);
                } catch (Exception $e) {
                    Log::error("Failed Genre processing SKU {$product->sku}: " . $e->getMessage());
                }

                // Optional delay between products to reduce rate-limit issues
                usleep(150_000); // 0.15s
            }
        }

        $this->info("✅ All products genre processed successfully.");
        return Command::SUCCESS;
    }
}

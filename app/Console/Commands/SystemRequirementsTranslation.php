<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\Openai\TranslationService;
use Illuminate\Console\Command;

class SystemRequirementsTranslation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'product:system-requirements-translation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process System Requiremnets Translation';

    /**
     * Execute the console command.
     */
    public function handle(TranslationService $translationService)
    {
        if (config('app.env') !== 'production') {
            $this->warn("This command only runs in production environment.");
            return Command::SUCCESS;
        }

        $delayMs = 500;

        $this->info('Processing System Requirements Translation...');
        $products = Product::all();

        foreach ($products as $index => $product) {
            $this->info("[$index] Translating product SKU={$product->sku}...");
            $translationService->processSystemRequirementsTranslation($product->sku);
            if ($index < $products->count() - 1) {
                usleep($delayMs * 1000);
            }
        }
        $this->info('✅ System Requirements Translation completed successfully.');
    }
}

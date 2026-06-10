<?php

namespace App\Console\Commands;

use App\Domains\Incomm\Services\IncommSyncService;
use App\Services\Json\ProductJsonUploadService;
use Illuminate\Console\Command;

class SyncIncommProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:incomm-products';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync products from Incomm DB to main DB';

    /**
     * Execute the console command.
     */
    public function handle(IncommSyncService $syncService, ProductJsonUploadService $uploadService)
    {
        $this->info('Syncing Incomm products...');
        $syncService->syncProducts();
        $this->info('✅ Sync completed successfully.');

        $this->info('Uploading products to R2...');
        $productCount = $uploadService->uploadAllProducts(ignoreTimestamp: true, priceSource: 2);
        $this->info("✅ Uploaded {$productCount} product(s) to R2.");

        $this->info('Uploading prices to R2...');
        $priceCount = $uploadService->uploadAllPrices(ignoreTimestamp: true, priceSource: 2);
        $this->info("✅ Uploaded {$priceCount} price(s) to R2.");
    }
}

<?php

namespace App\Console\Commands;

use App\Domains\PointNexus\Services\PointNexusSyncService;
use App\Services\Json\ProductJsonUploadService;
use Illuminate\Console\Command;

class SyncPointNexusProduct extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:pn-products';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync products from Point Nexus DB to main DB';

    /**
     * Execute the console command.
     */
    public function handle(PointNexusSyncService $syncService, ProductJsonUploadService $uploadService)
    {
        $this->info('Syncing Point Nexus products...');
        $syncService->syncProducts();
        $this->info('✅ Sync completed successfully.');

        $this->info('Uploading products to R2...');
        $productCount = $uploadService->uploadAllProducts(ignoreTimestamp: true, priceSource: 3);
        $this->info("✅ Uploaded {$productCount} product(s) to R2.");

        $this->info('Uploading prices to R2...');
        $priceCount = $uploadService->uploadAllPrices(ignoreTimestamp: true, priceSource: 3);
        $this->info("✅ Uploaded {$priceCount} price(s) to R2.");
    }
}

<?php

namespace App\Console\Commands;

use App\Services\Utils\SlugGenerationService;
use Illuminate\Console\Command;

class GenerateProductSlugs extends Command
{
    protected $signature = 'products:generate-slugs
                            {--sku= : Generate slug for a specific SKU only}';

    protected $description = 'Generate SEO-friendly slugs for all products or a specific SKU';

    public function __construct(
        protected SlugGenerationService $slugService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $sku = $this->option('sku');

        return $sku
            ? $this->handleSku((int) $sku)
            : $this->handleAll();
    }

    protected function handleSku(int $sku): int
    {
        $this->info("Generating slug for SKU: {$sku}...");

        $result = $this->slugService->generateForSku($sku);

        if ($result['found'] === 0) {
            $this->error("No products found for SKU: {$sku}");
            return Command::FAILURE;
        }

        foreach ($result['slugs'] as $id => $slug) {
            $this->info("Product ID {$id}: {$slug}");
        }

        if ($result['skipped'] > 0) {
            $this->warn("Skipped: {$result['skipped']} (missing name or platform)");
        }

        if ($result['failed'] > 0) {
            $this->warn("Failed: {$result['failed']}");
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    protected function handleAll(): int
    {
        $this->info('Starting slug generation for all products...');

        $sources = [
            config('services.sources.ztorm'),
            config('services.sources.point_nexus'),
            config('services.sources.genba'),
            config('services.sources.incomm')
        ];

        $stats = $this->slugService->generateForAll(
            sources: $sources,
            onChunk: function ($product, $success) {
                if (!$success) {
                    $this->warn("Failed: product ID {$product->id} (SKU: {$product->sku}) after 50 retries.");
                }
            }
        );

        $this->info('Slug generation completed.');
        $this->info("Updated: {$stats['updated']}");
        $this->info("Skipped: {$stats['skipped']}");

        if ($stats['failed'] > 0) {
            $this->warn("Failed: {$stats['failed']}");
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
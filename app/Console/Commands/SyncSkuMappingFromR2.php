<?php

namespace App\Console\Commands;

use App\Services\Webhooks\SkuMappingWebhookService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class SyncSkuMappingFromR2 extends Command
{
    protected $signature = 'sku-mapping:sync-from-r2
                                {--dry-run : Fetch and validate the JSON without calling the service}';

    protected $description = 'Fetch sku_mapping.json from R2 and process it via SkuMappingWebhookService';

    public function __construct(
        private readonly SkuMappingWebhookService $service,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Fetching sku_mapping.json from R2...');

        try {
            $payload = $this->fetchFromR2();


            $this->info("Fetched successfully. count={$payload['count']}");

            if ($this->option('dry-run')) {
                $this->warn('Dry-run mode — skipping service call.');
                return self::SUCCESS;
            }

            $webhook = $this->service->process(
                envelope: $payload,
                data: $payload['data'],
            );

            $this->info("Processed successfully. webhook_id={$webhook->id} event={$webhook->event}");

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error("Failed: {$e->getMessage()}");
            Log::error('SyncSkuMappingFromR2 failed', [
                'error' => $e->getMessage(),
            ]);
            return self::FAILURE;
        }
    }

    private function fetchFromR2(): array
    {
        try {
            $contents = Storage::disk('r2-resource')->get('product-json/sku_mapping.json');
        } catch (Exception $e) {
            dd($e->getMessage());
        }


        if ($contents === null) {
            throw new RuntimeException('sku_mapping.json not found on R2.');
        }

        $json = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

        if (empty($json['event']) || !isset($json['data']) || !is_array($json['data'])) {
            throw new RuntimeException('sku_mapping.json is missing required fields (event / data).');
        }

        return $json;
    }
}

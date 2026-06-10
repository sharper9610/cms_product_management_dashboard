<?php

namespace App\Services\Webhooks;

use App\Data\SkuMappingData;
use App\Models\SkuMapping;
use App\Models\SkuMappingWebhook;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class SkuMappingWebhookService
{
    public function process(array $envelope, array $data): SkuMappingWebhook
    {
        try {
            return DB::transaction(function () use ($envelope, $data) {
                $webhook  = $this->createWebhook($envelope);
                $mappings = $this->parseMappings($data);
                $this->syncMappings($webhook, $mappings);

                return $webhook;
            });
        } catch (Throwable $e) {
            Log::error('SkuMappingWebhookService: failed to process webhook', [
                'error'   => $e->getMessage(),
                'event'   => $envelope['event'] ?? null,
                'count'   => $envelope['count'] ?? null,
            ]);

            throw $e;
        }
    }

    private function createWebhook(array $envelope): SkuMappingWebhook
    {
        try {
            return SkuMappingWebhook::create([
                'event'        => $envelope['event'],
                'label'        => $envelope['label']        ?? null,
                'kind'         => $envelope['kind']         ?? null,
                'scope'        => $envelope['scope']        ?? null,
                'count'        => $envelope['count']        ?? 0,
                'generated_at' => $envelope['generated_at'] ?? null,
            ]);
        } catch (Throwable $e) {
            Log::error('SkuMappingWebhookService: failed to create webhook envelope', [
                'error'   => $e->getMessage(),
                'envelope' => $envelope,
            ]);

            throw $e;
        }
    }

    /**
     * @return SkuMappingData[]
     */
    private function parseMappings(array $data): array
    {
        try {
            return array_map(
                fn(array $mapping) => SkuMappingData::fromArray($mapping),
                $data
            );
        } catch (Throwable $e) {
            Log::error('SkuMappingWebhookService: failed to parse mappings', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * @param SkuMappingData[] $mappings
     */
    private function syncMappings(SkuMappingWebhook $webhook, array $mappings): void
    {
        try {
            DB::transaction(function () use ($webhook, $mappings) {
                SkuMapping::query()->delete();

                $now  = now();
                $rows = array_map(fn(SkuMappingData $m) => [
                    'webhook_id' => $webhook->id,
                    'parent_sku' => $m->parentSku,
                    'child_skus' => json_encode($m->childSkus),
                    'mapped_at'  => $m->mappedAt,
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $mappings);

                foreach (array_chunk($rows, 500) as $chunk) {
                    SkuMapping::insert($chunk);
                }
            });
        } catch (Throwable $e) {
            Log::error('SkuMappingWebhookService: failed to sync mappings', [
                'error'      => $e->getMessage(),
                'webhook_id' => $webhook->id,
            ]);

            throw $e;
        }
    }
}

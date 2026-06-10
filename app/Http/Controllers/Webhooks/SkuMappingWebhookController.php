<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Http\Requests\Webhooks\SkuMappingWebhookRequest;
use App\Services\Webhooks\SkuMappingWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class SkuMappingWebhookController extends Controller
{
    public function __construct(
        private readonly SkuMappingWebhookService $service,
    ) {}

    public function handle(SkuMappingWebhookRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();


            $webhook = $this->service->process(
                envelope: $validated,
                data:     $validated['data'],
            );

            return response()->json([
                'success' => true,
                'message' => 'Webhook processed successfully.',
                'data'    => [
                    'webhook_id'   => $webhook->id,
                    'event'        => $webhook->event,
                    'scope'        => $webhook->scope,
                    'count'        => $webhook->count,
                    'generated_at' => $webhook->generated_at,
                    'processed_at' => now(),
                ],
            ], 200);

        } catch (Throwable $e) {
            Log::error('SkuMappingWebhook failed', [
                'error' => $e->getMessage(),
                'event' => $request->input('event'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process webhook.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentFeeWebhookRequest;
use App\Services\OrderProcessing\PaymentFeeWebhookService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Throwable;

class PaymentFeeWebhookController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected PaymentFeeWebhookService $service
    ) {}

    public function store(PaymentFeeWebhookRequest $request): JsonResponse
    {
        try {
            $order = $this->service->handle($request->validated());

            return $this->successResponse([
                'order_id_2game'  => $order->order_id_2game,
                'payment_fee'     => $order->payment_fee,
                'payment_fee_eur' => $order->payment_fee_eur,
            ], 'Payment fee updated successfully.');
        } catch (ModelNotFoundException $e) {
            return $this->notFoundResponse('Order not found.');
        } catch (Throwable $e) {
            return $this->serverErrorResponse('Failed to process payment fee.', $e->getMessage());
        }
    }
}

<?php

use App\Http\Controllers\Api\BonusController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\LatamOldOrderHistoryController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\OrderControllerV2;
use App\Http\Controllers\Api\PaymentFeeWebhookController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SystemController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\PromptController;
use App\Http\Controllers\Webhooks\SkuMappingWebhookController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your API routes. Routes in
| this file are automatically loaded by the RouteServiceProvider within
| a group assigned the "api" middleware group.
|
*/

if (env('ENABLE_API_ROUTES', true)) {
    Route::get('/health', [SystemController::class, 'health']);

    Route::prefix('products')->middleware('api.static-password')->group(function () {
        Route::get('/', [ProductController::class, 'index']);
        Route::get('/v3', [ProductController::class, 'indexV3']);
        Route::get('/v2/{product_id}', [ProductController::class, 'getProductByIdV2']);

        Route::get('/{product_id}', [ProductController::class, 'getProductById']);
        Route::get('v3/{product_id}', [ProductController::class, 'getProductByIdV3']);


        Route::post('/translation/{sku}', [ProductController::class, 'translateSingle']);

        Route::prefix('prices')->group(function () {
            Route::get('/all', [ProductController::class, 'getPrices']);
            Route::get('/v3/all', [ProductController::class, 'getPricesV3']);
            Route::get('/{product_id}', [ProductController::class, 'getPricesByPrtoductId'])->whereNumber('product_id');
            Route::get('v3/{product_id}', [ProductController::class, 'getPricesByProductIdV3'])->whereNumber('product_id');
        });

        Route::post('/shopify/product-variants', [ProductController::class, 'storeProductVariants']);
        Route::post('/shopify/product-variants/remove-by-product', [ProductController::class, 'removeVariantsByShopifyProduct']);
    });

    Route::prefix('orders')->middleware('api.static-password')->group(function () {
        Route::post('/', [OrderController::class, 'processOrder']);
        Route::get('/redeem-key/{key_id}/{order_id}', [OrderController::class, 'redeemKey']);
        Route::get('/cancel', [OrderController::class, 'cancelOrder']);
        Route::post('/v2/cancel', [OrderController::class, 'cancelOrderV2']);
        Route::prefix('v3')->group(function () {
            Route::post('/', [OrderControllerV2::class, 'processMappedOrder']);
            Route::get('/details/{order_id}', [OrderControllerV2::class, 'orderDetails']);
            Route::post('/webhooks/payment-fee', [PaymentFeeWebhookController::class, 'store']);
        });
    });

    Route::post('storefront/orders/v3', [OrderControllerV2::class, 'processStorefrontOrder'])->middleware('storefront.auth');
    Route::get('storefront/redeem-key/v3/{key_id}/{order_id}', [OrderController::class, 'redeemKey'])->middleware('storefront.auth');
    Route::post('storefront/cancel/v3', [OrderController::class, 'cancelOrderV2'])->middleware('storefront.auth');
    Route::post('storefront/webhooks/payment-fee', [PaymentFeeWebhookController::class, 'store'])->middleware('storefront.auth');


    Route::prefix('customers')->middleware('api.static-password')->group(function () {
        Route::post('/resolve', [CustomerController::class, 'resolve']);
        Route::post('/sync', [CustomerController::class, 'sync']);
        Route::get('/by-shopify', [CustomerController::class, 'getByShopifyId']);
        Route::get('/{id}', [CustomerController::class, 'show']);
        Route::get('/v3/orders', [CustomerController::class, 'getCustomerOrderListByEmail']);

        Route::prefix('wallets')->group(function () {
            // Get balance
            // Route::get('/by-shopify/{shopify_customer_id}/balance', [WalletController::class, 'getBalanceByShopifyId']);

            Route::get('/{customer_id}/balance', [WalletController::class, 'getBalance'])->whereNumber('customer_id');

            // Add balance
            Route::post('/{customer_id}/add-balance', [WalletController::class, 'addBalance']);

            // Check balance
            Route::post('/{customer_id}/check-sufficient-balance', [WalletController::class, 'checkSufficientBalance']);

            // Deduct balance
            Route::post('/{customer_id}/deduct-balance', [WalletController::class, 'deductBalance']);

            // Purchase
            Route::post('/{customer_id}/purchase', [WalletController::class, 'processPurchase']);

            // Withdrawal
            Route::post('/{customer_id}/withdrawal', [WalletController::class, 'processWithdrawal']);

            // Refund
            Route::post('/{customer_id}/refund', [WalletController::class, 'processRefund']);


            // Transaction history
            Route::get('/{customer_id}/transactions', [WalletController::class, 'getTransactions']);
        });
    });

    Route::prefix('bonus')->middleware('api.static-password')->group(function () {
        Route::get('/product/{sku}', [BonusController::class, 'getProductBonusCap']);
        Route::post('/calculate-cart', [BonusController::class, 'calculateCart']);
        Route::post('/validate-cart', [BonusController::class, 'validateCart']);
        Route::post('/validate-cart-with-balance', [BonusController::class, 'validateCartWithBalance']);

        // Route::get('/restricted-products', [BonusController::class, 'getRestrictedProducts']);
    });

    Route::get('/magento/old-orders', [LatamOldOrderHistoryController::class, 'index'])->middleware('api.static-password');
    Route::get('/magento/redeem-key/{key_id}/{order_id}', [LatamOldOrderHistoryController::class, 'redeemMagentoKey'])->middleware('api.static-password');
    Route::get('/magento/old-order-emails', [LatamOldOrderHistoryController::class, 'getOldOrderEmails'])->middleware('api.static-password');
    Route::post('/webhooks/sku-mapping', [SkuMappingWebhookController::class, 'handle'])->middleware('api.static-password');
}

// Route::get('/test-tag-suggestion/{sku}', function ($sku) {
//     $service = app(TagSuggestionService::class);
//     $tags = $service->suggestTagsBySKU($sku);

//     return response()->json([
//         'sku'  => $sku,
//         'tags' => $tags,
//     ]);
// });


// Route::get('/test-rating-suggestion/{sku}', function ($sku) {
//     $service = app(RatingSuggestionService::class);
//     $rating = $service->getRatingBySKU($sku);

//     return response()->json([
//         'sku'    => $sku,
//         'rating' => $rating,
//     ]);
// });

// Route::get('/test-system-requirements-translation/{sku}', function ($sku) {
//     $service = new TranslationService();

//     // Process system requirements translation for the given SKU
//     $service->processSystemRequirementsTranslation((int)$sku);

//     return response()->json([
//         'status' => 'success',
//         'message' => "System requirements translation processed for SKU: {$sku}",
//     ]);
// });

Route::post('prompts/{id}/run', [PromptController::class, 'runPrompt']);

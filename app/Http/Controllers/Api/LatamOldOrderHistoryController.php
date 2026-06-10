<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\cms\BasketGbp;
use App\Models\cms\BasketItemGbp;
use App\Models\cms\CustomerGbp;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class LatamOldOrderHistoryController extends Controller
{
    use ApiResponse;

    protected string $ztormEndpoint;
    protected string $ztormPassword;

    public function __construct()
    {
        $this->ztormEndpoint = config('services.ztorm.endpoint') . '/api/1.0/shopify/get_install_key';
        $this->ztormPassword = config('services.ztorm.password');
    }

    public function index(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
            ]);

            $email = $request->query('email');
            $emailParts = explode('@', $email);
            $username = $emailParts[0] ?? '';
            // $domain = $emailParts[1] ?? '';

            // if (strtolower($domain) === '2game.com') {
            //     $customer = CustomerGbp::where('Email', 'like', "{$username}%")->first();
            // } else {
            //     $customer = CustomerGbp::where('Email', $email)->first();
            // }

            $customer = CustomerGbp::where('Email', 'like', "{$username}%")->first();

            if (!$customer) {
                return $this->serverErrorResponse("Customer not found with this email {$email} and email prefix: {$username}", '');
            }

            $retailerCustomerId = $customer->RetailerCustomerID;
            $customerEmail = $customer->Email;

            $baskets = BasketGbp::select(
                'basket_gbp.BasketID',
                'basket_gbp.RetailerOrderID as order_id',
                'basket_gbp.status as status',
                'basket_gbp.CountryCode as country_code',
                'basket_gbp.RetailerOrderID as retailer_order_id',
                'basket_gbp.EndTime as order_date',
            )
                ->where('basket_gbp.Source', 'like', '%.%.%.%')
                ->where('basket_gbp.RetailerCustomerID', $retailerCustomerId)
                ->orderBy('basket_gbp.BasketID', 'desc')
                ->get();

            $basketIds = $baskets->pluck('BasketID')->toArray();

            $allItems = BasketItemGbp::select(
                'basket_item_gbp.BasketID',
                'basket_item_gbp.CustomerProductID as customer_product_id',
                'basket_item_gbp.ProductPriceIncVAT as price',
                'basket_item_gbp.Currency as currency_code',
                'product_gbp.Name as product_title',
                'product_gbp.id as sku',
            )
                ->join('product_gbp', 'basket_item_gbp.ProductID', '=', 'product_gbp.id')
                ->whereIn('basket_item_gbp.BasketID', $basketIds)
                ->get();

            $skus = $allItems->pluck('sku')->filter()->unique()->toArray();

            $productVariants = DB::connection('mysql')
                ->table('product_variants')
                ->join('products', 'product_variants.product_id', '=', 'products.sku')
                ->whereIn('products.sku', $skus)
                ->select('products.sku', 'product_variants.shopify_variant_id')
                ->get()
                ->keyBy('sku');

            $itemsByBasket = $allItems->groupBy('BasketID');

            $orders = $baskets->map(function ($basket) use ($customerEmail, $itemsByBasket, $productVariants) {
                $items = $itemsByBasket->get($basket->BasketID, collect());

                $currencyCode = $items->first()?->currency_code ?? null;
                $totalPrice   = $items->sum('price');

                $lineItems = $items->map(function ($item) use ($productVariants) {
                    $variant = $productVariants->get($item->sku);

                    return [
                        'shopify_variant_id'  => $variant?->shopify_variant_id ?? null,
                        'product_title'       => $item->product_title,
                        'sku'                 => $item->sku,
                        'customer_product_id' => $item->customer_product_id,
                        'quantity'            => 1,
                        'price'               => $item->price,
                        'currency_code'       => $item->currency_code,
                    ];
                });

                return [
                    'order_id'          => $basket->order_id,
                    'retailer_order_id' => $basket->retailer_order_id,
                    'status'            => $basket->status,
                    'email'             => $customerEmail,
                    'country_code'      => $basket->country_code,
                    'currency_code'     => $currencyCode,
                    'total_price'       => $totalPrice,
                    'order_date'        => $basket->order_date,
                    'items'             => $lineItems,
                ];
            });

            return $this->successResponse($orders, "LATAM old orders fetched successfully for Customer With Email {$email}");
        } catch (Exception $e) {
            return $this->serverErrorResponse('Failed to fetch orders', $e->getMessage());
        }
    }


    public function redeemMagentoKey(Request $request, $key_id, $retailer_order_id)
    {
        try {
            Log::channel('order')->info("Magento Redeem key request received", [
                'key_id'   => $key_id,
                'retailer_order_id' => $retailer_order_id,
                'email'    => $request->query('email'),
            ]);


            $request->validate([
                'email' => 'required|email',
            ]);

            $email = $request->query('email');


            $emailParts = explode('@', $email);
            $username = $emailParts[0] ?? '';
            $domain = $emailParts[1] ?? '';

            // if (strtolower($domain) === '2game.com') {
            //     $customer = CustomerGbp::where('Email', 'like', "{$username}%")->first();
            // } else {
            //     $customer = CustomerGbp::where('Email', $email)->first();
            // }

            $customer = CustomerGbp::where('Email', 'like', "{$username}%")->first();

            if (!$customer) {
                return $this->serverErrorResponse("Customer not found with this email {$email} and email prefix: {$username}", '');
            }



            // $orderItem = BasketGbp::join('basket_item_gbp', 'basket_gbp.BasketID', '=', 'basket_item_gbp.BasketID')
            //     ->where('basket_gbp.RetailerCustomerID', $retailer_customer_id)
            //     ->where('basket_item_gbp.CustomerProductID', $key_id)
            //     ->select(
            //         'basket_gbp.status',
            //         'basket_item_gbp.CustomerProductID'
            //     )
            //     ->first();

            // if (!$orderItem) {
            //     Log::warning("Invalid Magento redeem attempt", [
            //         'email' => $email,
            //         'order_id' => $retailer_customer_id,
            //         'key_id' => $key_id
            //     ]);

            //     return $this->serverErrorResponse("Invalid order or key for this customer", '');
            // }


            // if (strtolower($orderItem->status) !== 'completed') {
            //     return $this->serverErrorResponse("Order is not completed. Cannot redeem key.", '');
            // }



            // $response = Http::get(config('services.ztorm.endpoint') . '/api/1.0/shopify/get_install_key', [
            //     'password' => config('services.ztorm.password'),
            //     'customer_product_id' => $key_id,
            //     'retailer_order_id' => $retailer_customer_id,
            // ]);

            $query = http_build_query([
                'password' => $this->ztormPassword,
                'customer_product_id' => $key_id,
                'retailer_order_id' => $retailer_order_id,
            ]);

            $finalUrl = $this->ztormEndpoint . '?' . $query;

            $response = Http::get($finalUrl);

            $data = $response->json('Response.Value.InstallKey');

            $combinedKey = $data['Value'];

            Log::channel('order')->info("Magento order redeem response", [
                'response' => $response->json(),
            ]);

            return $this->successResponse($combinedKey, "Key redeemed successfully");
        } catch (Exception $e) {
            Log::channel('order')->error("Redeem failed", [
                'error' => $e->getMessage(),
            ]);

            return $this->serverErrorResponse('Failed to redeem key', $e->getMessage());
        }
    }

    public function getOldOrderEmails(Request $request)
    {
        try {
            $request->validate([
                'length' => 'nullable|integer|min:1|max:1000',
                'offset' => 'nullable|integer|min:0',
            ]);

            $length = $request->input('length', 500);
            $offset = $request->input('offset', 0);

            $query = CustomerGbp::select(
                'RetailerCustomerID',
                'Email',
                'Username',
                'FirstName',
                'LastName',
                'CountryCode'
            );

            $total = $query->count();

            $customers = $query
                ->orderBy('id', 'desc')
                ->offset($offset)
                ->limit($length)
                ->get();

            return $this->successResponse([
                'total'   => $total,
                'length'  => $length,
                'offset'  => $offset,
                'count'   => $customers->count(),
                'data'    => $customers,
            ], "Customer list fetched successfully");
        } catch (Exception $e) {
            return $this->serverErrorResponse(
                'Failed to fetch customers',
                $e->getMessage()
            );
        }
    }
}

<?php

namespace App\Http\Controllers\pages;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Traits\HttpResponses;
use App\Models\Order;
use App\Models\OrderItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class OrderController extends Controller
{
    use HttpResponses;

    public function list(Request $request)
    {

        $this->checkPageAccess('product.view');

        // Get distinct countries from orders table - cached for 1 hour
        $countries = \Illuminate\Support\Facades\Cache::remember('countries_orders_list', 60 * 60 * 1, function () {
            return \App\Models\Order::distinct()
                ->whereNotNull('country_code')
                ->pluck('country_code')
                ->sort()
                ->values();
        });

        return view('content.pages.orders', ['countries' => $countries]);
    }

    public function index(Request $request)
    {
        $columns = [
            1 => 'id',
            2 => 'order_id_2game',
            12 => 'created_at',
        ];

        $search = [
            1 => 'id',
            3 => 'product_id',
            4 => 'status',
            11 => 'source',
            12 => 'created_at',
            13 => 'country_code',
        ];

        //  Get search values cleanly
        $searchValues = collect($search)
            ->mapWithKeys(function ($field, $index) use ($request) {
                return [$field => $request->input("columns.$index.search.value")];
            })
            ->filter() // Removes null/empty string values
            ->all();

        //  Helper function for date range parsing
        $applyDateFilter = function ($q, $value, $column = 'created_at') {
            if (strpos($value, ' to ') !== false) {
                $dates = explode(' to ', $value);
                if (count($dates) == 2) {
                    try {
                        $startDate = Carbon::createFromFormat('Y/m/d', trim($dates[0]))->startOfDay();
                        $endDate = Carbon::createFromFormat('Y/m/d', trim($dates[1]))->endOfDay();
                        $q->whereBetween($column, [$startDate, $endDate]);
                    } catch (\Exception $e) {
                        // Silently ignore invalid date format
                    }
                }
            } else {
                try {
                    $date = Carbon::createFromFormat('Y/m/d', trim($value))->startOfDay();
                    $q->whereDate($column, $date);
                } catch (\Exception $e) {
                    // Silently ignore invalid date format
                }
            }
        };

        //  --- Define Filter Logic for Orders ---
        $applyOrderFilters = function ($query) use ($searchValues, $applyDateFilter) {

            // Order-level filters
            $query->when($searchValues['id'] ?? null, function ($q, $value) {
                $q->where('order_id_2game', $value);
            });

            // Date filter
            $query->when($searchValues['created_at'] ?? null, function ($q, $value) use ($applyDateFilter) {
                $applyDateFilter($q, $value, 'created_at');
            });

            // Item-level filters (using whereHas to filter the orders)
            $query->when($searchValues['product_id'] ?? null, function ($q, $value) {
                $q->whereHas('items', fn($itemQuery) => $itemQuery->where('product_id', $value));
            });

            $query->when($searchValues['source'] ?? null, function ($q, $value) {
                $q->whereHas('items', fn($itemQuery) => $itemQuery->where('source', $value));
            });

            $query->when($searchValues['status'] ?? null, function ($q, $value) {
                $q->whereHas('items', fn($itemQuery) => $itemQuery->where('status', $value));
            });

            $query->when($searchValues['country_code'] ?? null, function ($q, $value) {
                $q->where('country_code', $value);
            });
        };

        //  --- Define Filter Logic for Item Totals ---
        $applyOrderItemFilters = function ($query) use ($searchValues, $applyDateFilter) {
            $query->where('status', OrderStatus::COMPLETED);

            // Item-level filters
            $query->when($searchValues['product_id'] ?? null, fn($q, $v) => $q->where('product_id', $v));
            $query->when($searchValues['source'] ?? null, fn($q, $v) => $q->where('source', $v));
            $query->when($searchValues['status'] ?? null, fn($q, $v) => $q->where('status', $v));
            $query->when($searchValues['created_at'] ?? null, function ($q, $value) use ($applyDateFilter) {
                $applyDateFilter($q, $value, 'created_at');
            });
            // Order-level filters (filtering items based on their parent order)
            $query->whereHas('order', function ($orderQuery) use ($searchValues) {

                $orderQuery->when($searchValues['order_id_2game'] ?? null, function ($q, $value) {
                    $q->where('order_id_2game', $value);
                });

                //        $orderQuery->when($searchValues['updated_at'] ?? null, function ($q, $value) use ($applyDateFilter) {
                //          $applyDateFilter($q, $value, 'updated_at');
                //        });
            });
        };

        // --- Base and Filtered Order Queries ---

        // Get total count before any filters
        $totalData = Order::count();

        // Base query
        $query = Order::query();

        // Apply filters
        $applyOrderFilters($query);

        // Clone query for filtering totals
        $filteredQuery = clone $query;
        $totalFiltered = $filteredQuery->count();
        $paymentFeeEurTotal = $filteredQuery
            ->where(function ($query) {
                $query->where('status', 'COMPLETED')
                    ->orWhere('status', 'PARTIALLY_COMPLETED');
            })
            ->sum('payment_fee_eur');

        // --- Pagination ---
        $limit = $request->input('length', 10);
        $start = $request->input('start', 0);

        // Standard datatables param
        $orderColumnIndex = $request->input('order.0.column', 12); // Default to index 9 (updated_at)
        $order = $columns[$orderColumnIndex] ?? 'created_at';
        $dir = $request->input('order.0.dir', 'desc');

        // Define a closure for item constraints, reusing item-only filter logic
        // This ensures withSum, withCount, and with('items') all use the *same* filters
        $itemConstrainer = function ($query) use ($searchValues) {
            $query->when($searchValues['product_id'] ?? null, fn($q, $v) => $q->where('product_id', $v))
                ->when($searchValues['source'] ?? null, fn($q, $v) => $q->where('source', $v))
                ->when($searchValues['status'] ?? null, fn($q, $v) => $q->where('status', $v));
        };

        // Fetch paginated orders with database-level aggregates
        $orders = $query
            ->with([
                // Eager load only the *filtered* items and their products
                'items' => $itemConstrainer,
                'items.product' => function ($query) {
                    $query->select('sku', 'name')->distinct('sku');
                },
                'transactions' => function ($q) {
                    $q->select('order_id', 'transaction_id', 'gateway');
                },
            ])
            // Calculate sums and counts in the DB, not in the loop
            ->withCount(['items' => $itemConstrainer]) // This creates 'items_count'
            ->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir)
            ->get();

        $data = [];
        $ids = $start;

        foreach ($orders as $order) {
            $nestedData = [];
            $nestedData['id'] = $order->id;
            $nestedData['fake_id'] = ++$ids;

            // Basic fields
            $nestedData['order_id_2game'] = $order->order_id_2game;
            $nestedData['last_failure_reason'] = $order->last_failure_reason;
            $nestedData['payment_fee'] = number_format($order->getPaymentFee(), 2);
            $nestedData['payment_fee_euro'] = number_format($order->getPaymentFeeEur(), 4);

            $nestedData['created_at'] = $order->created_at ? $order->created_at->format('Y-m-d H:i') : null;

            // Get sources from the pre-filtered items collection
            $nestedData['source'] = $order->items->pluck('source')->unique()->values()->toArray();

            $transaction = $order->transactions->first();
            $nestedData['transaction_id'] = $transaction->transaction_id ?? '';
            $nestedData['gateway'] = $transaction->gateway ?? '';
            $nestedData['country_code'] = $order->country_code ?? '';

            $status = [];

            //  Check count from the DB aggregate
            if ($order->items_count > 0) {
                // Use the aggregate values directly (e.g., 'items_sum_row_total')
                $row_total = (float) ($order->getTotalPrice() ?? 0);
                $row_total_euro = (float) ($order->getTotalPriceEur() ?? 0);
                $cost_price = (float) ($order->getCostPrice() ?? 0);
                $cost_price_euro = (float) ($order->getCostPriceEur() ?? 0);
                $vat_amount = (float) ($order->getVatAmount() ?? 0);
                $vat_amount_euro = (float) ($order->getVatAmountEur() ?? 0);
                $total_qty = (int) $order->items_count;

                //  Calculate profit with raw numbers
                $profit = (float) ($order->getProfit() ?? 0);
                $profit_euro = (float) ($order->getProfitEur() ?? 0);

                // Store formatted output
                $nestedData['total_price'] = number_format($row_total, 2);
                $nestedData['total_price_euro'] = number_format($row_total_euro, 4);
                $nestedData['cost_price'] = number_format($cost_price, 2);
                $nestedData['cost_price_euro'] = number_format($cost_price_euro, 4);
                $nestedData['vat_amount'] = number_format($vat_amount, 2);
                $nestedData['vat_amount_euro'] = number_format($vat_amount_euro, 4);
                $nestedData['profit'] = number_format($profit, 2);
                $nestedData['profit_euro'] = number_format($profit_euro, 4);
                $nestedData['total_qty'] = $total_qty;
                $nestedData['currency_code'] = optional($order->items->first())->currency_code ?? '';

                foreach ($order->items as $item) {
                    if (! empty($item->status)) {
                        $statusValue = is_string($item->status) ? $item->status : $item->status->value;
                        $offerStatus = strtoupper($statusValue);
                        $status[$offerStatus] = ($status[$offerStatus] ?? 0) + 1;
                    }
                }
                $nestedData['item_status'] = $status;

                // Collect product names from the pre-filtered collection
                $productNames = $order->items
                    ->pluck('product.name')
                    ->filter()
                    ->unique()
                    ->values();

                $uniqueProductCount = $productNames->count();
                if ($uniqueProductCount === 1) {
                    $nestedData['product'] = $productNames->first();
                } elseif ($uniqueProductCount > 1) {
                    $nestedData['product'] = 'Multiple product';
                } else {
                    $nestedData['product'] = '';
                }
                $nestedData['total_product'] = $uniqueProductCount;
            } else {
                // Default values when no items match filters
                $nestedData['total_price'] = '0.00';
                $nestedData['total_price_euro'] = '0.00';
                $nestedData['cost_price'] = '0.00';
                $nestedData['cost_price_euro'] = '0.00';
                $nestedData['vat_amount'] = '0.00';
                $nestedData['vat_amount_euro'] = '0.00';
                $nestedData['total_qty'] = 0;
                $nestedData['item_status'] = [];
                $nestedData['profit'] = '0.00';
                $nestedData['profit_euro'] = '0.00';
                $nestedData['product'] = '';
                $nestedData['total_product'] = 0;
                $nestedData['currency_code'] = '';
            }

            $data[] = $nestedData;
        }

        // --- OrderItem totals (with filters if applied) ---

        //  Use the filter logic defined at the top
        $orderItemsQuery = OrderItem::query();
        $applyOrderItemFilters($orderItemsQuery);

        // Get totals
        //  Clone query for each sum to avoid modification issues

        $totalPriceEuro = (clone $orderItemsQuery)->sum('row_total_eur');
        $costPriceEuro = (clone $orderItemsQuery)->sum('cost_price_euro');
        $vat_amount_euro = (clone $orderItemsQuery)->sum('vat_amount_eur'); //
        $totalQty = (clone $orderItemsQuery)->count();

        $profitTotalEuro = $totalPriceEuro
            - $vat_amount_euro
            - $costPriceEuro
            - $paymentFeeEurTotal;

        if ($totalQty == 0) {
            $profitTotalEuro = 0;
            $paymentFeeEurTotal = 0;
        }

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => intval($totalData),
            'recordsFiltered' => intval($totalFiltered),
            'code' => 200,
            'data' => $data,
            'totals' => [
                'price' => number_format($totalPriceEuro, 2),
                'costPrice' => number_format($costPriceEuro, 2),
                'paymentFeeTotal' => number_format($paymentFeeEurTotal, 2),
                'vat_amount' => number_format($vat_amount_euro, 2),
                'profit' => number_format($profitTotalEuro, 2),
                'qty' => $totalQty,
            ],
        ]);
    }

    public function show(Request $request, $id)
    {
        $this->checkPageAccess('product.view');

        $query = OrderItem::with(['product'])
            ->where('order_id', $id);

        // Apply filters if provided
        //    if ($request->filled('status')) {
        //      $query->where('status', $request->status);
        //    }

        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        $orderItems = $query->get();

        $order = Order::with(['transactions' => function ($q) {
            $q->select('order_id', 'transaction_id', 'gateway');
        }])->find($id);

        $items = $orderItems->map(function ($item) {
            $row_total = (float) ($item->row_total ?? 0);
            $row_total_euro = (float) ($item->row_total_eur ?? 0);
            $cost_price = (float) ($item->cost_price ?? 0);
            $cost_price_euro = (float) ($item->cost_price_euro ?? 0);
            $vat_amount = (float) ($item->vat_amount ?? 0);
            $vat_amount_euro = (float) ($item->vat_amount_eur ?? 0);

            return [
                'id' => $item->id,
                'product_name' => $item->product->name ?? '',
                'product_id' => $item->product_id ?? '',
                'qty' => 1,
                'price' => $item->row_total,
                'currency_code' => $item->currency_code,
                'key_id' => $item->key_id,
                'retailer_order_id' => $item->retailer_order_id,
                'discount_amount' => $item->discount_amount,

                'giftcard_amount' => $item->giftcard_amount,
                'status' => $item->status,

                'row_total' => number_format($row_total, 2),
                'row_total_euro' => number_format($row_total_euro, 4),

                'cost_price' => number_format($cost_price, 2),
                'cost_price_euro' => number_format($cost_price_euro, 4),

                'vat_amount' => number_format($vat_amount, 2),
                'vat_amount_euro' => number_format($vat_amount_euro, 4),

                'failed_reason' => $item->failed_reason,
                'redeemed_at' => $item->redeemed_at
                    ? Carbon::parse($item->redeemed_at)->format('Y-m-d H:i')
                    : null,
                'source' => $item->source === 1 ? 'ztorm' : ($item->source === 2 ? 'incomm' : ($item->source === 3 ? 'point nexus' : ($item->source === 4 ? 'genba' : ''))),
            ];
        });

        $transactions = $order->transactions->map(function ($t) {
            return [
                'transaction_id' => $t->transaction_id,
                'gateway' => $t->gateway,
                'amount' => $t->amount,
                'currency' => $t->currency,
            ];
        });

        return response()->json([
            'success' => true,
            'items' => $items,
            'order' => $order,
            'transactions' => $transactions
        ]);
    }

    public function redeemKey(Request $request, $id)
    {
        $item = OrderItem::find($id);

        if (! $item || empty($item->key_id)) {
            return response()->json([
                'success' => false,
                'message' => 'This item does not have a redeemable key.',
            ]);
        }

        $order = Order::find($item->order_id);

        if (! $order || empty($order->order_id_2game)) {
            return response()->json([
                'success' => false,
                'message' => 'Order reference not found. Please contact support.',
            ]);
        }

        $password = config('services.api.password');
        $baseUrl = rtrim(config('services.api.url'), '/');

        $order_id_2game = $order->order_id_2game;
        $key_id = $item->key_id;

        $apiUrl = "{$baseUrl}/orders/redeem-key/{$key_id}/{$order_id_2game}?password=" . urlencode($password);

        try {
            $response = Http::timeout(30)->get($apiUrl);
            $data = $response->json(); // 👈 ALWAYS parse body

            // ✅ API-level success
            if (
                isset($data['Response']) &&
                ($data['Response']['ErrorCode'] ?? null) === '0'
            ) {

                $newItem = OrderItem::find($id);

                activity('order_item') // log under "order_item" context
                    ->event('key_redeemed') // custom event
                    ->withProperties([
                        'item_id' => $item->id,
                        'shopify_order' => $order->order_id_2game,
                        'key_id' => $item->key_id,
                        'redeemed_at' => $newItem->redeemed_at,
                    ])
                    ->log("Key redeemed for item_id: {$item->id}, Shopify Order: {$order->order_id_2game}, Key ID: {$item->key_id}");

                return response()->json([
                    'success' => true,
                    'message' => 'Key redeemed successfully.',
                    'details' => $data['Response']['Value'],
                ]);
            }

            // ❌ API-level error (even if HTTP status is 404)
            if (isset($data['Response']['ErrorMsg'])) {
                return response()->json([
                    'success' => false,
                    'message' => $data['Response']['ErrorMsg'],
                ]);
            }

            // ❌ Unknown response format
            return response()->json([
                'success' => false,
                'message' => 'Unexpected response from redemption service.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to connect to the redemption service. Please try again later.',
            ]);
        }
    }
}

<?php

namespace App\Http\Controllers\pages;

use App\Http\Controllers\Controller;
use App\Http\Traits\HttpResponses;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    use HttpResponses;

    public function list()
    {
        $this->checkPageAccess('user.view');

        return view('content.pages.customers');
    }

    public function index(Request $request)
    {
        $columns = [
            5 => 'verified_email',
            7 => 'amount_spent',
            8 => 'number_of_orders',
            9 => 'shopify_created_at',
        ];

        $search = [
            1 => 'email',
            4 => 'state',
            5 => 'verified_email',
        ];

        $query = Customer::query();

        foreach ($search as $index => $field) {
          $searchValue = $request->input("columns.$index.search.value");
          if ($searchValue === null || $searchValue === '') {
              continue;
          }

          if ($field === 'email') {
            $query->where(function ($q) use ($searchValue) {
                $q->where('email', 'LIKE', "%{$searchValue}%")
                    ->orWhere('display_name', 'LIKE', "%{$searchValue}%")
                    ->orWhere('first_name', 'LIKE', "%{$searchValue}%")
                    ->orWhere('last_name', 'LIKE', "%{$searchValue}%");
            });
          } elseif ($field === 'state') {
            $query->where('state', $searchValue);
          } elseif ($field === 'verified_email') {
            $query->where('verified_email', $searchValue);
          } else {
            $query->where($field, 'LIKE', "%{$searchValue}%");
          }
        }

        // Summary totals in one query
        $totals = Customer::selectRaw('
            COUNT(*) AS total_customers,
            SUM(CASE WHEN state = "ENABLED"  THEN 1 ELSE 0 END) AS total_enabled,
            SUM(CASE WHEN state = "DISABLED" THEN 1 ELSE 0 END) AS total_disabled,
            SUM(CASE WHEN state = "INVITED"  THEN 1 ELSE 0 END) AS total_invited,
            SUM(CASE WHEN verified_email = 1 THEN 1 ELSE 0 END) AS total_verified
        ')->first();

        $totalData = $query->count();
        $limit     = $request->input('length', 10);
        $start     = $request->input('start', 0);
        $order     = $columns[$request->input('order.0.column')] ?? 'id';
        $dir       = $request->input('order.0.dir', 'desc');

        $customers = $query->select([
            'id',
            'shopify_customer_id',
            'shopify_legacy_id',
            'email',
            'first_name',
            'last_name',
            'display_name',
            'phone',
            'locale',
            'state',
            'tax_exempt',
            'verified_email',
            'amount_spent',
            'number_of_orders',
            'shopify_created_at',
            'created_at',
        ])
            ->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir)
            ->get();

        $data = $customers->map(function ($customer, $index) use ($start) {
            return [
                'id'                => $customer->id,
                'fake_id'           => $start + $index + 1,
                'shopify_legacy_id' => $customer->shopify_legacy_id ?? '—',
                'email'             => $customer->email,
                'full_name'         => $customer->full_name, // uses model accessor
                'phone'             => $customer->phone ?? '—',
                'locale'            => $customer->locale,
                'state'             => $customer->state,
                'verified_email'    => $customer->verified_email,
                'amount_spent'      => number_format($customer->amount_spent, 2),
                'number_of_orders'  => $customer->number_of_orders,
                'shopify_created_at' => $customer->shopify_created_at
                    ? $customer->shopify_created_at->format('Y-m-d')
                    : '—',
            ];
        });

        return response()->json([
            'draw'            => intval($request->input('draw')),
            'recordsTotal'    => $totalData,
            'recordsFiltered' => $totalData,
            'code'            => 200,
            'data'            => $data,
            'total_customers' => $totals->total_customers,
            'total_enabled'   => $totals->total_enabled,
            'total_disabled'  => $totals->total_disabled,
            'total_invited'   => $totals->total_invited,
            'total_verified'  => $totals->total_verified,
        ]);
    }

    public function show($id)
    {
        $this->checkPageAccess('user.view');

        $customer = Customer::with(['addresses', 'defaultAddress', 'metafields'])->find($id);

        if (!$customer) {
            return redirect()->route('customers')
                ->with('error', "Customer with ID {$id} not found.");
        }

        $addresses  = $customer->addresses;
        $metafields = $customer->metafields;

        return view('content.pages.customer-detail', compact('customer', 'addresses', 'metafields'));
    }
}

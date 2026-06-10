<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\ResolveCustomerRequest;
use App\Http\Requests\Customer\SyncCustomerRequest;
use App\Models\Customer;
use App\Models\Order;
use App\Services\Customer\CustomerService;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Throwable;

class CustomerController extends Controller
{
    use ApiResponse;

    public function __construct(
        private CustomerService $customerService
    ) {}

    /**
     * POST /api/customers/resolve
     */

    /**
     * @OA\Post(
     *     path="/api/customers/resolve",
     *     operationId="resolveCustomer",
     *     tags={"Customers"},
     *     summary="Resolve Customer",
     *     description="Resolves an existing customer using Shopify customer ID and returns CMS/customer identifiers.",
     *
     *     @OA\Parameter(
     *         name="password",
     *         in="query",
     *         description="Password for API authentication",
     *         required=true,
     *         @OA\Schema(type="string", example="A9f")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"shopify_customer_id"},
     *             @OA\Property(
     *                 property="shopify_customer_id",
     *                 type="string",
     *                 example="gid://shopify/Customer/1234567890",
     *                 description="Shopify Customer GID"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Customer resolved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="Response", type="object",
     *                 @OA\Property(property="Version", type="string", example="1.0"),
     *                 @OA\Property(property="ErrorCode", type="string", example="0"),
     *                 @OA\Property(property="ErrorMsg", type="string", example="Customer resolved successfully"),
     *                 @OA\Property(property="Value", type="object",
     *                     @OA\Property(property="cms_user_id", type="integer", example=1),
     *                     @OA\Property(property="customer_id", type="integer", example=1),
     *                     @OA\Property(
     *                         property="shopify_customer_id",
     *                         type="string",
     *                         example="gid://shopify/Customer/123456789"
     *                     ),
     *                     @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *                     @OA\Property(property="full_name", type="string", example="John Doe"),
     *                     @OA\Property(property="state", type="string", example="ENABLED")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Customer not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="Response", type="object",
     *                 @OA\Property(property="Version", type="string", example="1.0"),
     *                 @OA\Property(property="ErrorCode", type="string", example="2"),
     *                 @OA\Property(property="ErrorMsg", type="string", example="Customer not found"),
     *                 @OA\Property(property="Value", type="null", nullable=true)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="Response", type="object",
     *                 @OA\Property(property="Version", type="string", example="1.0"),
     *                 @OA\Property(property="ErrorCode", type="string", example="401"),
     *                 @OA\Property(property="ErrorMsg", type="string", example="Unauthorized"),
     *                 @OA\Property(property="Value", type="array", @OA\Items())
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="Response", type="object",
     *                 @OA\Property(property="Version", type="string", example="1.0"),
     *                 @OA\Property(property="ErrorCode", type="string", example="500"),
     *                 @OA\Property(property="ErrorMsg", type="string", example="Unexpected server error"),
     *                 @OA\Property(property="Value", type="null", nullable=true)
     *             )
     *         )
     *     )
     * )
     */

    public function resolve(ResolveCustomerRequest $request): JsonResponse
    {
        try {
            $customer = $this->customerService->resolve(
                $request->input('shopify_customer_id'),
                $request->input('email')
            );

            if (!$customer) {
                return $this->notFoundResponse('Customer not found');
            }

            return $this->successResponse([
                'cms_user_id' => $customer->id,
                'customer_id' => $customer->id,
                'shopify_customer_id' => $customer->shopify_customer_id,
                'email' => $customer->email,
                'full_name' => $customer->full_name,
                'state' => $customer->state,
            ], 'Customer resolved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Resolution failed', $e->getMessage());
        }
    }

    /**
     * POST /api/customers/sync
     */

    /**
     * @OA\Post(
     *     path="/api/customers/sync",
     *     operationId="syncCustomer",
     *     tags={"Customers"},
     *     summary="Sync Customer",
     *     description="Creates or updates a customer from Shopify data. Supports minimal and full customer payloads.",
     *
     *     @OA\Parameter(
     *         name="password",
     *         in="query",
     *         description="Password for API authentication",
     *         required=true,
     *         @OA\Schema(type="string", example="A9f")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"customer"},
     *
     *             @OA\Property(
     *                 property="customer",
     *                 type="object",
     *                 required={"id","email","firstName","lastName","state","createdAt","updatedAt"},
     *
     *                 @OA\Property(
     *                     property="id",
     *                     type="string",
     *                     example="gid://shopify/Customer/1234567890"
     *                 ),
     *                 @OA\Property(
     *                     property="email",
     *                     type="string",
     *                     format="email",
     *                     example="john.doe@example.com"
     *                 ),
     *                 @OA\Property(property="firstName", type="string", example="John"),
     *                 @OA\Property(property="lastName", type="string", example="Doe"),
     *                 @OA\Property(property="displayName", type="string", nullable=true, example="John Doe"),
     *                 @OA\Property(property="phone", type="string", nullable=true, example="+8801712345678"),
     *                 @OA\Property(property="legacyResourceId", type="string", nullable=true, example="987654321"),
     *                 @OA\Property(property="state", type="string", example="ENABLED"),
     *                 @OA\Property(property="locale", type="string", nullable=true, example="en"),
     *                 @OA\Property(property="taxExempt", type="boolean", nullable=true, example=false),
     *                 @OA\Property(property="verifiedEmail", type="boolean", example=true),
     *                 @OA\Property(property="validEmailAddress", type="boolean", example=true),
     *                 @OA\Property(property="note", type="string", nullable=true, example="VIP customer from Shopify"),
     *
     *                 @OA\Property(
     *                     property="tags",
     *                     type="array",
     *                     @OA\Items(type="string", example="vip")
     *                 ),
     *
     *                 @OA\Property(
     *                     property="amountSpent",
     *                     type="object",
     *                     nullable=true,
     *                     @OA\Property(property="amount", type="number", format="float", example=1250.75)
     *                 ),
     *
     *                 @OA\Property(property="numberOfOrders", type="integer", nullable=true, example=12),
     *
     *                 @OA\Property(
     *                     property="createdAt",
     *                     type="string",
     *                     format="date-time",
     *                     example="2024-01-10T10:00:00Z"
     *                 ),
     *                 @OA\Property(
     *                     property="updatedAt",
     *                     type="string",
     *                     format="date-time",
     *                     example="2024-02-01T15:30:00Z"
     *                 ),
     *
     *                 @OA\Property(
     *                     property="addresses",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="string", example="gid://shopify/MailingAddress/111"),
     *                         @OA\Property(property="address1", type="string", example="House 12, Road 5"),
     *                         @OA\Property(property="address2", type="string", nullable=true, example="Apartment B4"),
     *                         @OA\Property(property="city", type="string", example="Dhaka"),
     *                         @OA\Property(property="province", type="string", nullable=true, example="Dhaka"),
     *                         @OA\Property(property="provinceCode", type="string", nullable=true, example="DHK"),
     *                         @OA\Property(property="country", type="string", example="Bangladesh"),
     *                         @OA\Property(property="countryCode", type="string", nullable=true, example="BD"),
     *                         @OA\Property(property="zip", type="string", example="1207"),
     *                         @OA\Property(property="phone", type="string", nullable=true, example="+8801712345678"),
     *                         @OA\Property(property="company", type="string", nullable=true, example="Example Ltd"),
     *                         @OA\Property(property="firstName", type="string", nullable=true, example="John"),
     *                         @OA\Property(property="lastName", type="string", nullable=true, example="Doe")
     *                     )
     *                 ),
     *
     *                 @OA\Property(
     *                     property="defaultAddress",
     *                     type="object",
     *                     nullable=true,
     *                     @OA\Property(property="id", type="string", example="gid://shopify/MailingAddress/333"),
     *                     @OA\Property(property="address1", type="string", example="House 12, Road 5"),
     *                     @OA\Property(property="address2", type="string", nullable=true, example="Apartment B4"),
     *                     @OA\Property(property="city", type="string", example="Dhaka"),
     *                     @OA\Property(property="province", type="string", nullable=true, example="Dhaka"),
     *                     @OA\Property(property="country", type="string", example="Bangladesh"),
     *                     @OA\Property(property="zip", type="string", example="1207")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Customer synced successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="Response", type="object",
     *                 @OA\Property(property="Version", type="string", example="1.0"),
     *                 @OA\Property(property="ErrorCode", type="string", example="0"),
     *                 @OA\Property(property="ErrorMsg", type="string", example="Customer synced successfully"),
     *                 @OA\Property(property="Value", type="object",
     *                     @OA\Property(property="cms_user_id", type="integer", example=2),
     *                     @OA\Property(property="customer_id", type="integer", example=2),
     *                     @OA\Property(property="shopify_customer_id", type="string", example="gid://shopify/Customer/1234567890"),
     *                     @OA\Property(property="email", type="string", example="john.doe@example.com"),
     *                     @OA\Property(property="full_name", type="string", example="John Doe"),
     *                     @OA\Property(property="first_name", type="string", example="John"),
     *                     @OA\Property(property="last_name", type="string", example="Doe"),
     *                     @OA\Property(property="phone", type="string", example="+8801712345678"),
     *                     @OA\Property(property="state", type="string", example="ENABLED"),
     *                     @OA\Property(
     *                         property="synced_at",
     *                         type="string",
     *                         format="date-time",
     *                         example="2026-02-05T15:10:39+01:00"
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="Response", type="object",
     *                 @OA\Property(property="Version", type="string", example="1.0"),
     *                 @OA\Property(property="ErrorCode", type="string", example="422"),
     *                 @OA\Property(property="ErrorMsg", type="string", example="Validation failed"),
     *                 @OA\Property(
     *                     property="Value",
     *                     type="array",
     *                     @OA\Items(type="string", example="Email already exists with a different Shopify ID.")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="Response", type="object",
     *                 @OA\Property(property="Version", type="string", example="1.0"),
     *                 @OA\Property(property="ErrorCode", type="string", example="500"),
     *                 @OA\Property(property="ErrorMsg", type="string", example="Unexpected server error"),
     *                 @OA\Property(property="Value", type="null", nullable=true)
     *             )
     *         )
     *     )
     * )
     */
    public function sync(SyncCustomerRequest $request): JsonResponse
    {
        try {
            $customerData = $request->input('customer');
            $customer = $this->customerService->syncFromShopify($customerData);

            return $this->successResponse([
                'cms_user_id' => $customer->id,
                'customer_id' => $customer->id,
                'shopify_customer_id' => $customer->shopify_customer_id,
                'email' => $customer->email,
                'full_name' => $customer->full_name,
                'first_name' => $customer->first_name,
                'last_name' => $customer->last_name,
                'phone' => $customer->phone,
                'state' => $customer->state,
                'synced_at' => $customer->last_synced_at?->toIso8601String(),
            ], 'Customer synced successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Sync failed', $e->getMessage());
        }
    }


    /**
     * GET /api/customers/{id}
     */
    /**
     * @OA\Get(
     *     path="/api/customers/{id}",
     *     operationId="showCustomerById",
     *     tags={"Customers"},
     *     summary="Show Customer by CMS ID",
     *     description="Retrieves full customer details including addresses, tags, and sync metadata using CMS customer ID.",
     *
     *     @OA\Parameter(
     *         name="password",
     *         in="query",
     *         description="Password for API authentication",
     *         required=true,
     *         @OA\Schema(type="string", example="A9f")
     *     ),
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="CMS Customer ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=2)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Customer retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="Response", type="object",
     *                 @OA\Property(property="Version", type="string", example="1.0"),
     *                 @OA\Property(property="ErrorCode", type="string", example="0"),
     *                 @OA\Property(property="ErrorMsg", type="string", example="Customer retrieved successfully"),
     *                 @OA\Property(property="Value", type="object",
     *
     *                     @OA\Property(property="id", type="integer", example=2),
     *                     @OA\Property(
     *                         property="shopify_customer_id",
     *                         type="string",
     *                         example="gid://shopify/Customer/1234567890"
     *                     ),
     *                     @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
     *                     @OA\Property(property="full_name", type="string", example="John Doe"),
     *                     @OA\Property(property="first_name", type="string", example="John"),
     *                     @OA\Property(property="last_name", type="string", example="Doe"),
     *                     @OA\Property(property="phone", type="string", nullable=true, example="+8801712345678"),
     *                     @OA\Property(property="state", type="string", example="ENABLED"),
     *
     *                     @OA\Property(property="amount_spent", type="number", format="float", example=1250.75),
     *                     @OA\Property(property="number_of_orders", type="integer", example=12),
     *
     *                     @OA\Property(
     *                         property="addresses",
     *                         type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=2),
     *                             @OA\Property(property="customer_id", type="integer", example=2),
     *                             @OA\Property(
     *                                 property="shopify_address_id",
     *                                 type="string",
     *                                 example="gid://shopify/MailingAddress/111"
     *                             ),
     *                             @OA\Property(property="address1", type="string", example="House 12, Road 5"),
     *                             @OA\Property(property="address2", type="string", nullable=true, example="Apartment B4"),
     *                             @OA\Property(property="city", type="string", example="Dhaka"),
     *                             @OA\Property(property="province", type="string", nullable=true, example="Dhaka"),
     *                             @OA\Property(property="province_code", type="string", nullable=true, example="DHK"),
     *                             @OA\Property(property="country", type="string", example="Bangladesh"),
     *                             @OA\Property(property="country_code", type="string", nullable=true, example="BD"),
     *                             @OA\Property(property="zip", type="string", example="1207"),
     *                             @OA\Property(property="phone", type="string", nullable=true, example="+8801712345678"),
     *                             @OA\Property(property="company", type="string", nullable=true, example="Example Ltd"),
     *                             @OA\Property(property="first_name", type="string", nullable=true, example="John"),
     *                             @OA\Property(property="last_name", type="string", nullable=true, example="Doe"),
     *                             @OA\Property(property="is_default", type="boolean", example=false),
     *                             @OA\Property(
     *                                 property="created_at",
     *                                 type="string",
     *                                 format="date-time",
     *                                 example="2026-02-05T14:03:37.000000Z"
     *                             ),
     *                             @OA\Property(
     *                                 property="updated_at",
     *                                 type="string",
     *                                 format="date-time",
     *                                 example="2026-02-05T14:10:39.000000Z"
     *                             )
     *                         )
     *                     ),
     *
     *                     @OA\Property(
     *                         property="default_address",
     *                         type="object",
     *                         nullable=true,
     *                         @OA\Property(property="id", type="integer", example=4),
     *                         @OA\Property(property="customer_id", type="integer", example=2),
     *                         @OA\Property(
     *                             property="shopify_address_id",
     *                             type="string",
     *                             example="gid://shopify/MailingAddress/333"
     *                         ),
     *                         @OA\Property(property="address1", type="string", example="House 12, Road 5"),
     *                         @OA\Property(property="address2", type="string", nullable=true, example="Apartment B4"),
     *                         @OA\Property(property="city", type="string", example="Dhaka"),
     *                         @OA\Property(property="province", type="string", nullable=true, example="Dhaka"),
     *                         @OA\Property(property="province_code", type="string", nullable=true, example=null),
     *                         @OA\Property(property="country", type="string", example="Bangladesh"),
     *                         @OA\Property(property="country_code", type="string", nullable=true, example=null),
     *                         @OA\Property(property="zip", type="string", example="1207"),
     *                         @OA\Property(property="phone", type="string", nullable=true, example=null),
     *                         @OA\Property(property="company", type="string", nullable=true, example=null),
     *                         @OA\Property(property="first_name", type="string", nullable=true, example=null),
     *                         @OA\Property(property="last_name", type="string", nullable=true, example=null),
     *                         @OA\Property(property="is_default", type="boolean", example=true),
     *                         @OA\Property(
     *                             property="created_at",
     *                             type="string",
     *                             format="date-time",
     *                             example="2026-02-05T14:10:39.000000Z"
     *                         ),
     *                         @OA\Property(
     *                             property="updated_at",
     *                             type="string",
     *                             format="date-time",
     *                             example="2026-02-05T14:10:39.000000Z"
     *                         )
     *                     ),
     *
     *                     @OA\Property(
     *                         property="tags",
     *                         type="array",
     *                         @OA\Items(type="string", example="vip")
     *                     ),
     *
     *                     @OA\Property(
     *                         property="created_at",
     *                         type="string",
     *                         format="date-time",
     *                         example="2026-02-05T15:03:37+01:00"
     *                     ),
     *                     @OA\Property(
     *                         property="last_synced_at",
     *                         type="string",
     *                         format="date-time",
     *                         example="2026-02-05T15:10:39+01:00"
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Customer not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="Response", type="object",
     *                 @OA\Property(property="Version", type="string", example="1.0"),
     *                 @OA\Property(property="ErrorCode", type="string", example="404"),
     *                 @OA\Property(property="ErrorMsg", type="string", example="Customer not found"),
     *                 @OA\Property(property="Value", type="null", nullable=true)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="Response", type="object",
     *                 @OA\Property(property="Version", type="string", example="1.0"),
     *                 @OA\Property(property="ErrorCode", type="string", example="500"),
     *                 @OA\Property(property="ErrorMsg", type="string", example="Unexpected server error"),
     *                 @OA\Property(property="Value", type="null", nullable=true)
     *             )
     *         )
     *     )
     * )
     */

    public function show(string $id): JsonResponse
    {
        try {
            $customer = Customer::with(['addresses', 'defaultAddress'])
                ->findOrFail($id);

            return $this->successResponse([
                'id' => $customer->id,
                'shopify_customer_id' => $customer->shopify_customer_id,
                'email' => $customer->email,
                'full_name' => $customer->full_name,
                'first_name' => $customer->first_name,
                'last_name' => $customer->last_name,
                'phone' => $customer->phone,
                'state' => $customer->state,
                'amount_spent' => (float) $customer->amount_spent,
                'number_of_orders' => $customer->number_of_orders,
                'addresses' => $customer->addresses,
                'default_address' => $customer->defaultAddress,
                'tags' => $customer->tags,
                'created_at' => $customer->created_at?->toIso8601String(),
                'last_synced_at' => $customer->last_synced_at?->toIso8601String(),
            ], 'Customer retrieved successfully');
        } catch (\Exception $e) {
            return $this->notFoundResponse('Customer not found');
        }
    }

    /**
     * GET /api/customers/by-shopify/{shopify_id}
     */

    /**
     * @OA\Get(
     *     path="/api/customers/by-shopify",
     *     operationId="showCustomerByShopifyId",
     *     tags={"Customers"},
     *     summary="Show Customer by Shopify ID",
     *     description="Retrieves customer basic details using Shopify customer ID.",
     *
     *     @OA\Parameter(
     *         name="password",
     *         in="query",
     *         description="Password for API authentication",
     *         required=true,
     *         @OA\Schema(type="string", example="A9f")
     *     ),
     *
     *     @OA\Parameter(
     *         name="shopify_id",
     *         in="query",
     *         description="Shopify Customer GID",
     *         required=true,
     *         @OA\Schema(
     *             type="string",
     *             example="gid://shopify/Customer/123456789"
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Customer retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="Response", type="object",
     *                 @OA\Property(property="Version", type="string", example="1.0"),
     *                 @OA\Property(property="ErrorCode", type="string", example="0"),
     *                 @OA\Property(property="ErrorMsg", type="string", example="Customer retrieved successfully"),
     *                 @OA\Property(property="Value", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(
     *                         property="shopify_customer_id",
     *                         type="string",
     *                         example="gid://shopify/Customer/123456789"
     *                     ),
     *                     @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *                     @OA\Property(property="full_name", type="string", example="John Doe"),
     *                     @OA\Property(property="state", type="string", example="ENABLED")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Missing Shopify ID parameter",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="Response", type="object",
     *                 @OA\Property(property="Version", type="string", example="1.0"),
     *                 @OA\Property(property="ErrorCode", type="string", example="400"),
     *                 @OA\Property(property="ErrorMsg", type="string", example="Missing shopify_id parameter"),
     *                 @OA\Property(property="Value", type="null", nullable=true)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Customer not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="Response", type="object",
     *                 @OA\Property(property="Version", type="string", example="1.0"),
     *                 @OA\Property(property="ErrorCode", type="string", example="404"),
     *                 @OA\Property(property="ErrorMsg", type="string", example="Customer not found"),
     *                 @OA\Property(property="Value", type="null", nullable=true)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="Response", type="object",
     *                 @OA\Property(property="Version", type="string", example="1.0"),
     *                 @OA\Property(property="ErrorCode", type="string", example="500"),
     *                 @OA\Property(property="ErrorMsg", type="string", example="Unexpected server error"),
     *                 @OA\Property(property="Value", type="null", nullable=true)
     *             )
     *         )
     *     )
     * )
     */
    public function getByShopifyId(Request $request): JsonResponse
    {
        // Get shopify_id from query param
        $shopifyId = $request->query('shopify_id');

        if (!$shopifyId) {
            return $this->errorResponse(
                'shopify_id query parameter is required',
                null,
                '1',
                400
            );
        }

        // Decode in case it's URL-encoded
        $shopifyId = urldecode($shopifyId);


        try {
            $customer = Customer::with(['addresses', 'defaultAddress'])
                ->byShopifyId($shopifyId)
                ->firstOrFail();

            return $this->successResponse([
                'id' => $customer->id,
                'shopify_customer_id' => $customer->shopify_customer_id,
                'email' => $customer->email,
                'full_name' => $customer->full_name,
                'state' => $customer->state,
            ], 'Customer retrieved successfully');
        } catch (ModelNotFoundException $e) {
            return $this->notFoundResponse('Customer not found');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve customer', $e->getMessage());
        }
    }


    // public function getCustomerOrderListByEmail(Request $request)
    // {
    //     try {
    //         $validator = Validator::make(
    //             ['email' => $request->query('email')],
    //             [
    //                 'email' => 'required|email',
    //             ]
    //         );

    //         if ($validator->fails()) {
    //             return $this->validationErrorResponse($validator->errors());
    //         }

    //         $email = $request->query('email');
    //         $username = explode('@', $email)[0];

    //         $customer = Customer::where('email', 'like', "{$username}%")->first();

    //         if (!$customer) {
    //             return $this->notFoundResponse("Customer not found with email {$email}");
    //         }

    //         $orders = Order::where('email', $customer->email)
    //             ->whereHas('items', function ($query) {
    //                 $query->whereNotNull('key_id')
    //                     ->where('key_id', '!=', '');
    //             })
    //             ->with([
    //                 'items' => function ($query) {
    //                     $query->select('id', 'order_id', 'key_id')
    //                         ->whereNotNull('key_id')
    //                         ->where('key_id', '!=', '');
    //                 }
    //             ])
    //             ->select('id', 'order_id_2game')
    //             ->latest()
    //             ->get()
    //             ->map(function ($order) {
    //                 return [
    //                     'order_id' => $order->order_id_2game,
    //                     'items' => $order->items->map(function ($item) {
    //                         return [
    //                             'key_id' => (int) $item->key_id,
    //                         ];
    //                     }),
    //                 ];
    //             });

    //         if ($orders->isEmpty()) {
    //             return $this->notFoundResponse(
    //                 'No redeemable orders found for this customer'
    //             );
    //         }

    //         return $this->successResponse(
    //             $orders,
    //             'Redeemable orders retrieved successfully'
    //         );
    //     } catch (Throwable $e) {
    //         return $this->serverErrorResponse(
    //             'Failed to fetch customer orders',
    //             $e->getMessage()
    //         );
    //     }
    // }


    public function getCustomerOrderListByEmail(Request $request)
    {
        try {
            $validator = Validator::make(
                ['email' => $request->query('email')],
                ['email' => 'required|email']
            );

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $email = $request->query('email');
            $username = explode('@', $email)[0];

            $customer = Customer::where('email', 'like', "{$username}%")->first();

            if (!$customer) {
                return $this->notFoundResponse("Customer not found with email {$email}");
            }

            $page = max(1, (int) $request->query('page', 1));
            $limit = max(1, (int) $request->query('limit', 10));

            $query = Order::with(['items.product.media'])
                ->where('email', $customer->email);

            $total = $query->count();

            $orders = $query
                ->orderByDesc('created_at')
                ->skip(($page - 1) * $limit)
                ->take($limit)
                ->get();

            $data = $orders->map(function ($order) {

                $items = $order->items->map(function ($item) {

                    $product = $item->product;

                    if (!$product) {
                        return null;
                    }

                    $mainImage = optional(
                        $product->media->firstWhere('is_main', 1)
                    )->url;

                    return [
                        'id' => $item->id,
                        'slug' => $product->seo_url_name ?? (string) $product->sku,
                        'name' => $product->name,
                        'image' => $mainImage,
                        'type' => $product->product_type ?? 'DIGITAL',
                        'currency_code' => $item->currency_code,
                        'key_id'=> $item->key_id ?? null
                    ];
                })->filter()->values();

                $types = $items->pluck('type')->unique()->values()->toArray();
                $currencies = $items->pluck('currency_code')->unique()->values()->toArray();

                return [
                    'id' => $order->order_id_2game,
                    'status' => $order->status,
                    'createdAt' => optional($order->created_at)->toIso8601String(),
                    'types' => $types,
                    'total' => (float) $order->grand_total,
                    'currencies' => $currencies,
                    'country_code' => $order->country_code,
                    'itemsCount' => $items->count(),
                    'items' => $items,
                ];
            });

            $totalPages = (int) ceil($total / $limit);

            return $this->successResponse([
                'data' => $data,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'totalPages' => $totalPages,
                    'hasNextPage' => $page < $totalPages,
                    'hasPreviousPage' => $page > 1,
                ]
            ], 'Orders retrieved successfully');
        } catch (Throwable $e) {
            return $this->serverErrorResponse(
                'Failed to fetch customer orders',
                $e->getMessage()
            );
        }
    }
}

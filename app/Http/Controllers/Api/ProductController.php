<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Helpers;
use App\Http\Controllers\Controller;
use App\Http\Resources\IncommPriceResource;
use App\Http\Resources\PriceResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ProductResourceV2;
use App\Http\Resources\ProductResourceV3;
use App\Http\Resources\ZtormPriceResource;
use App\Models\Localization;
use App\Models\Option;
use App\Models\Price;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Openai\TranslationService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Throwable;

class ProductController extends Controller
{
    use ApiResponse;
    protected TranslationService $translationService;

    public function __construct(TranslationService $translationService)
    {
        $this->translationService = $translationService;
    }

    /**
     * @OA\Get(
     *     path="/api/products",
     *     operationId="getProductsList",
     *     tags={"Products"},
     *     summary="Get paginated list of products",
     *     description="Returns a paginated list of products with all associated data. Requires password authentication.",
     *     @OA\Parameter(
     *         name="password",
     *         in="query",
     *         description="Password for authentication",
     *         required=true,
     *         @OA\Schema(type="string", example="A9f")
     *     ),
     *     @OA\Parameter(
     *         name="length",
     *         in="query",
     *         description="Number of products to return",
     *         required=true,
     *         @OA\Schema(type="integer", example=10, minimum=1, maximum=1000)
     *     ),
     *     @OA\Parameter(
     *         name="offset",
     *         in="query",
     *         description="Number of products to skip",
     *         required=false,
     *         @OA\Schema(type="integer", example=0, minimum=0)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="Response", type="object",
     *                 @OA\Property(property="Version", type="string", example="1.0"),
     *                 @OA\Property(property="ErrorCode", type="string", example="0"),
     *                 @OA\Property(property="ErrorMsg", type="string", example="OK"),
     *                 @OA\Property(property="Value", type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="sku", type="integer", example=78647),
     *                         @OA\Property(property="product_type", type="string", example="Game"),
     *                         @OA\Property(property="default_language", type="string", example="en"),
     *                         @OA\Property(property="allowed_countries", type="array", @OA\Items(type="string", example="BR")),
     *                         @OA\Property(property="localizations", type="array",
     *                             @OA\Items(
     *                                 type="object",
     *                                 @OA\Property(property="locale", type="string", example="en"),
     *                                 @OA\Property(property="title", type="string", example="Ready or Not"),
     *                                 @OA\Property(property="short_description", type="string", example="<p>Ready or Not is an intense, tactical FPS...</p>"),
     *                                 @OA\Property(property="long_description", type="string", example="<p>Full game description...</p>"),
     *                                 @OA\Property(property="seo_tags", type="array", @OA\Items(type="string", example="Ready or Not PC Game Steam")),
     *                                 @OA\Property(property="genre_tags", type="array", @OA\Items(type="string", example="action")),
     *                                 @OA\Property(property="franchise_tags", type="array", @OA\Items(type="string", example="VOID Interactive")),
     *                                 @OA\Property(property="system_requirements", type="object", nullable=true,
     *                                     @OA\Property(property="requirements_info", type="array", @OA\Items(type="string", example="Steam account required")),
     *                                     @OA\Property(property="minimum", type="array", @OA\Items(type="string", example="OS: Windows 10")),
     *                                     @OA\Property(property="recommended", type="array", @OA\Items(type="string", example="Graphics: Nvidia GTX 1060"))
     *                                 )
     *                             )
     *                         ),
     *                         @OA\Property(property="media", type="object",
     *                             @OA\Property(property="images", type="array",
     *                                 @OA\Items(
     *                                     type="object",
     *                                     @OA\Property(property="url", type="string", example="https://images.2game.com/boxshotcu/78647_4i1BYTK6_full.jpg"),
     *                                     @OA\Property(property="is_main", type="boolean", example=true)
     *                                 )
     *                             ),
     *                             @OA\Property(property="videos", type="array",
     *                                 @OA\Items(
     *                                     type="object",
     *                                     @OA\Property(property="url", type="string", example="https://www.youtube.com/watch?v=z2FTd1uOEzQ")
     *                                 )
     *                             ),
     *                             @OA\Property(property="videos_steam", type="array", @OA\Items(type="object"))
     *                         ),
     *                         @OA\Property(property="tags", type="array", @OA\Items(type="string")),
     *                         @OA\Property(property="ratings", type="object",
     *                             @OA\Property(property="metacritic_score", type="string", example="4.1"),
     *                             @OA\Property(property="metacritic_label", type="string", example=""),
     *                             @OA\Property(property="pegi_ratings", type="array", @OA\Items(type="string"))
     *                         ),
     *                         @OA\Property(property="community_discussion", type="object",
     *                             @OA\Property(property="discord_server", type="string", example="")
     *                         ),
     *                         @OA\Property(property="metadata", type="object",
     *                             @OA\Property(property="genres", type="array", @OA\Items(type="string", example="action")),
     *                             @OA\Property(property="platform", type="array", @OA\Items(type="string", example="Steam")),
     *                             @OA\Property(property="release_date", type="string", example="2023-12-13"),
     *                             @OA\Property(property="system_requirements", type="object",
     *                                 @OA\Property(property="requirements_info", type="array", @OA\Items(type="string", example="Steam account required")),
     *                                 @OA\Property(property="minimum", type="array", @OA\Items(type="string", example="OS: Windows 10")),
     *                                 @OA\Property(property="recommended", type="array", @OA\Items(type="string", example="Graphics: Nvidia GTX 1060"))
     *                             ),
     *                             @OA\Property(property="drm_type", type="string", example="Steam"),
     *                             @OA\Property(property="developer", type="object", @OA\Property(property="Developer", type="string", example="VOID Interactive")),
     *                             @OA\Property(property="publisher", type="string", example="Green Man Loaded"),
     *                             @OA\Property(property="status", type="string", example="Active"),
     *                             @OA\Property(property="dlc", type="boolean", example=true),
     *                             @OA\Property(property="dlc_products_ids", type="array", @OA\Items(type="string", example="82617")),
     *                             @OA\Property(property="editions_products_ids", type="array", @OA\Items(type="string")),
     *                             @OA\Property(property="supported_languages", type="object",
     *                                 @OA\Property(property="interface", type="array", @OA\Items(type="string", example="en")),
     *                                 @OA\Property(property="full_audio", type="array", @OA\Items(type="string", example="en")),
     *                                 @OA\Property(property="subtitles", type="array", @OA\Items(type="string"))
     *                             )
     *                         ),
     *                         @OA\Property(property="localization_needed", type="object", example={"BRL":{"pt-br"}, "CLP":{"es-419"}}),
     *                         @OA\Property(property="last_updated", type="integer", example=1718629191)
     *                     )
     *                 )
     *             )
     *         )
     *     )
     * )
     */


    public function index(Request $request)
    {
        $response = Helpers::validateLengthOffset($request);
        if ($response) {
            return $response;
        }

        $length = (int) $request->get('length', 10);
        $offset = (int) $request->get('offset', 0);
        $products = Product::query()
            ->with(['prices', 'media', 'localizations', 'rating', 'tags'])
            ->skip($offset)
            ->take($length)
            ->get();

        return response()->json([
            'Response' => [
                'Version'   => '1.0',
                'ErrorCode' => '0',
                'ErrorMsg'  => 'OK',
                'Value'     => ProductResource::collection($products),
            ]
        ]);
    }


    public function indexV3(Request $request)
    {
        $response = Helpers::validateLengthOffset($request);
        if ($response) {
            return $response;
        }

        $length = (int) $request->get('length', 10);
        $offset = (int) $request->get('offset', 0);
        $products = Product::query()
            ->with(['prices', 'media', 'localizations', 'rating', 'tags'])
            ->skip($offset)
            ->take($length)
            ->get();

        return response()->json([
            'Response' => [
                'Version'   => '1.0',
                'ErrorCode' => '0',
                'ErrorMsg'  => 'OK',
                'Value'     => ProductResourceV3::collection($products),
            ]
        ]);
    }




    /**
     * @OA\Get(
     *     path="/api/products/{product_id}",
     *     operationId="getProductById",
     *     tags={"Products"},
     *     summary="Get product by ID",
     *     description="Returns a single product by its SKU with all associated data. Requires password authentication.",
     *     @OA\Parameter(
     *         name="password",
     *         in="query",
     *         description="Password for authentication",
     *         required=true,
     *         @OA\Schema(type="string", example="A9f")
     *     ),
     *     @OA\Parameter(
     *         name="product_id",
     *         in="path",
     *         description="Product SKU or ID",
     *         required=true,
     *         @OA\Schema(type="string", example="78647")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="Response", type="object",
     *                 @OA\Property(property="Version", type="string", example="1.0"),
     *                 @OA\Property(property="ErrorCode", type="string", example="0"),
     *                 @OA\Property(property="ErrorMsg", type="string", example="OK"),
     *                 @OA\Property(property="Value", type="object",
     *                     @OA\Property(property="sku", type="integer", example=78647),
     *                     @OA\Property(property="product_type", type="string", example="Game"),
     *                     @OA\Property(property="default_language", type="string", example="en", nullable=true),
     *                     @OA\Property(property="allowed_countries", type="array", @OA\Items(type="string", example="BR")),
     *                     @OA\Property(
     *                         property="localizations",
     *                         type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="locale", type="string", example="en"),
     *                             @OA\Property(property="title", type="string", example="Ready or Not"),
     *                             @OA\Property(property="short_description", type="string", example="<p>Ready or Not is an intense, tactical FPS game.</p>"),
     *                             @OA\Property(property="long_description", type="string", example="<p>Ready or Not is an intense, tactical FPS game with SWAT missions...</p>"),
     *                             @OA\Property(property="seo_tags", type="array", @OA\Items(type="string", example="Ready or Not PC Game Steam")),
     *                             @OA\Property(property="genre_tags", type="array", @OA\Items(type="string", example="action")),
     *                             @OA\Property(property="franchise_tags", type="array", @OA\Items(type="string", example="Ready or Not")),
     *                             @OA\Property(property="system_requirements", type="object",
     *                                 @OA\Property(property="minimum", type="array", @OA\Items(type="string", example="Requires a 64-bit processor and OS")),
     *                                 @OA\Property(property="recommended", type="array", @OA\Items(type="string", example="Requires a 64-bit processor and OS"))
     *                             )
     *                         )
     *                     ),
     *                     @OA\Property(
     *                         property="media",
     *                         type="object",
     *                         @OA\Property(property="images", type="array",
     *                             @OA\Items(
     *                                 type="object",
     *                                 @OA\Property(property="url", type="string", example="https://images.2game.com/boxshotcu/78647_4i1BYTK6_full.jpg"),
     *                                 @OA\Property(property="is_main", type="boolean", example=true)
     *                             )
     *                         ),
     *                         @OA\Property(property="videos", type="array", @OA\Items(type="object",
     *                             @OA\Property(property="url", type="string", example="https://www.youtube.com/watch?v=z2FTd1uOEzQ")
     *                         )),
     *                         @OA\Property(property="videos_steam", type="array", @OA\Items(type="object"))
     *                     ),
     *                     @OA\Property(property="tags", type="array", @OA\Items(type="string")),
     *                     @OA\Property(
     *                         property="ratings",
     *                         type="object",
     *                         @OA\Property(property="metacritic_score", type="string", example="4.1"),
     *                         @OA\Property(property="metacritic_label", type="string", example=""),
     *                         @OA\Property(property="pegi_ratings", type="array", @OA\Items(type="string"))
     *                     ),
     *                     @OA\Property(
     *                         property="community_discussion",
     *                         type="object",
     *                         @OA\Property(property="discord_server", type="string", example="")
     *                     ),
     *                     @OA\Property(
     *                         property="metadata",
     *                         type="object",
     *                         @OA\Property(property="genres", type="array", @OA\Items(type="string", example="action")),
     *                         @OA\Property(property="platform", type="array", @OA\Items(type="string", example="Steam")),
     *                         @OA\Property(property="release_date", type="string", example="2023-12-13", nullable=true),
     *                         @OA\Property(property="drm_type", type="string", example="Steam"),
     *                         @OA\Property(property="developer", type="array", @OA\Items(type="string", example="VOID Interactive")),
     *                         @OA\Property(property="publisher", type="string", example="Green Man Loaded"),
     *                         @OA\Property(property="status", type="string", example="Active"),
     *                         @OA\Property(property="dlc", type="boolean", example=true),
     *                         @OA\Property(property="dlc_products_ids", type="array", @OA\Items(type="string", example="82617")),
     *                         @OA\Property(property="editions_products_ids", type="array", @OA\Items(type="string")),
     *                         @OA\Property(
     *                             property="supported_languages",
     *                             type="object",
     *                             @OA\Property(property="interface", type="array", @OA\Items(type="string", example="en")),
     *                             @OA\Property(property="full_audio", type="array", @OA\Items(type="string", example="en")),
     *                             @OA\Property(property="subtitles", type="array", @OA\Items(type="string", example="en"))
     *                         )
     *                     ),
     *                     @OA\Property(
     *                         property="localization_needed",
     *                         type="object",
     *                         @OA\Property(property="BRL", type="array", @OA\Items(type="string", example="pt-br")),
     *                         @OA\Property(property="CLP", type="array", @OA\Items(type="string", example="es-419")),
     *                         @OA\Property(property="COP", type="array", @OA\Items(type="string", example="es-419")),
     *                         @OA\Property(property="CRC", type="array", @OA\Items(type="string", example="es-419")),
     *                         @OA\Property(property="PEN", type="array", @OA\Items(type="string", example="es-419")),
     *                         @OA\Property(property="UYU", type="array", @OA\Items(type="string", example="es-419"))
     *                     ),
     *                     @OA\Property(property="last_updated", type="integer", example=1718629191)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="Response", type="object",
     *                 @OA\Property(property="Version", type="string", example="1.0"),
     *                 @OA\Property(property="ErrorCode", type="string", example="404"),
     *                 @OA\Property(property="ErrorMsg", type="string", example="Product not found"),
     *                 @OA\Property(property="Value", type="array", @OA\Items())
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized, Invalid Password",
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
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="Response", type="object",
     *                 @OA\Property(property="Version", type="string", example="1.0"),
     *                 @OA\Property(property="ErrorCode", type="string", example="500"),
     *                 @OA\Property(property="ErrorMsg", type="string", example="Internal Server Error"),
     *                 @OA\Property(property="Value", type="array", @OA\Items())
     *             )
     *         )
     *     )
     * )
     */


    public function getProductById($product_id)
    {
        $product = Product::with(['media', 'localizations', 'rating', 'tags'])->where('sku', $product_id)->first();

        if (!$product) {
            return response()->json([
                'Response' => [
                    'Version'   => '1.0',
                    'ErrorCode' => '404',
                    'ErrorMsg'  => 'Product not found',
                    'Value'     => [],
                ]
            ], 404);
        }

        return response()->json([
            'Response' => [
                'Version'   => '1.0',
                'ErrorCode' => '0',
                'ErrorMsg'  => 'OK',
                'Value'     => new ProductResource($product),
            ]
        ]);
    }

    public function getProductByIdV3($product_id)
    {
        $product = Product::with(['media', 'localizations', 'rating', 'tags'])->where('sku', $product_id)->first();

        if (!$product) {
            return response()->json([
                'Response' => [
                    'Version'   => '1.0',
                    'ErrorCode' => '404',
                    'ErrorMsg'  => 'Product not found',
                    'Value'     => [],
                ]
            ], 404);
        }

        return response()->json([
            'Response' => [
                'Version'   => '1.0',
                'ErrorCode' => '0',
                'ErrorMsg'  => 'OK',
                'Value'     => new ProductResourceV3($product),
            ]
        ]);
    }








    /**
     * @OA\Get(
     *     path="/api/products/prices/{product_id}",
     *     operationId="getProductPrices",
     *     tags={"Products"},
     *     summary="Get paginated prices for a specific product",
     *     description="Returns a paginated list of prices for the given product ID. Supports multiple sources (Ztorm/Incomm). Requires static password authentication.",
     *     @OA\Parameter(
     *         name="product_id",
     *         in="path",
     *         description="ID of the product",
     *         required=true,
     *         @OA\Schema(type="integer", example=1419)
     *     ),
     *     @OA\Parameter(
     *         name="password",
     *         in="query",
     *         description="Static password for authentication",
     *         required=true,
     *         @OA\Schema(type="string", example="password")
     *     ),
     * @OA\Response(
     *     response=200,
     *     description="Successful operation (Ztorm & Incomm)",
     *     @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="Response", type="object",
     *             @OA\Property(property="Version", type="string", default="1.0", example="1.0"),
     *             @OA\Property(property="ErrorCode", type="string", default="0", example="0"),
     *             @OA\Property(property="ErrorMsg", type="string", default="OK", example="OK"),
     *             @OA\Property(property="Value", type="object",
     *                 @OA\Property(property="product_id", type="integer", default=0, example=1419),
     *                 @OA\Property(property="currency_pricing", type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="currency", type="string", example="USD"),
     *                         @OA\Property(property="price", type="number", format="float", nullable=true, example=29.99),
     *                         @OA\Property(property="countries", type="array", @OA\Items(type="string"), default={}),
     *                         @OA\Property(property="discount_percent", type="number", format="float", example=10),
     *                         @OA\Property(property="discount_valid_from", type="integer", example=1732131372),
     *                         @OA\Property(property="discount_valid_to", type="integer", example=1732217772),
     *                         @OA\Property(property="price_update_timestamp", type="integer", example=1732131372),
     *                         @OA\Property(property="region", type="string", nullable=true, example="USD_LATAM"),
     *                         @OA\Property(property="title", type="string", nullable=true, example="R$25 - DDP 250")
     *                     )
     *                 )
     *             )
     *         )
     *     )
     * ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation Error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="Response", type="object",
     *                 @OA\Property(property="Version", type="string", example="1.0"),
     *                 @OA\Property(property="ErrorCode", type="string", example="422"),
     *                 @OA\Property(property="ErrorMsg", type="string", example="The length query parameter is required."),
     *                 @OA\Property(property="Value", type="array", @OA\Items())
     *             )
     *         )
     *     ),
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
     *     @OA\Response(
     *         response=404,
     *         description="Product not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="Response", type="object",
     *                 @OA\Property(property="Version", type="string", example="1.0"),
     *                 @OA\Property(property="ErrorCode", type="string", example="404"),
     *                 @OA\Property(property="ErrorMsg", type="string", example="Product not found"),
     *                 @OA\Property(property="Value", type="array", @OA\Items())
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="Response", type="object",
     *                 @OA\Property(property="Version", type="string", example="1.0"),
     *                 @OA\Property(property="ErrorCode", type="string", example="500"),
     *                 @OA\Property(property="ErrorMsg", type="string", example="Internal Server Error"),
     *                 @OA\Property(property="Value", type="array", @OA\Items())
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Price import status",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="Response", type="object",
     *                 @OA\Property(property="Version", type="string", example="1.0"),
     *                 @OA\Property(property="ErrorCode", type="string", example="409"),
     *                 @OA\Property(property="ErrorMsg", type="string", example="Price import not completed Current status: running"),
     *                 @OA\Property(property="Value", type="array", @OA\Items())
     *             )
     *         )
     *     ),
     * )
     */

    public function getPricesByPrtoductId(Request $request, $product_id)
    {
        $import_status = Option::get('ztorm_price_import');
        if ($import_status == 'running') {
            return response()->json([
                'Response' => [
                    'Version'   => '1.0',
                    'ErrorCode' => '409',
                    'ErrorMsg'  => 'Price import not completed. Current status: ' . $import_status,
                    'Value'     => [],
                ]
            ], 409);
        }

        $product = Product::where('sku', $product_id)->first();
        if (!$product) {
            return response()->json([
                'Response' => [
                    'Version'   => '1.0',
                    'ErrorCode' => '404',
                    'ErrorMsg'  => 'Product not found',
                    'Value'     => [],
                ]
            ], 404);
        }

        if ($product->source == 1) {
            $prices = DB::connection('mysql_no_strict_group')
                ->table('prices')
                ->select([
                    'product_id',
                    'currency',
                    DB::raw('CASE 
                    WHEN is_converted = 1 AND steam_price IS NOT NULL AND steam_price > 0 
                    THEN steam_price 
                    ELSE price 
                END as final_price'),
                    'cost_estimate',
                    'is_active',
                    'is_converted',
                    'discount_valid_from',
                    'discount_valid_to',
                    'price_update_timestamp',
                    'discount_percent',
                    DB::raw('GROUP_CONCAT(country_code ORDER BY country_code) as country_codes'),
                ])
                ->where('product_id', $product->sku)
                ->whereNotNull('price')
                ->where('price', '>', 0)
                ->where('is_active', 1)
                ->groupBy(
                    'currency',
                    DB::raw('CASE 
                    WHEN is_converted = 1 AND steam_price IS NOT NULL AND steam_price > 0 
                    THEN steam_price 
                    ELSE price 
                END')
                )
                ->havingRaw('final_price > 0')
                ->get();
            $currencyPricing = ZtormPriceResource::collection($prices);
        } elseif ($product->source == 2 || $product->source == 3 || $product->source == 4) {
            $prices = $product->prices()
                ->whereNotNull('price')
                ->where('price', '>', 0)
                ->where('is_active', 1)
                ->get();

            $currencyPricing = IncommPriceResource::collection($prices);
        } else {
            $currencyPricing = [];
        }

        return response()->json([
            'Response' => [
                'Version'   => '1.0',
                'ErrorCode' => '0',
                'ErrorMsg'  => 'OK',
                'Value'     => [
                    'product_id'       => (int) $product->sku,
                    'currency_pricing' => $currencyPricing,
                ],
            ]
        ]);
    }



    public function getPricesByProductIdV3(Request $request, $product_id)
    {
        $import_status = Option::get('ztorm_price_import');
        if ($import_status === 'running') {
            return response()->json([
                'Response' => [
                    'Version'   => '1.0',
                    'ErrorCode' => '409',
                    'ErrorMsg'  => 'Price import not completed. Current status: ' . $import_status,
                    'Value'     => [],
                ]
            ], 409);
        }

        $product = Product::where('sku', $product_id)->first();
        if (!$product) {
            return response()->json([
                'Response' => [
                    'Version'   => '1.0',
                    'ErrorCode' => '404',
                    'ErrorMsg'  => 'Product not found',
                    'Value'     => [],
                ]
            ], 404);
        }

        // Load all prices
        if ($product->source == 1) {
            $prices = $product->prices()
                ->select([
                    'product_id',
                    'currency',
                    DB::raw('CASE 
                    WHEN is_converted = 1 AND steam_price IS NOT NULL AND steam_price > 0 
                    THEN steam_price 
                    ELSE price 
                END as price'),
                    'discount_valid_from',
                    'discount_valid_to',
                    'price_update_timestamp',
                    'discount_percent',
                ])
                ->where('product_id', $product->sku)
                ->whereNotNull('price')
                ->where('price', '>', 0)
                ->where('is_active', 1)
                ->get()
                ->keyBy('currency');
        } elseif ($product->source == 2 || $product->source == 3 || $product->source == 4) {
            $prices = $product->prices()
                ->whereNotNull('price')
                ->where('price', '>', 0)
                ->where('is_active', 1)
                ->get()
                ->keyBy('currency');
        } else {
            $prices = collect();
        }

        // Load store mapping
        $stores = config('shopify.store_matrix.stores');

        $storePrices = [];

        foreach ($stores as $storeKey => $store) {
            $currency = $store['currency'];

            if ($prices->has($currency)) {
                $priceObj = $prices->get($currency);
                $discountAmount = $priceObj->isDiscountActive()
                    ? $priceObj->discountAmount()
                    : null;
                $storePrices[$storeKey] = [
                    'currency'               => $currency,
                    'price'                  => $priceObj->price,
                    'price_after_discount'   => $discountAmount ?? null,
                    'discount_percent'       => $priceObj->discount_percent,
                    'discount_valid_from'    => (int) ($priceObj->discount_valid_from ?? 0),
                    'discount_valid_to'      => (int) ($priceObj->discount_valid_to ?? 0),
                    'price_update_timestamp' => (int) ($priceObj->price_update_timestamp ?? 0),
                ];
            }
            // else {
            //     $storePrices[$storeKey] = [
            //         'currency'               => $currency,
            //         'price'                  => null,
            //         'discount_percent'       => null,
            //         'discount_valid_from'    => null,
            //         'discount_valid_to'      => null,
            //         'price_update_timestamp' => null,
            //     ];
            // }
        }

        return response()->json([
            'Response' => [
                'Version'   => '1.0',
                'ErrorCode' => '0',
                'ErrorMsg'  => 'OK',
                'Value'     => [
                    'product_id' => (int) $product->sku,
                    'prices'     => $storePrices,
                ],
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/products/prices/all",
     *     operationId="getAllPrices",
     *     tags={"Products"},
     *     summary="Get paginated prices for all products",
     *     description="Returns a paginated list of prices across all products. Groups prices by product and applies the correct resource mapping (Ztorm/Incomm). Requires static password authentication.",
     *     @OA\Parameter(
     *         name="password",
     *         in="query",
     *         description="password for authentication",
     *         required=true,
     *         @OA\Schema(type="string", example="password")
     *     ),
     *     @OA\Parameter(
     *         name="length",
     *         in="query",
     *         description="Number of price records to return",
     *         required=false,
     *         @OA\Schema(type="integer", example=10, minimum=1, maximum=1000)
     *     ),
     *     @OA\Parameter(
     *         name="offset",
     *         in="query",
     *         description="Number of price records to skip",
     *         required=false,
     *         @OA\Schema(type="integer", example=0, minimum=0)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation (Ztorm & Incomm)",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="Response", type="object",
     *                 @OA\Property(property="Version", type="string", default="1.0", example="1.0"),
     *                 @OA\Property(property="ErrorCode", type="string", default="0", example="0"),
     *                 @OA\Property(property="ErrorMsg", type="string", default="OK", example="OK"),
     *                 @OA\Property(property="Value", type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="product_id", type="integer", example=1419),
     *                         @OA\Property(property="currency_pricing", type="array",
     *                             @OA\Items(
     *                                 type="object",
     *                                 @OA\Property(property="currency", type="string", example="USD"),
     *                                 @OA\Property(property="price", type="number", format="float", nullable=true, example=29.99),
     *                                 @OA\Property(property="countries", type="array", @OA\Items(type="string"), default={}),
     *                                 @OA\Property(property="discount_percent", type="number", format="float", example=10),
     *                                 @OA\Property(property="discount_valid_from", type="integer", example=1732131372),
     *                                 @OA\Property(property="discount_valid_to", type="integer", example=1732217772),
     *                                 @OA\Property(property="price_update_timestamp", type="integer", example=1732131372),
     *                                 @OA\Property(property="region", type="string", nullable=true, example="USD_LATAM"),
     *                                 @OA\Property(property="title", type="string", nullable=true, example="R$25 - DDP 250")
     *                             )
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation Error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="Response", type="object",
     *                 @OA\Property(property="Version", type="string", example="1.0"),
     *                 @OA\Property(property="ErrorCode", type="string", example="422"),
     *                 @OA\Property(property="ErrorMsg", type="string", example="The length query parameter is required."),
     *                 @OA\Property(property="Value", type="array", @OA\Items())
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Price import status",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="Response", type="object",
     *                 @OA\Property(property="Version", type="string", example="1.0"),
     *                 @OA\Property(property="ErrorCode", type="string", example="409"),
     *                 @OA\Property(property="ErrorMsg", type="string", example="Price import not completed Current status: running"),
     *                 @OA\Property(property="Value", type="array", @OA\Items())
     *             )
     *         )
     *     ),
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
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="Response", type="object",
     *                 @OA\Property(property="Version", type="string", example="1.0"),
     *                 @OA\Property(property="ErrorCode", type="string", example="500"),
     *                 @OA\Property(property="ErrorMsg", type="string", example="Internal Server Error"),
     *                 @OA\Property(property="Value", type="array", @OA\Items())
     *             )
     *         )
     *     )
     * )
     */
    // public function getPrices(Request $request)
    // {
    //     $import_status = Option::get('ztorm_price_import');

    //     if ($import_status == 'running') {
    //         return response()->json([
    //             'Response' => [
    //                 'Version'   => '1.0',
    //                 'ErrorCode' => '409',
    //                 'ErrorMsg'  => 'Price import not completed. Current status: ' . $import_status,
    //                 'Value'     => [],
    //             ]
    //         ], 409);
    //     }

    //     $response = Helpers::validateLengthOffset($request);
    //     if ($response) {
    //         return $response;
    //     }

    //     $length = (int) $request->query('length', 10);
    //     $offset = (int) $request->query('offset', 0);

    //     $prices = Price::with('product')
    //         ->whereNotNull('price')
    //         ->where('is_active', 1)
    //         ->skip($offset)
    //         ->take($length)
    //         ->get();

    //     // Group prices by product
    //     $grouped = $prices->groupBy('product_id')->map(function ($prices) {
    //         $product = $prices->first()->product;

    //         if ($product->source == 1) {
    //             $resourceClass = ZtormPriceResource::class;
    //         } elseif ($product->source == 2) {
    //             $resourceClass = IncommPriceResource::class;
    //         } else {
    //             return null;
    //         }

    //         return [
    //             'product_id'       => (int) $product->sku,
    //             'currency_pricing' => $resourceClass::collection($prices),
    //         ];
    //     })->values();

    //     return response()->json([
    //         'Response' => [
    //             'Version'   => '1.0',
    //             'ErrorCode' => '0',
    //             'ErrorMsg'  => 'OK',
    //             'Value'     => $grouped,
    //         ]
    //     ]);
    // }

    // public function getPrices(Request $request)
    // {
    //     $import_status = Option::get('ztorm_price_import');

    //     if ($import_status === 'running') {
    //         return response()->json([
    //             'Response' => [
    //                 'Version'   => '1.0',
    //                 'ErrorCode' => '409',
    //                 'ErrorMsg'  => 'Price import not completed. Current status: ' . $import_status,
    //                 'Value'     => [],
    //             ]
    //         ], 409);
    //     }

    //     $response = Helpers::validateLengthOffset($request);
    //     if ($response) {
    //         return $response;
    //     }

    //     $length = (int) $request->query('length', 10);
    //     $offset = (int) $request->query('offset', 0);


    //     $productIds = Price::whereNotNull('price')
    //         ->where('is_active', 1)
    //         ->select('product_id')
    //         ->distinct()
    //         ->orderBy('product_id')
    //         ->skip($offset)
    //         ->take($length)
    //         ->pluck('product_id');

    //     if ($productIds->isEmpty()) {
    //         return response()->json([
    //             'Response' => [
    //                 'Version'   => '1.0',
    //                 'ErrorCode' => '0',
    //                 'ErrorMsg'  => 'OK',
    //                 'Value'     => [],
    //             ]
    //         ]);
    //     }


    //     $prices = Price::with('product')
    //         ->whereIn('product_id', $productIds)
    //         ->whereNotNull('price')
    //         ->where('is_active', 1)
    //         ->get();

    //     $grouped = $prices
    //         ->groupBy('product_id')
    //         ->map(function ($prices) {
    //             $product = $prices->first()->product;

    //             if (!$product) {
    //                 return null;
    //             }

    //             if ($product->source == 1) {
    //                 $resourceClass = ZtormPriceResource::class;
    //             } elseif ($product->source == 2) {
    //                 $resourceClass = IncommPriceResource::class;
    //             } else {
    //                 return null;
    //             }

    //             return [
    //                 'product_id'       => (int) $product->sku,
    //                 'currency_pricing' => $resourceClass::collection($prices),
    //             ];
    //         })
    //         ->filter()
    //         ->values();

    //     return response()->json([
    //         'Response' => [
    //             'Version'   => '1.0',
    //             'ErrorCode' => '0',
    //             'ErrorMsg'  => 'OK',
    //             'Value'     => $grouped,
    //         ]
    //     ]);
    // }


    public function getPrices(Request $request)
    {
        $import_status = Option::get('ztorm_price_import');

        if ($import_status === 'running') {
            return response()->json([
                'Response' => [
                    'Version'   => '1.0',
                    'ErrorCode' => '409',
                    'ErrorMsg'  => 'Price import not completed. Current status: ' . $import_status,
                    'Value'     => [],
                ]
            ], 409);
        }

        $response = Helpers::validateLengthOffset($request);
        if ($response) {
            return $response;
        }

        $length = (int) $request->query('length', 10);
        $offset = (int) $request->query('offset', 0);

        $productIds = Price::whereNotNull('price')
            ->where('is_active', 1)
            ->select('product_id')
            ->distinct()
            ->orderBy('product_id')
            ->skip($offset)
            ->take($length)
            ->pluck('product_id');


        if ($productIds->isEmpty()) {
            return response()->json([
                'Response' => [
                    'Version'   => '1.0',
                    'ErrorCode' => '0',
                    'ErrorMsg'  => 'OK',
                    'Value'     => [],
                ]
            ]);
        }

        // Fetch all products and split by source
        $products = Product::whereIn('sku', $productIds)
            ->get()
            ->keyBy('sku');

        $ztormProductIds   = $products->where('source', 1)->pluck('sku');
        $incommProductIds  = $products->where('source', 2)->pluck('sku');

        // Ztorm: GROUP_CONCAT + final_price via CASE WHEN
        $ztormPrices = $ztormProductIds->isNotEmpty()
            ? DB::connection('mysql_no_strict_group')
            ->table('prices')
            ->select([
                'product_id',
                'currency',
                DB::raw('CASE WHEN is_converted = 1 AND steam_price IS NOT NULL AND steam_price > 0 THEN steam_price ELSE price END as final_price'),
                'cost_estimate',
                'is_active',
                'is_converted',
                'discount_valid_from',
                'discount_valid_to',
                'price_update_timestamp',
                'discount_percent',
                DB::raw('GROUP_CONCAT(country_code ORDER BY country_code) as country_codes'),
            ])
            ->whereIn('product_id', $ztormProductIds)
            ->whereNotNull('price')
            ->where('is_active', 1)
            ->groupBy(
                'product_id',
                'currency',
                DB::raw('CASE WHEN is_converted = 1 AND steam_price IS NOT NULL AND steam_price > 0 THEN steam_price ELSE price END')
            )
            ->get()
            ->groupBy('product_id')
            : collect();

        // Incomm: simple query, no GROUP_CONCAT needed
        $incommPrices = $incommProductIds->isNotEmpty()
            ? Price::whereIn('product_id', $incommProductIds)
            ->whereNotNull('price')
            ->where('is_active', 1)
            ->get()
            ->groupBy('product_id')
            : collect();

        $grouped = $productIds->map(function ($productId) use ($ztormPrices, $incommPrices, $products) {
            $product = $products->get($productId);

            if (!$product) {
                return null;
            }

            if ($product->source == 1) {
                $productPrices = $ztormPrices->get($productId, collect());
                $resourceClass = ZtormPriceResource::class;
            }
            // elseif ($product->source == 2) {
            //     $productPrices = $incommPrices->get($productId, collect());
            //     $resourceClass = IncommPriceResource::class;
            // } 
            else {
                return null;
            }

            if ($productPrices->isEmpty()) {
                return null;
            }

            return [
                'product_id'       => (int) $product->sku,
                'currency_pricing' => $resourceClass::collection($productPrices),
            ];
        })
            ->filter()
            ->values();

        return response()->json([
            'Response' => [
                'Version'   => '1.0',
                'ErrorCode' => '0',
                'ErrorMsg'  => 'OK',
                'Value'     => $grouped,
            ]
        ]);
    }


    public function getPricesV3(Request $request)
    {
        $import_status = Option::get('ztorm_price_import');

        if ($import_status === 'running') {
            return response()->json([
                'Response' => [
                    'Version'   => '1.0',
                    'ErrorCode' => '409',
                    'ErrorMsg'  => 'Price import not completed. Current status: ' . $import_status,
                    'Value'     => [],
                ]
            ], 409);
        }

        $response = Helpers::validateLengthOffset($request);
        if ($response) {
            return $response;
        }

        $length = (int) $request->query('length', 10);
        $offset = (int) $request->query('offset', 0);

        $productIds = Price::whereNotNull('price')
            ->where('is_active', 1)
            ->select('product_id')
            ->distinct()
            ->orderBy('product_id')
            ->skip($offset)
            ->take($length)
            ->pluck('product_id');

        if ($productIds->isEmpty()) {
            return response()->json([
                'Response' => [
                    'Version'   => '1.0',
                    'ErrorCode' => '0',
                    'ErrorMsg'  => 'OK',
                    'Value'     => [],
                ]
            ]);
        }

        // Fetch all products and split by source
        $products = Product::whereIn('sku', $productIds)
            ->get()
            ->keyBy('sku');

        // Source 1: Ztorm — uses steam_price when is_converted=1, else price
        $ztormProductIds = $products->where('source', 1)->pluck('sku');

        // Sources 2, 3, 4: Incomm / Point Nexus / Genba — use price directly
        $simplePriceProductIds = $products->whereIn('source', [2, 3, 4])->pluck('sku');

        // Build a reusable closure for the simple-price query (no steam_price logic)
        $fetchSimplePrices = function ($ids) {
            if ($ids->isEmpty()) {
                return collect();
            }

            return Price::whereIn('product_id', $ids)
                ->select([
                    'product_id',
                    'currency',
                    'price',
                    'discount_valid_from',
                    'discount_valid_to',
                    'price_update_timestamp',
                    'discount_percent',
                ])
                ->whereNotNull('price')
                ->where('price', '>', 0)
                ->where('is_active', 1)
                ->get()
                ->groupBy('product_id')
                ->map(fn($rows) => $rows->keyBy('currency'));
        };

        // Ztorm: prefer steam_price when is_converted=1 and steam_price > 0
        $ztormPrices = $ztormProductIds->isNotEmpty()
            ? Price::whereIn('product_id', $ztormProductIds)
            ->select([
                'product_id',
                'currency',
                DB::raw('CASE 
                    WHEN is_converted = 1 AND steam_price IS NOT NULL AND steam_price > 0 
                    THEN steam_price 
                    ELSE price 
                END as price'),
                'discount_valid_from',
                'discount_valid_to',
                'price_update_timestamp',
                'discount_percent',
            ])
            ->whereNotNull('price')
            ->where('price', '>', 0)
            ->where('is_active', 1)
            ->get()
            ->groupBy('product_id')
            ->map(fn($rows) => $rows->keyBy('currency'))
            : collect();

        // Incomm (2), Point Nexus (3), Genba (4): plain price
        $simplePrices = $fetchSimplePrices($simplePriceProductIds);

        // Load store mapping once
        $stores = config('shopify.store_matrix.stores');

        $grouped = $productIds->map(function ($productId) use ($products, $ztormPrices, $simplePrices, $stores) {
            $product = $products->get($productId);

            if (!$product) {
                return null;
            }

            $prices = match ((int) $product->source) {
                1       => $ztormPrices->get($productId, collect()),
                3, 4 => $simplePrices->get($productId, collect()),
                default => null,
            };

            if ($prices === null || $prices->isEmpty()) {
                return null;
            }

            $storePrices = [];

            foreach ($stores as $storeKey => $store) {
                $currency = $store['currency'];

                if ($prices->has($currency)) {
                    $priceObj = $prices->get($currency);
                    $discountAmount = $priceObj->isDiscountActive()
                        ? $priceObj->discountAmount()
                        : null;
                    $storePrices[$storeKey] = [
                        'currency'               => $currency,
                        'price'                  => $priceObj->price,
                        'price_after_discount'   => $discountAmount ?? null,
                        'discount_percent'       => $priceObj->discount_percent,
                        'discount_valid_from'    => (int) ($priceObj->discount_valid_from ?? 0),
                        'discount_valid_to'      => (int) ($priceObj->discount_valid_to ?? 0),
                        'price_update_timestamp' => (int) ($priceObj->price_update_timestamp ?? 0),
                    ];
                }
            }

            if (empty($storePrices)) {
                return null;
            }

            return [
                'product_id' => (int) $product->sku,
                'prices'     => $storePrices,
            ];
        })
            ->filter()
            ->values();

        return response()->json([
            'Response' => [
                'Version'   => '1.0',
                'ErrorCode' => '0',
                'ErrorMsg'  => 'OK',
                'Value'     => $grouped,
            ]
        ]);
    }





    /**
     * @OA\Post(
     *     path="/api/products/translation/{sku}",
     *     summary="Translate a single product into a single locale",
     *     description="Translates a product's title, short description, and long description from a source locale to a target locale and saves it in the localizations table.",
     *     tags={"Products"},
     *      @OA\Parameter(
     *         name="password",
     *         in="query",
     *         description="password for authentication",
     *         required=true,
     *         @OA\Schema(type="string", example="password")
     *     ),
     *
     *     @OA\Parameter(
     *         name="sku",
     *         in="path",
     *         description="Product SKU (unique identifier)",
     *         required=true,
     *         @OA\Schema(type="integer", example=12345)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"locale"},
     *             @OA\Property(property="locale", type="string", example="fr", description="Target locale for translation"),
     *             @OA\Property(property="source", type="string", example="en", description="Source locale (default: en)")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful translation",
     *         @OA\JsonContent(
     *             @OA\Property(property="Response", type="object",
     *                 @OA\Property(property="Version", type="string", example="1.0"),
     *                 @OA\Property(property="ErrorCode", type="string", example="0"),
     *                 @OA\Property(property="ErrorMsg", type="string", example="OK"),
     *                 @OA\Property(property="Value", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Source localization not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="Response", type="object",
     *                 @OA\Property(property="Version", type="string", example="1.0"),
     *                 @OA\Property(property="ErrorCode", type="string", example="404"),
     *                 @OA\Property(property="ErrorMsg", type="string", example="No source localization found for product 12345."),
     *                 @OA\Property(property="Value", type="null", example=null)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Translation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="Response", type="object",
     *                 @OA\Property(property="Version", type="string", example="1.0"),
     *                 @OA\Property(property="ErrorCode", type="string", example="500"),
     *                 @OA\Property(property="ErrorMsg", type="string", example="Translation failed: OpenAI API error"),
     *                 @OA\Property(property="Value", type="null", example=null)
     *             )
     *         )
     *     )
     * )
     */
    public function translateSingle(Request $request, int $sku)
    {
        $request->validate([
            'locale' => 'required|string',
            'source' => 'nullable|string',
        ]);

        try {
            $localization = Localization::where([
                'product_id' => $sku,
                'locale' => $request->input('source', 'en'),
            ])->first();

            if (!$localization) {
                return response()->json([
                    'Response' => [
                        'Version'   => '1.0',
                        'ErrorCode' => '404',
                        'ErrorMsg'  => "No source localization found for product {$sku}.",
                        'Value'     => null,
                    ]
                ], 404);
            }

            // Translate to requested locale
            $this->translationService->processLocaleForProduct(
                $sku,
                $request->input('locale'),
                $localization->locale,
                $localization->title,
                $localization->short_description,
                $localization->long_description
            );

            return response()->json([
                'Response' => [
                    'Version'   => '1.0',
                    'ErrorCode' => '0',
                    'ErrorMsg'  => 'OK',
                    'Value'     => [],
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'Response' => [
                    'Version'   => '1.0',
                    'ErrorCode' => '500',
                    'ErrorMsg'  => "Translation failed: {$e->getMessage()}",
                    'Value'     => null,
                ]
            ], 500);
        }
    }


    public function storeProductVariants(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sku' => 'required|string',
            'shopify_product_id' => 'required|string',
            'variants' => 'required|array|min:1',
            'variants.*.shopify_variant_id' => 'required|string',
            'variants.*.price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {

            $product = Product::where('sku', $request->sku)->first();

            if (!$product) {
                return $this->notFoundResponse(
                    'Product not found for SKU: ' . $request->sku
                );
            }

            $saved = 0;
            $updated = 0;
            $skipped = 0;

            DB::transaction(function () use ($request, $product, &$saved, &$updated, &$skipped) {

                foreach ($request->variants as $variant) {

                    $existingVariant = ProductVariant::where(
                        'shopify_variant_id',
                        $variant['shopify_variant_id']
                    )->first();

                    if (!$existingVariant) {

                        ProductVariant::create([
                            'product_id' => $product->id,
                            'shopify_product_id' => $request->shopify_product_id,
                            'shopify_variant_id' => $variant['shopify_variant_id'],
                            'price' => $variant['price'],
                        ]);

                        $saved++;
                        continue;
                    }

                    if ($existingVariant->price != $variant['price']) {
                        $existingVariant->update([
                            'price' => $variant['price']
                        ]);

                        $updated++;
                    } else {
                        $skipped++;
                    }
                }
            });

            $response = $this->successResponse(
                [
                    'sku' => $product->sku,
                    'shopify_product_id' => $request->shopify_product_id,
                    'variants_saved' => $saved,
                    'variants_price_updated' => $updated,
                    'variants_skipped' => $skipped,
                ],
                'Shopify variants processed successfully'
            );
        } catch (Throwable $e) {

            $response = $this->serverErrorResponse(
                'Failed to store Shopify variants',
                $e->getMessage()
            );
        }

        return $response;
    }


    public function removeVariantsByShopifyProduct(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'shopify_product_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            $query = ProductVariant::where(
                'shopify_product_id',
                $request->shopify_product_id
            );

            // ✅ Check existence first
            if (! $query->exists()) {
                return $this->notFoundResponse(
                    'No variants found for Shopify product ID: ' . $request->shopify_product_id
                );
            }

            // 🔥 Bulk delete
            $deleted = $query->delete();

            return $this->successResponse(
                [
                    'shopify_product_id' => $request->shopify_product_id,
                    'variants_deleted' => $deleted,
                ],
                'Shopify product variants removed successfully'
            );
        } catch (\Throwable $e) {
            return $this->serverErrorResponse(
                'Failed to remove Shopify variants',
                $e->getMessage()
            );
        }
    }



    public function getProductByIdV2($product_id)
    {
        $product = Product::with(['media', 'localizations', 'tags', 'rating'])
            ->where('sku', $product_id)
            ->first();

        if (!$product) {
            return response()->json([
                'error' => 'Product not found'
            ], 404);
        }

        return response()->json(
            new ProductResourceV2($product)
        );
    }
}

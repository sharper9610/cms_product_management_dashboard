<?php

namespace App\Swagger;

/**
 * @OA\Info(
 *      version="1.0.0",
 *      title="Product Management Shopify API",
 *      description="API documentation for Shopify and 2Game integration service",
 *      @OA\Contact(
 *          email="2game.com"
 *      ),
 *      @OA\License(
 *          name="MIT",
 *          url="https://opensource.org/licenses/MIT"
 *      )
 * )
 *
 * @OA\Server(
 *      url=L5_SWAGGER_CONST_HOST,
 *      description="API Server"
 * )
 *
 * @OA\SecurityScheme(
 *      securityScheme="bearerAuth",
 *      type="http",
 *      scheme="bearer",
 *      bearerFormat="JWT",
 *      description="Enter token in format (Bearer <token>)"
 * )
 *
 * @OA\SecurityScheme(
 *      securityScheme="apiKey",
 *      type="apiKey",
 *      in="header",
 *      name="X-API-Password",
 *      description="Static password authentication for API access"
 * )
 *
 * --------------------------------------------------------
 *  Tag Ordering (controls section order in Swagger UI)
 * --------------------------------------------------------
 *
 * @OA\Tag(
 *      name="Products",
 *      description="Endpoints related to product management"
 * )
 *
 * @OA\Tag(
 *      name="Orders",
 *      description="Endpoints related to order management"
 * )
 *
 * @OA\Tag(
 *      name="Customers",
 *      description="Endpoints related to customer management"
 * )
 *
 * @OA\Tag(
 *      name="System",
 *      description="System and utility endpoints"
 * )
 */
class OpenApiSpec
{
    // This class exists solely to hold the OpenAPI specification annotations
}

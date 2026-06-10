<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SystemController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/health",
     *     summary="Health check endpoint",
     *     description="Returns API health status. Useful for monitoring and load balancer checks.",
     *     tags={"System"},
     *     @OA\Response(
     *         response=200,
     *         description="API is healthy",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="ok")
     *         )
     *     )
     * )
     */
    public function health(): JsonResponse
    {
        return response()->json(['status' => 'ok'], 200);
    }
}

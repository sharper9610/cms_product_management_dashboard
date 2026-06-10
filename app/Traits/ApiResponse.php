<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    /**
     * Success response
     */
    protected function successResponse($data, string $message = 'Operation successful', int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'Response' => [
                'Version' => '1.0',
                'ErrorCode' => '0',
                'ErrorMsg' => $message,
                'Value' => $data,
            ]
        ], $statusCode);
    }

    /**
     * Error response
     */
    protected function errorResponse(string $message, $data = null, string $errorCode = '1', int $statusCode = 400): JsonResponse
    {
        return response()->json([
            'Response' => [
                'Version' => '1.0',
                'ErrorCode' => $errorCode,
                'ErrorMsg' => $message,
                'Value' => $data,
            ]
        ], $statusCode);
    }

    /**
     * Validation error response
     */
    protected function validationErrorResponse($errors): JsonResponse
    {
        return $this->errorResponse(
            'Validation failed',
            ['errors' => $errors],
            '1',
            422
        );
    }

    /**
     * Not found response
     */
    protected function notFoundResponse(string $message = 'Resource not found'): JsonResponse
    {
        return $this->errorResponse($message, null, '2', 404);
    }

    /**
     * Server error response
     */
    protected function serverErrorResponse(string $message = 'Internal server error', $error = null): JsonResponse
    {
        return $this->errorResponse(
            $message,
            $error ? ['error' => $error] : null,
            '500',
            500
        );
    }
}
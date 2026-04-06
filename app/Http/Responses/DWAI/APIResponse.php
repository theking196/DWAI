<?php

namespace App\Http\Responses\DWAI;

use Illuminate\Http\JsonResponse;

class APIResponse
{
    /**
     * Success response.
     */
    public static function success($data = null, string $message = 'Success', int $code = 200): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    /**
     * Error response.
     */
    public static function error(string $message, int $code = 400, $details = null): JsonResponse
    {
        $response = [
            'status' => 'error',
            'message' => $message,
            'code' => $code,
        ];

        if ($details) {
            $response['details'] = $details;
        }

        return response()->json($response, $code);
    }

    /**
     * Validation failure response.
     */
    public static function validationError(array $errors, string $message = 'Validation failed'): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'type' => 'validation',
            'message' => $message,
            'errors' => $errors,
        ], 422);
    }

    /**
     * Not found response.
     */
    public static function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return self::error($message, 404);
    }

    /**
     * Unauthorized response.
     */
    public static function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return self::error($message, 401);
    }

    /**
     * Created response.
     */
    public static function created($data, string $message = 'Created'): JsonResponse
    {
        return self::success($data, $message, 201);
    }

    /**
     * Deleted response.
     */
    public static function deleted(string $message = 'Deleted'): JsonResponse
    {
        return self::success(null, $message, 200);
    }

    /**
     * Paginated response.
     */
    public static function paginated($data, int $total, int $page, int $perPage): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $data,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'pages' => ceil($total / $perPage),
            ],
        ]);
    }
}

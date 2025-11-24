<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

/**
 * ApiResponse Trait
 *
 * Controller'larda standart API response'ları döndürmek için kullanılır.
 *
 * Kullanım:
 */
trait ApiResponse
{
    /**
     * Success response döndürür
     *
     * @param  mixed  $data  Response data (array, object, collection vs.)
     * @param  string  $message  Success mesajı
     * @param  int  $statusCode  HTTP status code (default: 200 OK)
     */
    protected function success($data = null, string $message = 'Operation successful', int $statusCode = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        // Data varsa ekle
        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Error response döndürür
     *
     * @param  string  $message  Error mesajı
     * @param  int  $statusCode  HTTP status code (default: 400 Bad Request)
     * @param  mixed  $errors  Validation errors veya detaylı error array (optional)
     */
    protected function error(string $message = 'Operation failed', int $statusCode = 400, $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        // Errors varsa ekle (validation errors vs.)
        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }
}

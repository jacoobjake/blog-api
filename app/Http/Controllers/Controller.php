<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

abstract class Controller
{
    public function success(?string $message = null, mixed $data = null, int $status = 200): JsonResponse
    {
        $response_data = [
            'success' => true,
            'message' => $message ?? __('success'),
        ];

        if (!empty($data)) {
            $response_data['data'] = $data;
        }

        return response()->json($response_data, $status);
    }
}

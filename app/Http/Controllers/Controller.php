<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
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

    public function error(?string $message = null, int $status = 400): JsonResponse
    {
        $response_data = [
            'success' => false,
            'message' => $message ?? __('error'),
        ];

        return response()->json($response_data, $status);
    }

    protected function modelActionMessage(Model $model, string $action): string
    {
        return __('messages.model_' . $action, ['model' => __("models." . $model::class)]);
    }
}

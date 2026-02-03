<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (NotFoundHttpException $exception) {
            $prev = $exception->getPrevious();
            $message = $exception->getMessage();

            if ($prev instanceof ModelNotFoundException) {
                $message = __('errors.model_not_found', ['model' => __("models." . $prev->getModel())]);
            }

            return response()->json([
                'success' => false,
                'message' => $message,
            ], 404);
        });

        $exceptions->render(function (ModelNotFoundException $exception) {
            return response()->json([
                'success' => false,
                'message' => __('errors.model_not_found', ['model' => __("models." . $exception->getModel())]),
            ], 404);
        });
    })->create();

<?php

use App\Http\Middleware\Json;
use App\Http\Middleware\Role;
use Illuminate\Foundation\Application;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function ($middleware) {
        $middleware->alias([
            'Json' => Json::class,
            'Role' => Role::class,
        ]);
    })

    ->withExceptions(function ($exceptions) {
        // Authentication error
        $exceptions->render(function (AuthenticationException $e, $request) {
            return response()->json([
                'message' => 'مۆڵەتت نییە ، دووبارە هەوڵبدەوە'
            ], 401);
        });

        // Validation errors
        $exceptions->render(function (ValidationException $e, $request) {
            return response()->json(['message' => collect($e->errors())->flatten()->first()], 422);
        });

        // Not Found (including model not found and 404 routes)
        $exceptions->renderable(function (NotFoundHttpException $e, $request) {
            return response()->json([
                'message' => 'ئەم زانیارییە نەدۆزرایەوە.'
            ], 404);
        });
    })
    ->create();

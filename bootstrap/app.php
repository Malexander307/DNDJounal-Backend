<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Exceptions\ApiException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(\App\Http\Middleware\RequestId::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn ($request) =>
            $request->expectsJson() || $request->is('api/*')
        );

        $exceptions->dontReport([
            ApiException::class,
        ]);

        // Validation
        $exceptions->renderable(function (ValidationException $e, $request) {
            $rid = $request->headers->get('X-Request-Id') ?? $request->attributes->get('request_id');
            return response()->json([
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'The given data was invalid.',
                    'details' => $e->errors(),
                ],
                'meta' => ['request_id' => $rid],
            ], 422);
        });

        // Authentication / Authorization
        $exceptions->renderable(function (AuthenticationException $e, $request) {
            $rid = $request->headers->get('X-Request-Id') ?? $request->attributes->get('request_id');
            return response()->json([
                'error' => ['code' => 'UNAUTHENTICATED', 'message' => 'Unauthenticated.'],
                'meta' => ['request_id' => $rid],
            ], 401);
        });

        $exceptions->renderable(function (AuthorizationException $e, $request) {
            $rid = $request->headers->get('X-Request-Id') ?? $request->attributes->get('request_id');
            return response()->json([
                'error' => ['code' => 'FORBIDDEN', 'message' => 'This action is unauthorized.'],
                'meta' => ['request_id' => $rid],
            ], 403);
        });

        // Not found (routes or models)
        $exceptions->renderable(function (NotFoundHttpException|ModelNotFoundException $e, $request) {
            $rid = $request->headers->get('X-Request-Id') ?? $request->attributes->get('request_id');
            return response()->json([
                'error' => ['code' => 'NOT_FOUND', 'message' => 'Resource not found.'],
                'meta' => ['request_id' => $rid],
            ], 404);
        });

        // Throttling
        $exceptions->renderable(function (ThrottleRequestsException $e, $request) {
            $rid = $request->headers->get('X-Request-Id') ?? $request->attributes->get('request_id');
            return response()->json([
                'error' => ['code' => 'TOO_MANY_REQUESTS', 'message' => 'Too many requests.'],
                'meta' => ['request_id' => $rid],
            ], 429);
        });

        // Domain/API exceptions thrown intentionally
        $exceptions->renderable(function (ApiException $e, $request) {
            $rid = $request->headers->get('X-Request-Id') ?? $request->attributes->get('request_id');
            return response()->json([
                'error' => array_filter([
                    'code' => $e->codeStr,
                    'message' => $e->getMessage(),
                    'details' => $e->details ?: null,
                ]),
                'meta' => ['request_id' => $rid],
            ], $e->status);
        });

        // Generic HTTP exceptions
        $exceptions->renderable(function (HttpExceptionInterface $e, $request) {
            $rid = $request->headers->get('X-Request-Id') ?? $request->attributes->get('request_id');
            $status = $e->getStatusCode();
            return response()->json([
                'error' => ['code' => 'HTTP_EXCEPTION', 'message' => $e->getMessage() ?: 'HTTP error.'],
                'meta' => ['request_id' => $rid],
            ], $status);
        });

        // Last-resort fallback for API requests
        $exceptions->renderable(function (\Throwable $e, $request) {
            if (!($request->expectsJson() || $request->is('api/*'))) {
                return null; // let default HTML rendering handle it
            }
            $rid = $request->headers->get('X-Request-Id') ?? $request->attributes->get('request_id');
            return response()->json([
                'error' => ['code' => 'INTERNAL_SERVER_ERROR', 'message' => 'Something went wrong.'],
                'meta' => ['request_id' => $rid],
            ], 500);
        });
    })->create();

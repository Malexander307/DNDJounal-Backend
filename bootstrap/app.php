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

        $errorResponseTemplate = function (
            string $textCode,
            string $message,
            mixed $details,
            int $requestId,
            int $status = 500
        ) {
            return response()->json([
                'error' => [
                    'code' => $textCode,
                    'message' => $message,
                    'details' => $details,
                ],
                'meta' => ['request_id' => $requestId],
            ], $status);
        };
        // Validation
        $exceptions->renderable(function (ValidationException $e, $request) use ($errorResponseTemplate) {
            $rid = $request->headers->get('X-Request-Id') ?? $request->attributes->get('request_id');
            $details = method_exists($e, 'errors') ? $e->errors() : null;
            $message = $e->getMessage() ?: 'The given data was invalid.';
            return $errorResponseTemplate('VALIDATION_ERROR', $message, $details, $rid, 422);
        });

        // Authentication / Authorization
        $exceptions->renderable(function (AuthenticationException $e, $request) use ($errorResponseTemplate) {
            $rid = $request->headers->get('X-Request-Id') ?? $request->attributes->get('request_id');
            return $errorResponseTemplate('UNAUTHENTICATED', 'Unauthenticated.', $e->getMessage() ?: null, $rid, 401);
        });

        $exceptions->renderable(function (AuthorizationException $e, $request) use ($errorResponseTemplate) {
            $rid = $request->headers->get('X-Request-Id') ?? $request->attributes->get('request_id');
            return $errorResponseTemplate('FORBIDDEN', 'This action is unauthorized.', $e->getMessage() ?: null, $rid, 403);
        });

        // Not found (routes or models)
        $exceptions->renderable(function (NotFoundHttpException|ModelNotFoundException $e, $request) use ($errorResponseTemplate) {
            $rid = $request->headers->get('X-Request-Id') ?? $request->attributes->get('request_id');
            return $errorResponseTemplate('NOT_FOUND', 'Resource not found.', $e->getMessage() ?: null, $rid, 404);
        });

        // Throttling
        $exceptions->renderable(function (ThrottleRequestsException $e, $request) use ($errorResponseTemplate) {
            $rid = $request->headers->get('X-Request-Id') ?? $request->attributes->get('request_id');
            return $errorResponseTemplate('TOO_MANY_REQUESTS', 'Too many requests.', $e->getMessage() ?: null, $rid, 429);
        });

        // Domain/API exceptions thrown intentionally
        $exceptions->renderable(function (ApiException $e, $request) use ($errorResponseTemplate) {
            $rid = $request->headers->get('X-Request-Id') ?? $request->attributes->get('request_id');
            return $errorResponseTemplate($e->codeStr, $e->getMessage(), $e->details ?: null, $rid, $e->status);
        });

        // Generic HTTP exceptions
        $exceptions->renderable(function (HttpExceptionInterface $e, $request) use ($errorResponseTemplate) {
            $rid = $request->headers->get('X-Request-Id') ?? $request->attributes->get('request_id');
            $status = $e->getStatusCode();
            return $errorResponseTemplate('HTTP_EXCEPTION', $e->getMessage() ?: 'HTTP error.', $e->getMessage() ?: null, $rid, $status);
        });

        // Last-resort fallback for API requests
        $exceptions->renderable(function (\Throwable $e, $request) use ($errorResponseTemplate) {
            if (!($request->expectsJson() || $request->is('api/*'))) {
                return null; // let default HTML rendering handle it
            }
            $rid = $request->headers->get('X-Request-Id') ?? $request->attributes->get('request_id');
            return $errorResponseTemplate('INTERNAL_SERVER_ERROR', 'Something went wrong.', $e->getMessage() ?: null, $rid, 500);
        });
    })->create();

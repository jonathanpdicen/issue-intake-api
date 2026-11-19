<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RejectMalformedJson;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->api(prepend: [
            RejectMalformedJson::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (Throwable $e, Request $request): ?JsonResponse {
            if (! $request->is('api/*')) {
                return null;
            }

            $error = function (int $status, string $code, string $message, array $details = []) use ($e): JsonResponse {
                $payload = [
                    'code' => $code,
                    'message' => $message,
                    'details' => (object) $details,
                ];

                if ($status === Response::HTTP_INTERNAL_SERVER_ERROR && config('app.debug')) {
                    $payload['debug'] = [
                        'exception' => $e::class,
                        'trace' => collect($e->getTrace())
                            ->map(fn (array $frame) => Arr::except($frame, ['args']))
                            ->all(),
                    ];
                }

                return response()->json(['error' => $payload], $status);
            };

            if ($e instanceof ValidationException) {
                return $error(Response::HTTP_UNPROCESSABLE_ENTITY, 'validation_failed', $e->getMessage(), $e->errors());
            }

            if ($e instanceof AuthenticationException) {
                return $error(Response::HTTP_UNAUTHORIZED, 'unauthenticated', 'Authentication is required.');
            }

            if ($e instanceof HttpExceptionInterface) {
                $status = $e->getStatusCode();
                $statusText = Response::$statusTexts[$status] ?? 'Error';

                return match ($status) {
                    Response::HTTP_NOT_FOUND => $error($status, 'not_found', 'Resource not found.'),
                    Response::HTTP_BAD_REQUEST => $error($status, 'bad_request', $e->getMessage() ?: $statusText),
                    default => $error($status, Str::snake($statusText), $statusText),
                };
            }

            return $error(
                Response::HTTP_INTERNAL_SERVER_ERROR,
                'server_error',
                config('app.debug') ? $e->getMessage() : 'Something went wrong on our side.',
            );
        });
    })->create();

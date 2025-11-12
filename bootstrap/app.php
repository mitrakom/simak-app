<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            \App\Http\Middleware\ApiResponseMiddleware::class,
        ]);

        $middleware->alias([
            'validate.institusi.slug' => \App\Http\Middleware\ValidateInstitusiSlug::class,
            'validate.institusi.exists' => \App\Http\Middleware\ValidateInstitusiExists::class,
            'feeder.ready' => \App\Http\Middleware\EnsureFeederClientReady::class,
            'has.institusi' => \App\Http\Middleware\EnsureUserHasInstitusi::class,
            'belongs.to.institusi' => \App\Http\Middleware\EnsureUserBelongsToInstitusi::class,
        ]);

        // Redirect guests to login with institusi slug
        $middleware->redirectGuestsTo(function ($request) {
            // Only redirect if there's an institusi in the route
            if ($request->route('institusi')) {
                $institusi = $request->route('institusi');
                $slug = is_string($institusi) ? $institusi : $institusi->slug;

                return route('auth.login.form', ['institusi' => $slug]);
            }

            // Fallback to home if no institusi
            return route('home');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => $e->errors(),
                ], 422);
            }
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Resource not found.',
                    'error' => 'not_found',
                ], 404);
            }
        });

        $exceptions->render(function (\Throwable $e, $request) {
            if ($request->is('api/*') && ! config('app.debug')) {
                return response()->json([
                    'message' => 'Internal server error.',
                    'error' => 'server_error',
                ], 500);
            }
        });
    })->create();

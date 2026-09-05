<?php

declare(strict_types=1);

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        // Central routes are constrained to the central domains so that tenant
        // routes (routes/tenant.php) can own the same paths on their own domains.
        // The {centralDomain} default is set in AppServiceProvider so that route()
        // keeps generating URLs for whichever central domain is being browsed.
        then: function (): void {
            Route::middleware('web')
                ->domain('{centralDomain}')
                ->where(['centralDomain' => collect(config()->array('tenancy.central_domains'))
                    ->map(fn (string $domain): string => preg_quote($domain, '#'))
                    ->implode('|')])
                ->group(base_path('routes/web.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        // Empty marker group used by Stancl\Tenancy\Features\UniversalRoutes to
        // detect routes (like Fortify's) that should initialize tenancy on
        // subdomains while still working on the central domain.
        $middleware->group('universal', []);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request): bool => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();

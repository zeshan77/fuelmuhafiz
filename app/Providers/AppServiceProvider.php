<?php

declare(strict_types=1);

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureCentralDomain();
    }

    /**
     * Bind the {centralDomain} route parameter so central routes can be generated
     * without passing it explicitly. Falls back to the first configured central
     * domain outside of central-domain requests (console, queues, tenant hosts).
     */
    protected function configureCentralDomain(): void
    {
        $centralDomains = config()->array('tenancy.central_domains');
        $host = request()->getHost();

        URL::defaults([
            'centralDomain' => in_array($host, $centralDomains, true)
                ? $host
                : ($centralDomains[0] ?? $host),
        ]);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}

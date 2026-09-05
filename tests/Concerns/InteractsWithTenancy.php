<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\Models\Domain;
use App\Models\Tenant;
use Illuminate\Support\Str;

/**
 * Provisions a tenant (with its own database) and initializes tenancy for
 * the duration of a test, mirroring how a request on a tenant subdomain
 * would behave. The tenant database is torn down after the test.
 */
trait InteractsWithTenancy
{
    protected Tenant $tenant;

    protected Domain $domain;

    /**
     * Create a tenant, give it a domain, and switch into its context.
     */
    protected function setUpTenancy(?string $subdomain = null): Tenant
    {
        $subdomain ??= 'test-'.Str::lower(Str::random(8));
        $appHost = (string) parse_url((string) config('app.url'), PHP_URL_HOST);

        $this->tenant = Tenant::create([
            'name' => 'Test Tenant',
        ]);

        $this->domain = $this->tenant->createDomain("{$subdomain}.{$appHost}");

        tenancy()->initialize($this->tenant);

        return $this->tenant;
    }

    /**
     * Revert to the central context and delete the tenant (and its database).
     */
    protected function tearDownTenancy(): void
    {
        if (! isset($this->tenant)) {
            return;
        }

        tenancy()->end();

        $this->tenant->delete();
    }
}

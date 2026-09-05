<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\InteractsWithTenancy;
use Tests\TestCase;

class TenantProvisioningTest extends TestCase
{
    use InteractsWithTenancy, RefreshDatabase;

    public function test_creating_a_tenant_provisions_and_migrates_its_own_database(): void
    {
        $this->setUpTenancy();

        $this->assertTrue(Schema::hasTable('users'));
        $this->assertTrue(Schema::hasTable('passkeys'));
        $this->assertTrue(Schema::hasTable('sessions'));

        $this->tearDownTenancy();
    }

    public function test_users_created_in_one_tenant_are_not_visible_in_another(): void
    {
        $this->setUpTenancy('tenant-a');
        User::factory()->create(['email' => 'owner@tenant-a.test']);
        $this->tearDownTenancy();

        $this->setUpTenancy('tenant-b');

        $this->assertSame(0, User::query()->where('email', 'owner@tenant-a.test')->count());

        $this->tearDownTenancy();
    }

    /**
     * A regression test for the actual HTTP identification middleware, not
     * just tenancy()->initialize() called directly in the test process.
     * InitializeTenancyBySubdomain only matches the leading label of the
     * host against `domains.domain`; since domains are stored as the full
     * host (e.g. "acme.fuelmuhafiz.test"), the app must use
     * InitializeTenancyByDomain (full-host matching) instead.
     */
    public function test_a_tenant_domain_is_identified_by_the_actual_http_middleware(): void
    {
        $tenant = Tenant::create(['id' => 'http-domain-test', 'name' => 'HTTP Domain Test']);
        $domain = $tenant->createDomain('http-domain-test.localhost');

        $response = $this->get("http://{$domain->domain}/");

        $response->assertOk();
        $response->assertSee($tenant->id);

        tenancy()->end();
        $tenant->delete();
    }
}

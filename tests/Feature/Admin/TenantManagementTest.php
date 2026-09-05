<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Domain;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TenantManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenants_index_lists_tenants_with_their_domains(): void
    {
        $admin = User::factory()->create();
        $tenant = $this->makeTenantWithoutProvisioning('list-test', 'Acme Fuels');
        $tenant->createDomain('acme.localhost');

        $response = $this->actingAs($admin)->get(route('admin.tenants.index'));

        $response->assertOk();

        $tenant->delete();
    }

    public function test_creating_a_tenant_provisions_its_database_domain_and_owner_account(): void
    {
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.tenants.store'), [
            'name' => 'Acme Fuels',
            'currency' => 'PKR',
            'timezone' => 'Asia/Karachi',
            'subdomain' => 'acme-crud-test',
            'owner_name' => 'Acme Owner',
            'owner_email' => 'owner@acme-crud-test.test',
        ]);

        $response->assertSessionHasNoErrors();

        $tenant = Tenant::query()->where('name', 'Acme Fuels')->firstOrFail();

        $response->assertRedirect(route('admin.tenants.edit', $tenant));

        $domain = Domain::query()->where('tenant_id', $tenant->id)->firstOrFail();
        $this->assertStringStartsWith('acme-crud-test.', $domain->domain);

        tenancy()->initialize($tenant);
        $this->assertDatabaseHas('users', ['email' => 'owner@acme-crud-test.test']);
        tenancy()->end();

        $tenant->delete();
    }

    public function test_creating_a_tenant_rejects_an_invalid_subdomain(): void
    {
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.tenants.store'), [
            'name' => 'Acme Fuels',
            'currency' => 'PKR',
            'timezone' => 'Asia/Karachi',
            'subdomain' => 'Not A Valid Subdomain!',
            'owner_name' => 'Acme Owner',
            'owner_email' => 'owner@acme.test',
        ]);

        $response->assertSessionHasErrors('subdomain');
        $this->assertDatabaseMissing('tenants', ['name' => 'Acme Fuels']);
    }

    public function test_creating_a_tenant_rejects_a_subdomain_that_is_already_taken(): void
    {
        $admin = User::factory()->create();
        $tenant = $this->makeTenantWithoutProvisioning('taken-test', 'Existing Tenant');
        $tenant->createDomain('taken.localhost');

        $response = $this->actingAs($admin)->post(route('admin.tenants.store'), [
            'name' => 'Another Tenant',
            'currency' => 'PKR',
            'timezone' => 'Asia/Karachi',
            'subdomain' => 'taken',
            'owner_name' => 'Owner',
            'owner_email' => 'owner@another.test',
        ]);

        $response->assertSessionHasErrors('subdomain');

        $tenant->delete();
    }

    public function test_admin_can_update_tenant_details(): void
    {
        $admin = User::factory()->create();
        $tenant = $this->makeTenantWithoutProvisioning('update-test', 'Old Name');
        $tenant->createDomain('update-test.localhost');

        $response = $this->actingAs($admin)->patch(route('admin.tenants.update', $tenant), [
            'name' => 'New Name',
            'currency' => 'PKR',
            'timezone' => 'Asia/Karachi',
            'is_active' => false,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame('New Name', $tenant->fresh()->name);
        $this->assertFalse($tenant->fresh()->is_active);

        $tenant->delete();
    }

    public function test_admin_can_add_and_remove_an_additional_domain(): void
    {
        $admin = User::factory()->create();
        $tenant = $this->makeTenantWithoutProvisioning('domain-test', 'Domain Test');
        $tenant->createDomain('domain-test.localhost');

        $response = $this->actingAs($admin)->post(route('admin.tenants.domains.store', $tenant), [
            'domain' => 'domain-test-2.localhost',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('domains', ['domain' => 'domain-test-2.localhost', 'tenant_id' => $tenant->id]);

        $newDomain = Domain::query()->where('domain', 'domain-test-2.localhost')->firstOrFail();

        $removeResponse = $this->actingAs($admin)->delete(route('admin.tenants.domains.destroy', [$tenant, $newDomain]));

        $removeResponse->assertRedirect(route('admin.tenants.edit', $tenant));
        $this->assertDatabaseMissing('domains', ['id' => $newDomain->id]);

        $tenant->delete();
    }

    public function test_a_domain_outside_the_central_domains_is_rejected(): void
    {
        $admin = User::factory()->create();
        $tenant = $this->makeTenantWithoutProvisioning('foreign-domain-test', 'Foreign Domain Test');
        $tenant->createDomain('foreign-domain-test.localhost');

        $response = $this->actingAs($admin)->post(route('admin.tenants.domains.store', $tenant), [
            'domain' => 'totally-unrelated.example.com',
        ]);

        $response->assertSessionHasErrors('domain');

        $tenant->delete();
    }

    public function test_the_only_remaining_domain_cannot_be_removed(): void
    {
        $admin = User::factory()->create();
        $tenant = $this->makeTenantWithoutProvisioning('last-domain-test', 'Last Domain');
        $domain = $tenant->createDomain('last-domain-test.localhost');

        $response = $this->actingAs($admin)->delete(route('admin.tenants.domains.destroy', [$tenant, $domain]));

        $response->assertRedirect(route('admin.tenants.edit', $tenant));
        $this->assertDatabaseHas('domains', ['id' => $domain->id]);

        $tenant->delete();
    }

    public function test_deleting_a_tenant_removes_it_and_its_domains(): void
    {
        $admin = User::factory()->create();
        $tenant = $this->makeTenantWithoutProvisioning('delete-test', 'Delete Me');
        $tenant->createDomain('delete-test.localhost');

        $response = $this->actingAs($admin)->delete(route('admin.tenants.destroy', $tenant));

        $response->assertRedirect(route('admin.tenants.index'));
        $this->assertDatabaseMissing('tenants', ['id' => 'delete-test']);
        $this->assertDatabaseMissing('domains', ['tenant_id' => 'delete-test']);
    }

    public function test_tenants_can_be_searched_by_name_and_by_domain(): void
    {
        $admin = User::factory()->create();

        $shaheen = $this->makeTenantWithoutProvisioning('shaheen', 'Shaheen Fuels');
        $shaheen->createDomain('shaheen.localhost');

        $indus = $this->makeTenantWithoutProvisioning('indus', 'Indus Petroleum');
        $indus->createDomain('indus-pk.localhost');

        $byName = $this->actingAs($admin)->get(route('admin.tenants.index', ['search' => 'Shaheen']));
        $byName->assertOk();
        $byName->assertInertia(fn (Assert $page) => $page
            ->has('tenants.data', 1)
            ->where('tenants.data.0.name', 'Shaheen Fuels'));

        $byDomain = $this->actingAs($admin)->get(route('admin.tenants.index', ['search' => 'indus-pk']));
        $byDomain->assertInertia(fn (Assert $page) => $page
            ->has('tenants.data', 1)
            ->where('tenants.data.0.name', 'Indus Petroleum'));

        $shaheen->delete();
        $indus->delete();
    }

    public function test_tenants_can_be_filtered_by_derived_status(): void
    {
        $admin = User::factory()->create();

        $active = $this->makeTenantWithoutProvisioning('status-active', 'Active Tenant');
        $trial = $this->makeTenantWithoutProvisioning('status-trial', 'Trial Tenant');
        $trial->update(['trial_ends_at' => Carbon::now()->addWeek()]);
        $suspended = $this->makeTenantWithoutProvisioning('status-suspended', 'Suspended Tenant');
        $suspended->update(['is_active' => false]);

        foreach ([
            'active' => 'Active Tenant',
            'trial' => 'Trial Tenant',
            'suspended' => 'Suspended Tenant',
        ] as $status => $expectedName) {
            $response = $this->actingAs($admin)->get(route('admin.tenants.index', ['status' => $status]));

            $response->assertInertia(fn (Assert $page) => $page
                ->has('tenants.data', 1)
                ->where('tenants.data.0.name', $expectedName)
                ->where('tenants.data.0.status', $status));
        }

        $active->delete();
        $trial->delete();
        $suspended->delete();
    }

    public function test_the_stat_cards_report_real_counts(): void
    {
        $admin = User::factory()->create();

        $active = $this->makeTenantWithoutProvisioning('stats-active', 'Active Tenant');
        $active->createDomain('stats-active.localhost');
        $suspended = $this->makeTenantWithoutProvisioning('stats-suspended', 'Suspended Tenant');
        $suspended->update(['is_active' => false]);

        $response = $this->actingAs($admin)->get(route('admin.tenants.index'));

        // Stats are a deferred prop, so they arrive on a follow-up request.
        $response->assertInertia(fn (Assert $page) => $page
            ->missing('stats')
            ->loadDeferredProps(fn (Assert $reload) => $reload
                ->where('stats.total', 2)
                ->where('stats.active', 1)
                ->where('stats.suspended', 1)
                ->where('stats.trial', 0)
                ->where('stats.domains', 1)
                ->where('stats.newThisMonth', 2)));

        $active->delete();
        $suspended->delete();
    }

    public function test_tenants_can_be_exported_as_csv_honouring_filters(): void
    {
        $admin = User::factory()->create();

        $active = $this->makeTenantWithoutProvisioning('export-active', 'Exported Tenant');
        $active->createDomain('exported.localhost');
        $suspended = $this->makeTenantWithoutProvisioning('export-suspended', 'Hidden Tenant');
        $suspended->update(['is_active' => false]);

        $response = $this->actingAs($admin)->get(route('admin.tenants.export', ['status' => 'active']));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();

        $this->assertStringContainsString('Exported Tenant', $csv);
        $this->assertStringContainsString('exported.localhost', $csv);
        $this->assertStringNotContainsString('Hidden Tenant', $csv);

        $active->delete();
        $suspended->delete();
    }

    /**
     * Create a tenant row without firing TenantCreated, so tests that only
     * care about the central `tenants`/`domains` tables don't pay for a real
     * database provisioning round-trip.
     */
    private function makeTenantWithoutProvisioning(string $id, string $name): Tenant
    {
        return Tenant::withoutEvents(fn (): Tenant => Tenant::create(['id' => $id, 'name' => $name]));
    }
}

<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeds a freshly-migrated tenant database. Runs automatically after tenant
 * creation via Stancl\Tenancy\Jobs\SeedDatabase (see TenancyServiceProvider)
 * and can be re-run manually with `php artisan tenants:seed`.
 */
class TenantDatabaseSeeder extends Seeder
{
    /**
     * Seed the tenant's database.
     */
    public function run(): void
    {
        // Fuel types, expense categories, and the owner user are seeded here
        // once the corresponding domain models land (see Phase 1 of PLAN.md).
    }
}

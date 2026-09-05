<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Domain;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('fuelmuhafiz:demo-tenant {--subdomain=demo : Subdomain to provision the tenant under} {--email=owner@demo.test : Email address for the tenant owner account} {--fresh : Delete an existing tenant on this domain before provisioning}')]
#[Description('Provision a demo tenant for manual verification of the tenancy setup.')]
class DemoTenantCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $subdomain = (string) $this->option('subdomain');
        $email = (string) $this->option('email');
        $appHost = (string) parse_url((string) config('app.url'), PHP_URL_HOST);
        $domainName = "{$subdomain}.{$appHost}";

        $existingDomain = Domain::query()->where('domain', $domainName)->first();

        if ($existingDomain) {
            if (! $this->option('fresh')) {
                $this->components->error("A tenant already exists at [{$domainName}]. Pass --fresh to replace it.");

                return self::FAILURE;
            }

            $existingDomain->tenant->delete();
        }

        $tenant = Tenant::create([
            'name' => 'Demo Petrol Pump',
            'contact_email' => $email,
        ]);

        $tenant->createDomain($domainName);

        tenancy()->initialize($tenant);

        try {
            $owner = User::factory()->create([
                'name' => 'Demo Owner',
                'email' => $email,
            ]);
        } finally {
            tenancy()->end();
        }

        $this->components->info("Demo tenant provisioned at http://{$domainName}");
        $this->components->twoColumnDetail('Owner email', $owner->email);
        $this->components->twoColumnDetail('Owner password', 'password');
        $this->components->warn('Station, tank, and shift sample data will be seeded here once the Phase 1 domain models land (see PLAN.md).');

        return self::SUCCESS;
    }
}

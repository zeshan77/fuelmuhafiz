<?php

declare(strict_types=1);

namespace App\Actions\Tenants;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Throwable;

class CreateTenant
{
    /**
     * Create a tenant, provision its database (synchronously, via the
     * TenantCreated event pipeline in TenancyServiceProvider), attach its
     * first domain, and create the owner's account inside the new tenant
     * database with a random one-time password.
     *
     * @param  array<string, mixed>  $attributes
     * @return array{tenant: Tenant, owner: User, password: string}
     */
    public function handle(array $attributes, string $subdomain, string $ownerName, string $ownerEmail): array
    {
        $tenant = Tenant::create($attributes);

        try {
            $tenant->createDomain($subdomain.'.'.$this->centralHost());

            $password = Str::password(20);

            tenancy()->initialize($tenant);

            try {
                $owner = new User([
                    'name' => $ownerName,
                    'email' => $ownerEmail,
                    'password' => $password,
                ]);
                $owner->email_verified_at = Carbon::now();
                $owner->save();
            } finally {
                tenancy()->end();
            }
        } catch (Throwable $e) {
            $tenant->delete();

            throw $e;
        }

        return [
            'tenant' => $tenant,
            'owner' => $owner,
            'password' => $password,
        ];
    }

    protected function centralHost(): string
    {
        return (string) parse_url((string) config('app.url'), PHP_URL_HOST);
    }
}

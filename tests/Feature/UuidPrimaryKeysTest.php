<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Domain;
use App\Models\Passkey;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passkeys\Passkeys;
use Tests\TestCase;

class UuidPrimaryKeysTest extends TestCase
{
    use RefreshDatabase;

    private const string UUID_PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/';

    public function test_users_are_created_with_a_uuid_primary_key(): void
    {
        $user = User::factory()->create();

        $this->assertMatchesRegularExpression(self::UUID_PATTERN, $user->id);
        $this->assertNonIncrementingStringKey($user);
    }

    public function test_domains_are_created_with_a_uuid_primary_key(): void
    {
        // withoutEvents keeps the TenantCreated listeners from provisioning a real
        // tenant database, which RefreshDatabase would not roll back.
        $tenant = Tenant::withoutEvents(fn (): Tenant => Tenant::create(['id' => 'uuid-test-tenant']));
        $domain = $tenant->domains()->create(['domain' => 'uuid-test-tenant.localhost']);

        $this->assertInstanceOf(Domain::class, $domain);
        $this->assertMatchesRegularExpression(self::UUID_PATTERN, $domain->id);
        $this->assertNonIncrementingStringKey($domain);
    }

    public function test_passkeys_resolve_to_the_uuid_keyed_model(): void
    {
        $this->assertSame(Passkey::class, Passkeys::passkeyModel());
        $this->assertNonIncrementingStringKey(new Passkey);
    }

    public function test_sessions_store_the_uuid_user_key(): void
    {
        $user = User::factory()->create();

        $this->assertIsString($user->getAuthIdentifier());
        $this->assertSame($user->id, $user->getAuthIdentifier());
    }

    private function assertNonIncrementingStringKey(Model $model): void
    {
        $this->assertSame('string', $model->getKeyType());
        $this->assertFalse($model->getIncrementing());
    }
}

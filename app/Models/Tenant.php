<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TenantStatus;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

/**
 * @property string $id
 * @property string $name
 * @property string|null $ntn
 * @property string|null $strn
 * @property string|null $contact_name
 * @property string|null $contact_phone
 * @property string|null $contact_email
 * @property string $currency
 * @property string $timezone
 * @property bool $is_active
 * @property Carbon|null $trial_ends_at
 * @property-read Collection<int, Domain> $domains
 */
class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    /**
     * Override HasDomains::domains() with an explicit return type so static
     * analysis (and IDEs) can see it as a proper relation.
     *
     * @return HasMany<Domain, $this>
     */
    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class, 'tenant_id');
    }

    /**
     * Columns that are real columns on the tenants table, rather than being
     * stored in the virtual `data` JSON column.
     *
     * @return array<int, string>
     */
    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'ntn',
            'strn',
            'contact_name',
            'contact_phone',
            'contact_email',
            'currency',
            'timezone',
            'is_active',
            'trial_ends_at',
        ];
    }

    /**
     * The tenant's lifecycle status, derived from `is_active` and
     * `trial_ends_at` rather than stored as its own column.
     *
     * Deliberately a plain method, not an Eloquent accessor: this model uses
     * the VirtualColumn trait, which serialises unknown attributes into the
     * `data` JSON column.
     */
    public function currentStatus(): TenantStatus
    {
        return match (true) {
            ! $this->is_active => TenantStatus::Suspended,
            $this->trial_ends_at?->isFuture() ?? false => TenantStatus::Trial,
            default => TenantStatus::Active,
        };
    }

    /**
     * Constrain a query to tenants with the given derived status.
     *
     * @param  Builder<Tenant>  $query
     */
    #[Scope]
    protected function status(Builder $query, TenantStatus $status): void
    {
        match ($status) {
            TenantStatus::Suspended => $query->where('is_active', false),
            TenantStatus::Trial => $query->where('is_active', true)
                ->whereNotNull('trial_ends_at')
                ->where('trial_ends_at', '>', Carbon::now()),
            TenantStatus::Active => $query->where('is_active', true)
                ->where(fn (Builder $query) => $query
                    ->whereNull('trial_ends_at')
                    ->orWhere('trial_ends_at', '<=', Carbon::now())),
        };
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'trial_ends_at' => 'datetime',
        ];
    }
}

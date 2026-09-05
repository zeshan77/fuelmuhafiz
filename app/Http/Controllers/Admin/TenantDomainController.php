<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTenantDomainRequest;
use App\Models\Domain;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class TenantDomainController extends Controller
{
    /**
     * Attach an additional domain to the tenant.
     *
     * $centralDomain is bound by the {centralDomain} wildcard that
     * bootstrap/app.php wraps every central route in. Laravel resolves
     * controller arguments positionally, so it must be declared explicitly
     * here to keep $tenant from receiving the domain string instead of the
     * model (see TenantController::edit()).
     */
    public function store(string $centralDomain, StoreTenantDomainRequest $request, Tenant $tenant): RedirectResponse
    {
        $tenant->createDomain($request->string('domain')->lower()->toString());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Domain added.')]);

        return to_route('admin.tenants.edit', $tenant);
    }

    /**
     * Remove a domain from the tenant, as long as it isn't the last one.
     *
     * See the note on store() about the unused $centralDomain parameter.
     */
    public function destroy(string $centralDomain, Tenant $tenant, Domain $domain): RedirectResponse
    {
        abort_unless($domain->tenant_id === $tenant->id, 404);

        if ($tenant->domains()->count() <= 1) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('A tenant must have at least one domain.')]);

            return to_route('admin.tenants.edit', $tenant);
        }

        $domain->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Domain removed.')]);

        return to_route('admin.tenants.edit', $tenant);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Tenants\CreateTenant;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTenantRequest;
use App\Http\Requests\Admin\UpdateTenantRequest;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TenantController extends Controller
{
    /**
     * Display a list of tenants.
     */
    public function index(): Response
    {
        $tenants = Tenant::query()
            ->with('domains')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/tenants/index', [
            'tenants' => $tenants,
        ]);
    }

    /**
     * Show the form for creating a new tenant.
     */
    public function create(): Response
    {
        return Inertia::render('admin/tenants/create', [
            'centralHost' => $this->centralHost(),
        ]);
    }

    /**
     * Provision a new tenant, its first domain, and its owner account.
     */
    public function store(StoreTenantRequest $request, CreateTenant $createTenant): RedirectResponse
    {
        $result = $createTenant->handle(
            attributes: $request->safe()->only([
                'name', 'ntn', 'strn', 'contact_name', 'contact_phone', 'contact_email', 'currency', 'timezone',
            ]),
            subdomain: $request->string('subdomain')->lower()->toString(),
            ownerName: $request->string('owner_name')->toString(),
            ownerEmail: $request->string('owner_email')->toString(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Tenant created.')]);

        return to_route('admin.tenants.edit', $result['tenant'])->with([
            'generatedPassword' => $result['password'],
            'ownerEmail' => $result['owner']->email,
        ]);
    }

    /**
     * Show the form for editing a tenant and managing its domains.
     *
     * $centralDomain is bound by the {centralDomain} wildcard that
     * bootstrap/app.php wraps every central route in (needed so route()
     * generates URLs for whichever central host is being browsed). Laravel
     * resolves controller arguments positionally, so any central action with
     * an additional route-bound parameter must also declare this one to keep
     * $tenant from receiving the domain string instead of the model.
     */
    public function edit(string $centralDomain, Tenant $tenant): Response
    {
        return Inertia::render('admin/tenants/edit', [
            'tenant' => $tenant,
            'domains' => $tenant->domains()->oldest()->get(),
            'generatedPassword' => session('generatedPassword'),
            'ownerEmail' => session('ownerEmail'),
        ]);
    }

    /**
     * Update the tenant's details.
     *
     * See the note on edit() about the unused $centralDomain parameter.
     */
    public function update(string $centralDomain, UpdateTenantRequest $request, Tenant $tenant): RedirectResponse
    {
        $tenant->update([
            ...$request->safe()->except('is_active'),
            'is_active' => $request->boolean('is_active'),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Tenant updated.')]);

        return to_route('admin.tenants.edit', $tenant);
    }

    /**
     * Delete the tenant, its domains, and its database.
     *
     * See the note on edit() about the unused $centralDomain parameter.
     */
    public function destroy(string $centralDomain, Tenant $tenant): RedirectResponse
    {
        $tenant->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Tenant deleted.')]);

        return to_route('admin.tenants.index');
    }

    protected function centralHost(): string
    {
        return (string) parse_url((string) config('app.url'), PHP_URL_HOST);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Tenants\CreateTenant;
use App\Enums\TenantStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTenantRequest;
use App\Http\Requests\Admin\UpdateTenantRequest;
use App\Models\Domain;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TenantController extends Controller
{
    /**
     * Display a searchable, filterable list of tenants.
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('search'));
        $status = TenantStatus::tryFrom((string) $request->string('status'));

        $tenants = $this->filteredQuery($search, $status)
            ->with('domains')
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Tenant $tenant): array => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'currency' => $tenant->currency,
                'contact_name' => $tenant->contact_name,
                'created_at' => $tenant->created_at->toIso8601String(),
                'status' => $tenant->currentStatus()->value,
                'domains' => $tenant->domains
                    ->map(fn (Domain $domain): array => [
                        'id' => $domain->id,
                        'domain' => $domain->domain,
                    ])
                    ->values()
                    ->all(),
            ]);

        return Inertia::render('admin/tenants/index', [
            'tenants' => $tenants,
            'filters' => [
                'search' => $search,
                'status' => $status?->value,
            ],
            'stats' => Inertia::defer(fn (): array => $this->stats()),
        ]);
    }

    /**
     * Download the current tenant list as CSV, honouring the active filters.
     */
    public function export(Request $request): StreamedResponse
    {
        $search = trim((string) $request->string('search'));
        $status = TenantStatus::tryFrom((string) $request->string('status'));

        // `id` breaks ties so chunked iteration can't skip or repeat rows.
        $query = $this->filteredQuery($search, $status)
            ->with('domains')
            ->orderByDesc('created_at')
            ->orderBy('id');

        return response()->streamDownload(function () use ($query): void {
            $handle = fopen('php://output', 'wb');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, ['Name', 'Domains', 'Currency', 'Status', 'Contact', 'Created at']);

            $query->each(function (Tenant $tenant) use ($handle): void {
                fputcsv($handle, [
                    $tenant->name,
                    $tenant->domains->pluck('domain')->implode(' '),
                    $tenant->currency,
                    $tenant->currentStatus()->value,
                    $tenant->contact_name,
                    $tenant->created_at->toDateTimeString(),
                ]);
            });

            fclose($handle);
        }, 'tenants-'.Carbon::now()->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv',
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

    /**
     * @return Builder<Tenant>
     */
    protected function filteredQuery(string $search, ?TenantStatus $status): Builder
    {
        return Tenant::query()
            ->when($search !== '', fn (Builder $query) => $query->where(
                fn (Builder $query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhereHas('domains', fn (Builder $query) => $query->where('domain', 'like', "%{$search}%"))
            ))
            ->when($status instanceof TenantStatus, fn (Builder $query) => $query->status($status));
    }

    /**
     * Headline counts for the page's stat cards.
     *
     * @return array<string, int>
     */
    protected function stats(): array
    {
        return [
            'total' => Tenant::query()->count(),
            'active' => Tenant::query()->status(TenantStatus::Active)->count(),
            'trial' => Tenant::query()->status(TenantStatus::Trial)->count(),
            'suspended' => Tenant::query()->status(TenantStatus::Suspended)->count(),
            'domains' => Domain::query()->count(),
            'newThisMonth' => Tenant::query()
                ->where('created_at', '>=', Carbon::now()->startOfMonth())
                ->count(),
        ];
    }

    protected function centralHost(): string
    {
        return (string) parse_url((string) config('app.url'), PHP_URL_HOST);
    }
}

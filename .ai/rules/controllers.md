---
paths:
    - 'app/Http/Controllers/**'
---

# Controllers

## Central controllers with route-bound params need a leading $centralDomain

bootstrap/app.php wraps every route in routes/web.php with `Route::domain('{centralDomain}')`, so on every central route the domain match is itself a route parameter. Laravel resolves controller method arguments positionally (see `Illuminate\Routing\ResolvesRouteDependencies`): class-typed params (Request/FormRequest/Eloquent models) get matched anywhere via `instanceof`, but a plain scalar route param like `centralDomain` only lines up correctly if a same-typed scalar parameter is declared for it in the same relative order.

Rule: any central controller method that takes an _additional_ route-bound parameter (a model or another scalar) beyond a single Request/FormRequest must also declare `string $centralDomain` explicitly (put it first, before FormRequest/model params). Methods with **zero** route-bound params (e.g. `index()`, `create()`, or a plain `store(SomeRequest $request)`) don't need it — the leftover scalar is silently ignored by PHP.

Without it, the extra model parameter silently receives the domain string instead of the bound model (a `TypeError` under strict_types, or worse, silently wrong data if the param is untyped) — see App\Http\Controllers\Admin\TenantController::edit()/update()/destroy() and TenantDomainController for the pattern to copy. This will bite every future controller with route-model binding (stations, tanks, shifts, etc. in Phase 1), so always check for it when adding a `{model}` route parameter.

<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\Admin\TenantDomainController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('tenants/export', [TenantController::class, 'export'])->name('tenants.export');

    Route::resource('tenants', TenantController::class)->except(['show']);

    Route::post('tenants/{tenant}/domains', [TenantDomainController::class, 'store'])
        ->name('tenants.domains.store');

    Route::delete('tenants/{tenant}/domains/{domain}', [TenantDomainController::class, 'destroy'])
        ->name('tenants.domains.destroy');
});

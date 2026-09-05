<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Domain;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;

class StoreTenantDomainRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'domain' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)+$/',
            ],
        ];
    }

    /**
     * Reject domains that are already taken, or that can't resolve to a
     * tenant because they don't sit under one of the central domains
     * (see Stancl\Tenancy\Middleware\InitializeTenancyBySubdomain).
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->filled('domain')) {
                return;
            }

            $domain = Str::lower((string) $this->string('domain'));
            $centralDomains = config()->array('tenancy.central_domains');

            if (! Str::endsWith($domain, $centralDomains)) {
                $validator->errors()->add('domain', __('The domain must be a subdomain of :hosts.', [
                    'hosts' => implode(', ', $centralDomains),
                ]));

                return;
            }

            if (Domain::query()->where('domain', $domain)->exists()) {
                $validator->errors()->add('domain', __('This domain is already taken.'));
            }
        });
    }
}

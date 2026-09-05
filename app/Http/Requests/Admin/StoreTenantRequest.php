<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Domain;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;

class StoreTenantRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'ntn' => ['nullable', 'string', 'max:50'],
            'strn' => ['nullable', 'string', 'max:50'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'currency' => ['required', 'string', 'size:3'],
            'timezone' => ['required', 'string', 'max:64'],
            'subdomain' => ['required', 'string', 'max:63', 'regex:/^[a-z0-9]+(-[a-z0-9]+)*$/'],
            'owner_name' => ['required', 'string', 'max:255'],
            'owner_email' => ['required', 'string', 'email', 'max:255'],
        ];
    }

    /**
     * Reject a subdomain that is already taken by another tenant.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->filled('subdomain') && Domain::query()->where('domain', $this->fullDomain())->exists()) {
                $validator->errors()->add('subdomain', __('This subdomain is already taken.'));
            }
        });
    }

    /**
     * The full domain the new tenant will be reachable at.
     */
    public function fullDomain(): string
    {
        $host = (string) parse_url((string) config('app.url'), PHP_URL_HOST);

        return Str::lower((string) $this->string('subdomain')).'.'.$host;
    }
}

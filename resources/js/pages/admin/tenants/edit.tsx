import { Form, Head, router } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import TenantController from '@/actions/App/Http/Controllers/Admin/TenantController';
import TenantDomainController from '@/actions/App/Http/Controllers/Admin/TenantDomainController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import type { Domain, Tenant } from '@/types';

export default function EditTenant({
    tenant,
    domains,
    generatedPassword,
    ownerEmail,
}: {
    tenant: Tenant;
    domains: Domain[];
    generatedPassword?: string;
    ownerEmail?: string;
}) {
    return (
        <>
            <Head title={`Edit ${tenant.name}`} />

            <div className="max-w-2xl space-y-6">
                <Heading title={tenant.name} description="Manage this tenant" />

                {generatedPassword && (
                    <Alert>
                        <AlertTitle>Owner account created</AlertTitle>
                        <AlertDescription>
                            <p>
                                Email: <strong>{ownerEmail}</strong>
                            </p>
                            <p>
                                Temporary password:{' '}
                                <strong className="font-mono">
                                    {generatedPassword}
                                </strong>
                            </p>
                            <p>
                                Hand this to the owner now — it will not be
                                shown again.
                            </p>
                        </AlertDescription>
                    </Alert>
                )}

                <Form
                    {...TenantController.update.form({ tenant: tenant.id })}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Business name</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    required
                                    defaultValue={tenant.name}
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="currency">Currency</Label>
                                    <Input
                                        id="currency"
                                        name="currency"
                                        required
                                        defaultValue={tenant.currency}
                                        maxLength={3}
                                        className="uppercase"
                                    />
                                    <InputError message={errors.currency} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="timezone">Timezone</Label>
                                    <Input
                                        id="timezone"
                                        name="timezone"
                                        required
                                        defaultValue={tenant.timezone}
                                    />
                                    <InputError message={errors.timezone} />
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="ntn">NTN</Label>
                                    <Input
                                        id="ntn"
                                        name="ntn"
                                        defaultValue={tenant.ntn ?? ''}
                                    />
                                    <InputError message={errors.ntn} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="strn">STRN</Label>
                                    <Input
                                        id="strn"
                                        name="strn"
                                        defaultValue={tenant.strn ?? ''}
                                    />
                                    <InputError message={errors.strn} />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="contact_name">
                                    Contact name
                                </Label>
                                <Input
                                    id="contact_name"
                                    name="contact_name"
                                    defaultValue={tenant.contact_name ?? ''}
                                />
                                <InputError message={errors.contact_name} />
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="contact_phone">
                                        Contact phone
                                    </Label>
                                    <Input
                                        id="contact_phone"
                                        name="contact_phone"
                                        defaultValue={
                                            tenant.contact_phone ?? ''
                                        }
                                    />
                                    <InputError
                                        message={errors.contact_phone}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="contact_email">
                                        Contact email
                                    </Label>
                                    <Input
                                        id="contact_email"
                                        type="email"
                                        name="contact_email"
                                        defaultValue={
                                            tenant.contact_email ?? ''
                                        }
                                    />
                                    <InputError
                                        message={errors.contact_email}
                                    />
                                </div>
                            </div>

                            <div className="flex items-center space-x-3">
                                <Checkbox
                                    id="is_active"
                                    name="is_active"
                                    defaultChecked={tenant.is_active}
                                />
                                <Label htmlFor="is_active">Active</Label>
                            </div>

                            <div className="flex items-center gap-4">
                                <Button disabled={processing} type="submit">
                                    {processing && <Spinner />}
                                    Save
                                </Button>
                            </div>
                        </>
                    )}
                </Form>

                <div className="space-y-4 border-t pt-6">
                    <Heading
                        variant="small"
                        title="Domains"
                        description="Hosts this tenant is reachable at"
                    />

                    <ul className="divide-border divide-y rounded-lg border">
                        {domains.map((domain) => (
                            <li
                                key={domain.id}
                                className="flex items-center justify-between px-4 py-2"
                            >
                                <span className="font-mono text-sm">
                                    {domain.domain}
                                </span>

                                <AlertDialog>
                                    <AlertDialogTrigger asChild>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            disabled={domains.length <= 1}
                                            aria-label={`Remove ${domain.domain}`}
                                        >
                                            <Trash2 />
                                        </Button>
                                    </AlertDialogTrigger>
                                    <AlertDialogContent>
                                        <AlertDialogHeader>
                                            <AlertDialogTitle>
                                                Remove {domain.domain}?
                                            </AlertDialogTitle>
                                            <AlertDialogDescription>
                                                The tenant will no longer be
                                                reachable at this domain.
                                            </AlertDialogDescription>
                                        </AlertDialogHeader>
                                        <AlertDialogFooter>
                                            <AlertDialogCancel>
                                                Cancel
                                            </AlertDialogCancel>
                                            <AlertDialogAction
                                                onClick={() =>
                                                    router.delete(
                                                        TenantDomainController.destroy(
                                                            {
                                                                tenant: tenant.id,
                                                                domain: domain.id,
                                                            },
                                                        ).url,
                                                    )
                                                }
                                            >
                                                Remove
                                            </AlertDialogAction>
                                        </AlertDialogFooter>
                                    </AlertDialogContent>
                                </AlertDialog>
                            </li>
                        ))}
                    </ul>

                    <Form
                        {...TenantDomainController.store.form({
                            tenant: tenant.id,
                        })}
                        resetOnSuccess
                        className="flex items-start gap-2"
                    >
                        {({ processing, errors }) => (
                            <>
                                <div className="grid flex-1 gap-2">
                                    <Input
                                        name="domain"
                                        placeholder="another-name.example.com"
                                    />
                                    <InputError message={errors.domain} />
                                </div>
                                <Button disabled={processing} type="submit">
                                    {processing && <Spinner />}
                                    Add domain
                                </Button>
                            </>
                        )}
                    </Form>
                </div>
            </div>
        </>
    );
}

EditTenant.layout = {
    breadcrumbs: [
        { title: 'Tenants', href: '/admin/tenants' },
        { title: 'Edit tenant', href: '/admin/tenants/edit' },
    ],
};

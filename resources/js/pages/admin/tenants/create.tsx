import { Form, Head, Link } from '@inertiajs/react';
import TenantController from '@/actions/App/Http/Controllers/Admin/TenantController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { index } from '@/routes/admin/tenants';

export default function CreateTenant({ centralHost }: { centralHost: string }) {
    return (
        <>
            <Head title="New tenant" />

            <div className="max-w-2xl space-y-6">
                <Heading
                    title="New tenant"
                    description="Provision a tenant, its first domain, and its owner account"
                />

                <Form {...TenantController.store.form()} className="space-y-6">
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Business name</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    required
                                    autoFocus
                                    placeholder="Acme Fuels"
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="subdomain">Subdomain</Label>
                                <div className="flex items-center gap-2">
                                    <Input
                                        id="subdomain"
                                        name="subdomain"
                                        required
                                        placeholder="acme"
                                        className="max-w-56"
                                    />
                                    <span className="text-muted-foreground text-sm">
                                        .{centralHost}
                                    </span>
                                </div>
                                <InputError message={errors.subdomain} />
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="currency">Currency</Label>
                                    <Input
                                        id="currency"
                                        name="currency"
                                        required
                                        defaultValue="PKR"
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
                                        defaultValue="Asia/Karachi"
                                    />
                                    <InputError message={errors.timezone} />
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="ntn">NTN</Label>
                                    <Input id="ntn" name="ntn" />
                                    <InputError message={errors.ntn} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="strn">STRN</Label>
                                    <Input id="strn" name="strn" />
                                    <InputError message={errors.strn} />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="contact_name">
                                    Contact name
                                </Label>
                                <Input id="contact_name" name="contact_name" />
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
                                    />
                                    <InputError
                                        message={errors.contact_email}
                                    />
                                </div>
                            </div>

                            <hr className="border-border" />

                            <div className="grid gap-2">
                                <Label htmlFor="owner_name">Owner name</Label>
                                <Input
                                    id="owner_name"
                                    name="owner_name"
                                    required
                                    placeholder="Owner's full name"
                                />
                                <InputError message={errors.owner_name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="owner_email">Owner email</Label>
                                <Input
                                    id="owner_email"
                                    type="email"
                                    name="owner_email"
                                    required
                                    placeholder="owner@example.com"
                                />
                                <InputError message={errors.owner_email} />
                            </div>

                            <div className="flex items-center gap-4">
                                <Button disabled={processing} type="submit">
                                    {processing && <Spinner />}
                                    Create tenant
                                </Button>
                                <Button variant="outline" asChild>
                                    <Link href={index()}>Cancel</Link>
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

CreateTenant.layout = {
    breadcrumbs: [
        { title: 'Tenants', href: '/admin/tenants' },
        { title: 'New tenant', href: '/admin/tenants/create' },
    ],
};

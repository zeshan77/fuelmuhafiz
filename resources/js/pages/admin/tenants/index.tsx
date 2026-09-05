import { Head, Link, router } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import TenantController from '@/actions/App/Http/Controllers/Admin/TenantController';
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
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import Heading from '@/components/heading';
import { create, edit } from '@/routes/admin/tenants';
import type { Paginated, Tenant } from '@/types';

export default function TenantsIndex({
    tenants,
}: {
    tenants: Paginated<Tenant>;
}) {
    return (
        <>
            <Head title="Tenants" />

            <div className="flex flex-col gap-6">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Tenants"
                        description="Petrol pump businesses running on this platform"
                    />

                    <Button asChild>
                        <Link href={create()}>
                            <Plus />
                            New tenant
                        </Link>
                    </Button>
                </div>

                <div className="rounded-lg border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Name</TableHead>
                                <TableHead>Domain</TableHead>
                                <TableHead>Currency</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="text-right">
                                    Actions
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {tenants.data.length === 0 && (
                                <TableRow>
                                    <TableCell
                                        colSpan={5}
                                        className="text-muted-foreground text-center"
                                    >
                                        No tenants yet.
                                    </TableCell>
                                </TableRow>
                            )}

                            {tenants.data.map((tenant) => (
                                <TableRow key={tenant.id}>
                                    <TableCell className="font-medium">
                                        <Link
                                            href={edit({ tenant: tenant.id })}
                                            className="hover:underline"
                                        >
                                            {tenant.name}
                                        </Link>
                                    </TableCell>
                                    <TableCell className="text-muted-foreground">
                                        {tenant.domains
                                            ?.map((domain) => domain.domain)
                                            .join(', ') || '—'}
                                    </TableCell>
                                    <TableCell>{tenant.currency}</TableCell>
                                    <TableCell>
                                        <Badge
                                            variant={
                                                tenant.is_active
                                                    ? 'default'
                                                    : 'secondary'
                                            }
                                        >
                                            {tenant.is_active
                                                ? 'Active'
                                                : 'Inactive'}
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <AlertDialog>
                                            <AlertDialogTrigger asChild>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    aria-label={`Delete ${tenant.name}`}
                                                >
                                                    <Trash2 />
                                                </Button>
                                            </AlertDialogTrigger>
                                            <AlertDialogContent>
                                                <AlertDialogHeader>
                                                    <AlertDialogTitle>
                                                        Delete {tenant.name}?
                                                    </AlertDialogTitle>
                                                    <AlertDialogDescription>
                                                        This permanently deletes
                                                        the tenant, its domains,
                                                        and its entire database.
                                                        This cannot be undone.
                                                    </AlertDialogDescription>
                                                </AlertDialogHeader>
                                                <AlertDialogFooter>
                                                    <AlertDialogCancel>
                                                        Cancel
                                                    </AlertDialogCancel>
                                                    <AlertDialogAction
                                                        onClick={() =>
                                                            router.delete(
                                                                TenantController.destroy(
                                                                    {
                                                                        tenant: tenant.id,
                                                                    },
                                                                ).url,
                                                            )
                                                        }
                                                    >
                                                        Delete
                                                    </AlertDialogAction>
                                                </AlertDialogFooter>
                                            </AlertDialogContent>
                                        </AlertDialog>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>

                {(tenants.prev_page_url || tenants.next_page_url) && (
                    <div className="flex justify-end gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            disabled={!tenants.prev_page_url}
                            asChild={!!tenants.prev_page_url}
                        >
                            {tenants.prev_page_url ? (
                                <Link href={tenants.prev_page_url}>
                                    Previous
                                </Link>
                            ) : (
                                <span>Previous</span>
                            )}
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            disabled={!tenants.next_page_url}
                            asChild={!!tenants.next_page_url}
                        >
                            {tenants.next_page_url ? (
                                <Link href={tenants.next_page_url}>Next</Link>
                            ) : (
                                <span>Next</span>
                            )}
                        </Button>
                    </div>
                )}
            </div>
        </>
    );
}

TenantsIndex.layout = {
    breadcrumbs: [{ title: 'Tenants', href: '/admin/tenants' }],
};

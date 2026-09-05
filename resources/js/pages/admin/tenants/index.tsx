import { Deferred, Head, Link, router } from '@inertiajs/react';
import { Download, Globe, Pencil, Plus, Trash2 } from 'lucide-react';
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
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useInitials } from '@/hooks/use-initials';
import { cn } from '@/lib/utils';
import { create, edit, index } from '@/routes/admin/tenants';
import type { Paginated, TenantListItem, TenantStats } from '@/types';

const STATUS_TABS = [
    { label: 'All', value: null },
    { label: 'Active', value: 'active' },
    { label: 'Trial', value: 'trial' },
    { label: 'Suspended', value: 'suspended' },
] as const;

const STATUS_STYLES: Record<string, { pill: string; dot: string }> = {
    active: {
        pill: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300',
        dot: 'bg-emerald-500',
    },
    trial: {
        pill: 'bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300',
        dot: 'bg-sky-500',
    },
    suspended: {
        pill: 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300',
        dot: 'bg-rose-500',
    },
};

const AVATAR_TONES = [
    'bg-emerald-100 text-emerald-900 dark:bg-emerald-950 dark:text-emerald-200',
    'bg-lime-100 text-lime-900 dark:bg-lime-950 dark:text-lime-200',
    'bg-amber-100 text-amber-900 dark:bg-amber-950 dark:text-amber-200',
    'bg-sky-100 text-sky-900 dark:bg-sky-950 dark:text-sky-200',
    'bg-rose-100 text-rose-900 dark:bg-rose-950 dark:text-rose-200',
];

function toneFor(value: string): string {
    const sum = Array.from(value).reduce(
        (total, character) => total + character.charCodeAt(0),
        0,
    );

    return AVATAR_TONES[sum % AVATAR_TONES.length];
}

function formatDate(value: string | null): string {
    if (!value) {
        return 'Unknown';
    }

    return new Date(value).toLocaleDateString(undefined, {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}

function StatCard({
    label,
    value,
    hint,
    badge,
    highlight = false,
}: {
    label: string;
    value: string | number;
    hint?: string;
    badge?: string;
    highlight?: boolean;
}) {
    return (
        <div
            className={cn(
                'rounded-2xl border p-5 shadow-xs',
                highlight
                    ? 'bg-brand text-brand-foreground border-transparent'
                    : 'bg-card',
            )}
        >
            <p
                className={cn(
                    'text-[10px] font-medium tracking-[0.12em] uppercase',
                    highlight
                        ? 'text-brand-foreground/70'
                        : 'text-muted-foreground',
                )}
            >
                {label}
            </p>
            <div className="mt-3 flex flex-wrap items-baseline gap-x-2 gap-y-1">
                <span
                    className={cn(
                        'text-3xl leading-none font-semibold tracking-tight',
                        highlight && 'text-lime',
                    )}
                >
                    {value}
                </span>
                {badge && (
                    <span className="rounded-full bg-lime-100 px-2 py-0.5 text-xs font-medium text-lime-900 dark:bg-lime-950 dark:text-lime-200">
                        {badge}
                    </span>
                )}
                {hint && (
                    <span
                        className={cn(
                            'text-xs',
                            highlight
                                ? 'text-brand-foreground/70'
                                : 'text-muted-foreground',
                        )}
                    >
                        {hint}
                    </span>
                )}
            </div>
        </div>
    );
}

function StatCardSkeleton({ highlight = false }: { highlight?: boolean }) {
    return (
        <div
            className={cn(
                'rounded-2xl border p-5 shadow-xs',
                highlight ? 'bg-brand border-transparent' : 'bg-card',
            )}
        >
            <Skeleton className="h-2.5 w-24 rounded-full" />
            <Skeleton className="mt-4 h-7 w-16 rounded-md" />
        </div>
    );
}

export default function TenantsIndex({
    tenants,
    filters,
    stats,
}: {
    tenants: Paginated<TenantListItem>;
    filters: { search: string; status: string | null };
    stats?: TenantStats;
}) {
    const getInitials = useInitials();

    const exportUrl = TenantController.export.url(undefined, {
        query: {
            search: filters.search || undefined,
            status: filters.status ?? undefined,
        },
    });

    return (
        <>
            <Head title="Tenants" />

            <div className="space-y-6 px-4 pb-12 md:px-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="max-w-md">
                        <h1 className="text-5xl font-semibold tracking-tight">
                            Tenants.
                        </h1>
                        <p className="text-muted-foreground mt-3 text-sm">
                            Petrol pump businesses running on this platform —
                            each with its own domain, currency and staff.
                        </p>
                    </div>

                    <div className="flex items-center gap-2">
                        <Button
                            variant="outline"
                            className="rounded-lg"
                            asChild
                        >
                            <a href={exportUrl}>
                                <Download />
                                Export
                            </a>
                        </Button>
                        <Button className="rounded-lg" asChild>
                            <Link href={create()}>
                                <Plus />
                                New tenant
                            </Link>
                        </Button>
                    </div>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <Deferred
                        data="stats"
                        fallback={
                            <>
                                <StatCardSkeleton />
                                <StatCardSkeleton />
                                <StatCardSkeleton />
                                <StatCardSkeleton highlight />
                            </>
                        }
                    >
                        <>
                            <StatCard
                                label="Total tenants"
                                value={stats?.total ?? 0}
                                badge={
                                    stats?.newThisMonth
                                        ? `+${stats.newThisMonth} this month`
                                        : undefined
                                }
                            />
                            <StatCard
                                label="On trial"
                                value={stats?.trial ?? 0}
                                hint="in trial period"
                            />
                            <StatCard
                                label="Suspended"
                                value={stats?.suspended ?? 0}
                                hint="need attention"
                            />
                            <StatCard
                                label="Active"
                                value={stats?.active ?? 0}
                                hint="serving traffic"
                                highlight
                            />
                        </>
                    </Deferred>
                </div>

                <div className="bg-card rounded-2xl border shadow-xs">
                    <div className="flex flex-wrap items-center justify-between gap-3 p-4">
                        <div className="bg-muted inline-flex rounded-full p-1">
                            {STATUS_TABS.map((tab) => {
                                const isActive =
                                    (filters.status ?? null) === tab.value;

                                return (
                                    <Link
                                        key={tab.label}
                                        href={index(undefined, {
                                            query: {
                                                search:
                                                    filters.search || undefined,
                                                status: tab.value ?? undefined,
                                            },
                                        })}
                                        preserveScroll
                                        className={cn(
                                            'rounded-full px-4 py-1.5 text-sm font-medium transition',
                                            isActive
                                                ? 'bg-card text-foreground shadow-xs'
                                                : 'text-muted-foreground hover:text-foreground',
                                        )}
                                    >
                                        {tab.label}
                                    </Link>
                                );
                            })}
                        </div>

                        <p className="text-muted-foreground text-sm">
                            {tenants.total}{' '}
                            {tenants.total === 1 ? 'tenant' : 'tenants'}
                            {filters.search && ` matching “${filters.search}”`}
                        </p>
                    </div>

                    <Table>
                        <TableHeader>
                            <TableRow className="hover:bg-transparent">
                                <TableHead className="text-muted-foreground pl-6 text-[10px] tracking-[0.12em] uppercase">
                                    Name
                                </TableHead>
                                <TableHead className="text-muted-foreground text-[10px] tracking-[0.12em] uppercase">
                                    Domain
                                </TableHead>
                                <TableHead className="text-muted-foreground text-[10px] tracking-[0.12em] uppercase">
                                    Currency
                                </TableHead>
                                <TableHead className="text-muted-foreground text-[10px] tracking-[0.12em] uppercase">
                                    Status
                                </TableHead>
                                <TableHead className="text-muted-foreground pr-6 text-right text-[10px] tracking-[0.12em] uppercase">
                                    Actions
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {tenants.data.length === 0 && (
                                <TableRow className="hover:bg-transparent">
                                    <TableCell
                                        colSpan={5}
                                        className="text-muted-foreground py-12 text-center"
                                    >
                                        {filters.search || filters.status
                                            ? 'No tenants match these filters.'
                                            : 'No tenants yet — create the first one.'}
                                    </TableCell>
                                </TableRow>
                            )}

                            {tenants.data.map((tenant) => {
                                const status =
                                    STATUS_STYLES[tenant.status] ??
                                    STATUS_STYLES.active;

                                return (
                                    <TableRow key={tenant.id}>
                                        <TableCell className="py-3 pl-6">
                                            <div className="flex items-center gap-3">
                                                <span
                                                    className={cn(
                                                        'flex size-9 shrink-0 items-center justify-center rounded-full text-xs font-semibold',
                                                        toneFor(tenant.id),
                                                    )}
                                                >
                                                    {getInitials(tenant.name)}
                                                </span>
                                                <div className="min-w-0">
                                                    <Link
                                                        href={edit({
                                                            tenant: tenant.id,
                                                        })}
                                                        className="block truncate font-semibold hover:underline"
                                                    >
                                                        {tenant.name}
                                                    </Link>
                                                    <p className="text-muted-foreground truncate text-xs">
                                                        {tenant.contact_name
                                                            ? `${tenant.contact_name} · `
                                                            : ''}
                                                        Added{' '}
                                                        {formatDate(
                                                            tenant.created_at,
                                                        )}
                                                    </p>
                                                </div>
                                            </div>
                                        </TableCell>

                                        <TableCell>
                                            <div className="flex flex-wrap items-center gap-1.5">
                                                {tenant.domains.length ===
                                                    0 && (
                                                    <span className="text-muted-foreground text-xs">
                                                        No domain
                                                    </span>
                                                )}
                                                {tenant.domains
                                                    .slice(0, 1)
                                                    .map((domain) => (
                                                        <span
                                                            key={domain.id}
                                                            className="bg-muted inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 font-mono text-xs"
                                                        >
                                                            <Globe className="text-muted-foreground size-3" />
                                                            {domain.domain}
                                                        </span>
                                                    ))}
                                                {tenant.domains.length > 1 && (
                                                    <span className="text-muted-foreground text-xs">
                                                        +
                                                        {tenant.domains.length -
                                                            1}
                                                    </span>
                                                )}
                                            </div>
                                        </TableCell>

                                        <TableCell className="text-muted-foreground text-sm">
                                            {tenant.currency}
                                        </TableCell>

                                        <TableCell>
                                            <span
                                                className={cn(
                                                    'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium capitalize',
                                                    status.pill,
                                                )}
                                            >
                                                <span
                                                    className={cn(
                                                        'size-1.5 rounded-full',
                                                        status.dot,
                                                    )}
                                                />
                                                {tenant.status}
                                            </span>
                                        </TableCell>

                                        <TableCell className="pr-6">
                                            <div className="flex items-center justify-end gap-1">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="text-muted-foreground hover:text-foreground size-8"
                                                    aria-label={`Edit ${tenant.name}`}
                                                    asChild
                                                >
                                                    <Link
                                                        href={edit({
                                                            tenant: tenant.id,
                                                        })}
                                                    >
                                                        <Pencil />
                                                    </Link>
                                                </Button>

                                                <AlertDialog>
                                                    <AlertDialogTrigger asChild>
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            className="text-muted-foreground hover:text-destructive size-8"
                                                            aria-label={`Delete ${tenant.name}`}
                                                        >
                                                            <Trash2 />
                                                        </Button>
                                                    </AlertDialogTrigger>
                                                    <AlertDialogContent>
                                                        <AlertDialogHeader>
                                                            <AlertDialogTitle>
                                                                Delete{' '}
                                                                {tenant.name}?
                                                            </AlertDialogTitle>
                                                            <AlertDialogDescription>
                                                                This permanently
                                                                deletes the
                                                                tenant, its
                                                                domains, and its
                                                                entire database.
                                                                This cannot be
                                                                undone.
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
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                );
                            })}
                        </TableBody>
                    </Table>

                    <div className="flex flex-wrap items-center justify-between gap-3 border-t p-4">
                        <p className="text-muted-foreground text-sm">
                            Showing {tenants.data.length} of {tenants.total}{' '}
                            {tenants.total === 1 ? 'tenant' : 'tenants'}
                        </p>

                        <div className="flex items-center gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                className="rounded-lg"
                                disabled={!tenants.prev_page_url}
                                asChild={!!tenants.prev_page_url}
                            >
                                {tenants.prev_page_url ? (
                                    <Link
                                        href={tenants.prev_page_url}
                                        preserveScroll
                                    >
                                        Previous
                                    </Link>
                                ) : (
                                    <span>Previous</span>
                                )}
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                className="rounded-lg"
                                disabled={!tenants.next_page_url}
                                asChild={!!tenants.next_page_url}
                            >
                                {tenants.next_page_url ? (
                                    <Link
                                        href={tenants.next_page_url}
                                        preserveScroll
                                    >
                                        Next
                                    </Link>
                                ) : (
                                    <span>Next</span>
                                )}
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}

TenantsIndex.layout = {
    breadcrumbs: [
        { title: 'Platform', href: '/admin/tenants' },
        { title: 'Tenants', href: '/admin/tenants' },
    ],
};

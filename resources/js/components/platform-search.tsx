import { router, usePage } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { type FormEvent, useState } from 'react';
import { index } from '@/routes/admin/tenants';

/**
 * Console-wide search. Tenants (and their domains) are the only searchable
 * records in the central app today, so this submits straight to the tenant
 * list rather than pretending to be a global index.
 */
export function PlatformSearch() {
    const filters = usePage().props.filters as { search?: string } | undefined;

    const [term, setTerm] = useState(filters?.search ?? '');

    const submit = (event: FormEvent) => {
        event.preventDefault();

        router.get(
            index().url,
            { search: term },
            { preserveState: true, replace: true },
        );
    };

    return (
        <form onSubmit={submit} className="relative hidden w-72 sm:block">
            <Search className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
            <input
                type="search"
                name="search"
                value={term}
                onChange={(event) => setTerm(event.target.value)}
                placeholder="Search tenants or domains"
                aria-label="Search tenants or domains"
                className="border-border bg-card placeholder:text-muted-foreground focus-visible:ring-ring/40 h-9 w-full rounded-full border py-2 pr-4 pl-9 text-sm shadow-xs transition outline-none focus-visible:ring-2"
            />
        </form>
    );
}

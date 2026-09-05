export type Domain = {
    id: string;
    domain: string;
    created_at: string;
    updated_at: string;
};

export type Tenant = {
    id: string;
    name: string;
    ntn: string | null;
    strn: string | null;
    contact_name: string | null;
    contact_phone: string | null;
    contact_email: string | null;
    currency: string;
    timezone: string;
    is_active: boolean;
    trial_ends_at: string | null;
    created_at: string;
    updated_at: string;
    domains?: Domain[];
};

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type Paginated<T> = {
    data: T[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
    total: number;
};

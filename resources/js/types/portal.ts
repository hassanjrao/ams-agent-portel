export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

// Laravel's LengthAwarePaginator as JSON.
export type Paginated<T> = {
    data: T[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
};

export type Option = {
    id: number;
    name: string;
};

export type AirlineOption = Option & { code: string };

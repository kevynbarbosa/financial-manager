export type BankAccount = {
    id: number;
    name: string;
    institution: string;
    balance: number;
    currency?: string;
    accountType?: string;
    monthlyMovements: {
        income: number;
        expense: number;
    };
};

export type TransactionTag = {
    id: number;
    name: string;
};

export type BankTransaction = {
    id: number;
    description: string;
    amount: number;
    type: 'credit' | 'debit';
    occurred_at: string | null;
    category?: string | null;
    tags: TransactionTag[];
    account: {
        id: number;
        name: string;
        institution: string | null;
    };
};

export type TransactionFilters = {
    search?: string;
    type?: string;
    account?: number | null;
    start_date?: string;
    end_date?: string;
};

export type PaginatedResource<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    next_page_url: string | null;
    prev_page_url: string | null;
    first_page_url: string;
    last_page_url: string;
    path: string;
};

export type TagReportEntry = {
    id: number;
    name: string;
    credit: number;
    debit: number;
    net: number;
};

export type TagReports = {
    totals: {
        credit: number;
        debit: number;
    };
    breakdown: TagReportEntry[];
};

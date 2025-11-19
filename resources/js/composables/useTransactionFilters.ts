import { computed, reactive, watch } from 'vue';
import type { TransactionFilters } from '@/types/accounts';

export type SortColumn = NonNullable<TransactionFilters['sort']>;
export type SortDirection = NonNullable<TransactionFilters['direction']>;

export type FilterFormState = {
    search: string;
    type: string;
    account: string;
    start_date: string;
    end_date: string;
    category: string;
    sort: SortColumn;
    direction: SortDirection;
};

type FiltersSource = () => TransactionFilters | undefined;

type UseTransactionFiltersOptions = {
    defaultSort?: SortColumn;
    defaultDirection?: SortDirection;
    initialSortDirectionByColumn?: Record<SortColumn, SortDirection>;
};

export const useTransactionFilters = (source: FiltersSource, options?: UseTransactionFiltersOptions) => {
    const defaultSort: SortColumn = options?.defaultSort ?? 'occurred_at';
    const defaultDirection: SortDirection = options?.defaultDirection ?? 'desc';
    const initialSortDirectionByColumn = options?.initialSortDirectionByColumn ?? {
        description: 'asc',
        amount: 'desc',
        occurred_at: 'desc',
    };

    const buildState = (filters?: TransactionFilters): FilterFormState => ({
        search: filters?.search ?? '',
        type: filters?.type ?? '',
        account: filters?.account ? String(filters.account) : '',
        start_date: filters?.start_date ?? '',
        end_date: filters?.end_date ?? '',
        category: filters?.category ?? '',
        sort: filters?.sort ?? defaultSort,
        direction: filters?.direction ?? defaultDirection,
    });

    const filterState = reactive<FilterFormState>(buildState(source()));

    const hasActiveFilters = computed(() => {
        return Boolean(
            filterState.search ||
                filterState.type ||
                filterState.account ||
                filterState.start_date ||
                filterState.end_date ||
                filterState.category,
        );
    });

    const filtersPayload = computed(() => {
        const payload: Record<string, string> = {};

        if (filterState.search) {
            payload.search = filterState.search;
        }

        if (filterState.type) {
            payload.type = filterState.type;
        }

        if (filterState.account) {
            payload.account = filterState.account;
        }

        if (filterState.start_date) {
            payload.start_date = filterState.start_date;
        }

        if (filterState.end_date) {
            payload.end_date = filterState.end_date;
        }

        if (filterState.category) {
            payload.category = filterState.category;
        }

        payload.sort = filterState.sort || defaultSort;
        payload.direction = filterState.direction || defaultDirection;

        return payload;
    });

    const resetFilters = () => {
        const state = buildState();

        filterState.search = state.search;
        filterState.type = state.type;
        filterState.account = state.account;
        filterState.start_date = state.start_date;
        filterState.end_date = state.end_date;
        filterState.category = state.category;
        filterState.sort = state.sort;
        filterState.direction = state.direction;
    };

    watch(
        source,
        (updatedFilters) => {
            const state = buildState(updatedFilters);

            filterState.search = state.search;
            filterState.type = state.type;
            filterState.account = state.account;
            filterState.start_date = state.start_date;
            filterState.end_date = state.end_date;
            filterState.category = state.category;
            filterState.sort = state.sort;
            filterState.direction = state.direction;
        },
        { deep: true },
    );

    const toggleSort = (column: SortColumn) => {
        if (filterState.sort === column) {
            filterState.direction = filterState.direction === 'asc' ? 'desc' : 'asc';

            return;
        }

        filterState.sort = column;
        filterState.direction = initialSortDirectionByColumn[column] ?? defaultDirection;
    };

    const ariaSortFor = (column: SortColumn): 'none' | 'ascending' | 'descending' => {
        if (filterState.sort !== column) {
            return 'none';
        }

        return filterState.direction === 'asc' ? 'ascending' : 'descending';
    };

    const sortButtonLabel = (column: SortColumn, label: string) => {
        if (filterState.sort !== column) {
            return `Ordenar por ${label}`;
        }

        const directionLabel = filterState.direction === 'asc' ? 'ascendente' : 'descendente';

        return `Ordenar por ${label} (${directionLabel})`;
    };

    const getSortDirection = (column: SortColumn): SortDirection | null => {
        return filterState.sort === column ? filterState.direction : null;
    };

    return {
        filterState,
        hasActiveFilters,
        filtersPayload,
        resetFilters,
        toggleSort,
        ariaSortFor,
        sortButtonLabel,
        getSortDirection,
    };
};

<script setup lang="ts">
import TransactionCategoryDropdown from '@/components/accounts/TransactionCategoryDropdown.vue';
import DataTablePagination from '@/components/common/DataTablePagination.vue';
import DatePicker from '@/components/common/DatePicker.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useTransactionFilters, type SortColumn, type SortDirection } from '@/composables/useTransactionFilters';
import { formatDateTime } from '@/lib/date-utils';
import { formatCurrency } from '@/pages/accounts/utils';
import { index as accountsIndex } from '@/routes/accounts';
import { edit as editTransaction } from '@/routes/transactions';
import { update as updateTransactionCategoryRoute } from '@/routes/transactions/category';
import { modal as bulkCategoryModalRoute } from '@/routes/transactions/category/bulk';
import type { BankTransaction, PaginatedResource, TransactionCategoryOption, TransactionFilters } from '@/types/accounts';
import { router } from '@inertiajs/vue3';
import { ArrowDown, ArrowDownRight, ArrowUp, ArrowUpDown, ArrowUpRight, Filter, Pencil, Sparkles } from 'lucide-vue-next';
import { computed, ref } from 'vue';

type AccountOption = {
    id: number;
    label: string;
    value: string;
};

const defaultSortColumn: SortColumn = 'occurred_at';
const defaultSortDirection = 'desc';

const initialSortDirectionByColumn: Record<SortColumn, SortDirection> = {
    description: 'asc',
    amount: 'desc',
    occurred_at: 'desc',
};

const props = withDefaults(
    defineProps<{
        transactions: PaginatedResource<BankTransaction>;
        filters: TransactionFilters;
        accountOptions?: AccountOption[];
        categoryOptions?: TransactionCategoryOption[];
    }>(),
    {
        transactions: () => ({
            data: [],
            current_page: 1,
            last_page: 1,
            per_page: 10,
            total: 0,
            from: null,
            to: null,
            next_page_url: null,
            prev_page_url: null,
            first_page_url: '',
            last_page_url: '',
            path: '',
        }),
        filters: () => ({
            search: '',
            type: '',
            account: null,
            start_date: '',
            end_date: '',
            category: '',
            sort: defaultSortColumn,
            direction: defaultSortDirection,
        }),
        accountOptions: () => [],
        categoryOptions: () => [],
    },
);

const filtersSource = computed(() => props.filters);

const {
    filterState,
    hasActiveFilters,
    filtersPayload,
    resetFilters: resetFilterState,
    toggleSort: toggleSortState,
    ariaSortFor,
    sortButtonLabel,
    getSortDirection,
} = useTransactionFilters(() => filtersSource.value, {
    defaultSort: defaultSortColumn,
    defaultDirection: defaultSortDirection,
    initialSortDirectionByColumn,
});

const isLoading = ref(false);

const submitFilters = () => {
    router.get(accountsIndex().url, filtersPayload.value, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['accounts', 'summary', 'transactions', 'transactionFilters', 'transactionCategoryOptions'],
        onStart: () => {
            isLoading.value = true;
        },
        onFinish: () => {
            isLoading.value = false;
        },
    });
};

const resetFilters = () => {
    resetFilterState();
    submitFilters();
};

const handleSort = (column: SortColumn) => {
    toggleSortState(column);
    submitFilters();
};

const sortIconFor = (column: SortColumn) => {
    const direction = getSortDirection(column);

    if (!direction) {
        return ArrowUpDown;
    }

    return direction === 'asc' ? ArrowUp : ArrowDown;
};

const transactionCountLabel = computed(() => {
    const from = props.transactions.from ?? 0;
    const to = props.transactions.to ?? 0;
    const total = props.transactions.total ?? 0;

    return `Mostrando ${from} a ${to} de ${total} transações`;
});

const tableIsEmpty = computed(() => !props.transactions.data.length && !isLoading.value);

const transactionDetailsModalUrl = (transactionId: number) => editTransaction(transactionId).url;
const updateTransactionCategory = (transactionId: number, categoryValue: string | null) => {
    const payload = {
        category_id: categoryValue ? Number(categoryValue) : null,
    };

    if (payload.category_id !== null && Number.isNaN(payload.category_id)) {
        payload.category_id = null;
    }

    router.put(updateTransactionCategoryRoute(transactionId).url, payload, {
        preserveScroll: true,
        preserveState: true,
        only: ['transactions', 'transactionFilters'],
    });
};

const categoryFilterOptions = computed(() => {
    const base = [
        { value: '', label: 'Todas as categorias' },
        { value: 'none', label: 'Sem categoria' },
    ];

    const categories =
        props.categoryOptions?.map((category) => ({
            value: String(category.id),
            label: category.name,
        })) ?? [];

    return [...base, ...categories];
});

const bulkCategoryModalUrl = computed(() => bulkCategoryModalRoute().url);
</script>

<template>
    <Card class="border border-border/70 bg-gradient-to-b from-background to-muted/30">
        <CardHeader class="gap-3">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <CardTitle class="text-2xl">Transações recentes</CardTitle>
                    <CardDescription>{{ transactionCountLabel }}</CardDescription>
                </div>
                <div class="flex flex-col items-start gap-2 md:items-end">
                    <div class="flex items-center gap-3 rounded-md border border-border/60 px-3 py-2 text-xs text-muted-foreground">
                        <div class="flex items-center gap-1">
                            <span class="h-2 w-2 rounded-full bg-emerald-500" />
                            Créditos
                        </div>
                        <div class="flex items-center gap-1">
                            <span class="h-2 w-2 rounded-full bg-rose-500" />
                            Débitos
                        </div>
                    </div>
                    <ModalLink
                        :href="bulkCategoryModalUrl"
                        as="button"
                        type="button"
                        class="inline-flex items-center justify-center gap-2 rounded-md bg-primary px-3 py-2 text-xs font-semibold text-primary-foreground shadow hover:opacity-90 focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background focus-visible:outline-none"
                    >
                        <Sparkles class="h-3.5 w-3.5" />
                        <span>Atribuir categoria em massa</span>
                    </ModalLink>
                </div>
            </div>
        </CardHeader>
        <CardContent class="space-y-4">
            <form class="space-y-4" @submit.prevent="submitFilters">
                <div class="space-y-1">
                    <label class="text-xs font-medium text-muted-foreground">Período das transações</label>
                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        <DatePicker v-model="filterState.start_date" placeholder="Data inicial" />
                        <DatePicker v-model="filterState.end_date" placeholder="Data final" />
                    </div>
                </div>

                <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    <div class="space-y-1">
                        <label class="text-xs font-medium text-muted-foreground" for="transaction-search">Buscar</label>
                        <Input id="transaction-search" v-model="filterState.search" type="text" placeholder="Descrição ou categoria" />
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-medium text-muted-foreground" for="transaction-category">Categoria</label>
                        <select
                            id="transaction-category"
                            v-model="filterState.category"
                            class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm shadow-sm focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none"
                        >
                            <option v-for="option in categoryFilterOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-medium text-muted-foreground" for="transaction-type">Tipo</label>
                        <select
                            id="transaction-type"
                            v-model="filterState.type"
                            class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm shadow-sm focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none"
                        >
                            <option value="">Todos</option>
                            <option value="credit">Crédito</option>
                            <option value="debit">Débito</option>
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-medium text-muted-foreground" for="transaction-account">Conta</label>
                        <select
                            id="transaction-account"
                            v-model="filterState.account"
                            class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm shadow-sm focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none"
                        >
                            <option value="">Todas as contas</option>
                            <option v-for="option in accountOptions" :key="option.id" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                    </div>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="flex items-center gap-2 text-xs text-muted-foreground">
                        <Filter class="h-3.5 w-3.5" />
                        <span>Filtros refinam todas as contas.</span>
                    </div>
                    <div class="flex gap-2">
                        <Button type="button" variant="ghost" size="sm" :disabled="!hasActiveFilters || isLoading" @click="resetFilters">
                            Limpar
                        </Button>
                        <Button type="submit" size="sm" :disabled="isLoading">
                            <span v-if="isLoading">Filtrando...</span>
                            <span v-else>Aplicar filtros</span>
                        </Button>
                    </div>
                </div>
            </form>

            <div class="relative overflow-hidden rounded-lg border border-border/60 bg-card shadow-sm">
                <div
                    v-if="isLoading"
                    class="absolute inset-0 z-10 flex items-center justify-center bg-background/60 text-sm font-medium text-muted-foreground"
                >
                    Carregando transações...
                </div>
                <Table :class="isLoading ? 'opacity-50' : ''">
                    <TableHeader>
                        <TableRow>
                            <TableHead>Conta</TableHead>
                            <TableHead :aria-sort="ariaSortFor('description')">
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1 text-left font-medium transition-colors hover:text-foreground focus-visible:outline-none"
                                    :aria-pressed="filterState.sort === 'description'"
                                    :aria-label="sortButtonLabel('description', 'descrição')"
                                    :title="sortButtonLabel('description', 'descrição')"
                                    :disabled="isLoading"
                                    @click="handleSort('description')"
                                >
                                    Descrição
                                    <component
                                        :is="sortIconFor('description')"
                                        class="h-3.5 w-3.5"
                                        :class="filterState.sort === 'description' ? 'text-foreground' : 'text-muted-foreground/70'"
                                    />
                                </button>
                            </TableHead>
                            <TableHead>Categoria</TableHead>
                            <TableHead>Tipo</TableHead>
                            <TableHead class="text-right" :aria-sort="ariaSortFor('amount')">
                                <button
                                    type="button"
                                    class="ml-auto inline-flex items-center gap-1 text-right font-medium transition-colors hover:text-foreground focus-visible:outline-none"
                                    :aria-pressed="filterState.sort === 'amount'"
                                    :aria-label="sortButtonLabel('amount', 'valor')"
                                    :title="sortButtonLabel('amount', 'valor')"
                                    :disabled="isLoading"
                                    @click="handleSort('amount')"
                                >
                                    Valor
                                    <component
                                        :is="sortIconFor('amount')"
                                        class="h-3.5 w-3.5"
                                        :class="filterState.sort === 'amount' ? 'text-foreground' : 'text-muted-foreground/70'"
                                    />
                                </button>
                            </TableHead>
                            <TableHead :aria-sort="ariaSortFor('occurred_at')">
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1 text-left font-medium transition-colors hover:text-foreground focus-visible:outline-none"
                                    :aria-pressed="filterState.sort === 'occurred_at'"
                                    :aria-label="sortButtonLabel('occurred_at', 'data')"
                                    :title="sortButtonLabel('occurred_at', 'data')"
                                    :disabled="isLoading"
                                    @click="handleSort('occurred_at')"
                                >
                                    Data
                                    <component
                                        :is="sortIconFor('occurred_at')"
                                        class="h-3.5 w-3.5"
                                        :class="filterState.sort === 'occurred_at' ? 'text-foreground' : 'text-muted-foreground/70'"
                                    />
                                </button>
                            </TableHead>
                            <TableHead class="text-right">Ações</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody v-if="!tableIsEmpty">
                        <TableRow v-for="transaction in transactions.data" :key="transaction.id">
                            <TableCell>
                                <div class="flex flex-col">
                                    <span class="font-medium">{{ transaction.account.name }}</span>
                                    <span class="text-xs text-muted-foreground">{{
                                        transaction.account.institution || 'Instituição não informada'
                                    }}</span>
                                </div>
                            </TableCell>
                            <TableCell>
                                <p class="font-medium text-foreground">{{ transaction.description }}</p>
                                <div v-if="transaction.is_transfer" class="mt-1 flex items-center gap-2">
                                    <Badge variant="warning">Transferência entre contas</Badge>
                                </div>
                            </TableCell>
                            <TableCell>
                                <TransactionCategoryDropdown
                                    :selected="transaction.category"
                                    :options="props.categoryOptions"
                                    :disabled="isLoading"
                                    @select="(value) => updateTransactionCategory(transaction.id, value)"
                                />
                            </TableCell>
                            <TableCell>
                                <span
                                    class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-xs font-medium"
                                    :class="
                                        transaction.type === 'credit'
                                            ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-300'
                                            : 'bg-rose-50 text-rose-600 dark:bg-rose-500/15 dark:text-rose-300'
                                    "
                                >
                                    <ArrowUpRight v-if="transaction.type === 'credit'" class="h-3.5 w-3.5" />
                                    <ArrowDownRight v-else class="h-3.5 w-3.5" />
                                    {{ transaction.type === 'credit' ? 'Entrada' : 'Saída' }}
                                </span>
                            </TableCell>
                            <TableCell class="text-right">
                                <span
                                    :class="
                                        transaction.type === 'credit' ? 'text-emerald-600 dark:text-emerald-300' : 'text-rose-600 dark:text-rose-300'
                                    "
                                    class="font-semibold"
                                >
                                    {{ formatCurrency(transaction.amount) }}
                                </span>
                            </TableCell>
                            <TableCell>
                                <span class="text-sm text-muted-foreground">
                                    {{ transaction.occurred_at ? formatDateTime(transaction.occurred_at) : '-' }}
                                </span>
                            </TableCell>
                            <TableCell class="text-right">
                                <ModalLink
                                    :href="transactionDetailsModalUrl(transaction.id)"
                                    as="button"
                                    type="button"
                                    class="inline-flex items-center justify-center gap-2 rounded-md border border-border/70 bg-background px-3 py-2 text-xs font-medium text-foreground transition hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-70"
                                >
                                    <Pencil class="h-3.5 w-3.5" />
                                    <span>Editar</span>
                                </ModalLink>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                    <TableBody v-else>
                        <TableRow>
                            <TableCell class="text-center text-sm text-muted-foreground" colspan="7">
                                Nenhuma transação encontrada para os filtros selecionados.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <DataTablePagination
                v-if="transactions.last_page > 1"
                :data="transactions"
                :only="['transactions', 'transactionFilters']"
                preserve-state
                preserve-scroll
                replace
            />
        </CardContent>
    </Card>
</template>

<script setup lang="ts">
import DataTablePagination from '@/components/common/DataTablePagination.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatDateTime } from '@/lib/date-utils';
import { index as accountsIndex } from '@/routes/accounts';
import { edit as transactionTagsEdit } from '@/routes/transactions/tags';
import { update as updateTransactionCategoryRoute } from '@/routes/transactions/category';
import { formatCurrency } from '@/pages/accounts/utils';
import type { BankTransaction, PaginatedResource, TransactionCategoryOption, TransactionFilters } from '@/types/accounts';
import { router } from '@inertiajs/vue3';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import {
    ArrowDownRight,
    ArrowUpRight,
    Filter,
    Pencil,
    Car,
    Check,
    Coffee,
    CreditCard,
    Dumbbell,
    Gift,
    Home,
    PiggyBank,
    ShoppingBag,
    Store,
    Wallet,
} from 'lucide-vue-next';
import { computed, reactive, ref, watch } from 'vue';

type AccountOption = {
    id: number;
    label: string;
    value: string;
};

type FilterFormState = {
    search: string;
    type: string;
    account: string;
    start_date: string;
    end_date: string;
    category: string;
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
        }),
        accountOptions: () => [],
        categoryOptions: () => [],
    }
);

const filterState = reactive<FilterFormState>({
    search: props.filters?.search ?? '',
    type: props.filters?.type ?? '',
    account: props.filters?.account ? String(props.filters.account) : '',
    start_date: props.filters?.start_date ?? '',
    end_date: props.filters?.end_date ?? '',
    category: props.filters?.category ?? '',
});

const isLoading = ref(false);

const hasActiveFilters = computed(() => {
    return Boolean(
        filterState.search ||
            filterState.type ||
            filterState.account ||
            filterState.start_date ||
            filterState.end_date ||
            filterState.category
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

    return payload;
});

const submitFilters = () => {
    router.get(accountsIndex().url, filtersPayload.value, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['transactions', 'transactionFilters'],
        onStart: () => {
            isLoading.value = true;
        },
        onFinish: () => {
            isLoading.value = false;
        },
    });
};

const resetFilters = () => {
    filterState.search = '';
    filterState.type = '';
    filterState.account = '';
    filterState.start_date = '';
    filterState.end_date = '';
    filterState.category = '';
    submitFilters();
};

watch(
    () => props.filters,
    (currentFilters) => {
        filterState.search = currentFilters?.search ?? '';
        filterState.type = currentFilters?.type ?? '';
        filterState.account = currentFilters?.account ? String(currentFilters.account) : '';
        filterState.start_date = currentFilters?.start_date ?? '';
        filterState.end_date = currentFilters?.end_date ?? '';
        filterState.category = currentFilters?.category ?? '';
    }
);

const transactionCountLabel = computed(() => {
    const from = props.transactions.from ?? 0;
    const to = props.transactions.to ?? 0;
    const total = props.transactions.total ?? 0;

    return `Mostrando ${from} a ${to} de ${total} transações`;
});

const tableIsEmpty = computed(() => !props.transactions.data.length && !isLoading.value);

const transactionTagModalUrl = (transactionId: number) => transactionTagsEdit(transactionId).url;
const updateTransactionCategory = (transactionId: number, categoryValue: string) => {
    const payload = {
        category_id: categoryValue === 'none' ? null : Number(categoryValue),
    };

    if (Number.isNaN(payload.category_id as number)) {
        payload.category_id = null;
    }

    router.put(updateTransactionCategoryRoute(transactionId).url, payload, {
        preserveScroll: true,
        preserveState: true,
        only: ['transactions', 'transactionFilters'],
    });
};

const iconComponents: Record<string, any> = {
    wallet: Wallet,
    'credit-card': CreditCard,
    'shopping-bag': ShoppingBag,
    store: Store,
    'piggy-bank': PiggyBank,
    car: Car,
    home: Home,
    gift: Gift,
    coffee: Coffee,
    dumbbell: Dumbbell,
};

const categoryFilterOptions = computed(() => {
    const base = [{ value: '', label: 'Todas as categorias' }, { value: 'none', label: 'Sem categoria' }];

    const categories = props.categoryOptions?.map((category) => ({
        value: String(category.id),
        label: category.name,
    })) ?? [];

    return [...base, ...categories];
});
</script>

<template>
    <Card class="border border-border/70 bg-gradient-to-b from-background to-muted/30">
        <CardHeader class="gap-3">
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <CardTitle class="text-2xl">Transações recentes</CardTitle>
                    <CardDescription>{{ transactionCountLabel }}</CardDescription>
                </div>
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
            </div>
        </CardHeader>
        <CardContent class="space-y-4">
            <form class="space-y-3" @submit.prevent="submitFilters">
                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
                    <div class="space-y-1">
                        <label class="text-xs font-medium text-muted-foreground" for="transaction-search">Buscar</label>
                        <Input
                            id="transaction-search"
                            v-model="filterState.search"
                            type="text"
                            placeholder="Descrição ou categoria"
                        />
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-medium text-muted-foreground" for="transaction-category">Categoria</label>
                        <select
                            id="transaction-category"
                            v-model="filterState.category"
                            class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
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
                            class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
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
                            class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                        >
                            <option value="">Todas as contas</option>
                            <option v-for="option in accountOptions" :key="option.id" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-medium text-muted-foreground">Período</label>
                        <div class="flex gap-2">
                            <Input v-model="filterState.start_date" type="date" class="w-full" />
                            <Input v-model="filterState.end_date" type="date" class="w-full" />
                        </div>
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
                <div v-if="isLoading" class="absolute inset-0 z-10 flex items-center justify-center bg-background/60 text-sm font-medium text-muted-foreground">
                    Carregando transações...
                </div>
                <Table :class="isLoading ? 'opacity-50' : ''">
                    <TableHeader>
                        <TableRow>
                            <TableHead>Conta</TableHead>
                            <TableHead>Descrição</TableHead>
                            <TableHead>Categoria</TableHead>
                            <TableHead>Tags</TableHead>
                            <TableHead>Tipo</TableHead>
                            <TableHead class="text-right">Valor</TableHead>
                            <TableHead>Data</TableHead>
                            <TableHead class="text-right">Ações</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody v-if="!tableIsEmpty">
                        <TableRow v-for="transaction in transactions.data" :key="transaction.id">
                            <TableCell>
                                <div class="flex flex-col">
                                    <span class="font-medium">{{ transaction.account.name }}</span>
                                    <span class="text-xs text-muted-foreground">{{ transaction.account.institution || 'Instituição não informada' }}</span>
                                </div>
                            </TableCell>
                            <TableCell>
                                <p class="font-medium text-foreground">{{ transaction.description }}</p>
                            </TableCell>
                            <TableCell>
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <button
                                            type="button"
                                            class="flex w-full items-center justify-between rounded-md border border-border/60 px-3 py-2 text-left text-sm font-medium transition hover:border-primary"
                                        >
                                            <span class="flex items-center gap-2">
                                                <span
                                                    class="inline-flex h-6 w-6 items-center justify-center rounded-full border"
                                                    :style="{ backgroundColor: transaction.category?.color || 'transparent' }"
                                                >
                                                    <component
                                                        v-if="transaction.category?.icon && iconComponents[transaction.category.icon]"
                                                        :is="iconComponents[transaction.category.icon]"
                                                        class="h-3.5 w-3.5 text-background"
                                                    />
                                                </span>
                                                <span>
                                                    {{ transaction.category?.name ?? 'Sem categoria' }}
                                                </span>
                                            </span>
                                            <Pencil class="h-3.5 w-3.5 text-muted-foreground" />
                                        </button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent class="min-w-[260px]">
                                        <DropdownMenuItem @click="updateTransactionCategory(transaction.id, 'none')">
                                            <div class="flex items-center justify-between w-full">
                                                <span>Sem categoria</span>
                                                <Check v-if="!transaction.category" class="h-3.5 w-3.5 text-primary" />
                                            </div>
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            v-for="category in props.categoryOptions"
                                            :key="category.id"
                                            @click="updateTransactionCategory(transaction.id, String(category.id))"
                                        >
                                            <div class="flex items-center justify-between w-full">
                                                <span class="flex items-center gap-2">
                                                    <span
                                                        class="inline-flex h-6 w-6 items-center justify-center rounded-full border"
                                                        :style="{ backgroundColor: category.color || 'transparent' }"
                                                    >
                                                        <component
                                                            v-if="category.icon && iconComponents[category.icon]"
                                                            :is="iconComponents[category.icon]"
                                                            class="h-3.5 w-3.5 text-background"
                                                        />
                                                    </span>
                                                    {{ category.name }}
                                                </span>
                                                <Check
                                                    v-if="transaction.category?.id === category.id"
                                                    class="h-3.5 w-3.5 text-primary"
                                                />
                                            </div>
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </TableCell>
                            <TableCell>
                                <div class="flex flex-wrap gap-1">
                                    <span
                                        v-for="tag in transaction.tags"
                                        :key="tag.id"
                                        class="rounded-full bg-muted px-2 py-0.5 text-[11px] font-medium text-foreground"
                                    >
                                        {{ tag.name }}
                                    </span>
                                    <span v-if="!transaction.tags.length" class="text-xs text-muted-foreground">Sem tags</span>
                                </div>
                            </TableCell>
                            <TableCell>
                                <span
                                    class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-xs font-medium"
                                    :class="transaction.type === 'credit' ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-300' : 'bg-rose-50 text-rose-600 dark:bg-rose-500/15 dark:text-rose-300'"
                                >
                                    <ArrowUpRight v-if="transaction.type === 'credit'" class="h-3.5 w-3.5" />
                                    <ArrowDownRight v-else class="h-3.5 w-3.5" />
                                    {{ transaction.type === 'credit' ? 'Entrada' : 'Saída' }}
                                </span>
                            </TableCell>
                            <TableCell class="text-right">
                                <span
                                    :class="transaction.type === 'credit' ? 'text-emerald-600 dark:text-emerald-300' : 'text-rose-600 dark:text-rose-300'"
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
                                    :href="transactionTagModalUrl(transaction.id)"
                                    as="button"
                                    type="button"
                                    class="inline-flex items-center justify-center gap-2 rounded-md border border-border/70 bg-background px-3 py-2 text-xs font-medium text-foreground transition hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-70"
                                >
                                    <Pencil class="h-3.5 w-3.5" />
                                    <span>Editar</span>
                                </ModalLink>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                    <TableBody v-else>
                        <TableRow>
                            <TableCell class="text-center text-sm text-muted-foreground" colspan="8">
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

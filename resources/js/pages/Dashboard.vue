<script lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

export default {
    layout: (h, page) => h(AppLayout, { breadcrumbs }, () => page),
};
</script>

<script setup lang="ts">
import DatePicker from '@/components/common/DatePicker.vue';
import ContainerDefault from '@/components/layouts/ContainerDefault.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useSharedDateFilters } from '@/composables/useSharedDateFilters';
import { formatCurrency } from '@/pages/accounts/utils';
import { Head, router } from '@inertiajs/vue3';
import {
    Car,
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
import { computed, onMounted, reactive, ref, type Component, watch } from 'vue';

type CategorySpendingItem = {
    id: number | null;
    name: string;
    icon: string | null;
    color: string | null;
    total: number;
    percentage: number;
};

type CategorySpendingReport = {
    total: number;
    categories: CategorySpendingItem[];
};

const props = withDefaults(
    defineProps<{
        categorySpending: CategorySpendingReport;
        filters: {
            start_date: string;
            end_date: string;
        };
    }>(),
    {
        categorySpending: () => ({
            total: 0,
            categories: [],
        }),
        filters: () => ({
            start_date: '',
            end_date: '',
        }),
    },
);

const iconComponents: Record<string, Component> = {
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

const totalSpent = computed(() => props.categorySpending.total ?? 0);
const formattedTotalSpent = computed(() => formatCurrency(totalSpent.value));
const categoriesWithShare = computed<CategorySpendingItem[]>(() => {
    const total = Math.abs(totalSpent.value);

    if (!total) {
        return props.categorySpending.categories.map((category) => ({
            ...category,
            percentage: 0,
        }));
    }

    return props.categorySpending.categories.map((category) => {
        const share = (Math.abs(category.total) / total) * 100;

        return {
            ...category,
            percentage: Number(share.toFixed(2)),
        };
    });
});
const topCategory = computed<CategorySpendingItem | null>(() => categoriesWithShare.value[0] ?? null);
const hasReportData = computed(() => categoriesWithShare.value.length > 0);

const resolveIconComponent = (icon?: string | null): Component => {
    if (!icon) {
        return Wallet;
    }

    return iconComponents[icon] ?? Wallet;
};

const shareWidth = (percentage: number) => {
    return Math.min(100, Math.max(0, percentage));
};

const filterState = reactive({
    start_date: props.filters?.start_date ?? '',
    end_date: props.filters?.end_date ?? '',
});
const isFiltering = ref(false);
const filtersApplied = computed(() => Boolean(filterState.start_date || filterState.end_date));
const { getSharedDateFilters, setSharedDateFilters } = useSharedDateFilters();
let allowSharedSyncFromProps = false;

const normalizeDateValue = (value?: string | null) => value ?? '';

const submitFilters = () => {
    setSharedDateFilters(normalizeDateValue(filterState.start_date), normalizeDateValue(filterState.end_date));

    router.get(dashboard().url, filterState, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
        onStart: () => {
            isFiltering.value = true;
        },
        onFinish: () => {
            isFiltering.value = false;
        },
    });
};

const resetFilters = () => {
    filterState.start_date = '';
    filterState.end_date = '';
    submitFilters();
};

watch(
    () => props.filters,
    (current) => {
        filterState.start_date = current?.start_date ?? '';
        filterState.end_date = current?.end_date ?? '';

        if (allowSharedSyncFromProps) {
            setSharedDateFilters(normalizeDateValue(current?.start_date), normalizeDateValue(current?.end_date));
        }
    }
);

onMounted(() => {
    const sharedFilters = getSharedDateFilters();
    const sharedStart = normalizeDateValue(sharedFilters.start);
    const sharedEnd = normalizeDateValue(sharedFilters.end);
    const hasSharedFilters = Boolean(sharedStart || sharedEnd);
    const startDiffers = sharedStart !== normalizeDateValue(filterState.start_date);
    const endDiffers = sharedEnd !== normalizeDateValue(filterState.end_date);

    if (hasSharedFilters && (startDiffers || endDiffers)) {
        filterState.start_date = sharedStart;
        filterState.end_date = sharedEnd;
        submitFilters();
    } else if (!hasSharedFilters) {
        setSharedDateFilters(normalizeDateValue(filterState.start_date), normalizeDateValue(filterState.end_date));
    }

    allowSharedSyncFromProps = true;
});
</script>

<template>
    <Head title="Dashboard" />

    <ContainerDefault>
        <div class="space-y-6">
            <Card class="border border-border/70">
                <CardHeader class="space-y-2">
                    <CardTitle>Período analisado</CardTitle>
                    <CardDescription>Filtre o relatório por intervalo de datas para entender tendências recentes.</CardDescription>
                </CardHeader>
                <CardContent>
                    <form class="grid gap-4 md:grid-cols-[repeat(3,minmax(0,1fr))]" @submit.prevent="submitFilters">
                        <div class="space-y-2">
                            <label class="text-xs font-semibold uppercase tracking-wide text-muted-foreground" for="dashboard-start-date">
                                Data inicial
                            </label>
                            <DatePicker id="dashboard-start-date" v-model="filterState.start_date" placeholder="Selecione a data inicial" />
                        </div>
                        <div class="space-y-2">
                            <label class="text-xs font-semibold uppercase tracking-wide text-muted-foreground" for="dashboard-end-date">
                                Data final
                            </label>
                            <DatePicker id="dashboard-end-date" v-model="filterState.end_date" placeholder="Selecione a data final" />
                        </div>
                        <div class="flex items-end gap-2">
                            <Button type="submit" class="flex-1" :disabled="isFiltering">
                                {{ isFiltering ? 'Atualizando...' : 'Aplicar filtros' }}
                            </Button>
                            <Button type="button" variant="outline" :disabled="!filtersApplied || isFiltering" @click="resetFilters">
                                Limpar
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <Card class="border border-border/70 bg-gradient-to-br from-background to-muted/60">
                <CardHeader class="space-y-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-muted-foreground">Resumo</p>
                    <CardTitle class="text-3xl">Relatório de gastos por categoria</CardTitle>
                    <CardDescription>
                        Analise quais categorias concentram as maiores saídas para tomar decisões mais rápidas.
                    </CardDescription>
                </CardHeader>
                <CardContent class="grid gap-4 md:grid-cols-2">
                    <div class="rounded-xl border border-border/70 p-4">
                        <p class="text-sm text-muted-foreground">Total analisado</p>
                        <p class="mt-1 text-3xl font-semibold text-foreground">{{ formattedTotalSpent }}</p>
                        <p class="mt-2 text-xs text-muted-foreground">Somatório das transações de débito registradas.</p>
                    </div>
                    <div v-if="topCategory" class="rounded-xl border border-border/70 p-4">
                        <p class="text-sm text-muted-foreground">Categoria em destaque</p>
                        <div class="mt-3 flex items-center gap-3">
                            <span
                                class="inline-flex h-12 w-12 items-center justify-center rounded-full border border-border/70 text-foreground"
                                :style="{ backgroundColor: topCategory.color || 'transparent' }"
                            >
                                <component :is="resolveIconComponent(topCategory.icon)" class="h-5 w-5" />
                            </span>
                            <div>
                                <p class="text-lg font-semibold text-foreground">{{ topCategory.name }}</p>
                                <p class="text-xs text-muted-foreground">{{ topCategory.percentage }}% dos gastos</p>
                            </div>
                        </div>
                        <p class="mt-4 text-sm font-medium text-foreground">{{ formatCurrency(topCategory.total) }}</p>
                    </div>
                    <div v-else class="rounded-xl border border-dashed border-border/70 p-4 text-sm text-muted-foreground">
                        Ainda não existem transações categorizadas para gerar o resumo.
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Gastos por categoria</CardTitle>
                    <CardDescription>Distribuição das saídas ordenada da maior para a menor participação.</CardDescription>
                </CardHeader>
                <CardContent>
                    <Table v-if="hasReportData">
                        <TableHeader>
                            <TableRow>
                                <TableHead>Categoria</TableHead>
                                <TableHead class="text-right">Total gasto</TableHead>
                                <TableHead class="text-right">Participação</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="category in categoriesWithShare"
                                :key="category.id ?? `uncategorized-${category.name}`"
                            >
                                <TableCell>
                                    <div class="flex items-center gap-3">
                                        <span
                                            class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-border/70 text-foreground"
                                            :style="{ backgroundColor: category.color || 'transparent' }"
                                        >
                                            <component :is="resolveIconComponent(category.icon)" class="h-4 w-4" />
                                        </span>
                                        <div>
                                            <p class="font-semibold text-foreground">{{ category.name }}</p>
                                            <p class="text-xs text-muted-foreground">{{ category.percentage }}% do total</p>
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell class="text-right text-sm font-semibold text-foreground">
                                    {{ formatCurrency(category.total) }}
                                </TableCell>
                                <TableCell class="text-right">
                                    <div class="flex flex-col items-end gap-2">
                                        <span class="text-sm font-medium text-muted-foreground">{{ category.percentage }}%</span>
                                        <div class="flex w-32 items-center gap-2">
                                            <div class="h-2 flex-1 rounded-full bg-muted">
                                                <span
                                                    class="block h-full rounded-full"
                                                    :style="{
                                                        width: `${shareWidth(category.percentage)}%`,
                                                        backgroundColor: category.color || 'hsl(var(--primary))',
                                                    }"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                    <div v-else class="flex flex-col items-center justify-center gap-2 py-10 text-center text-sm text-muted-foreground">
                        <p>Nenhum gasto foi registrado ainda.</p>
                        <p>Importe um extrato ou cadastre transações para gerar o relatório automaticamente.</p>
                    </div>
                </CardContent>
            </Card>
        </div>
    </ContainerDefault>
</template>

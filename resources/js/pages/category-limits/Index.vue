<script lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { index as categoryLimitsIndex } from '@/routes/category-limits';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Limites por categoria',
        href: categoryLimitsIndex().url,
    },
];

export default {
    layout: (h, page) => h(AppLayout, { breadcrumbs }, () => page),
};
</script>

<script setup lang="ts">
import CategoryLimitCard from '@/components/categories/CategoryLimitCard.vue';
import ContainerDefault from '@/components/layouts/ContainerDefault.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { formatCurrency } from '@/pages/accounts/utils';
import { index as categoriesIndex } from '@/routes/categories';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

type CategoryLimitPayload = {
    id: number;
    name: string;
    icon: string | null;
    color: string | null;
    limit: {
        id: number;
        monthly_limit: number;
    } | null;
    spent: number;
    remaining: number | null;
    progress: number | null;
};

const props = defineProps<{
    categories: CategoryLimitPayload[];
    period: {
        label: string;
        start: string;
        end: string;
    };
}>();

const totalCategories = computed(() => props.categories.length);
const categoriesWithLimit = computed(() => props.categories.filter((category) => Boolean(category.limit)));
const activeLimitCount = computed(() => categoriesWithLimit.value.length);
const limitedPercentage = computed(() => {
    if (!totalCategories.value) {
        return 0;
    }

    return Math.round((activeLimitCount.value / totalCategories.value) * 100);
});
const totalBudgeted = computed(() =>
    categoriesWithLimit.value.reduce((total, category) => total + (category.limit?.monthly_limit ?? 0), 0),
);
const totalSpent = computed(() => props.categories.reduce((total, category) => total + category.spent, 0));
const formattedBudget = computed(() => formatCurrency(totalBudgeted.value));
const formattedSpent = computed(() => formatCurrency(totalSpent.value));
const categoriesUrl = categoriesIndex().url;
</script>

<template>
    <Head title="Limites por categoria" />

    <ContainerDefault>
        <div class="space-y-6">
            <Card class="border border-border/70 bg-card">
                <CardHeader class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <CardTitle>Limites por categoria</CardTitle>
                        <CardDescription>
                            Estabeleça limites mensais para cada categoria e compare com os gastos reais do período {{ period.label }}.
                        </CardDescription>
                    </div>
                    <Button as-child variant="outline">
                        <Link :href="categoriesUrl">Gerenciar categorias</Link>
                    </Button>
                </CardHeader>
                <CardContent class="grid gap-4 md:grid-cols-3">
                    <div class="rounded-lg border border-border/60 p-4">
                        <p class="text-sm text-muted-foreground">Limites ativos</p>
                        <p class="mt-1 text-2xl font-semibold text-foreground">
                            {{ activeLimitCount }} / {{ totalCategories }}
                        </p>
                        <p class="text-xs text-muted-foreground">{{ limitedPercentage }}% das categorias</p>
                    </div>
                    <div class="rounded-lg border border-border/60 p-4">
                        <p class="text-sm text-muted-foreground">Gasto no período</p>
                        <p class="mt-1 text-2xl font-semibold text-foreground">{{ formattedSpent }}</p>
                        <p class="text-xs text-muted-foreground">Todas as categorias de débito</p>
                    </div>
                    <div class="rounded-lg border border-border/60 p-4">
                        <p class="text-sm text-muted-foreground">Total reservado</p>
                        <p class="mt-1 text-2xl font-semibold text-foreground">{{ formattedBudget }}</p>
                        <p class="text-xs text-muted-foreground">Somatório dos limites definidos</p>
                    </div>
                </CardContent>
            </Card>

            <div v-if="categories.length" class="grid gap-5 lg:grid-cols-2 xl:grid-cols-3">
                <CategoryLimitCard
                    v-for="category in categories"
                    :key="category.id"
                    :category="category"
                    :period-label="period.label"
                />
            </div>
            <div
                v-else
                class="flex flex-col items-center justify-center gap-3 rounded-2xl border border-dashed border-border/60 bg-card py-16 text-center"
            >
                <p class="text-lg font-semibold text-foreground">Nenhuma categoria cadastrada ainda</p>
                <p class="max-w-md text-sm text-muted-foreground">
                    Crie categorias primeiro para configurar limites individuais e acompanhar se os gastos estão dentro do
                    planejado.
                </p>
                <Button as-child>
                    <Link :href="categoriesUrl">Cadastrar categorias</Link>
                </Button>
            </div>
        </div>
    </ContainerDefault>
</template>

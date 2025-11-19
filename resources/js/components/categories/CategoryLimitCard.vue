<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { formatCurrency } from '@/pages/accounts/utils';
import { destroy as deleteLimitRoute, store as storeCategoryLimitRoute, update as updateCategoryLimitRoute } from '@/routes/category-limits';
import { useForm } from '@inertiajs/vue3';
import { computed, onMounted, watch } from 'vue';
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

const iconComponents: Record<string, unknown> = {
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

const props = defineProps<{
    category: CategoryLimitPayload;
    periodLabel: string;
}>();

const form = useForm({
    transaction_category_id: props.category.id,
    monthly_limit: props.category.limit?.monthly_limit ?? '',
});

const syncForm = () => {
    form.defaults({
        transaction_category_id: props.category.id,
        monthly_limit: props.category.limit?.monthly_limit ?? '',
    });
    form.transaction_category_id = props.category.id;
    form.monthly_limit = props.category.limit?.monthly_limit ?? '';
    form.clearErrors();
};

onMounted(syncForm);

watch(
    () => [props.category.id, props.category.limit?.monthly_limit],
    () => {
        syncForm();
    }
);

const hasLimit = computed(() => Boolean(props.category.limit));
const progressValue = computed(() => Math.min(100, props.category.progress ?? 0));
const spentLabel = computed(() => formatCurrency(props.category.spent));
const limitLabel = computed(() => (props.category.limit ? formatCurrency(props.category.limit.monthly_limit) : null));
const remainingLabel = computed(() => {
    if (props.category.remaining === null || props.category.remaining === undefined) {
        return null;
    }

    return formatCurrency(props.category.remaining);
});
const remainingClass = computed(() => {
    if (props.category.remaining === null || props.category.remaining === undefined) {
        return 'text-muted-foreground';
    }

    return props.category.remaining > 0 ? 'text-emerald-500' : 'text-rose-500';
});

const badgeVariant = computed(() => {
    if (!hasLimit.value) {
        return 'outline';
    }

    if (props.category.remaining !== null && props.category.remaining <= 0) {
        return 'destructive';
    }

    if (progressValue.value >= 80) {
        return 'secondary';
    }

    return 'default';
});

const badgeLabel = computed(() => {
    if (!hasLimit.value) {
        return 'Sem limite';
    }

    if (props.category.remaining !== null && props.category.remaining <= 0) {
        return 'Limite excedido';
    }

    if (progressValue.value >= 80) {
        return 'Atenção';
    }

    return 'Dentro do limite';
});

const resolvedIcon = computed(() => {
    if (props.category.icon && iconComponents[props.category.icon]) {
        return iconComponents[props.category.icon];
    }

    return Wallet;
});

const submit = () => {
    if (hasLimit.value && props.category.limit) {
        form.put(updateCategoryLimitRoute(props.category.limit.id).url, {
            preserveScroll: true,
        });

        return;
    }

    form.post(storeCategoryLimitRoute().url, {
        preserveScroll: true,
    });
};

const removeLimit = () => {
    if (!props.category.limit) {
        return;
    }

    form.delete(deleteLimitRoute(props.category.limit.id).url, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Card class="h-full border border-border/70 bg-gradient-to-b from-background to-muted/20 shadow-sm">
        <CardContent class="flex h-full flex-col gap-4 p-5">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span
                        class="inline-flex h-12 w-12 items-center justify-center rounded-2xl border border-border/60 text-foreground"
                        :style="{ backgroundColor: category.color || 'transparent' }"
                    >
                        <component :is="resolvedIcon" class="h-5 w-5" />
                    </span>
                    <div>
                        <h3 class="text-base font-semibold text-foreground">{{ category.name }}</h3>
                        <p class="text-xs text-muted-foreground">Período: {{ periodLabel }}</p>
                    </div>
                </div>
                <Badge :variant="badgeVariant as never">{{ badgeLabel }}</Badge>
            </div>

            <div class="space-y-1">
                <div class="flex items-baseline gap-2">
                    <p class="text-3xl font-semibold text-foreground">{{ spentLabel }}</p>
                    <span class="text-sm text-muted-foreground">gasto no mês</span>
                </div>
                <p v-if="hasLimit && limitLabel" class="text-sm text-muted-foreground">
                    Limite: <span class="font-medium text-foreground">{{ limitLabel }}</span>
                </p>
                <p v-if="remainingLabel" class="text-xs font-medium" :class="remainingClass">
                    Restante: {{ remainingLabel }}
                </p>
                <p v-else-if="!hasLimit" class="text-xs text-muted-foreground">Defina um limite mensal para acompanhar essa categoria.</p>
            </div>

            <div class="space-y-2">
                <div class="h-2 w-full rounded-full bg-muted">
                    <span
                        class="block h-full rounded-full bg-primary transition-all"
                        :class="{
                            'bg-rose-500': hasLimit && category.remaining !== null && category.remaining <= 0,
                            'bg-amber-500': hasLimit && category.remaining !== null && category.remaining > 0 && progressValue >= 80,
                            'bg-muted-foreground/30': !hasLimit,
                        }"
                        :style="{ width: hasLimit ? `${progressValue}%` : '30%' }"
                    />
                </div>
                <div class="flex items-center justify-between text-xs text-muted-foreground">
                    <span>0%</span>
                    <span v-if="hasLimit">{{ progressValue }}%</span>
                    <span v-else>Sem limite</span>
                </div>
            </div>

            <form class="space-y-3" @submit.prevent="submit">
                <div class="space-y-1">
                    <label class="text-xs font-semibold uppercase tracking-wide text-muted-foreground" :for="`limit-input-${category.id}`">
                        Limite mensal
                    </label>
                    <Input
                        :id="`limit-input-${category.id}`"
                        v-model="form.monthly_limit"
                        type="number"
                        min="0"
                        step="0.01"
                        placeholder="0,00"
                    />
                    <p v-if="form.errors.monthly_limit" class="text-xs text-rose-500">{{ form.errors.monthly_limit }}</p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <Button type="submit" class="flex-1" :disabled="form.processing">
                        <span v-if="form.processing">
                            {{ hasLimit ? 'Atualizando...' : 'Definindo...' }}
                        </span>
                        <span v-else>
                            {{ hasLimit ? 'Atualizar limite' : 'Definir limite' }}
                        </span>
                    </Button>

                    <Button
                        v-if="hasLimit"
                        type="button"
                        variant="ghost"
                        :disabled="form.processing"
                        @click="removeLimit"
                    >
                        Remover
                    </Button>
                </div>
            </form>
        </CardContent>
    </Card>
</template>

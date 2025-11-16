<script setup lang="ts">
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import type { BankAccount } from '@/types/accounts';
import { ArrowDownRight, ArrowUpRight } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    account: BankAccount;
}>();

const currencyFormatter = new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
});

const formatCurrency = (value: number) => currencyFormatter.format(value);

const incomePercent = computed(() => {
    const { income, expense } = props.account.monthlyMovements;
    const total = income + expense;

    if (!total) {
        return 0;
    }

    return Math.round((income / total) * 100);
});
</script>

<template>
    <Card class="border border-border/60 shadow-sm transition hover:border-primary/40" :gradientBorder="false">
        <CardHeader class="space-y-0 pb-0">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-sm leading-tight font-semibold">{{ account.name }}</p>
                    <p class="text-xs text-muted-foreground">{{ account.institution }}</p>
                </div>
                <span class="text-sm font-semibold text-primary">{{ formatCurrency(account.balance) }}</span>
            </div>
        </CardHeader>
        <CardContent class="space-y-2 pt-4">
            <div class="flex items-center justify-between text-xs font-medium">
                <span class="flex items-center gap-1 text-emerald-500">
                    <ArrowUpRight class="h-3.5 w-3.5" />
                    {{ formatCurrency(account.monthlyMovements.income) }}
                </span>
                <span class="flex items-center gap-1 text-rose-500">
                    <ArrowDownRight class="h-3.5 w-3.5" />
                    {{ formatCurrency(account.monthlyMovements.expense) }}
                </span>
            </div>
            <div class="h-2 w-full rounded-full bg-muted">
                <div class="h-full rounded-full bg-emerald-500 transition-[width]" :style="{ width: `${incomePercent}%` }" />
            </div>
            <p class="text-[11px] text-muted-foreground">Movimentação mensal</p>
        </CardContent>
    </Card>
</template>

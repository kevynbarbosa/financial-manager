<script setup lang="ts">
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import type { BankAccount } from '@/types/accounts';
import { formatCurrency } from '@/pages/accounts/utils';
import { edit as editAccountRoute } from '@/routes/accounts';
import { ArrowDownRight, ArrowUpRight, Pencil } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    account: BankAccount;
}>();

const incomePercent = computed(() => {
    const income = Math.max(0, props.account.monthlyMovements.income);
    const expense = Math.abs(props.account.monthlyMovements.expense);
    const total = income + expense;

    if (!total) {
        return 0;
    }

    return Math.round((income / total) * 100);
});

const editAccountUrl = (accountId: number) => editAccountRoute(accountId).url;
</script>

<template>
    <Card class="border border-border/60 shadow-sm transition hover:border-primary/40" :gradientBorder="false">
        <CardHeader class="space-y-0 pb-0">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-sm leading-tight font-semibold">{{ account.name }}</p>
                    <p class="text-xs text-muted-foreground">{{ account.institution }}</p>
                </div>
                <div class="flex flex-col items-end gap-1">
                    <span class="text-sm font-semibold text-primary">{{ formatCurrency(account.balance) }}</span>
                    <ModalLink
                        :href="editAccountUrl(account.id)"
                        as="button"
                        type="button"
                        class="inline-flex items-center gap-1 rounded-md border border-border/60 px-2 py-1 text-[11px] font-semibold text-muted-foreground transition hover:border-primary hover:text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    >
                        <Pencil class="h-3 w-3" />
                        Editar
                    </ModalLink>
                </div>
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
            <div class="h-2 w-full overflow-hidden rounded-full bg-muted">
                <div class="h-full rounded-full bg-emerald-500 transition-[width]" :style="{ width: `${incomePercent}%` }" />
            </div>
            <p class="text-[11px] text-muted-foreground">Movimentação mensal</p>
        </CardContent>
    </Card>
</template>

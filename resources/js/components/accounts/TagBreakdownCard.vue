<script setup lang="ts">
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import type { TagReports } from '@/types/accounts';
import { formatCurrency } from '@/pages/accounts/utils';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        reports?: TagReports;
    }>(),
    {
        reports: () => ({
            totals: {
                credit: 0,
                debit: 0,
            },
            breakdown: [],
        }),
    }
);

const reports = computed(() => props.reports);

const hasData = computed(() => reports.value.breakdown.length > 0);
const topTags = computed(() => reports.value.breakdown.slice(0, 5));
const totalTaggedExpenses = computed(() => reports.value.totals.debit);

const expenseShare = (value: number) => {
    if (!totalTaggedExpenses.value) {
        return 0;
    }

    return Math.round((value / totalTaggedExpenses.value) * 100);
};
</script>

<template>
    <Card class="border border-border/70">
        <CardHeader>
            <CardTitle>Relatório por Tags</CardTitle>
            <CardDescription>
                Descubra quais tags concentram as entradas e saídas marcadas, facilitando seus relatórios personalizados.
            </CardDescription>
        </CardHeader>
        <CardContent>
            <div v-if="!hasData" class="flex flex-col items-center justify-center gap-2 rounded-lg border border-dashed border-border/80 px-6 py-10 text-center text-sm text-muted-foreground">
                <p class="font-medium text-foreground">Nenhuma tag aplicada às transações.</p>
                <p class="max-w-md text-xs text-muted-foreground">
                    Utilize tags nas transações para gerar relatórios rápidos sobre os grupos mais relevantes do seu financeiro.
                </p>
            </div>
            <div v-else class="space-y-6">
                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="rounded-lg border border-border/60 bg-muted/40 p-4">
                        <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Entradas com tags</p>
                        <p class="text-2xl font-semibold text-emerald-500">{{ formatCurrency(reports.totals.credit) }}</p>
                    </div>
                    <div class="rounded-lg border border-border/60 bg-muted/40 p-4">
                        <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Saídas com tags</p>
                        <p class="text-2xl font-semibold text-rose-500">{{ formatCurrency(reports.totals.debit) }}</p>
                    </div>
                </div>
                <div class="space-y-3">
                    <div v-for="tag in topTags" :key="tag.id" class="rounded-lg border border-border/60 bg-card/60 p-4 shadow-sm">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="font-semibold text-foreground">{{ tag.name }}</p>
                            <span :class="tag.net >= 0 ? 'text-emerald-500' : 'text-rose-500'" class="text-xs font-semibold">
                                {{ tag.net >= 0 ? '+' : '-' }}{{ formatCurrency(Math.abs(tag.net)) }}
                            </span>
                        </div>
                        <div class="mt-1 flex flex-wrap items-center justify-between text-xs font-medium">
                            <span class="text-emerald-500">Entradas: {{ formatCurrency(tag.credit) }}</span>
                            <span class="text-rose-500">Saídas: {{ formatCurrency(tag.debit) }}</span>
                        </div>
                        <div class="mt-3 h-1.5 w-full rounded-full bg-muted">
                            <div
                                class="h-full rounded-full bg-rose-500 transition-[width]"
                                :style="{ width: `${expenseShare(tag.debit)}%` }"
                            />
                        </div>
                        <p class="mt-1 text-xs text-muted-foreground">
                            Participação nas saídas taggeadas: {{ expenseShare(tag.debit) }}%
                        </p>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>

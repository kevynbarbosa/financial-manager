<script lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { index as accountsIndex } from '@/routes/accounts';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Controle de Contas',
        href: accountsIndex().url,
    },
];

export default {
    layout: (h, page) => h(AppLayout, { breadcrumbs }, () => page),
};
</script>

<script setup lang="ts">
import BankAccountCard from '@/components/accounts/BankAccountCard.vue';
import BankTransactionsTable from '@/components/accounts/BankTransactionsTable.vue';
import ContainerDefault from '@/components/layouts/ContainerDefault.vue';
import Button from '@/components/ui/button/Button.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { formatCurrency } from '@/pages/accounts/utils';
import { Head } from '@inertiajs/vue3';
import { ArrowDownRight, ArrowUpRight, FileUp } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import type { BankAccount, BankTransaction, PaginatedResource, TransactionFilters } from '@/types/accounts';

const props = withDefaults(
    defineProps<{
        accounts: BankAccount[];
        summary: {
            totalBalance: number;
            totalIncome: number;
            totalExpense: number;
            period: {
                start: string;
                end: string;
            };
        };
        transactions: PaginatedResource<BankTransaction>;
        transactionFilters: TransactionFilters;
    }>(),
    {
        accounts: () => [],
        summary: () => ({
            totalBalance: 0,
            totalIncome: 0,
            totalExpense: 0,
            period: {
                start: '',
                end: '',
            },
        }),
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
        transactionFilters: () => ({
            search: '',
            type: '',
            account: null,
            start_date: '',
            end_date: '',
        }),
    }
);

const ofxInputRef = ref<HTMLInputElement | null>(null);
const importFeedback = ref('');
const accountFilterOptions = computed(() =>
    props.accounts.map((account) => ({
        id: account.id,
        value: String(account.id),
        label: `${account.name} • ${account.institution}`,
    }))
);

const triggerOfxImport = () => {
    ofxInputRef.value?.click();
};

const handleOfxSelected = (event: Event) => {
    const target = event.target as HTMLInputElement;

    if (!target?.files?.length) {
        return;
    }

    const [file] = target.files;
    importFeedback.value = `Arquivo pronto para importação: ${file.name}`;
    target.value = '';
};
</script>

<template>
    <Head title="Controle de Contas" />

    <ContainerDefault>
        <Card class="border border-border/70 bg-gradient-to-br from-background to-muted/60">
            <CardHeader class="space-y-2">
                <p class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">Painel financeiro</p>
                <CardTitle class="text-3xl">Controle de Contas Bancárias</CardTitle>
                <CardDescription>Gerencie as contas, visualize saldos e acompanhe a movimentação mensal em um só lugar.</CardDescription>
            </CardHeader>
            <CardContent class="flex flex-col gap-3 text-sm text-muted-foreground sm:flex-row sm:items-center sm:justify-between">
                <p>Importe extratos OFX e mantenha o fluxo das contas atualizado em tempo real.</p>
                <div class="flex flex-col items-start gap-2 sm:flex-row sm:items-center">
                    <Button size="sm" class="gap-1" @click="triggerOfxImport">
                        <FileUp class="h-4 w-4" />
                        Importar arquivo OFX
                    </Button>
                    <input
                        ref="ofxInputRef"
                        class="sr-only"
                        type="file"
                        accept=".ofx"
                        @change="handleOfxSelected"
                    />
                    <p v-if="importFeedback" class="text-xs text-muted-foreground">{{ importFeedback }}</p>
                </div>
            </CardContent>
        </Card>

        <div class="grid gap-4 md:grid-cols-3">
            <Card class="border border-border/60">
                <CardHeader class="space-y-1 pb-2">
                    <CardDescription class="text-xs uppercase tracking-wide text-muted-foreground">Saldo consolidado</CardDescription>
                    <CardTitle class="text-3xl">{{ formatCurrency(summary.totalBalance) }}</CardTitle>
                </CardHeader>
                <CardContent class="pt-0">
                    <p class="text-xs text-muted-foreground">Atualizado em tempo real</p>
                </CardContent>
            </Card>
            <Card class="border border-border/60">
                <CardHeader class="space-y-1 pb-2">
                    <CardDescription class="text-xs uppercase tracking-wide text-muted-foreground">Entradas do mês</CardDescription>
                    <p class="flex items-center gap-2 text-emerald-500">
                        <ArrowUpRight class="h-4 w-4" />
                        <span class="text-2xl font-semibold">{{ formatCurrency(summary.totalIncome) }}</span>
                    </p>
                </CardHeader>
                <CardContent class="pt-0">
                    <p class="text-xs text-muted-foreground">Inclui salários, vendas e aportes</p>
                </CardContent>
            </Card>
            <Card class="border border-border/60">
                <CardHeader class="space-y-1 pb-2">
                    <CardDescription class="text-xs uppercase tracking-wide text-muted-foreground">Saídas do mês</CardDescription>
                    <p class="flex items-center gap-2 text-rose-500">
                        <ArrowDownRight class="h-4 w-4" />
                        <span class="text-2xl font-semibold">{{ formatCurrency(summary.totalExpense) }}</span>
                    </p>
                </CardHeader>
                <CardContent class="pt-0">
                    <p class="text-xs text-muted-foreground">Pagamentos, investimentos e despesas</p>
                </CardContent>
            </Card>
        </div>

        <Card class="border border-border/70">
            <CardHeader>
                <div>
                    <CardTitle>Contas bancárias</CardTitle>
                    <CardDescription>Visualize rapidamente saldos e movimentações recentes.</CardDescription>
                </div>
            </CardHeader>
            <CardContent>
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <BankAccountCard v-for="account in accounts" :key="account.id" :account="account" />
                </div>
            </CardContent>
        </Card>

        <BankTransactionsTable
            :transactions="transactions"
            :filters="transactionFilters"
            :account-options="accountFilterOptions"
        />
    </ContainerDefault>
</template>

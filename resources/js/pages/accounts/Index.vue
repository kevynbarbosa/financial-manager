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
import ContainerDefault from '@/components/layouts/ContainerDefault.vue';
import Button from '@/components/ui/button/Button.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Head } from '@inertiajs/vue3';
import { ArrowDownRight, ArrowUpRight, FileUp } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import type { BankAccount } from '@/types/accounts';

const bankAccounts = ref<BankAccount[]>([
    {
        id: 1,
        name: 'Conta Corrente - Nubank',
        institution: 'Nubank',
        balance: 12540.74,
        monthlyMovements: {
            income: 18452.2,
            expense: 14210.9,
        },
    },
    {
        id: 2,
        name: 'Conta PJ - Itaú',
        institution: 'Itaú Empresas',
        balance: 30210.9,
        monthlyMovements: {
            income: 28110.5,
            expense: 19540.25,
        },
    },
    {
        id: 3,
        name: 'Poupança - Caixa',
        institution: 'Caixa Econômica',
        balance: 18200,
        monthlyMovements: {
            income: 1200,
            expense: 350,
        },
    },
]);

const currencyFormatter = new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
});

const ofxInputRef = ref<HTMLInputElement | null>(null);
const importFeedback = ref('');

const totalBalance = computed(() => bankAccounts.value.reduce((sum, account) => sum + account.balance, 0));
const totalIncome = computed(() => bankAccounts.value.reduce((sum, account) => sum + account.monthlyMovements.income, 0));
const totalExpense = computed(() => bankAccounts.value.reduce((sum, account) => sum + account.monthlyMovements.expense, 0));

const formatCurrency = (value: number) => currencyFormatter.format(value);

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
                    <CardTitle class="text-3xl">{{ formatCurrency(totalBalance) }}</CardTitle>
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
                        <span class="text-2xl font-semibold">{{ formatCurrency(totalIncome) }}</span>
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
                        <span class="text-2xl font-semibold">{{ formatCurrency(totalExpense) }}</span>
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
                    <BankAccountCard v-for="account in bankAccounts" :key="account.id" :account="account" />
                </div>
            </CardContent>
        </Card>
    </ContainerDefault>
</template>

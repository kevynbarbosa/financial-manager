<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { formatCurrency } from '@/pages/accounts/utils';
import { formatDateTime } from '@/lib/date-utils';
import type { BankTransaction, TransactionCategorySummary } from '@/types/accounts';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { update as updateTransaction } from '@/routes/transactions';
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

type TransactionForm = {
    description: string;
    category_id: number | null;
    is_transfer: boolean;
};

const props = withDefaults(
    defineProps<{
        transaction: BankTransaction;
        categoryOptions?: TransactionCategorySummary[];
    }>(),
    {
        categoryOptions: () => [],
    }
);

const form = useForm<TransactionForm>({
    description: props.transaction.description ?? '',
    category_id: props.transaction.category?.id ?? null,
    is_transfer: props.transaction.is_transfer ?? false,
});

const categoryOptions = computed(() => props.categoryOptions);

const selectCategory = (category: TransactionCategorySummary | null) => {
    form.category_id = category?.id ?? null;
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

const closeModal = () => {
    if (typeof window !== 'undefined') {
        window.history.back();
    }
};

const submit = () => {
    form.put(updateTransaction(props.transaction.id).url, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Editar transação" />

    <Modal max-width="xl" close-button>
        <div class="space-y-6 p-6">
            <div class="space-y-1">
                <p class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">Transação</p>
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-border/70 bg-muted/30 p-4">
                    <div>
                        <p class="text-sm font-semibold text-foreground">{{ transaction.account.name }}</p>
                        <p class="text-xs text-muted-foreground">
                            {{ transaction.occurred_at ? formatDateTime(transaction.occurred_at) : 'Data não informada' }}
                        </p>
                    </div>
                    <div class="text-right">
                        <p
                            class="text-xl font-semibold"
                            :class="transaction.type === 'credit' ? 'text-emerald-500' : 'text-rose-500'"
                        >
                            {{ formatCurrency(transaction.amount) }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{ transaction.type === 'credit' ? 'Entrada' : 'Saída' }}
                        </p>
                    </div>
                </div>
                <p class="text-sm text-muted-foreground">
                    Ajuste a descrição, categoria e marque transferências entre contas.
                </p>
            </div>

            <form class="space-y-6" @submit.prevent="submit">
                <div class="space-y-2">
                    <label class="text-sm font-medium text-foreground" for="transaction-description">Descrição</label>
                    <Textarea
                        id="transaction-description"
                        v-model="form.description"
                        rows="3"
                        placeholder="Ex: Pagamento do fornecedor XPTO"
                    />
                    <p v-if="form.errors.description" class="text-xs text-rose-500">
                        {{ form.errors.description }}
                    </p>
                </div>

                <div
                    class="flex flex-col gap-3 rounded-lg border border-border/60 bg-muted/20 p-4 md:flex-row md:items-center md:justify-between"
                >
                    <div>
                        <p class="text-sm font-semibold text-foreground">Transferência entre contas</p>
                        <p class="text-xs text-muted-foreground">
                            Quando ativado, a transação será ignorada em relatórios e somatórios.
                        </p>
                    </div>
                    <Switch
                        :model-value="form.is_transfer"
                        @update:model-value="(checked: boolean) => (form.is_transfer = checked)"
                        aria-label="Marcar como transferência entre contas"
                    />
                </div>

                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-foreground">Categoria</p>
                        <span class="text-xs text-muted-foreground">
                            {{ form.category_id ? 'Categoria aplicada' : 'Sem categoria' }}
                        </span>
                    </div>
                    <div class="grid gap-2 sm:grid-cols-2">
                        <button
                            type="button"
                            class="flex items-center justify-center gap-2 rounded-lg border px-3 py-2 text-sm font-medium transition"
                            :class="!form.category_id ? 'border-primary bg-primary/10 text-primary' : 'border-border/70 text-muted-foreground hover:border-primary/60'"
                            @click="selectCategory(null)"
                        >
                            Sem categoria
                        </button>
                        <button
                            v-for="category in categoryOptions"
                            :key="category.id"
                            type="button"
                            class="flex items-center justify-start gap-3 rounded-lg border px-3 py-2 text-left text-sm transition"
                            :class="form.category_id === category.id ? 'border-primary bg-primary/10 text-primary' : 'border-border/70 text-muted-foreground hover:border-primary/60'"
                            @click="selectCategory(category)"
                        >
                            <span
                                class="inline-flex h-8 w-8 items-center justify-center rounded-full border"
                                :style="{ backgroundColor: category.color || 'transparent' }"
                            >
                                <component
                                    v-if="category.icon && iconComponents[category.icon]"
                                    :is="iconComponents[category.icon]"
                                    class="h-4 w-4 text-background"
                                />
                            </span>
                            <span class="font-semibold">{{ category.name }}</span>
                        </button>
                    </div>
                    <p v-if="form.errors.category_id" class="text-xs text-rose-500">{{ form.errors.category_id }}</p>
                </div>

                <div class="flex items-center justify-end gap-2">
                    <Button type="button" variant="ghost" @click="closeModal">
                        Cancelar
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        <span v-if="form.processing">Salvando...</span>
                        <span v-else>Salvar alterações</span>
                    </Button>
                </div>
            </form>
        </div>
    </Modal>
</template>

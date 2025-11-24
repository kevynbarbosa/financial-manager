<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Switch } from '@/components/ui/switch';
import TransactionCategoryDropdown from '@/components/accounts/TransactionCategoryDropdown.vue';
import type { TransactionCategoryOption, TransactionCategorySummary } from '@/types/accounts';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { bulk as bulkAssignCategory } from '@/routes/transactions/category';

type BulkAssignForm = {
    category_id: number | null;
    match_type: 'exact' | 'contains';
    term: string;
    overwrite_existing: boolean;
};

const props = withDefaults(
    defineProps<{
        categoryOptions?: TransactionCategorySummary[];
    }>(),
    {
        categoryOptions: () => [],
    }
);

const availableCategories = computed(() => props.categoryOptions);
const dropdownOptions = computed<TransactionCategoryOption[]>(() =>
    availableCategories.value.map((category) => ({
        ...category,
        value: String(category.id),
        label: category.name,
    })),
);

const form = useForm<BulkAssignForm>({
    category_id: availableCategories.value[0]?.id ?? null,
    match_type: 'contains',
    term: '',
    overwrite_existing: false,
});

const selectedCategory = computed<TransactionCategorySummary | null>(() => {
    if (!form.category_id) {
        return null;
    }

    return availableCategories.value.find((category) => category.id === form.category_id) ?? null;
});

const matchTypeOptions = [
    {
        value: 'contains',
        title: 'Contém na descrição',
        description: 'Encontra qualquer transação que inclua o termo digitado.',
    },
    {
        value: 'exact',
        title: 'Descrição exata',
        description: 'Somente transações cuja descrição corresponda exatamente ao termo.',
    },
] as const;

const closeModal = () => {
    if (typeof window !== 'undefined') {
        window.history.back();
    }
};

const submit = () => {
    form.post(bulkAssignCategory().url, {
        preserveScroll: true,
        onSuccess: closeModal,
    });
};
</script>

<template>
    <Head title="Atribuir categoria em massa" />

    <Modal max-width="lg" close-button>
        <div class="space-y-6 p-6">
            <div class="space-y-1">
                <h2 class="text-xl font-semibold text-foreground">Atribuir categoria em massa</h2>
                <p class="text-sm text-muted-foreground">
                    Defina uma regra simples para aplicar rapidamente uma categoria às transações que coincidirem com o termo informado.
                </p>
            </div>

            <form class="space-y-5" @submit.prevent="submit">
                <div class="space-y-2">
                    <p class="text-sm font-medium text-foreground">Categoria desejada</p>
                    <TransactionCategoryDropdown
                        :selected="selectedCategory"
                        :options="dropdownOptions"
                        :disabled="form.processing || !dropdownOptions.length"
                        @select="(value) => (form.category_id = value ? Number(value) : null)"
                    />
                    <p v-if="!dropdownOptions.length" class="text-xs text-muted-foreground">Nenhuma categoria cadastrada ainda.</p>
                    <p v-if="form.errors.category_id" class="text-xs text-rose-500">{{ form.errors.category_id }}</p>
                </div>

                <div class="space-y-3">
                    <p class="text-sm font-medium text-foreground">Como aplicar a regra</p>
                    <div class="grid gap-2 sm:grid-cols-2">
                        <button
                            v-for="option in matchTypeOptions"
                            :key="option.value"
                            type="button"
                            class="flex flex-col items-start gap-1 rounded-lg border px-3 py-2 text-left text-sm transition"
                            :class="
                                form.match_type === option.value
                                    ? 'border-primary bg-primary/10 text-primary'
                                    : 'border-border/70 text-muted-foreground hover:border-primary/60'
                            "
                            @click="form.match_type = option.value"
                        >
                            <span class="font-semibold">{{ option.title }}</span>
                            <span class="text-xs text-muted-foreground">{{ option.description }}</span>
                        </button>
                    </div>
                    <p v-if="form.errors.match_type" class="text-xs text-rose-500">{{ form.errors.match_type }}</p>
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium text-foreground" for="bulk-term">
                        Termo da busca
                    </label>
                    <Input
                        id="bulk-term"
                        v-model="form.term"
                        placeholder="Ex: netflix, mercado bom"
                        autocomplete="off"
                    />
                    <p class="text-xs text-muted-foreground">
                        Para <strong>descrição exata</strong>, digite o texto completo. Para <strong>contém</strong>, basta parte do nome.
                    </p>
                    <p v-if="form.errors.term" class="text-xs text-rose-500">{{ form.errors.term }}</p>
                </div>

                <div class="flex flex-col gap-3 rounded-lg border border-border/60 bg-muted/20 p-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="space-y-1">
                        <p class="text-sm font-semibold text-foreground">Sobrescrever categorias existentes</p>
                        <p class="text-xs text-muted-foreground">
                            Quando ativado, transações que já possuem categoria serão atualizadas também.
                        </p>
                    </div>
                    <Switch
                        :model-value="form.overwrite_existing"
                        @update:model-value="(checked: boolean) => (form.overwrite_existing = checked)"
                        aria-label="Sobrescrever categorias existentes"
                    />
                </div>

                <div class="flex items-center justify-end gap-2">
                    <Button type="button" variant="ghost" @click="closeModal">
                        Cancelar
                    </Button>
                    <Button type="submit" :disabled="form.processing || !form.category_id || !form.term">
                        <span v-if="form.processing">Aplicando...</span>
                        <span v-else>Aplicar regra</span>
                    </Button>
                </div>
            </form>
        </div>
    </Modal>
</template>

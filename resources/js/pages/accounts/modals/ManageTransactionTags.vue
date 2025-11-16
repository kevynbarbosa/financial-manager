<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { formatCurrency } from '@/pages/accounts/utils';
import { formatDateTime } from '@/lib/date-utils';
import type { BankTransaction, TransactionTag } from '@/types/accounts';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { update as updateTransactionTags } from '@/routes/transactions/tags';

type TagForm = {
    description: string;
    tags: string[];
};

const props = withDefaults(
    defineProps<{
        transaction: BankTransaction;
        availableTags?: TransactionTag[];
    }>(),
    {
        availableTags: () => [],
    }
);

const form = useForm<TagForm>({
    description: props.transaction.description ?? '',
    tags: props.transaction.tags.map((tag) => tag.name),
});

const newTag = ref('');

const sortedTagOptions = computed(() => {
    return [...props.availableTags].sort((a, b) => a.name.localeCompare(b.name, 'pt-BR'));
});

const selectedTags = computed(() => form.tags);

const toggleTag = (tagName: string) => {
    const normalized = tagName.trim();

    if (!normalized.length) {
        return;
    }

    if (form.tags.includes(normalized)) {
        form.tags = form.tags.filter((tag) => tag !== normalized);
        return;
    }

    form.tags = [...form.tags, normalized];
};

const removeTag = (tagName: string) => {
    form.tags = form.tags.filter((tag) => tag !== tagName);
};

const addNewTag = () => {
    const normalized = newTag.value.trim();

    if (!normalized.length) {
        return;
    }

    if (!form.tags.includes(normalized)) {
        form.tags = [...form.tags, normalized];
    }

    newTag.value = '';
};

const closeModal = () => {
    if (typeof window !== 'undefined') {
        window.history.back();
    }
};

const submit = () => {
    form.put(updateTransactionTags(props.transaction.id).url, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Editar tags da transação" />

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
                    Ajuste a descrição e adicione tags para organizar relatórios e filtros.
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

                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-foreground">Tags selecionadas</p>
                            <p class="text-xs text-muted-foreground">Clique sobre uma tag para removê-la.</p>
                        </div>
                        <span class="text-xs text-muted-foreground">{{ selectedTags.length }} selecionadas</span>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="tag in selectedTags"
                            :key="tag"
                            type="button"
                            class="group inline-flex items-center gap-1 rounded-full bg-emerald-500/10 px-3 py-1 text-xs font-medium text-emerald-600 transition hover:bg-emerald-500/20 dark:bg-emerald-500/20 dark:text-emerald-200"
                            @click="removeTag(tag)"
                        >
                            {{ tag }}
                            <span class="text-[10px] uppercase tracking-wide text-emerald-500 group-hover:text-emerald-600 dark:text-emerald-200">
                                Remover
                            </span>
                        </button>
                        <p v-if="!selectedTags.length" class="text-xs text-muted-foreground">Nenhuma tag aplicada.</p>
                    </div>
                    <p v-if="form.errors.tags" class="text-xs text-rose-500">{{ form.errors.tags }}</p>
                </div>

                <div class="space-y-3">
                    <p class="text-sm font-medium text-foreground">Tags disponíveis</p>
                    <div v-if="sortedTagOptions.length" class="flex flex-wrap gap-2">
                        <button
                            v-for="tagOption in sortedTagOptions"
                            :key="tagOption.id"
                            type="button"
                            class="rounded-full border px-3 py-1 text-xs font-medium transition"
                            :class="
                                selectedTags.includes(tagOption.name)
                                    ? 'border-emerald-500 bg-emerald-500/10 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-200'
                                    : 'border-border/70 text-muted-foreground hover:border-foreground/60'
                            "
                            @click="toggleTag(tagOption.name)"
                        >
                            {{ tagOption.name }}
                        </button>
                    </div>
                    <p v-else class="text-xs text-muted-foreground">Você ainda não possui tags cadastradas.</p>
                </div>

                <div class="space-y-3 rounded-lg border border-dashed border-border/70 p-4">
                    <div class="flex flex-col gap-1">
                        <p class="text-sm font-medium text-foreground">Adicionar uma nova tag</p>
                        <p class="text-xs text-muted-foreground">Tags criadas aqui ficam disponíveis para futuras transações.</p>
                    </div>
                    <div class="flex flex-col gap-2 sm:flex-row">
                        <Input v-model="newTag" type="text" placeholder="Ex: Educação" class="flex-1" />
                        <Button
                            type="button"
                            class="sm:w-auto"
                            :disabled="!newTag.trim().length"
                            @click="addNewTag"
                        >
                            Adicionar
                        </Button>
                    </div>
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

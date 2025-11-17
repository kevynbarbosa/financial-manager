<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { update as updateAccountRoute } from '@/routes/accounts';

type AccountPayload = {
    id: number;
    name: string;
    institution?: string | null;
    account_type: string;
    account_number?: string | null;
    currency: string;
};

type AccountTypeOption = {
    value: string;
    label: string;
};

const props = defineProps<{
    account: AccountPayload;
    accountTypes: AccountTypeOption[];
}>();

const form = useForm({
    name: props.account.name,
    institution: props.account.institution ?? '',
    account_type: props.account.account_type,
    account_number: props.account.account_number ?? '',
    currency: props.account.currency ?? 'BRL',
});

const accountTypeOptions = computed(() => props.accountTypes);

const submit = () => {
    form.put(updateAccountRoute(props.account.id).url, {
        preserveScroll: true,
    });
};

const closeModal = () => {
    if (typeof window !== 'undefined') {
        window.history.back();
    }
};
</script>

<template>
    <Head title="Editar conta" />

    <Modal close-button max-width="lg">
        <div class="space-y-6 p-6">
            <div>
                <h2 class="text-xl font-semibold text-foreground">Editar conta bancária</h2>
                <p class="text-sm text-muted-foreground">Atualize nome, instituição e configurações desta conta.</p>
            </div>

            <form class="space-y-4" @submit.prevent="submit">
                <div class="space-y-2">
                    <label class="text-sm font-medium text-foreground" for="account-name">Nome</label>
                    <Input id="account-name" v-model="form.name" placeholder="Conta principal" />
                    <p v-if="form.errors.name" class="text-xs text-rose-500">{{ form.errors.name }}</p>
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium text-foreground" for="account-institution">Instituição</label>
                    <Input id="account-institution" v-model="form.institution" placeholder="Banco" />
                    <p v-if="form.errors.institution" class="text-xs text-rose-500">{{ form.errors.institution }}</p>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-foreground" for="account-type">Tipo da conta</label>
                        <select
                            id="account-type"
                            v-model="form.account_type"
                            class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                        >
                            <option v-for="option in accountTypeOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                        <p v-if="form.errors.account_type" class="text-xs text-rose-500">
                            {{ form.errors.account_type }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-foreground" for="account-number">Número / identificador</label>
                        <Input id="account-number" v-model="form.account_number" placeholder="0000-0" />
                        <p v-if="form.errors.account_number" class="text-xs text-rose-500">
                            {{ form.errors.account_number }}
                        </p>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium text-foreground" for="account-currency">Moeda</label>
                    <Input id="account-currency" v-model="form.currency" maxlength="3" placeholder="BRL" />
                    <p v-if="form.errors.currency" class="text-xs text-rose-500">{{ form.errors.currency }}</p>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
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

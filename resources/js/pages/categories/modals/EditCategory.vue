<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Head, useForm } from '@inertiajs/vue3';
import { update as categoriesUpdate } from '@/routes/categories';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { computed } from 'vue';
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

interface CategoryPayload {
    id: number;
    name: string;
    icon: string | null;
    color: string | null;
}

const props = defineProps<{
    category: CategoryPayload;
    iconOptions: string[];
    colorOptions: string[];
}>();

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

const form = useForm({
    name: props.category.name,
    icon: props.category.icon ?? '',
    color: props.category.color ?? props.colorOptions[0] ?? '#22c55e',
});

const iconOptions = computed(() =>
    props.iconOptions.map((icon) => ({
        value: icon,
        component: iconComponents[icon] ?? Wallet,
    }))
);

const submit = () => {
    form.put(categoriesUpdate(props.category.id).url, {
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
    <Head title="Editar categoria" />

    <Modal close-button max-width="lg">
        <div class="space-y-6 p-6">
            <div>
                <h2 class="text-xl font-semibold text-foreground">Editar categoria</h2>
                <p class="text-sm text-muted-foreground">Atualize visual e identificação da categoria.</p>
            </div>

            <form class="space-y-4" @submit.prevent="submit">
                <div class="space-y-2">
                    <label class="text-sm font-medium text-foreground" for="category-name">Nome</label>
                    <input
                        id="category-name"
                        v-model="form.name"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm shadow-sm focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none"
                        placeholder="Descreva a categoria"
                    />
                    <p v-if="form.errors.name" class="text-xs text-rose-500">{{ form.errors.name }}</p>
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium text-foreground">Ícone</label>
                    <div class="grid grid-cols-5 gap-2">
                        <TooltipProvider>
                            <Tooltip v-for="icon in iconOptions" :key="icon.value">
                                <TooltipTrigger as-child>
                                    <button
                                        type="button"
                                        class="flex h-10 items-center justify-center rounded-md border transition"
                                        :class="
                                            form.icon === icon.value
                                                ? 'border-primary bg-primary/10 text-primary'
                                                : 'border-border/70 text-muted-foreground hover:border-primary/60'
                                        "
                                        @click="form.icon = icon.value"
                                    >
                                        <component :is="icon.component" class="h-4 w-4" />
                                    </button>
                                </TooltipTrigger>
                                <TooltipContent>{{ icon.value }}</TooltipContent>
                            </Tooltip>
                        </TooltipProvider>
                    </div>
                    <p v-if="form.errors.icon" class="text-xs text-rose-500">{{ form.errors.icon }}</p>
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium text-foreground">Cor</label>
                    <div class="grid grid-cols-6 gap-2">
                        <button
                            v-for="color in colorOptions"
                            :key="color"
                            type="button"
                            class="h-8 rounded-full border transition"
                            :style="{ backgroundColor: color }"
                            :class="form.color === color ? 'border-primary scale-[1.05]' : 'border-transparent'"
                            @click="form.color = color"
                        />
                    </div>
                    <p v-if="form.errors.color" class="text-xs text-rose-500">{{ form.errors.color }}</p>
                </div>

                <div class="flex items-center justify-end gap-2">
                    <Button type="button" variant="ghost" @click="closeModal">Cancelar</Button>
                    <Button type="submit" :disabled="form.processing">
                        <span v-if="form.processing">Salvando...</span>
                        <span v-else>Salvar alterações</span>
                    </Button>
                </div>
            </form>
        </div>
    </Modal>
</template>

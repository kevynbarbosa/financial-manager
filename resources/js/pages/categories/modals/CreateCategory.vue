<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { store as categoriesStore } from '@/routes/categories';
import { Head, useForm } from '@inertiajs/vue3';
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

const props = defineProps<{
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

const iconOptions = computed(() =>
    props.iconOptions.map((icon) => ({
        value: icon,
        component: iconComponents[icon] ?? Wallet,
    }))
);

const palette = computed(() => (props.colorOptions.length ? props.colorOptions : ['#22c55e']));

const form = useForm({
    name: '',
    icon: '',
    color: palette.value[0],
});

const submit = () => {
    form.post(categoriesStore().url, {
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
    <Head title="Nova categoria" />

    <Modal close-button max-width="lg">
        <div class="space-y-6 p-6">
            <div>
                <h2 class="text-xl font-semibold text-foreground">Criar categoria</h2>
                <p class="text-sm text-muted-foreground">Organize suas transações com ícones e cores personalizados.</p>
            </div>

            <form class="space-y-4" @submit.prevent="submit">
                <div class="space-y-2">
                    <label class="text-sm font-medium text-foreground" for="category-name">Nome</label>
                    <Input id="category-name" v-model="form.name" placeholder="Ex: Alimentação" />
                    <p v-if="form.errors.name" class="text-xs text-rose-500">{{ form.errors.name }}</p>
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium text-foreground">Ícone</label>
                    <div class="grid grid-cols-5 gap-2">
                        <button
                            v-for="icon in iconOptions"
                            :key="icon.value"
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
                    </div>
                    <p v-if="form.errors.icon" class="text-xs text-rose-500">{{ form.errors.icon }}</p>
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium text-foreground">Cor</label>
                    <div class="grid grid-cols-6 gap-2">
                        <button
                            v-for="color in palette"
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
                    <Button type="button" variant="ghost" @click="closeModal">
                        Cancelar
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        <span v-if="form.processing">Salvando...</span>
                        <span v-else>Criar</span>
                    </Button>
                </div>
            </form>
        </div>
    </Modal>
</template>

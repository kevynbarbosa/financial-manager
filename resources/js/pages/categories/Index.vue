<script setup lang="ts">
import ContainerDefault from '@/components/layouts/ContainerDefault.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { destroy as categoriesDestroy, create as categoriesCreate, edit as categoriesEdit } from '@/routes/categories';
import { Head, useForm } from '@inertiajs/vue3';
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

interface Category {
    id: number;
    name: string;
    icon: string | null;
    color: string | null;
}

defineProps<{
    categories: Category[];
    iconOptions: string[];
    colorOptions: string[];
}>();

const iconComponents: Record<string, unknown> = {
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

const deleteForm = useForm({});

const deleteCategory = (category: Category) => {
    if (!confirm('Deseja remover esta categoria?')) {
        return;
    }

    deleteForm.delete(categoriesDestroy(category.id).url, {
        preserveScroll: true,
    });
};

const createCategoryUrl = categoriesCreate().url;
const editCategoryUrl = (categoryId: number) => categoriesEdit(categoryId).url;
</script>

<template>
    <Head title="Categorias" />

    <ContainerDefault>
        <div class="space-y-6">
            <Card>
                <CardHeader class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <CardTitle>Categorias</CardTitle>
                        <p class="text-sm text-muted-foreground">Gerencie os ícones e cores usados nas transações.</p>
                    </div>
                    <ModalLink
                        :href="createCategoryUrl"
                        as="button"
                        type="button"
                        class="inline-flex items-center gap-2 rounded-md border border-border/70 px-4 py-2 text-sm font-semibold text-muted-foreground transition hover:border-primary hover:text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    >
                        Nova categoria
                    </ModalLink>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Nome</TableHead>
                                <TableHead>Ícone</TableHead>
                                <TableHead>Cor</TableHead>
                                <TableHead class="text-right">Ações</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="category in categories" :key="category.id">
                                <TableCell>
                                    <div class="flex flex-col">
                                        <span class="font-medium">{{ category.name }}</span>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <component
                                        v-if="category.icon"
                                        :is="iconComponents[category.icon]"
                                        class="h-4 w-4 text-muted-foreground"
                                    />
                                    <span v-else>-</span>
                                </TableCell>
                                <TableCell>
                                    <span class="inline-flex h-5 w-5 items-center justify-center rounded-full border" :style="{ backgroundColor: category.color || '#e5e7eb' }" />
                                </TableCell>
                                <TableCell class="text-right">
                                    <div class="flex justify-end gap-2">
                                        <ModalLink
                                            :href="editCategoryUrl(category.id)"
                                            as="button"
                                            type="button"
                                            class="inline-flex items-center rounded-md border border-border/70 px-3 py-1 text-xs font-semibold text-muted-foreground transition hover:border-primary hover:text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                        >
                                            Editar
                                        </ModalLink>
                                        <Button type="button" variant="destructive" size="sm" @click="deleteCategory(category)">
                                            Remover
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="!categories.length">
                                <TableCell colspan="4" class="text-center text-sm text-muted-foreground">
                                    Nenhuma categoria cadastrada.
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>
    </ContainerDefault>
</template>

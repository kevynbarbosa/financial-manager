<script setup lang="ts">
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import type { TransactionCategoryOption, TransactionCategorySummary } from '@/types/accounts';
import { resolveCategoryIcon } from '@/lib/category-icons';
import { Check, Pencil } from 'lucide-vue-next';

const props = withDefaults(
    defineProps<{
        selected?: TransactionCategorySummary | null;
        options?: TransactionCategoryOption[];
        disabled?: boolean;
    }>(),
    {
        selected: null,
        options: () => [],
        disabled: false,
    },
);

const emit = defineEmits<{
    (e: 'select', value: string | null): void;
}>();

const handleSelect = (value: string | null) => {
    emit('select', value);
};
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <button
                type="button"
                class="flex w-full items-center justify-between rounded-md border border-border/60 px-3 py-2 text-left text-sm font-medium transition hover:border-primary disabled:cursor-not-allowed disabled:opacity-60"
                :disabled="disabled"
            >
                <span class="flex items-center gap-2">
                    <span
                        class="inline-flex h-6 w-6 items-center justify-center rounded-full border"
                        :style="{ backgroundColor: selected?.color || 'transparent' }"
                    >
                        <component
                            v-if="resolveCategoryIcon(selected?.icon)"
                            :is="resolveCategoryIcon(selected?.icon)"
                            class="h-3.5 w-3.5 text-background"
                        />
                    </span>
                    <span>{{ selected?.name ?? 'Sem categoria' }}</span>
                </span>
                <Pencil class="h-3.5 w-3.5 text-muted-foreground" />
            </button>
        </DropdownMenuTrigger>
        <DropdownMenuContent class="min-w-[260px]">
            <DropdownMenuItem @click="handleSelect(null)">
                <div class="flex w-full items-center justify-between">
                    <span>Sem categoria</span>
                    <Check v-if="!selected" class="h-3.5 w-3.5 text-primary" />
                </div>
            </DropdownMenuItem>
            <DropdownMenuItem
                v-for="category in options"
                :key="category.id"
                @click="handleSelect(String(category.id))"
            >
                <div class="flex w-full items-center justify-between">
                    <span class="flex items-center gap-2">
                        <span
                            class="inline-flex h-6 w-6 items-center justify-center rounded-full border"
                            :style="{ backgroundColor: category.color || 'transparent' }"
                        >
                            <component
                                v-if="resolveCategoryIcon(category.icon)"
                                :is="resolveCategoryIcon(category.icon)"
                                class="h-3.5 w-3.5 text-background"
                            />
                        </span>
                        {{ category.name }}
                    </span>
                    <Check v-if="selected?.id === category.id" class="h-3.5 w-3.5 text-primary" />
                </div>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>

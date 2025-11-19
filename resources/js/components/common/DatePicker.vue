<script setup lang="ts">
import type { DateValue } from '@internationalized/date';
import { DateFormatter, getLocalTimeZone, parseDate, today } from '@internationalized/date';
import { computed, ref, watch } from 'vue';

import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { cn } from '@/lib/utils';
import { CalendarIcon } from 'lucide-vue-next';

const props = withDefaults(
    defineProps<{
        id?: string;
        modelValue?: string | null;
        placeholder?: string;
        disabled?: boolean;
        locale?: string;
    }>(),
    {
        modelValue: null,
        placeholder: 'Selecionar data',
        disabled: false,
        locale: 'pt-BR',
    },
);

const emit = defineEmits<{
    (e: 'update:modelValue', value: string | null): void;
}>();

const timeZone = getLocalTimeZone();
const fallbackPlaceholder = today(timeZone);
const selectedDate = ref<DateValue | null>(null);

const dateFormatter = computed(
    () =>
        new DateFormatter(props.locale, {
            dateStyle: 'medium',
        }),
);

watch(
    () => props.modelValue,
    (value) => {
        selectedDate.value = value ? parseDate(value) : null;
    },
    { immediate: true },
);

const buttonLabel = computed(() => {
    if (!selectedDate.value) {
        return props.placeholder;
    }

    return dateFormatter.value.format(selectedDate.value.toDate(timeZone));
});

const handleSelect = (value?: DateValue) => {
    selectedDate.value = value ?? null;
    emit('update:modelValue', value ? value.toString() : null);
};
</script>

<template>
    <Popover v-slot="{ close }">
        <PopoverTrigger as-child>
            <Button
                :id="id"
                variant="outline"
                :disabled="disabled"
                :class="cn('w-full justify-start text-left font-normal', !selectedDate && 'text-muted-foreground')"
            >
                <CalendarIcon class="mr-2 h-4 w-4" />
                {{ buttonLabel }}
            </Button>
        </PopoverTrigger>
        <PopoverContent class="w-auto p-0" align="start">
            <Calendar
                :model-value="selectedDate ?? undefined"
                :default-placeholder="selectedDate ?? fallbackPlaceholder"
                layout="month-and-year"
                initial-focus
                @update:model-value="(value) => {
                    handleSelect(value);
                    close();
                }"
            />
        </PopoverContent>
    </Popover>
</template>

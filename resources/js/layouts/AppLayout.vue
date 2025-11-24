<script setup lang="ts">
import { Toaster } from '@/components/ui/sonner';
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItemType } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { watch } from 'vue';
import 'vue-sonner/style.css';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage<{
    flash?: {
        success?: string;
        error?: string;
    };
}>();

watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) {
            toast.success(flash.success);
        }

        if (flash?.error) {
            toast.error(flash.error);
        }
    },
    { deep: true, immediate: true },
);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <slot />
        <Toaster />
    </AppLayout>
</template>

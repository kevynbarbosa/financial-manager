<template>
    <div v-if="data.last_page > 1" class="px-6 py-4">
        <Pagination>
            <PaginationList>
                <PaginationPrev
                    :href="data.prev_page_url"
                    :disabled="!data.prev_page_url"
                    v-bind="linkBindings"
                />

                <template v-for="page in paginationPages" :key="page">
                    <PaginationListItem
                        v-if="typeof page === 'number'"
                        :href="buildPageUrl(page)"
                        :active="page === data.current_page"
                        v-bind="linkBindings"
                    >
                        {{ page }}
                    </PaginationListItem>
                    <PaginationEllipsis v-else />
                </template>

                <PaginationNext
                    :href="data.next_page_url"
                    :disabled="!data.next_page_url"
                    v-bind="linkBindings"
                />
            </PaginationList>
        </Pagination>
    </div>
</template>

<script setup lang="ts">
import { Pagination, PaginationEllipsis, PaginationList, PaginationListItem, PaginationNext, PaginationPrev } from '@/components/ui/pagination';
import { computed } from 'vue';

interface PaginatedData {
    current_page: number;
    last_page: number;
    next_page_url: string | null;
    prev_page_url: string | null;
}

interface Props {
    data: PaginatedData;
    only?: string[];
    preserveState?: boolean;
    preserveScroll?: boolean;
    replace?: boolean;
}

const props = defineProps<Props>();

const paginationPages = computed(() => {
    const pages = [];
    const current = props.data.current_page;
    const last = props.data.last_page;

    if (current > 3) {
        pages.push(1);
        if (current > 4) {
            pages.push('...');
        }
    }

    for (let i = Math.max(1, current - 2); i <= Math.min(last, current + 2); i++) {
        pages.push(i);
    }

    if (current < last - 2) {
        if (current < last - 3) {
            pages.push('...');
        }
        pages.push(last);
    }

    return pages;
});

const buildPageUrl = (page: number) => {
    const url = new URL(window.location.href);
    url.searchParams.set('page', page.toString());
    return url.pathname + url.search;
};

const linkBindings = computed(() => ({
    only: props.only,
    preserveState: props.preserveState ?? false,
    preserveScroll: props.preserveScroll ?? false,
    replace: props.replace ?? false,
}));
</script>

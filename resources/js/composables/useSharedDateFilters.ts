import { ref } from 'vue';

type SharedDateFilters = {
    start: string;
    end: string;
};

const STORAGE_KEY = 'financial-manager:shared-date-filters';

const sharedStartDate = ref<string>('');
const sharedEndDate = ref<string>('');
let isHydrated = false;

const isBrowser = (): boolean => typeof window !== 'undefined';

const hydrateFromStorage = (): void => {
    if (isHydrated || !isBrowser()) {
        return;
    }

    try {
        const raw = window.localStorage.getItem(STORAGE_KEY);

        if (raw) {
            const parsed = JSON.parse(raw) as Partial<SharedDateFilters>;
            sharedStartDate.value = parsed.start ?? '';
            sharedEndDate.value = parsed.end ?? '';
        }
    } catch {
        sharedStartDate.value = '';
        sharedEndDate.value = '';
    } finally {
        isHydrated = true;
    }
};

const persistToStorage = (): void => {
    if (!isBrowser()) {
        return;
    }

    try {
        window.localStorage.setItem(
            STORAGE_KEY,
            JSON.stringify({
                start: sharedStartDate.value,
                end: sharedEndDate.value,
            }),
        );
    } catch {
        // Swallow storage errors silently to avoid breaking UX.
    }
};

const normalizeDateValue = (value?: string | null): string => {
    return value ?? '';
};

export const useSharedDateFilters = () => {
    hydrateFromStorage();

    const setSharedDateFilters = (start?: string | null, end?: string | null): void => {
        const normalizedStart = normalizeDateValue(start);
        const normalizedEnd = normalizeDateValue(end);

        if (sharedStartDate.value === normalizedStart && sharedEndDate.value === normalizedEnd) {
            return;
        }

        sharedStartDate.value = normalizedStart;
        sharedEndDate.value = normalizedEnd;
        persistToStorage();
    };

    const clearSharedDateFilters = (): void => {
        setSharedDateFilters('', '');
    };

    const getSharedDateFilters = (): SharedDateFilters => {
        return {
            start: sharedStartDate.value,
            end: sharedEndDate.value,
        };
    };

    return {
        sharedStartDate,
        sharedEndDate,
        getSharedDateFilters,
        setSharedDateFilters,
        clearSharedDateFilters,
    };
};

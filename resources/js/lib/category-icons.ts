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

const categoryIconComponents: Record<string, any> = {
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

export const resolveCategoryIcon = (icon?: string | null) => {
    if (!icon) {
        return null;
    }

    return categoryIconComponents[icon] ?? null;
};

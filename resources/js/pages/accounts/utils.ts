const formatterCache: Record<string, Intl.NumberFormat> = {};

const getFormatter = (currency: string, locale: string) => {
    const cacheKey = `${locale}-${currency}`;

    if (!formatterCache[cacheKey]) {
        formatterCache[cacheKey] = new Intl.NumberFormat(locale, {
            style: 'currency',
            currency,
        });
    }

    return formatterCache[cacheKey];
};

export const formatCurrency = (value: number, currency = 'BRL', locale = 'pt-BR') => {
    return getFormatter(currency, locale).format(value);
};

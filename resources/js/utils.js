// Always format a record in its own currency. Preference changes never convert amounts.
export const formatCurrency = (value, currency = "DOP", locale = "es-DO") => {
    const amount = Number(value);
    return new Intl.NumberFormat(locale, {
        style: "currency",
        currency,
        currencyDisplay: "code",
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number.isFinite(amount) ? amount : 0);
};

// Mobile keyboards may use a comma or a point. Group separators are not accepted.
export const parseAmount = (value, { allowZero = false } = {}) => {
    const text = String(value ?? "").trim();
    if (!/^\d+(?:[.,]\d{1,2})?$/.test(text)) return null;
    const amount = Number(text.replace(",", "."));
    return Number.isFinite(amount) && (amount > 0 || (allowZero && amount === 0)) ? amount : null;
};

export const formatNumber = (value, locale = "es-DO") =>
    new Intl.NumberFormat(locale, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number.isFinite(Number(value)) ? Number(value) : 0);

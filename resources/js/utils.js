const numberFormatter = (locale, options) => {
    const region = typeof locale === "string" && locale.trim() ? locale : "es-DO";
    try {
        return new Intl.NumberFormat(region, options);
    } catch (error) {
        if (!(error instanceof RangeError)) throw error;
        return new Intl.NumberFormat("es-DO", options);
    }
};

// Missing record currency must not be guessed from the user's preference.
export const formatCurrency = (value, currency = "DOP", locale = "es-DO") => {
    const amount = Number(value);
    if (value == null || value === "" || !Number.isFinite(amount)) return "Importe sin definir";
    const code = typeof currency === "string" ? currency.trim().toUpperCase() : "";
    if (!/^[A-Z]{3}$/.test(code)) {
        return `${formatNumber(amount, locale)} (moneda sin definir)`;
    }
    return numberFormatter(locale, {
        style: "currency",
        currency: code,
        currencyDisplay: "code",
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(amount);
};

// Mobile keyboards may use a comma or a point. Group separators are not accepted.
export const parseAmount = (value, { allowZero = false } = {}) => {
    const text = String(value ?? "").trim();
    if (!/^\d+(?:[.,]\d{1,2})?$/.test(text)) return null;
    const amount = Number(text.replace(",", "."));
    return Number.isFinite(amount) && (amount > 0 || (allowZero && amount === 0)) ? amount : null;
};

export const formatNumber = (value, locale = "es-DO") =>
    numberFormatter(locale, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number.isFinite(Number(value)) ? Number(value) : 0);

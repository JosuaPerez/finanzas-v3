import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import { formatCurrency, formatNumber } from "@/utils";

export const useMoney = () => {
    const page = usePage();
    const currency = computed(
        () => page.props.auth?.user?.preferred_currency ?? "DOP",
    );
    const locale = computed(
        () => page.props.auth?.user?.number_locale ?? "es-DO",
    );
    const money = (amount, recordCurrency = "DOP") =>
        formatCurrency(amount, recordCurrency, locale.value);
    const date = (value) =>
        value
            ? new Intl.DateTimeFormat(locale.value, {
                  day: "numeric",
                  month: "long",
                  year: "numeric",
              }).format(new Date(`${value.slice(0, 10)}T12:00:00`))
            : "";
    const number = (amount) => formatNumber(amount, locale.value);
    return { currency, locale, money, date, number };
};

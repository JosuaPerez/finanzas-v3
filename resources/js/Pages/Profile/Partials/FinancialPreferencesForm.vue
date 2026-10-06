<script setup>
import { computed } from "vue";
import { useForm, usePage } from "@inertiajs/vue3";
import { formatCurrency } from "@/utils";

const page = usePage();
const form = useForm({
    preferred_currency: page.props.auth.user.preferred_currency,
    number_locale: page.props.auth.user.number_locale,
});
const preview = computed(() =>
    formatCurrency(1234.56, form.preferred_currency, form.number_locale),
);
const submit = () => {
    if (form.processing) return;
    form.patch(route("profile.financial-preferences"), {
        preserveScroll: true,
    });
};
</script>

<template>
    <section
        id="financial-preferences"
        class="rounded-2xl border border-slate-800 bg-slate-900 p-5 sm:p-7"
    >
        <h2 class="text-lg font-semibold text-white">
            Moneda y formato regional
        </h2>
        <p class="mt-2 text-sm leading-relaxed text-slate-400">
            Elige la moneda para tus nuevos registros. Los anteriores conservan
            su moneda e importe; aquí no se realizan conversiones.
        </p>
        <form @submit.prevent="submit" class="mt-5 space-y-5">
            <div>
                <label
                    for="preferred-currency"
                    class="mb-2 block text-sm text-slate-300"
                    >Moneda principal</label
                >
                <select
                    id="preferred-currency"
                    v-model="form.preferred_currency"
                    class="finance-input"
                    :disabled="form.processing"
                >
                    <option
                        v-for="(label, code) in page.props.finance.currencies"
                        :key="code"
                        :value="code"
                    >
                        {{ code }} · {{ label }}
                    </option>
                </select>
                <p
                    v-if="form.errors.preferred_currency"
                    role="alert"
                    class="mt-2 text-sm text-red-300"
                >
                    {{ form.errors.preferred_currency }}
                </p>
            </div>
            <div>
                <label
                    for="number-locale"
                    class="mb-2 block text-sm text-slate-300"
                    >Formato de números y fechas</label
                >
                <select
                    id="number-locale"
                    v-model="form.number_locale"
                    class="finance-input"
                    :disabled="form.processing"
                >
                    <option
                        v-for="(label, code) in page.props.finance.locales"
                        :key="code"
                        :value="code"
                    >
                        {{ label }}
                    </option>
                </select>
                <p
                    v-if="form.errors.number_locale"
                    role="alert"
                    class="mt-2 text-sm text-red-300"
                >
                    {{ form.errors.number_locale }}
                </p>
            </div>
            <p class="rounded-xl bg-slate-950 p-4 text-sm text-slate-400">
                Así se verá un importe:
                <span class="font-semibold text-white">{{ preview }}</span>
            </p>
            <div class="flex flex-wrap items-center gap-3">
                <button class="finance-button" :disabled="form.processing">
                    {{
                        form.processing ? "Guardando…" : "Guardar preferencias"
                    }}
                </button>
                <p
                    v-if="form.recentlySuccessful"
                    role="status"
                    class="text-sm text-emerald-300"
                >
                    Preferencias guardadas.
                </p>
            </div>
        </form>
    </section>
</template>

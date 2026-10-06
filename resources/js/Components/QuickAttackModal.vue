<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from "vue";
import { Link, useForm, usePage } from "@inertiajs/vue3";
import { useMoney } from "@/composables/useMoney";
import { parseAmount } from "@/utils";

const page = usePage();
const { currency, money } = useMoney();
const dialog = ref(null);
const amountInput = ref(null);
const isOpen = ref(false);
const amountText = ref("");
const requestId = ref(null);
const viewportHeight = ref(null);
let previousOverflow = "";
const form = useForm({
    type: "expense",
    debt_id: "",
    descripcion: "",
    currency: currency.value,
    already_budgeted: false,
});
const debts = computed(() => page.props.movementDebts ?? []);
const selectedDebt = computed(() =>
    debts.value.find((debt) => String(debt.id) === String(form.debt_id)),
);
const recordCurrency = computed(() =>
    form.type === "payment" ? selectedDebt.value?.currency : form.currency,
);
const mismatch = computed(
    () =>
        page.props.movementBudget &&
        recordCurrency.value &&
        recordCurrency.value !== page.props.movementBudget.currency,
);
const parsed = computed(() => parseAmount(amountText.value));
const canSubmit = computed(
    () =>
        parsed.value !== null &&
        !mismatch.value &&
        (form.type === "expense"
            ? form.descripcion.trim().length > 0
            : !!selectedDebt.value),
);
const updateViewport = () => {
    viewportHeight.value = window.visualViewport?.height ?? window.innerHeight;
};
const open = async (event) => {
    if (isOpen.value) return;
    if (!amountText.value) form.currency = page.props.movementBudget?.currency ?? currency.value;
    if (!requestId.value) requestId.value = page.props.movementRequestId;
    if (event?.detail?.type === "payment") {
        form.type = "payment";
        form.debt_id = event.detail.debtId;
    }
    previousOverflow = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    isOpen.value = true;
    updateViewport();
    await nextTick();
    dialog.value.showModal();
    amountInput.value?.focus();
};
const close = () => {
    if (form.processing) return;
    dialog.value?.close();
    isOpen.value = false;
    document.body.style.overflow = previousOverflow;
    // Keep the draft when dismissed; reset only after a successful save.
};
const keydown = (event) => {
    if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === "k") {
        event.preventDefault();
        isOpen.value ? close() : open();
    }
};
const submit = () => {
    if (form.processing || !canSubmit.value) return;
    form.transform(() =>
        form.type === "expense"
            ? {
                  monto: parsed.value,
                  descripcion: form.descripcion.trim(),
                  currency: form.currency,
                  already_budgeted: form.already_budgeted,
                  request_id: requestId.value,
              }
            : { amount: parsed.value, request_id: requestId.value },
    );
    form.post(
        form.type === "expense"
            ? route("quick-attack.store")
            : route("debts.pay", selectedDebt.value.id),
        {
            preserveScroll: true,
            onSuccess: () => {
                // Inertia still marks the form processing during onSuccess.
                dialog.value?.close();
                isOpen.value = false;
                document.body.style.overflow = previousOverflow;
                form.reset();
                form.clearErrors();
                amountText.value = "";
                requestId.value = null;
            },
        },
    );
};
watch(
    () => form.type,
    () => form.clearErrors(),
);
onMounted(() => {
    window.addEventListener("open-quick-attack", open);
    window.addEventListener("keydown", keydown);
    window.visualViewport?.addEventListener("resize", updateViewport);
});
onUnmounted(() => {
    window.removeEventListener("open-quick-attack", open);
    window.removeEventListener("keydown", keydown);
    window.visualViewport?.removeEventListener("resize", updateViewport);
    if (isOpen.value) document.body.style.overflow = previousOverflow;
});
</script>

<template>
    <Teleport to="body">
        <dialog
            ref="dialog"
            class="movement-dialog"
            :style="{
                maxHeight: viewportHeight
                    ? `${viewportHeight - 24}px`
                    : 'calc(100dvh - 24px)',
            }"
            aria-labelledby="movement-title"
            @cancel.prevent="close"
            @click="
                (event) => {
                    if (event.target === dialog) close();
                }
            "
        >
            <div class="p-5 sm:p-6">
                <header class="flex items-center justify-between gap-3">
                    <h2
                        id="movement-title"
                        class="text-lg font-semibold text-white"
                    >
                        Registrar movimiento
                    </h2>
                    <button
                        type="button"
                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-slate-300 hover:bg-slate-800"
                        :disabled="form.processing"
                        aria-label="Cerrar registro de movimiento"
                        @click="close"
                    >
                        ✕
                    </button>
                </header>
                <form @submit.prevent="submit" class="mt-4 space-y-5">
                    <fieldset
                        :disabled="form.processing"
                        class="space-y-5 disabled:opacity-70"
                    >
                        <div>
                            <label
                                for="movement-type"
                                class="mb-2 block text-sm text-slate-300"
                                >Tipo de movimiento</label
                            >
                            <select
                                id="movement-type"
                                v-model="form.type"
                                class="finance-input"
                            >
                                <option value="expense">Gasto</option>
                                <option value="payment">Pago de deuda</option>
                            </select>
                        </div>
                        <div v-if="form.type === 'payment'">
                            <label
                                for="movement-debt"
                                class="mb-2 block text-sm text-slate-300"
                                >Deuda a pagar</label
                            >
                            <select
                                id="movement-debt"
                                v-model="form.debt_id"
                                class="finance-input"
                                required
                            >
                                <option value="" disabled>
                                    Elige una deuda
                                </option>
                                <option
                                    v-for="debt in debts"
                                    :key="debt.id"
                                    :value="debt.id"
                                >
                                    {{ debt.name }} · {{ debt.currency }}
                                </option>
                            </select>
                            <p
                                v-if="selectedDebt"
                                class="mt-2 text-sm text-slate-400"
                            >
                                Saldo:
                                {{
                                    money(
                                        selectedDebt.balance,
                                        selectedDebt.currency,
                                    )
                                }}
                            </p>
                            <p
                                v-if="!debts.length"
                                class="mt-2 text-sm text-slate-400"
                            >
                                No tienes deudas activas.
                                <Link
                                    :href="route('deudas')"
                                    class="underline"
                                    @click="close"
                                    >Registrar una deuda</Link
                                >
                            </p>
                        </div>
                        <div v-else>
                            <label
                                for="movement-currency"
                                class="mb-2 block text-sm text-slate-300"
                                >Moneda del gasto</label
                            >
                            <select
                                id="movement-currency"
                                v-model="form.currency"
                                class="finance-input"
                            >
                                <option
                                    v-for="(label, code) in page.props.finance
                                        .currencies"
                                    :key="code"
                                    :value="code"
                                >
                                    {{ code }} · {{ label }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label
                                for="movement-amount"
                                class="mb-2 block text-sm text-slate-300"
                                >Importe
                                <span v-if="recordCurrency"
                                    >({{ recordCurrency }})</span
                                ></label
                            >
                            <input
                                id="movement-amount"
                                ref="amountInput"
                                v-model="amountText"
                                class="finance-input text-xl tabular-nums"
                                type="text"
                                inputmode="decimal"
                                autocomplete="off"
                                placeholder="0,00"
                                required
                                :aria-invalid="
                                    !!(form.errors.monto || form.errors.amount)
                                "
                                aria-describedby="amount-hint amount-error"
                            />
                            <p
                                id="amount-hint"
                                class="mt-2 text-xs text-slate-400"
                            >
                                Usa coma o punto decimal, sin separadores de
                                miles. Hasta dos decimales.
                            </p>
                            <p
                                v-if="amountText && parsed === null"
                                role="alert"
                                class="mt-2 text-sm text-red-300"
                            >
                                Introduce un importe mayor que cero, por ejemplo
                                25,50.
                            </p>
                            <p
                                id="amount-error"
                                role="alert"
                                class="mt-2 text-sm text-red-300"
                            >
                                {{
                                    form.errors.monto ||
                                    form.errors.amount ||
                                    form.errors.municion
                                }}
                            </p>
                        </div>
                        <div v-if="form.type === 'expense'">
                            <label
                                for="movement-description"
                                class="mb-2 block text-sm text-slate-300"
                                >¿En qué lo gastaste?</label
                            >
                            <input
                                id="movement-description"
                                v-model="form.descripcion"
                                class="finance-input"
                                type="text"
                                placeholder="Por ejemplo, transporte"
                                maxlength="255"
                                required
                            />
                            <p
                                v-if="form.errors.descripcion"
                                role="alert"
                                class="mt-2 text-sm text-red-300"
                            >
                                {{ form.errors.descripcion }}
                            </p>
                            <p
                                v-if="form.errors.currency"
                                role="alert"
                                class="mt-2 text-sm text-red-300"
                            >
                                {{ form.errors.currency }}
                            </p>
                        </div>
                        <div
                            class="rounded-xl bg-slate-950 p-4 text-sm leading-relaxed text-slate-400"
                        >
                            <label v-if="form.type === 'expense' && page.props.movementBudget && !mismatch" class="mb-3 flex min-h-11 items-center gap-3 text-slate-200">
                                <input type="checkbox" v-model="form.already_budgeted" class="h-5 w-5 rounded-sm" />
                                Ya incluido en los gastos fijos de este presupuesto
                            </label>
                            <p v-if="form.type === 'expense' && mismatch">
                                Elige {{ page.props.movementBudget.currency }}, la moneda de tu presupuesto. No se convierten importes automáticamente.
                            </p>
                            <p v-else-if="form.type === 'expense' && page.props.movementBudget">
                                {{ form.already_budgeted ? 'Se guardará en el historial sin descontarlo otra vez.' : 'Se descontará del disponible del presupuesto. Si lo supera, el disponible quedará negativo.' }}
                                No registra un pago de deuda.
                            </p>
                            <p v-else-if="form.type === 'expense'">
                                Se guardará en el historial. Como no tienes presupuesto, no se descontará de un disponible.
                            </p>
                            <p v-else-if="mismatch">
                                La deuda está en {{ selectedDebt.currency }} y
                                tu presupuesto en
                                {{ page.props.movementBudget.currency }}. Este
                                formulario no convierte monedas. Revisa el pago
                                en
                                <Link
                                    :href="route('deudas')"
                                    class="inline-flex min-h-11 items-center text-cyan-300 underline"
                                    @click="close"
                                    >Deudas</Link
                                >.
                            </p>
                            <p v-else-if="page.props.movementBudget">
                                El pago reduce el saldo de la deuda y el
                                disponible del presupuesto en
                                {{ page.props.movementBudget.currency }}. No se
                                crea un gasto adicional.
                            </p>
                            <p v-else>
                                El pago reduce el saldo de la deuda. Como no
                                tienes presupuesto, no se descontará de un
                                disponible.
                            </p>
                            <p
                                v-if="parsed && recordCurrency"
                                class="mt-2 font-medium text-white"
                            >
                                Vas a registrar
                                {{ money(parsed, recordCurrency) }}.
                            </p>
                            <p v-if="form.errors.server" role="alert" class="mt-2 text-red-300">{{ form.errors.server }}</p>
                            <p v-if="form.errors.request_id" role="alert" class="mt-2 text-red-300">{{ form.errors.request_id }}</p>
                            <p v-if="form.errors.municion" role="alert" class="mt-2 text-red-300">{{ form.errors.municion }}</p>
                        </div>
                    </fieldset>
                    <button
                        id="movement-submit"
                        type="submit"
                        class="finance-button w-full"
                        :disabled="form.processing || !canSubmit"
                    >
                        {{
                            form.processing
                                ? "Guardando…"
                                : form.type === "expense"
                                  ? "Guardar gasto"
                                  : "Guardar pago"
                        }}
                    </button>
                </form>
            </div>
        </dialog>
    </Teleport>
</template>

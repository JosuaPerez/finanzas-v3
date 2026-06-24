<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BossCard from '@/Components/BossCard.vue';
import CombatLog from '@/Components/CombatLog.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { formatMoney, getSymbol, getHPStats, cleanNum, vMoney } from '@/composables/useDebtUtils';

const props = defineProps({
    debts:             Array,
    ammunition:        { type: Number, default: 0 },  // Capital Libre del último presupuesto (DOP)
    usd_exchange_rate: { type: Number, default: 59.50 }, // Tasa de cambio USD→DOP
});

const form = ref({
    type: 'loan',
    currency: 'DOP',
    name: '',
    balance: '',
    interest_rate: '',
    minimum_payment: '',
    credit_limit: '',
    cutoff_date: '',
    payment_date: '',
    original_amount: '',
    overdraft_percentage: '',
    fecha_inicio: '',
    plazo_original_meses: '',
});

// ─── Intelligence Report: Real-Time Amortization Computed Properties ───────────

/** Monthly interest rate (decimal). e.g. 24% annual → 0.02 monthly */
const monthlyInterestRate = computed(() => {
    const r = parseFloat(form.value.interest_rate);
    if (!r || r <= 0) return 0;
    return r / 100 / 12;
});

/** HP (current balance) as a clean number */
const hp = computed(() => cleanNum(form.value.balance));

/** Interest charged on next billing cycle / month */
const nextMonthInterest = computed(() => {
    if (!hp.value || !monthlyInterestRate.value) return 0;
    return hp.value * monthlyInterestRate.value;
});

/** For Préstamo: how much of the payment actually chips away at the principal */
const realDamage = computed(() => {
    if (form.value.type !== 'loan') return 0;
    const cuota = cleanNum(form.value.minimum_payment);
    if (!cuota) return 0;
    return cuota - nextMonthInterest.value;
});

/**
 * For Préstamo: number of months to fully pay off using the standard
 * amortization formula: n = -ln(1 - r·P/C) / ln(1 + r)
 * Returns Infinity when the payment doesn't beat the interest.
 */
const monthsToPayoff = computed(() => {
    if (form.value.type !== 'loan') return null;
    const cuota = cleanNum(form.value.minimum_payment);
    const r = monthlyInterestRate.value;
    const P = hp.value;
    if (!cuota || !r || !P) return null;
    if (cuota <= nextMonthInterest.value) return Infinity;
    const n = -Math.log(1 - (r * P) / cuota) / Math.log(1 + r);
    return Math.ceil(n);
});

/** Payoff month/year formatted in Spanish, e.g. "Octubre 2028" */
const payoffDate = computed(() => {
    if (!monthsToPayoff.value || !isFinite(monthsToPayoff.value)) return null;
    const d = new Date();
    d.setMonth(d.getMonth() + monthsToPayoff.value);
    return d.toLocaleDateString('es-DO', { month: 'long', year: 'numeric' });
});

/** Current month number into the loan (1-based), derived from fecha_inicio */
const currentLoanMonth = computed(() => {
    if (!form.value.fecha_inicio) return null;
    const start = new Date(form.value.fecha_inicio);
    if (isNaN(start)) return null;
    const now = new Date();
    const months =
        (now.getFullYear() - start.getFullYear()) * 12 +
        (now.getMonth() - start.getMonth()) + 1;
    return months > 0 ? months : 1;
});

/** Dynamic focus-ring class based on active debt type */
const focusRing = computed(() =>
    form.value.type === 'loan'
        ? 'focus:ring-blue-500 focus:border-blue-500'
        : 'focus:ring-orange-500 focus:border-orange-500'
);

// formatMoney, getSymbol, getHPStats, cleanNum, vMoney → imported from @/composables/useDebtUtils

// ─── Dominican Banks & Credit Cards Catalogue (Cascading) ───────────────────
const bancosYTarjetas = {
    'Banco Popular': [
        { name: 'Visa Clásica / Standard', interest: 60, overdraft: 10 },
        { name: 'Visa Oro',                interest: 60, overdraft: 10 },
        { name: 'Visa Platinum',           interest: 60, overdraft: 10 },
        { name: 'Mastercard Black',        interest: 60, overdraft: 10 },
    ],
    'BHD': [
        { name: 'Visa Clásica BHD',   interest: 60, overdraft: 10 },
        { name: 'Visa Platinum BHD',  interest: 60, overdraft: 10 },
        { name: 'Mastercard Mujer',   interest: 60, overdraft: 10 },
    ],
    'Banreservas': [
        { name: 'Visa Standard',    interest: 60, overdraft: 10 },
        { name: 'Mastercard Black', interest: 60, overdraft: 10 },
        { name: 'Visa SER',         interest: 45, overdraft: 10 },
    ],
    'Scotiabank': [
        { name: 'Visa Clásica Scotia',  interest: 60, overdraft: 10 },
        { name: 'Visa Gold Scotia',     interest: 55, overdraft: 10 },
        { name: 'Visa Platinum Scotia', interest: 50, overdraft: 10 },
    ],
    'Banco Santa Cruz': [
        { name: 'Visa Clásica Santa Cruz',  interest: 60, overdraft: 10 },
        { name: 'Visa Platinum Santa Cruz', interest: 55, overdraft: 10 },
    ],
    'Otro Banco': [
        { name: 'Otra Tarjeta...', interest: 0, overdraft: 0 },
    ],
};

/** Local-only ref: tracks which bank is selected in Dropdown 1. */
const bancoSeleccionado = ref('');

/** Cards available for the currently selected bank. */
const tarjetasDelBanco = computed(() =>
    bancoSeleccionado.value ? (bancosYTarjetas[bancoSeleccionado.value] ?? []) : []
);

/**
 * Reset the card name whenever the bank changes so stale values
 * can't be submitted with a mismatched bank.
 */
watch(bancoSeleccionado, () => {
    form.value.name             = '';
    form.value.interest_rate    = '';
    form.value.overdraft_percentage = '';
});

/** Called on Dropdown 2 change — auto-fills rate & overdraft. */
const onCardSelect = (cardName) => {
    const list = tarjetasDelBanco.value;
    const card = list.find(c => c.name === cardName);
    if (!card || card.interest === 0) return;   // «Otra Tarjeta...» → leave fields editable
    form.value.interest_rate        = card.interest;
    form.value.overdraft_percentage = card.overdraft;
};

const notification = ref({ show: false, message: '', type: 'success' });
const showNotification = (message, type = 'success') => {
    notification.value = { show: true, message, type };
    setTimeout(() => { notification.value.show = false; }, 4000);
};

const strategy = ref('avalanche');
const sortedDebts = computed(() => {
    if (!props.debts) return [];
    return [...props.debts].sort((a, b) => strategy.value === 'avalanche' ? b.interest_rate - a.interest_rate : a.balance - b.balance);
});

const showPayModal = ref(false);
const showDeleteModal = ref(false);
const selectedDebt = ref(null);
const paymentAmount = ref('');
const isSubmitting = ref(false);
const floatingDamages = ref({});
const payErrors = ref({});

// ── Payment modal computed ────────────────────────────────────────────────────

/**
 * DOP-equivalent cost of the typed amount, currency-aware.
 * USD debts are converted using the exchange rate prop before being
 * compared against the DOP-denominated capital libre (ammunition).
 */
const dopCost = computed(() => {
    const amount = cleanNum(paymentAmount.value);
    if (!amount) return 0;
    return selectedDebt.value?.currency === 'USD'
        ? amount * props.usd_exchange_rate
        : amount;
});

/** True when the DOP cost of the attack exceeds available capital. */
const isOverAmmo = computed(() =>
    props.ammunition > 0 && dopCost.value > props.ammunition
);

const openPayModal = (debt) => {
    selectedDebt.value = debt;
    paymentAmount.value = debt.minimum_payment ? debt.minimum_payment.toString() : '';
    showPayModal.value = true;
};
const closePayModal = () => {
    showPayModal.value  = false;
    selectedDebt.value  = null;
    paymentAmount.value = '';
    payErrors.value     = {}; // clear stale backend errors each time modal closes
};

const confirmDelete = (debt) => {
    selectedDebt.value = debt;
    showDeleteModal.value = true;
};
const closeDeleteModal = () => { showDeleteModal.value = false; selectedDebt.value = null; };

const saveDebt = () => {
    const cleanBalance = cleanNum(form.value.balance);
    if (!form.value.name || cleanBalance <= 0) {
        showNotification("Completa el nombre y un saldo válido.", "error");
        return;
    }

    const payload = {
        type: form.value.type,
        currency: form.value.currency,
        name: form.value.name,
        balance: cleanBalance,
        interest_rate: cleanNum(form.value.interest_rate),
        minimum_payment: cleanNum(form.value.minimum_payment),
        credit_limit: form.value.type === 'credit_card' ? cleanNum(form.value.credit_limit) : null,
        cutoff_date: form.value.type === 'credit_card' ? form.value.cutoff_date : null,
        payment_date: form.value.type === 'credit_card' ? form.value.payment_date : null,
        overdraft_percentage: form.value.type === 'credit_card' ? cleanNum(form.value.overdraft_percentage) : 0,
        original_amount: form.value.type === 'loan' ? cleanNum(form.value.original_amount) : null,
        fecha_inicio: form.value.type === 'loan' ? (form.value.fecha_inicio || null) : null,
        plazo_original_meses: form.value.type === 'loan' ? (parseInt(form.value.plazo_original_meses) || null) : null,
    };

    isSubmitting.value = true;
    router.post(route('debts.store'), payload, {
        preserveScroll: true,
        onSuccess: () => {
            form.value = { type: form.value.type, currency: form.value.currency, name: '', balance: '', interest_rate: '', minimum_payment: '', credit_limit: '', cutoff_date: '', payment_date: '', original_amount: '', overdraft_percentage: '', fecha_inicio: '', plazo_original_meses: '' };
            showNotification('¡Nuevo enemigo detectado en el radar!', 'success');
        },
        onFinish: () => { isSubmitting.value = false; }
    });
};

const executeDelete = () => {
    isSubmitting.value = true;
    router.delete(route('debts.destroy', selectedDebt.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showNotification('Enemigo aniquilado y borrado de los registros.', 'success');
            closeDeleteModal();
        },
        onFinish: () => { isSubmitting.value = false; }
    });
};

const submitPayment = () => {
    const amount = cleanNum(paymentAmount.value);
    if (!amount || amount <= 0) {
        showNotification("Ingresa un monto válido para atacar.", "error");
        return;
    }

    isSubmitting.value = true;
    payErrors.value    = {}; // reset stale errors before each attempt
    router.post(route('debts.pay', selectedDebt.value.id), { amount }, {
        preserveScroll: true,
        onSuccess: () => {
            const debtId = selectedDebt.value.id;
            if (selectedDebt.value.balance - amount <= 0) {
                showNotification('¡GOLPE FINAL! El jefe ha sido destruido.', 'success');
            } else {
                showNotification(`¡Impacto Crítico! Le quitaste ${formatMoney(amount)} de HP.`, 'success');
            }
            // Trigger floating damage text on the BossCard
            floatingDamages.value[debtId] = amount;
            setTimeout(() => { delete floatingDamages.value[debtId]; }, 1500);
            closePayModal();
        },
        onError: (errors) => { payErrors.value = errors; },
        onFinish: () => { isSubmitting.value = false; }
    });
};
</script>

<template>
    <Head title="Modo Guerra - Deudas" />

    <AuthenticatedLayout>
        <div class="py-12 relative">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <PageHeader
                    subtitle="ZONA DE COMBATE"
                    title="🔥 Atacar Jefes"
                    description="Analiza los puntos de vida de tus deudas y ejecuta pagos estratégicos."
                />
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

                    <!-- SECCIÓN IZQUIERDA: RADAR / AÑADIR ENEMIGO -->
                    <div class="lg:col-span-4">
                        <div class="bg-slate-900 overflow-hidden shadow-2xl sm:rounded-2xl p-6 border border-slate-800 sticky top-6">
                            <div class="flex items-center gap-3 mb-6">
                                <div class="relative flex h-3 w-3">
                                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                  <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
                                </div>
                                <h3 class="text-xl font-black text-white tracking-wide uppercase">Radar de Enemigos</h3>
                            </div>

                            <div class="flex gap-2 mb-6">
                                <div class="flex p-1 bg-slate-800 rounded-lg w-2/3 border border-slate-700">
                                    <button @click="form.type = 'loan'" type="button"
                                        :class="form.type === 'loan' ? 'bg-blue-600 text-white font-bold shadow-md' : 'text-slate-400 hover:text-white'"
                                        class="w-1/2 py-2 text-xs rounded-md transition-all">👹 Préstamo</button>
                                    <button @click="form.type = 'credit_card'" type="button"
                                        :class="form.type === 'credit_card' ? 'bg-orange-600 text-white font-bold shadow-md' : 'text-slate-400 hover:text-white'"
                                        class="w-1/2 py-2 text-xs rounded-md transition-all">👾 Tarjeta</button>
                                </div>
                                <div class="flex p-1 bg-slate-800 rounded-lg w-1/3 border border-slate-700">
                                    <button @click="form.currency = 'DOP'" type="button"
                                        :class="form.currency === 'DOP' ? 'bg-slate-600 text-white font-bold' : 'text-slate-400 hover:text-white'"
                                        class="w-1/2 py-2 text-xs rounded-md transition-all">RD$</button>
                                    <button @click="form.currency = 'USD'" type="button"
                                        :class="form.currency === 'USD' ? 'bg-green-700 text-white font-bold' : 'text-slate-400 hover:text-white'"
                                        class="w-1/2 py-2 text-xs rounded-md transition-all">US$</button>
                                </div>
                            </div>

                            <!-- ── GRID: Identificación + Estadísticas de Combate ── -->
                            <div class="grid grid-cols-1 gap-6">

                                <!-- ─ SECCIÓN 1: IDENTIFICACIÓN ─ -->
                                <div class="space-y-4">
                                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] flex items-center gap-2">
                                        <span class="h-px flex-1 bg-slate-700/70"></span>Identificación<span class="h-px flex-1 bg-slate-700/70"></span>
                                    </p>

                                    <!-- Nombre del Objetivo — text for loans, select for credit cards -->
                                    <div>
                                        <label class="block text-xs font-bold text-slate-400 mb-1.5 uppercase tracking-wider">Nombre del Objetivo</label>

                                        <!-- ── PRÉSTAMO: plain text input ── -->
                                        <input
                                            v-if="form.type === 'loan'"
                                            type="text"
                                            v-model="form.name"
                                            :class="focusRing"
                                            class="w-full bg-slate-950 border border-slate-700 text-white rounded-lg px-3 py-2.5 transition-all placeholder-slate-600 focus:outline-none focus:ring-2"
                                            placeholder="Ej. El Ogro del Banco"
                                        >

                                        <!-- ── TARJETA: cascading bank → card selects ── -->
                                        <div v-else class="space-y-3">

                                            <!-- ① Dropdown 1: Bank -->
                                            <div class="relative">
                                                <select
                                                    v-model="bancoSeleccionado"
                                                    :class="focusRing"
                                                    class="w-full bg-slate-950 border border-slate-700 text-white rounded-lg px-3 py-2.5 pr-9 transition-all focus:outline-none focus:ring-2 appearance-none cursor-pointer"
                                                >
                                                    <option value="" disabled class="text-slate-500">Selecciona el Banco 🏦</option>
                                                    <option
                                                        v-for="banco in Object.keys(bancosYTarjetas)"
                                                        :key="banco"
                                                        :value="banco"
                                                        class="bg-slate-900 text-white"
                                                    >{{ banco }}</option>
                                                </select>
                                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                    </svg>
                                                </div>
                                            </div>

                                            <!-- ② Dropdown 2: Card (revealed after bank is picked) -->
                                            <Transition
                                                enter-active-class="transition-all duration-300 ease-out"
                                                enter-from-class="opacity-0 -translate-y-1"
                                                enter-to-class="opacity-100 translate-y-0"
                                            >
                                                <div v-if="bancoSeleccionado" class="relative">
                                                    <select
                                                        v-model="form.name"
                                                        @change="onCardSelect(form.name)"
                                                        :class="focusRing"
                                                        class="w-full bg-slate-950 border border-orange-900/60 text-white rounded-lg px-3 py-2.5 pr-9 transition-all focus:outline-none focus:ring-2 appearance-none cursor-pointer"
                                                    >
                                                        <option value="" disabled class="text-slate-500">Selecciona la Tarjeta 💳</option>
                                                        <option
                                                            v-for="card in tarjetasDelBanco"
                                                            :key="card.name"
                                                            :value="card.name"
                                                            class="bg-slate-900 text-white"
                                                        >{{ card.name }}{{ card.interest > 0 ? ` — ${card.interest}% anual` : '' }}</option>
                                                    </select>
                                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                                        <svg class="w-4 h-4 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                        </svg>
                                                    </div>
                                                </div>
                                            </Transition>
                                        </div>
                                    </div>

                                    <!-- HP / Balance -->
                                    <div>
                                        <label class="block text-xs font-bold text-red-400 mb-1.5 uppercase tracking-wider">
                                            {{ form.type === 'loan' ? '❤️ HP Actual (Saldo)' : '💥 Daño Acumulado (Deuda)' }}
                                        </label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <span class="text-slate-400 font-bold text-sm">{{ getSymbol(form.currency) }}</span>
                                            </div>
                                            <input type="text" v-model="form.balance" v-money
                                                :class="focusRing"
                                                class="w-full bg-slate-950 border border-red-900/60 text-white rounded-lg pl-12 pr-3 py-2.5 font-mono transition-all placeholder-slate-600 focus:outline-none focus:ring-2"
                                                placeholder="0.00">
                                        </div>
                                    </div>

                                    <!-- Loan extra: Monto Original -->
                                    <div v-if="form.type === 'loan'" class="p-3.5 bg-slate-800/50 border border-blue-900/50 rounded-xl">
                                        <label class="block text-[10px] font-bold text-blue-400 mb-1.5 uppercase tracking-wider">🛡️ Vida Máxima (Monto Original)</label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <span class="text-blue-500 font-bold text-sm">{{ getSymbol(form.currency) }}</span>
                                            </div>
                                            <input type="text" v-model="form.original_amount" v-money
                                                class="w-full bg-slate-950 border border-slate-700 text-white rounded-lg pl-12 pr-3 py-2.5 font-mono focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all placeholder-slate-600"
                                                placeholder="0.00">
                                        </div>
                                    </div>

                                    <!-- Credit Card extras -->
                                    <div v-if="form.type === 'credit_card'" class="p-3.5 bg-slate-800/50 border border-orange-900/50 rounded-xl space-y-4">
                                        <div class="grid grid-cols-3 gap-2">
                                            <div class="col-span-2">
                                                <label class="block text-[10px] font-bold text-orange-400 mb-1.5 uppercase tracking-wider">Límite Aprobado (Max HP)</label>
                                                <div class="relative">
                                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                        <span class="text-orange-500 font-bold text-sm">{{ getSymbol(form.currency) }}</span>
                                                    </div>
                                                    <input type="text" v-model="form.credit_limit" v-money
                                                        class="w-full bg-slate-950 border border-slate-700 text-white rounded-lg pl-12 pr-3 py-2.5 font-mono focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all placeholder-slate-600"
                                                        placeholder="0.00">
                                                </div>
                                            </div>
                                            <div class="col-span-1">
                                                <label class="block text-[10px] font-bold text-orange-400 mb-1.5 uppercase tracking-wider">% Sobregiro</label>
                                                <div class="relative">
                                                    <input type="number" v-model="form.overdraft_percentage"
                                                        class="w-full bg-slate-950 border border-slate-700 text-white rounded-lg pr-7 pl-3 py-2.5 font-mono focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all placeholder-slate-600"
                                                        placeholder="10">
                                                    <div class="absolute inset-y-0 right-0 pr-2.5 flex items-center pointer-events-none">
                                                        <span class="text-orange-500 font-bold text-xs">%</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-2 gap-3">
                                            <div>
                                                <label class="block text-[10px] font-bold text-slate-400 mb-1.5 uppercase tracking-wider">Día de Corte</label>
                                                <input type="number" v-model="form.cutoff_date" min="1" max="31"
                                                    class="w-full bg-slate-950 border border-slate-700 text-white rounded-lg px-3 py-2.5 font-mono focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all placeholder-slate-600"
                                                    placeholder="Ej: 15">
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-bold text-slate-400 mb-1.5 uppercase tracking-wider">Día de Pago</label>
                                                <input type="number" v-model="form.payment_date" min="1" max="31"
                                                    class="w-full bg-slate-950 border border-slate-700 text-white rounded-lg px-3 py-2.5 font-mono focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all placeholder-slate-600"
                                                    placeholder="Ej: 5">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- ─ SECCIÓN 2: ESTADÍSTICAS DE COMBATE ─ -->
                                <div class="space-y-4">
                                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] flex items-center gap-2">
                                        <span class="h-px flex-1 bg-slate-700/70"></span>Estadísticas de Combate<span class="h-px flex-1 bg-slate-700/70"></span>
                                    </p>

                                    <div class="grid grid-cols-2 gap-3">
                                        <!-- Tasa de Interés -->
                                        <div>
                                            <label class="block text-xs font-bold text-slate-400 mb-1.5 uppercase tracking-wider">⚔️ Tasa Anual</label>
                                            <div class="relative">
                                                <input type="number" v-model="form.interest_rate"
                                                    :class="focusRing"
                                                    class="w-full bg-slate-950 border border-slate-700 text-white rounded-lg pl-3 pr-8 py-2.5 font-mono focus:outline-none focus:ring-2 transition-all placeholder-slate-600"
                                                    placeholder="0">
                                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                                    <span class="text-slate-500 font-bold text-xs">%</span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Cuota / Pago Mínimo -->
                                        <div>
                                            <label class="block text-xs font-bold text-slate-400 mb-1.5 uppercase tracking-wider">
                                                {{ form.type === 'loan' ? '🗡️ Cuota Fija' : '🛡️ Pago Mínimo' }}
                                            </label>
                                            <input type="text" v-model="form.minimum_payment" v-money
                                                :class="focusRing"
                                                class="w-full bg-slate-950 border border-slate-700 text-white rounded-lg px-3 py-2.5 font-mono focus:outline-none focus:ring-2 transition-all placeholder-slate-600"
                                                placeholder="0.00">
                                        </div>
                                    </div>

                                    <!-- Loan-only: Fecha de Inicio + Plazo Original -->
                                    <div v-if="form.type === 'loan'" class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-[10px] font-bold text-blue-400 mb-1.5 uppercase tracking-wider">📅 Día de Despliegue</label>
                                            <input type="date" v-model="form.fecha_inicio"
                                                class="w-full bg-slate-950 border border-slate-700 text-white rounded-lg px-3 py-2.5 font-mono focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all placeholder-slate-600 [color-scheme:dark]">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-bold text-blue-400 mb-1.5 uppercase tracking-wider">🗓️ Duración (meses)</label>
                                            <input type="number" v-model="form.plazo_original_meses" min="1" max="600"
                                                class="w-full bg-slate-950 border border-slate-700 text-white rounded-lg px-3 py-2.5 font-mono focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all placeholder-slate-600"
                                                placeholder="Ej: 60">
                                        </div>
                                    </div>
                                </div>

                                <!-- ─ REPORTE DE INTELIGENCIA ─ -->
                                <Transition
                                    enter-active-class="transition-all duration-500 ease-out"
                                    enter-from-class="opacity-0 -translate-y-2"
                                    enter-to-class="opacity-100 translate-y-0"
                                    leave-active-class="transition-all duration-300 ease-in"
                                    leave-from-class="opacity-100"
                                    leave-to-class="opacity-0"
                                >
                                    <div
                                        v-if="hp > 0 && monthlyInterestRate > 0"
                                        class="bg-slate-800/40 border border-slate-600 rounded-xl p-4 text-sm text-slate-300 space-y-2"
                                    >
                                        <!-- Header -->
                                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.18em] flex items-center gap-2 mb-1">
                                            <span class="text-base">📡</span> Reporte de Inteligencia
                                        </p>

                                        <!-- Tarjeta de Crédito -->
                                        <p v-if="form.type === 'credit_card'" class="leading-relaxed">
                                            Si no liquidas el saldo este mes, el enemigo contraatacará con aprox.
                                            <strong class="text-orange-400 font-mono">
                                                {{ getSymbol(form.currency) }} {{ formatMoney(nextMonthInterest) }}
                                            </strong>
                                            en intereses.
                                        </p>

                                        <!-- Préstamo válido -->
                                        <template v-else-if="form.type === 'loan' && isFinite(monthsToPayoff) && monthsToPayoff > 0">
                                            <p class="leading-relaxed">
                                                Tu cuota de
                                                <strong class="text-blue-400 font-mono">{{ getSymbol(form.currency) }} {{ formatMoney(cleanNum(form.minimum_payment)) }}</strong>
                                                absorberá
                                                <strong class="text-red-400 font-mono">{{ getSymbol(form.currency) }} {{ formatMoney(nextMonthInterest) }}</strong>
                                                en intereses y hará
                                                <strong class="text-green-400 font-mono">{{ getSymbol(form.currency) }} {{ formatMoney(realDamage) }}</strong>
                                                de daño al capital.
                                            </p>
                                            <p class="leading-relaxed">
                                                A este ritmo, derrotarás a este jefe en
                                                <strong class="text-yellow-400">{{ payoffDate }}</strong>
                                                <span class="text-slate-500">({{ monthsToPayoff }} meses)</span>.
                                            </p>
                                            <!-- Progreso histórico (optional) -->
                                            <p v-if="currentLoanMonth && form.plazo_original_meses"
                                               class="flex items-center gap-1.5 text-slate-400 border-t border-slate-700/60 pt-2 mt-1">
                                                <span>📍</span>
                                                <span>Progreso histórico: Estás en el mes
                                                    <strong class="text-white">{{ currentLoanMonth }}</strong>
                                                    de
                                                    <strong class="text-white">{{ form.plazo_original_meses }}</strong>.
                                                </span>
                                            </p>
                                        </template>

                                        <!-- Préstamo inválido: cuota menor que el interés -->
                                        <div
                                            v-else-if="form.type === 'loan' && cleanNum(form.minimum_payment) > 0 && !isFinite(monthsToPayoff)"
                                            class="flex items-start gap-2 p-2.5 rounded-lg bg-red-900/20 border border-red-700/50"
                                        >
                                            <span class="text-lg leading-none mt-0.5">⚠️</span>
                                            <p class="text-red-300 leading-relaxed">
                                                <strong>ALERTA:</strong> Tu cuota es demasiado baja y no logra perforar el escudo de intereses
                                                (<strong class="font-mono text-red-400">{{ getSymbol(form.currency) }} {{ formatMoney(nextMonthInterest) }}</strong>).
                                                El HP del enemigo <strong>aumentará</strong> cada mes.
                                            </p>
                                        </div>
                                    </div>
                                </Transition>

                                <!-- ─ BOTÓN FIJAR OBJETIVO ─ -->
                                <button @click="saveDebt"
                                    :disabled="isSubmitting"
                                    :class="[
                                        { 'opacity-70 cursor-wait pointer-events-none': isSubmitting },
                                        form.type === 'loan'
                                            ? 'bg-blue-600 hover:bg-blue-500 shadow-[0_0_18px_rgba(37,99,235,0.45)]'
                                            : 'bg-orange-600 hover:bg-orange-500 shadow-[0_0_18px_rgba(234,88,12,0.45)]'
                                    ]"
                                    class="w-full inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl font-black uppercase tracking-widest transition-all duration-300 ease-out hover:scale-105 hover:-translate-y-0.5 text-white">
                                    🎯 Fijar Objetivo
                                </button>

                            </div>
                        </div>
                    </div>

                    <!-- SECCIÓN DERECHA: CAMPO DE BATALLA -->
                    <div class="lg:col-span-8">

                        <!-- HUD: Municiones -->
                        <div v-if="ammunition > 0"
                            class="mb-6 bg-slate-900 border border-blue-500/30 p-5 rounded-2xl flex flex-col sm:flex-row justify-between items-center shadow-2xl relative overflow-hidden">
                            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-blue-600 to-indigo-600"></div>
                            <div class="flex items-center gap-4 mb-3 sm:mb-0 relative z-10">
                                <div class="bg-blue-600/20 p-3 rounded-xl border border-blue-500/50 text-2xl shadow-[0_0_15px_rgba(37,99,235,0.4)]">🔋</div>
                                <div>
                                    <h3 class="text-blue-400 font-black text-sm uppercase tracking-widest">Munición Disponible</h3>
                                    <p class="text-slate-400 text-xs font-medium">Capital listo para disparar</p>
                                </div>
                            </div>
                            <div class="text-center sm:text-right w-full sm:w-auto bg-slate-950 px-6 py-3 rounded-xl border border-slate-800 shadow-inner relative z-10">
                                <span class="text-3xl font-black text-white font-mono tracking-tight">RD$ {{ formatMoney(ammunition) }}</span>
                            </div>
                        </div>

                        <!-- LISTA DE JEFES -->
                        <div class="bg-slate-900/80 backdrop-blur-sm overflow-hidden shadow-2xl sm:rounded-3xl p-6 md:p-8 border border-slate-700/60 ring-1 ring-white/5 relative">
                            <!-- Ambient accent line -->
                            <div class="absolute top-0 left-0 right-0 h-[2px] bg-gradient-to-r from-transparent via-red-500/50 to-transparent"></div>

                            <div v-if="debts && debts.length > 1" class="flex bg-slate-800/80 p-1 rounded-xl mb-6 border border-slate-700/50 w-fit">
                                    <button @click="strategy = 'avalanche'"
                                        :class="strategy === 'avalanche' ? 'bg-slate-950 text-white font-bold shadow-md' : 'text-slate-500 hover:text-slate-300'"
                                        class="px-4 py-2 rounded-lg text-xs transition-all uppercase tracking-wider">🌋 Avalancha</button>
                                    <button @click="strategy = 'snowball'"
                                        :class="strategy === 'snowball' ? 'bg-slate-950 text-white font-bold shadow-md' : 'text-slate-500 hover:text-slate-300'"
                                        class="px-4 py-2 rounded-lg text-xs transition-all uppercase tracking-wider">⛄ Bola Nieve</button>
                                </div>

                            <div v-if="debts && debts.length > 0" class="space-y-6">

                                <!-- TARJETA DE ENEMIGO (JEFE) -->
                                <BossCard
                                    v-for="(debt, index) in sortedDebts"
                                    :key="debt.id"
                                    :debt="debt"
                                    :index="index"
                                    :floating-damage="floatingDamages[debt.id] ?? null"
                                    @attack="openPayModal"
                                    @abort="confirmDelete"
                                />
                            </div>

                            <div v-else
                                class="text-center p-16 border-2 border-dashed border-slate-700/60 rounded-3xl bg-slate-950/30 mt-6">
                                <div class="text-6xl mb-4 opacity-60">🕊️</div>
                                <h3 class="text-2xl font-black text-white tracking-tight">Zona Despejada</h3>
                                <p class="text-slate-400 mt-2 font-medium">No hay jefes enemigos en el radar. Estás en paz financiera o te falta encender el escáner a la izquierda.</p>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL DE ATAQUE -->
        <div v-if="showPayModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-slate-900/90 transition-opacity backdrop-blur-md" @click="closePayModal"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-slate-900 rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-slate-700">
                    <div class="bg-slate-900 px-6 pt-8 pb-6">
                        <h3 class="text-2xl font-black text-white flex items-center gap-3 border-b border-slate-700 pb-4 tracking-tight">
                            ⚔️ Atacar a {{ selectedDebt?.name }}
                        </h3>

                        <div v-if="ammunition > 0" class="mt-6 mb-6 p-4 bg-blue-900/30 border border-blue-500/30 rounded-xl flex justify-between items-center">
                            <span class="text-xs font-bold text-blue-400 uppercase tracking-widest flex items-center gap-2">🔋 Munición:</span>
                            <span class="text-lg font-black text-blue-300 font-mono">RD$ {{ formatMoney(ammunition) }}</span>
                        </div>

                        <div class="mt-4">
                            <p class="text-xs text-slate-400 font-bold uppercase tracking-widest mb-1">HP Actual del Jefe:</p>
                            <p class="text-4xl font-black text-red-500 mb-8 font-mono">{{ getSymbol(selectedDebt?.currency) }} {{ formatMoney(selectedDebt?.balance) }}</p>

                            <label class="block text-sm font-bold text-white mb-3 tracking-wide">¿Con cuánto poder vas a golpear?</label>
                            <div class="relative rounded-xl shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none">
                                    <span class="text-slate-400 font-bold text-xl">{{ getSymbol(selectedDebt?.currency) }}</span>
                                </div>
                                <input type="text" v-model="paymentAmount" v-money autofocus
                                    class="block w-full rounded-2xl border-slate-600 bg-slate-800 text-white pl-16 py-5 text-2xl font-mono focus:border-red-500 focus:ring-red-500 shadow-inner"
                                    placeholder="0.00">
                            </div>

                            <!-- USD conversion hint —— shown only for USD debts with a typed amount -->
                            <Transition
                                enter-active-class="transition-all duration-300 ease-out"
                                enter-from-class="opacity-0 -translate-y-1"
                                enter-to-class="opacity-100 translate-y-0"
                                leave-active-class="transition-all duration-200 ease-in"
                                leave-from-class="opacity-100"
                                leave-to-class="opacity-0"
                            >
                                <p v-if="selectedDebt?.currency === 'USD' && cleanNum(paymentAmount) > 0"
                                    class="mt-3 text-sm text-blue-400 font-medium leading-relaxed">
                                    Equivalente a <strong class="font-mono text-blue-300">RD$ {{ formatMoney(dopCost) }}</strong>.
                                    Tasa de referencia ({{ usd_exchange_rate }}).
                                    <span class="text-slate-500">Puede variar en cualquier momento.</span>
                                </p>
                            </Transition>

                            <!-- Over-ammo warning —— shown when typed amount exceeds capital libre -->
                            <Transition
                                enter-active-class="transition-all duration-300 ease-out"
                                enter-from-class="opacity-0 -translate-y-1"
                                enter-to-class="opacity-100 translate-y-0"
                                leave-active-class="transition-all duration-200 ease-in"
                                leave-from-class="opacity-100"
                                leave-to-class="opacity-0"
                            >
                                <div v-if="isOverAmmo"
                                    class="mt-3 flex items-center gap-2 p-3 bg-amber-900/40 border border-amber-500/50 rounded-xl">
                                    <span class="text-base shrink-0">⚠️</span>
                                    <p class="text-amber-300 text-xs font-bold leading-relaxed">
                                        Munición insuficiente. Capital disponible:
                                        <span class="font-mono">RD$ {{ formatMoney(ammunition) }}</span>
                                    </p>
                                </div>
                            </Transition>

                            <!-- Backend municion error (server-side catch) -->
                            <div v-if="payErrors.municion"
                                class="mt-3 flex items-center gap-2 p-3 bg-red-900/40 border border-red-500/50 rounded-xl">
                                <span class="text-base shrink-0">🚫</span>
                                <p class="text-red-300 text-xs font-bold">{{ payErrors.municion }}</p>
                            </div>

                            <p class="text-[10px] text-slate-500 mt-3 font-bold uppercase tracking-wider">* Cargado con el ataque mínimo por defecto.</p>
                        </div>
                    </div>
                    <div class="bg-slate-800/50 px-6 py-4 sm:flex sm:flex-row-reverse border-t border-slate-700">
                        <button @click="submitPayment"
                            :disabled="isSubmitting || isOverAmmo"
                            :class="{
                                'opacity-70 cursor-wait !pointer-events-none': isSubmitting,
                                'opacity-50 cursor-not-allowed !pointer-events-none': isOverAmmo && !isSubmitting,
                            }"
                            class="w-full sm:w-auto sm:ml-3 inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl font-black uppercase tracking-widest transition-all duration-300 ease-out hover:scale-105 hover:-translate-y-0.5 bg-red-600 hover:bg-red-500 text-white shadow-[0_0_15px_rgba(220,38,38,0.4)]">
                            💥 Lanzar Ataque
                        </button>
                        <button @click="closePayModal"
                            class="mt-3 w-full inline-flex justify-center rounded-xl border border-slate-600 shadow-sm px-6 py-3 bg-transparent text-sm font-bold text-slate-300 hover:bg-slate-700 sm:mt-0 sm:ml-3 sm:w-auto transition-colors">
                            Retirada
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL DE BORRAR -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-slate-900/90 transition-opacity backdrop-blur-md" @click="closeDeleteModal"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-slate-900 rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-slate-700">
                    <div class="bg-slate-900 px-6 pt-8 pb-6">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-14 w-14 rounded-full bg-red-500/20 border border-red-500/50 sm:mx-0 sm:h-12 sm:w-12">
                                ⚠️
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 class="text-xl leading-6 font-black text-white tracking-tight">Eliminar Registro</h3>
                                <div class="mt-3">
                                    <p class="text-sm text-slate-400">¿Estás seguro de que deseas eliminar a <strong class="text-red-400">{{ selectedDebt?.name }}</strong> del radar? Esta acción no devolverá tu munición ni se puede deshacer.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-slate-800/50 px-6 py-4 sm:flex sm:flex-row-reverse border-t border-slate-700">
                        <button @click="executeDelete" type="button"
                            :disabled="isSubmitting"
                            :class="{ 'opacity-70 cursor-wait pointer-events-none': isSubmitting }"
                            class="w-full sm:w-auto sm:ml-3 inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl font-black uppercase tracking-widest transition-all duration-300 ease-out hover:scale-105 hover:-translate-y-0.5 bg-red-600 hover:bg-red-500 text-white shadow-[0_0_15px_rgba(220,38,38,0.4)]">
                            Eliminar del Radar
                        </button>
                        <button @click="closeDeleteModal" type="button"
                            class="mt-3 w-full inline-flex justify-center rounded-xl border border-slate-600 shadow-sm px-6 py-3 bg-transparent text-sm font-bold text-slate-300 hover:bg-slate-700 sm:mt-0 sm:ml-3 sm:w-auto transition-colors">
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <CombatLog
            :show="notification.show"
            :message="notification.message"
            :type="notification.type"
        />

    </AuthenticatedLayout>
</template>
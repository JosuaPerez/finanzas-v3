<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CombatLog from '@/Components/CombatLog.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { useMoney } from '@/composables/useMoney';
const page = usePage();
const { money: formatCurrency, currency, locale } = useMoney();
import { cleanNum } from '@/composables/useDebtUtils';

const props = defineProps({
    budgets:    { type: Array,  default: () => [] },
    totalDebts: { type: Number, default: 0 },
    debtTotals: { type: Object, default: () => ({}) },
});

// ── Formulario de quincena ───────────────────────────────────────────────────
const budgetCurrency = ref(currency.value);
const sameCurrencyDebts = computed(() => Number(props.debtTotals[budgetCurrency.value] ?? 0));
const income = ref('');
const fixedExpenses = ref([
    { id: 1, name: 'Casa / Alquiler',          amount: '' },
    { id: 2, name: 'Comida / Supermercado',    amount: '' },
    { id: 3, name: 'Luz / Servicios',          amount: '' },
    { id: 4, name: 'Transporte / Gasolina',    amount: '' },
]);

const totalFixed  = computed(() => fixedExpenses.value.reduce((s, i) => s + cleanNum(i.amount), 0));
const remaining   = computed(() => {
    let base = cleanNum(income.value) - totalFixed.value;
    return base;
});

const addFixedRow    = () => fixedExpenses.value.push({ id: Date.now(), name: '', amount: '' });
const removeFixedRow = (i) => fixedExpenses.value.splice(i, 1);
const getPercent     = (amt) => cleanNum(income.value) > 0
    ? ((cleanNum(amt) / cleanNum(income.value)) * 100).toFixed(1) : 0;

// ── Notificaciones ───────────────────────────────────────────────────────────
const notification  = ref({ show: false, message: '', type: 'success' });
const isSubmitting  = ref(false);
const notify = (message, type = 'success') => {
    notification.value = { show: true, message, type };
    setTimeout(() => { notification.value.show = false; }, 4000);
};

// ── Guardar presupuesto ──────────────────────────────────────────────────────
const saveBudget = () => {
    if (isSubmitting.value) return;
    if (!Number.isFinite(cleanNum(income.value)) || cleanNum(income.value) <= 0) {
        notify('Ingresa un ingreso para poder guardar el presupuesto.', 'error');
        return;
    }
    if (fixedExpenses.value.some(item => !Number.isFinite(cleanNum(item.amount)))) {
        notify('Usa coma o punto decimal y hasta dos decimales, sin separadores de miles.', 'error');
        return;
    }
    const payload = {
        currency: budgetCurrency.value,
        title:                `Presupuesto del ${new Date().toLocaleDateString(locale.value)}`,
        income:               cleanNum(income.value),
        fixed_expenses_total: totalFixed.value,
        details: {
            fixed:          fixedExpenses.value.map(i => ({ name: i.name, amount: cleanNum(i.amount) })),
            debts_deducted: 0,
            remaining:      remaining.value,
        },
    };
    isSubmitting.value = true;
    router.post(route('budgets.store'), { ...payload, request_id: page.props.movementRequestId }, {
        preserveScroll: true,
        onSuccess: () => notify('¡Presupuesto guardado! Tu capital libre está listo.', 'success'),
        onError: (errors) => notify(Object.values(errors)[0] ?? 'Revisa los datos del presupuesto.', 'error'),
        onFinish:  () => { isSubmitting.value = false; },
    });
};

const downloadAsCsv = () => {
    notify('Generando Excel…', 'success');
    window.location.href = route('budgets.export');
};

// ── Modal de historial ───────────────────────────────────────────────────────
const showModal      = ref(false);
const selectedBudget = ref(null);

const openBudgetDetails = (budget) => {
    let d = budget.details;
    if (typeof d === 'string') d = JSON.parse(d);
    selectedBudget.value = { ...budget, details: d };
    showModal.value = true;
};
const closeModal = () => { showModal.value = false; selectedBudget.value = null; };
</script>

<template>
    <Head title="Presupuesto" />

    <AuthenticatedLayout>

        <div class="py-10">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

                <!-- ── Page title ── -->
                <PageHeader
                    subtitle="CENTRO DE MANDO"
                    title="Presupuesto"
                    description="Registra un ingreso y organiza tus gastos en una misma moneda."
                />

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

                    <!-- ── LEFT: Calculator ── -->
                    <div class="lg:col-span-12 rounded-xl border border-slate-800 p-4">
                        <label for="budget-currency" class="mb-2 block text-sm text-slate-300">Moneda del nuevo presupuesto</label>
                        <select id="budget-currency" v-model="budgetCurrency" class="finance-input"><option v-for="(label, code) in $page.props.finance.currencies" :key="code" :value="code">{{ code }} · {{ label }}</option></select>
                        <p class="mt-2 text-sm text-slate-400">Solo se incluyen deudas en esta moneda. Tus presupuestos anteriores conservan su moneda.</p>
                    </div>
                    <div class="lg:col-span-8">
                        <div class="bg-slate-900/80 backdrop-blur-xs border border-slate-700/60 ring-1 ring-white/5 sm:rounded-3xl shadow-xl p-6 md:p-8 relative overflow-hidden">
                            <div class="absolute top-0 left-0 right-0 h-[2px] bg-linear-to-r from-transparent via-blue-500/60 to-transparent"></div>

                            <!-- 1. Income -->
                            <div class="mb-8 p-6 bg-slate-950 rounded-2xl border border-slate-800 shadow-inner">
                                <label for="budget-income" class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">
                                    1. Ingreso — ¿Cuánto recibiste?
                                </label>
                                <div class="relative rounded-xl shadow-xs">
                                    <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none">
                                        <span class="text-slate-500 font-bold sm:text-xl">{{ budgetCurrency }}</span>
                                    </div>
                                    <input id="budget-income" type="text" v-model="income" inputmode="decimal"
                                        class="block w-full bg-slate-800 text-white rounded-xl border-slate-700 pl-16 py-4 text-xl font-mono focus:border-blue-500 focus:ring-blue-500 shadow-inner"
                                        placeholder="0.00">
                                </div>
                            </div>

                            <!-- 2. Fixed expenses -->
                            <div class="mb-8 p-6 border border-dashed border-slate-700 rounded-2xl bg-slate-950/50">
                                <h3 class="text-lg font-bold text-white mb-1">2. Gastos fijos</h3>
                                <p class="text-xs text-slate-400 mb-5">Organiza los gastos que necesitas cubrir con este ingreso.</p>

                                <div class="space-y-3 mb-5">
                                    <div v-for="(gasto, index) in fixedExpenses" :key="gasto.id"
                                        class="flex flex-col md:flex-row items-center gap-3 bg-slate-900 p-3 rounded-xl border border-slate-800 hover:border-slate-600 transition-all">
                                        <input type="text" v-model="gasto.name"
                                            class="w-full md:w-1/2 border-0 border-b border-slate-700 focus:border-blue-500 focus:ring-0 font-bold text-slate-300 bg-transparent placeholder-slate-600"
                                            placeholder="Concepto (Ej. Luz)">
                                        <div class="flex items-center w-full md:w-1/2 gap-2">
                                            <span class="text-slate-500 font-bold ml-2 md:ml-0">{{ budgetCurrency }}</span>
                                            <input type="text" v-model="gasto.amount" inputmode="decimal"
                                                class="w-full bg-slate-800 text-white border-slate-700 rounded-lg focus:ring-blue-500 focus:border-blue-500 font-mono text-sm"
                                                placeholder="0.00">
                                            <span class="bg-slate-950 text-slate-400 px-2 py-2 rounded-lg text-xs font-bold min-w-14 text-center border border-slate-800">
                                                {{ getPercent(gasto.amount) }}%
                                            </span>
                                            <button @click="removeFixedRow(index)"
                                                class="bg-red-500/10 text-red-500 border border-red-500/20 hover:bg-red-500 hover:text-white px-3 py-2 rounded-lg font-bold transition-colors">✖</button>
                                        </div>
                                    </div>
                                </div>

                                <button @click="addFixedRow"
                                    class="w-full bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 font-bold py-3 px-4 rounded-xl transition-colors mb-6 text-sm">
                                    + Añadir Suministro Fijo
                                </button>

                                <div class="bg-red-900/20 border-l-4 border-red-500 p-4 rounded-xl flex flex-col sm:flex-row justify-between items-center shadow-inner">
                                    <h3 class="text-red-400 font-bold text-sm uppercase tracking-wider mb-2 sm:mb-0">Munición Comprometida:</h3>
                                    <p class="text-2xl font-black text-red-500 font-mono">{{ formatCurrency(totalFixed, budgetCurrency) }}</p>
                                </div>
                            </div>

                            <!-- 3. Active debts toggle -->
                            <div v-if="sameCurrencyDebts > 0"
                                class="mb-8 bg-amber-900/20 p-5 rounded-2xl border border-amber-500/30 flex flex-col sm:flex-row items-center justify-between gap-4 shadow-inner">
                                <div>
                                    <h4 class="font-bold text-amber-400 flex items-center gap-2">⚠️ Amenazas Activas</h4>
                                    <p class="text-sm text-amber-200/70 mt-1">Tienes <strong class="text-amber-300 font-mono">{{ formatCurrency(sameCurrencyDebts, budgetCurrency) }}</strong> en deudas registradas en esta moneda.</p>
                                </div>
                                <p class="text-sm text-amber-200/70">Los pagos se descuentan al registrarlos en Deudas.</p>
                            </div>

                            <!-- Capital libre display -->
                            <div class="mb-8">
                                <div :class="['p-8 rounded-3xl text-center border transition-all shadow-lg relative overflow-hidden',
                                    remaining > 0 ? 'bg-blue-900/20 border-blue-500/50' : (remaining === 0 ? 'bg-slate-900 border-slate-700' : 'bg-red-900/20 border-red-500/50')]">
                                    <div v-if="remaining > 0" class="absolute top-0 left-0 w-full h-1 bg-linear-to-r from-blue-500 to-indigo-500"></div>
                                    <h3 class="text-xs font-black uppercase tracking-widest mb-3"
                                        :class="remaining > 0 ? 'text-blue-400' : (remaining === 0 ? 'text-slate-500' : 'text-red-400')">
                                        Disponible estimado
                                    </h3>
                                    <div class="text-5xl font-black font-mono tracking-tight"
                                        :class="remaining > 0 ? 'text-white' : (remaining === 0 ? 'text-slate-600' : 'text-red-500')">
                                        {{ formatCurrency(remaining, budgetCurrency) }}
                                    </div>
                                </div>
                            </div>

                            <!-- Action buttons -->
                            <div class="pt-6 border-t border-slate-800 flex flex-col sm:flex-row gap-4">
                                <button @click="saveBudget"
                                    :disabled="isSubmitting"
                                    :class="{ 'opacity-70 cursor-wait pointer-events-none': isSubmitting }"
                                    class="w-full inline-flex items-center justify-center gap-2 px-6 py-4 rounded-xl font-black uppercase tracking-widest transition-all duration-300 ease-out hover:scale-105 hover:-translate-y-0.5 bg-blue-600 hover:bg-blue-500 text-white shadow-[0_0_15px_rgba(37,99,235,0.4)]">
                                    💾 Guardar Plan
                                </button>
                                <button @click="downloadAsCsv"
                                    class="w-full inline-flex items-center justify-center gap-2 px-6 py-4 rounded-xl font-bold uppercase tracking-wider transition-all duration-300 ease-out hover:scale-105 bg-transparent hover:bg-slate-800 text-slate-300 border border-slate-600 hover:border-slate-500">
                                    📊 Extraer Datos
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- ── RIGHT: Recent history ── -->
                    <div class="lg:col-span-4">
                        <div class="sticky top-24">
                            <div class="bg-slate-900/80 backdrop-blur-xs border border-slate-700/60 ring-1 ring-white/5 sm:rounded-3xl shadow-xl p-6 relative overflow-hidden">
                                <div class="absolute top-0 left-0 right-0 h-[2px] bg-amber-500/50"></div>
                                <h2 class="text-lg font-black text-white mb-6 flex items-center gap-2 uppercase tracking-widest">
                                    🗂️ Historial Reciente
                                </h2>

                                <div v-if="budgets && budgets.length > 0"
                                    class="flex flex-col gap-4 max-h-[600px] overflow-y-auto pr-2">
                                    <div v-for="budget in budgets" :key="budget.id"
                                        @click="openBudgetDetails(budget)"
                                        class="p-5 border border-slate-700/50 rounded-2xl bg-slate-950 hover:bg-slate-800 hover:border-slate-600 transition-all cursor-pointer shadow-inner group">
                                        <h3 class="font-bold text-sm text-blue-400 mb-3 group-hover:text-blue-300">{{ budget.title }}</h3>
                                        <div class="space-y-2">
                                            <p class="text-xs text-slate-300 flex justify-between border-b border-slate-800/50 pb-2">
                                                <span class="font-bold text-slate-500">Ingreso:</span>
                                                <span class="font-mono">{{ formatCurrency(budget.income, budget.currency) }}</span>
                                            </p>
                                            <p class="text-xs text-slate-300 flex justify-between">
                                                <span class="font-bold text-slate-500">G. Fijos:</span>
                                                <span class="font-mono text-red-400">{{ formatCurrency(budget.fixed_expenses_total, budget.currency) }}</span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-center py-10">
                                    <p class="text-slate-500 text-sm">No hay registros de combate aún.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Budget detail modal ── -->
        <Transition
            enter-active-class="ease-out duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100"
            leave-active-class="ease-in duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center px-4">
                <div class="absolute inset-0 bg-slate-950/90 backdrop-blur-md" @click="closeModal"></div>
                <Transition enter-active-class="ease-out duration-200" enter-from-class="opacity-0 scale-95 translate-y-4"
                    enter-to-class="opacity-100 scale-100 translate-y-0" appear>
                    <div class="relative w-full max-w-lg bg-slate-900 border border-slate-700 rounded-3xl shadow-2xl overflow-hidden">
                        <div class="h-1 w-full bg-linear-to-r from-blue-600 via-indigo-500 to-violet-600"></div>
                        <div class="px-6 pt-6 pb-4 sm:p-8">
                            <h3 class="text-xl font-black text-white mb-6 flex justify-between items-center border-b border-slate-700 pb-4">
                                <span>📄 {{ selectedBudget.title }}</span>
                                <button @click="closeModal" class="text-slate-500 hover:text-red-500 transition-colors text-2xl leading-none">&times;</button>
                            </h3>
                            <div class="flex justify-between bg-slate-950 p-4 rounded-xl border border-slate-800 mb-6 shadow-inner">
                                <span class="text-slate-400 font-bold uppercase text-xs tracking-wider">Ingreso Total:</span>
                                <span class="text-blue-400 font-black font-mono text-lg">{{ formatCurrency(selectedBudget.income, selectedBudget.currency) }}</span>
                            </div>
                            <h4 class="font-bold text-slate-300 text-xs uppercase tracking-widest mb-3">Suministros Consumidos:</h4>
                            <ul class="space-y-2 mb-4 max-h-48 overflow-y-auto pr-2">
                                <li v-for="item in selectedBudget.details.fixed" :key="item.name"
                                    class="flex justify-between text-sm bg-slate-800/50 p-3 rounded-lg border border-slate-700/50">
                                    <span class="text-slate-300 font-medium">{{ item.name }}</span>
                                    <span class="text-red-400 font-bold font-mono">{{ formatCurrency(item.amount, selectedBudget.currency) }}</span>
                                </li>
                            </ul>
                            <div v-if="selectedBudget.details.debts_deducted > 0"
                                class="flex justify-between items-center text-sm bg-amber-900/20 p-4 rounded-xl border border-amber-500/30 mt-4">
                                <span class="text-amber-400 font-bold uppercase text-xs">Ataque a Jefes:</span>
                                <span class="text-amber-500 font-black font-mono">- {{ formatCurrency(selectedBudget.details.debts_deducted, selectedBudget.currency) }}</span>
                            </div>
                            <ul v-if="selectedBudget.details.expenses?.length" class="mt-4 space-y-2">
                                <li v-for="expense in selectedBudget.details.expenses" :key="expense.id" class="rounded-lg bg-slate-800 p-3 text-sm">
                                    <div class="flex justify-between gap-3"><span>{{ expense.description }}</span><span>{{ formatCurrency(expense.amount, expense.currency) }}</span></div>
                                    <p class="mt-1 text-xs text-slate-400">{{ expense.deducted ? 'Descontado del disponible' : 'Ya incluido en gastos fijos; sin otro descuento' }}</p>
                                </li>
                            </ul>
                            <div class="mt-6 p-5 bg-blue-900/20 rounded-2xl flex justify-between items-center border border-blue-500/50 shadow-[0_0_15px_rgba(37,99,235,0.1)]">
                                <span class="font-bold text-blue-400 uppercase text-xs tracking-wider">Capital Libre:</span>
                                <span class="font-black text-white font-mono text-2xl">{{ formatCurrency(selectedBudget.details.remaining, selectedBudget.currency) }}</span>
                            </div>
                        </div>
                        <div class="bg-slate-950 px-6 py-4 flex justify-end border-t border-slate-800">
                            <button @click="closeModal"
                                class="bg-transparent hover:bg-slate-800 border border-slate-600 text-white font-bold py-2 px-8 rounded-xl transition-colors">
                                Cerrar Archivo
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>

        <CombatLog :show="notification.show" :message="notification.message" :type="notification.type" />

    </AuthenticatedLayout>
</template>

<style scoped>
.dot { transition: transform 0.2s; }
</style>

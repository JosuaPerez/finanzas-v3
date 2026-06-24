<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CombatLog from '@/Components/CombatLog.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { formatMoney as fmtMoney, getSymbol } from '@/composables/useDebtUtils';

const props = defineProps({
    budgets:         Array,
    defeated_bosses: { type: Array, default: () => [] },
});

// Re-export the shared formatter (keeps existing template bindings working)
const formatMoney = (amount) => Number(amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

/**
 * Returns the most meaningful "Max HP" figure for a defeated boss.
 * Loans  → original_amount  (the full principal at inception)
 * Cards  → credit_limit     (the approved spending ceiling)
 */
const getMaxHp = (debt) => {
    if (debt.type === 'loan')        return Number(debt.original_amount) || 0;
    if (debt.type === 'credit_card') return Number(debt.credit_limit)    || 0;
    return 0;
};

// 🛠️ FUNCIONES DE DESCOMPRESIÓN SEGURAS
const parseDetails = (details) => {
    if (!details) return { fixed: [], remaining: 0, debt_payments: [] };
    if (typeof details === 'string') {
        try { return JSON.parse(details); } catch (e) { return { fixed: [], remaining: 0, debt_payments: [] }; }
    }
    return details;
};

const getRemaining = (details) => parseDetails(details).remaining || 0;

const getDebtPaymentsTotal = (details) => {
    const parsed = parseDetails(details);
    if (!parsed.debt_payments) return 0;
    return parsed.debt_payments.reduce((sum, p) => sum + Number(p.amount), 0);
};

// 🛠️ LÓGICA DEL MODAL
const showModal = ref(false);
const selectedBudget = ref(null);

const notification = ref({ show: false, message: '', type: 'success' });
const showNotification = (message, type = 'success') => {
    notification.value = { show: true, message, type };
    setTimeout(() => { notification.value.show = false; }, 4000);
};

const openDetails = (budget) => {
    selectedBudget.value = { ...budget, parsedDetails: parseDetails(budget.details) };
    showModal.value = true;
};
const closeModal = () => { showModal.value = false; selectedBudget.value = null; };

const downloadExcel = (id) => {
    showNotification('Generando reporte de batalla...', 'success');
    window.location.href = route('budgets.export', id);
};
</script>

<template>

    <Head title="Sala de Archivos" />

    <AuthenticatedLayout>
        <!-- <template #header>
            <div class="flex items-center justify-between w-full">
                <h2 class="font-black text-lg sm:text-2xl text-white uppercase tracking-widest flex items-center gap-2">
                    🗂️ Sala de Archivos
                </h2>

                <Link :href="route('dashboard')"
                      class="shrink-0 flex items-center gap-2 sm:gap-3 bg-slate-900 border border-slate-700 hover:border-blue-500/50 text-slate-300 hover:text-white px-4 sm:px-5 py-2 sm:py-2.5 rounded-xl sm:rounded-2xl transition-all shadow-lg hover:shadow-[0_0_15px_rgba(59,130,246,0.3)] group">
                    <span class="text-lg sm:text-xl group-hover:-translate-x-1 transition-transform">❮</span>
                    <div class="flex-col text-left leading-none hidden sm:flex">
                        <span class="text-[10px] font-black uppercase tracking-widest text-blue-500">Comando</span>
                        <span class="text-xs font-bold uppercase tracking-tight">Retornar a Base</span>
                    </div>
                </Link>
            </div>
        </template> -->

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

                <PageHeader
                    subtitle="SALA DE ARCHIVOS"
                    title="🗂️ Registros de Guerra"
                    description="Revisa el historial de tus batallas pasadas y presupuestos guardados."
                />

                <!-- Main panel — unified dark glassmorphism -->
                <div class="bg-slate-900/80 backdrop-blur-sm overflow-hidden shadow-2xl sm:rounded-3xl p-6 md:p-8 border border-slate-700/60 ring-1 ring-white/5 relative">

                    <!-- Ambient accent line -->
                    <div class="absolute top-0 left-0 right-0 h-[2px] bg-gradient-to-r from-transparent via-amber-500/50 to-transparent"></div>

                    <!-- record count badge -->
                    <div v-if="budgets && budgets.length > 0" class="mb-6 text-[10px] font-black uppercase tracking-widest text-slate-500 bg-slate-800/60 border border-slate-700/50 px-3 py-1.5 rounded-lg w-fit">
                        {{ budgets.length }} {{ budgets.length === 1 ? 'registro' : 'registros' }}
                    </div>

                    <!-- Budget grid -->
                    <div v-if="budgets && budgets.length > 0"
                        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

                        <div v-for="budget in budgets" :key="budget.id"
                            class="bg-slate-900/80 backdrop-blur-sm border border-slate-700/60 ring-1 ring-white/5 rounded-2xl shadow-xl transition-all duration-300 hover:-translate-y-1 hover:shadow-[0_8px_30px_rgba(0,0,0,0.4)] overflow-hidden flex flex-col group">

                            <!-- Card header -->
                            <div class="bg-slate-950/50 border-b border-slate-800/80 p-5 relative overflow-hidden">
                                <div class="absolute top-0 left-0 right-0 h-[1px] bg-gradient-to-r from-transparent via-amber-500/30 to-transparent"></div>
                                <h3 class="font-black text-base text-white mb-1 truncate">📄 {{ budget.title }}</h3>
                                <p class="text-[10px] text-slate-500 font-black uppercase tracking-widest">
                                    Guardado: {{ new Date(budget.created_at).toLocaleDateString('es-DO') }}
                                </p>
                            </div>

                            <!-- Card body -->
                            <div class="p-5 flex-grow space-y-3">
                                <div class="flex justify-between items-center pb-3 border-b border-slate-800/60">
                                    <span class="text-slate-400 font-bold text-xs uppercase tracking-wider">Ingreso Quincenal</span>
                                    <span class="text-white font-black font-mono text-sm">RD$ {{ formatMoney(budget.income) }}</span>
                                </div>
                                <div class="flex justify-between items-center pb-3 border-b border-slate-800/60">
                                    <span class="text-slate-400 font-bold text-xs uppercase tracking-wider">Gastos Fijos</span>
                                    <span class="text-red-400 font-black font-mono text-sm">- RD$ {{ formatMoney(budget.fixed_expenses_total) }}</span>
                                </div>

                                <div v-if="getDebtPaymentsTotal(budget.details) > 0"
                                    class="flex justify-between items-center pb-3 border-b border-slate-800/60">
                                    <span class="text-red-400 font-black text-xs uppercase tracking-wider flex items-center gap-1">⚔️ Ataques a Deudas</span>
                                    <span class="text-red-400 font-black font-mono text-sm">- RD$ {{ formatMoney(getDebtPaymentsTotal(budget.details)) }}</span>
                                </div>

                                <!-- Capital libre (highlighted row) -->
                                <div class="flex justify-between items-center bg-blue-500/10 border border-blue-500/20 p-3 rounded-xl">
                                    <span class="text-blue-400 font-black text-xs uppercase tracking-wider">💰 Capital Libre</span>
                                    <span class="text-blue-300 font-black font-mono">RD$ {{ formatMoney(getRemaining(budget.details)) }}</span>
                                </div>
                            </div>

                            <!-- Card actions -->
                            <div class="p-4 pt-0 mt-auto flex gap-3">
                                <button @click="openDetails(budget)"
                                    class="flex-1 inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl font-black uppercase tracking-widest text-xs transition-all duration-300 ease-out hover:scale-105 hover:-translate-y-0.5 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 hover:border-slate-500">
                                    🔍 Detalles
                                </button>
                                <button @click="downloadExcel(budget.id)"
                                    class="flex-1 inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl font-black uppercase tracking-widest text-xs transition-all duration-300 ease-out hover:scale-105 hover:-translate-y-0.5 bg-emerald-600 hover:bg-emerald-500 text-white shadow-[0_0_12px_rgba(16,185,129,0.3)]">
                                    📊 Excel
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Empty state -->
                    <div v-else
                        class="text-center p-16 border-2 border-dashed border-slate-700/60 rounded-3xl bg-slate-950/30 mt-2">
                        <div class="text-6xl mb-4 opacity-50">🗄️</div>
                        <h3 class="text-2xl font-black text-white tracking-tight">Sala de Archivos Vacía</h3>
                        <p class="text-slate-400 mt-2 font-medium text-sm">Aún no hay presupuestos guardados. Ve al <span class="text-blue-400 font-bold">Presupuesto</span> y crea tu primer plan de batalla.</p>
                    </div>

                </div>
            </div>

            <!-- ══════════════════════════════════════════════════════════════
                 💀 HALL OF FAME — Enemigos Vencidos
                 Shown only when at least one debt has been fully paid off.
            ═══════════════════════════════════════════════════════════════ -->
            <div v-if="defeated_bosses.length > 0" class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-8">

                <!-- Section panel -->
                <div class="bg-slate-900/80 backdrop-blur-sm overflow-hidden shadow-2xl sm:rounded-3xl p-6 md:p-8 border border-emerald-900/40 ring-1 ring-white/5 relative">

                    <!-- Victory accent line (emerald instead of amber) -->
                    <div class="absolute top-0 left-0 right-0 h-[2px] bg-gradient-to-r from-transparent via-emerald-500/50 to-transparent"></div>

                    <!-- Section header -->
                    <div class="flex items-start justify-between mb-6">
                        <div>
                            <p class="text-[10px] font-black text-emerald-600 uppercase tracking-[0.2em] mb-1">SALA DE TROFEOS</p>
                            <h2 class="text-2xl font-black text-white tracking-tight">💀 Enemigos Vencidos</h2>
                            <p class="text-slate-400 text-sm font-medium mt-1">Jefes que cayeron ante tu disciplina financiera.</p>
                        </div>
                        <div class="text-[10px] font-black uppercase tracking-widest text-emerald-400 bg-emerald-900/30 border border-emerald-500/30 px-3 py-1.5 rounded-lg shrink-0 ml-4">
                            {{ defeated_bosses.length }} {{ defeated_bosses.length === 1 ? 'jefe' : 'jefes' }} eliminados
                        </div>
                    </div>

                    <!-- Trophy cards grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div
                            v-for="boss in defeated_bosses"
                            :key="boss.id"
                            class="bg-slate-950/60 border border-emerald-900/50 rounded-2xl p-4 flex items-start gap-4 hover:border-emerald-700/60 transition-all duration-300 group relative overflow-hidden"
                        >
                            <!-- Subtle inner glow on hover -->
                            <div class="absolute inset-0 bg-emerald-500/0 group-hover:bg-emerald-500/5 transition-colors duration-300 rounded-2xl pointer-events-none"></div>

                            <!-- Type icon -->
                            <div class="text-3xl shrink-0 relative z-10">
                                {{ boss.type === 'credit_card' ? '👾' : '👹' }}
                            </div>

                            <!-- Info -->
                            <div class="flex-1 min-w-0 relative z-10">
                                <p class="font-black text-white truncate text-sm">{{ boss.name }}</p>
                                <p class="text-[10px] uppercase tracking-widest text-slate-500 font-bold mt-0.5">
                                    {{ boss.type === 'credit_card' ? 'Tarjeta de Crédito' : 'Préstamo' }}
                                    &middot; {{ boss.currency }}
                                </p>

                                <!-- Max HP row -->
                                <div v-if="getMaxHp(boss) > 0" class="mt-2.5 flex items-center gap-1.5">
                                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Max HP:</span>
                                    <span class="text-xs text-slate-200 font-black font-mono">
                                        {{ getSymbol(boss.currency) }} {{ formatMoney(getMaxHp(boss)) }}
                                    </span>
                                </div>

                                <!-- Defeat date -->
                                <p class="text-[10px] text-slate-600 font-bold mt-1.5 uppercase tracking-wider">
                                    Derrotado: {{ new Date(boss.updated_at).toLocaleDateString('es-DO') }}
                                </p>
                            </div>

                            <!-- Vencido badge -->
                            <span class="relative z-10 shrink-0 self-start text-[10px] font-black uppercase tracking-widest bg-emerald-900/50 text-emerald-400 border border-emerald-500/40 px-2 py-1 rounded-lg whitespace-nowrap">
                                ✓ Vencido
                            </span>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- ===================== MODAL DE DETALLES ===================== -->
        <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog"
            aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-slate-950/90 transition-opacity backdrop-blur-md" @click="closeModal"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div
                    class="inline-block align-bottom bg-slate-900 rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-700">

                    <!-- Modal header -->
                    <div class="bg-slate-900 px-6 pt-8 pb-4 border-b border-slate-800">
                        <h3 class="text-xl leading-6 font-black text-white flex justify-between items-start tracking-tight">
                            <span>📄 {{ selectedBudget.title }}</span>
                            <button @click="closeModal"
                                class="text-slate-500 hover:text-red-400 transition-colors ml-4 shrink-0 text-2xl leading-none">&times;</button>
                        </h3>
                    </div>

                    <!-- Modal body -->
                    <div class="bg-slate-900 px-6 py-6">
                        <div class="flex justify-between bg-slate-950 p-4 rounded-xl border border-slate-800 mb-6 shadow-inner">
                            <span class="text-slate-400 font-black uppercase text-xs tracking-wider">Ingreso Total:</span>
                            <span class="text-blue-400 font-black font-mono text-lg">RD$ {{ formatMoney(selectedBudget.income) }}</span>
                        </div>

                        <h4 class="font-black text-slate-300 text-xs uppercase tracking-widest mb-3">📉 Suministros Consumidos:</h4>
                        <ul class="space-y-2 mb-4 max-h-48 overflow-y-auto pr-1">
                            <li v-for="item in selectedBudget.parsedDetails.fixed" :key="item.name"
                                class="flex justify-between text-sm bg-slate-800/50 p-3 rounded-xl border border-slate-700/50">
                                <span class="text-slate-300 font-medium">{{ item.name }}</span>
                                <span class="text-red-400 font-black font-mono">RD$ {{ formatMoney(item.amount) }}</span>
                            </li>
                        </ul>

                        <div v-if="selectedBudget.parsedDetails.debt_payments && selectedBudget.parsedDetails.debt_payments.length > 0">
                            <h4 class="font-black text-red-400 text-xs uppercase tracking-widest mb-3 mt-5">⚔️ Municiones Disparadas (Deudas):</h4>
                            <ul class="space-y-2 mb-4">
                                <li v-for="pago in selectedBudget.parsedDetails.debt_payments" :key="pago.name"
                                    class="flex justify-between text-sm bg-red-900/20 p-3 rounded-xl border border-red-500/20">
                                    <span class="text-red-300 font-bold">{{ pago.name }}</span>
                                    <span class="text-red-400 font-black font-mono">- RD$ {{ formatMoney(pago.amount) }}</span>
                                </li>
                            </ul>
                        </div>

                        <!-- Capital libre -->
                        <div class="mt-6 p-5 bg-blue-900/20 rounded-2xl flex justify-between items-center border border-blue-500/50 shadow-[0_0_15px_rgba(37,99,235,0.1)]">
                            <span class="font-black text-blue-400 uppercase text-xs tracking-wider">💰 Capital Libre Restante:</span>
                            <span class="font-black text-white font-mono text-2xl">RD$ {{ formatMoney(selectedBudget.parsedDetails.remaining) }}</span>
                        </div>
                    </div>

                    <!-- Modal footer -->
                    <div class="bg-slate-950 px-6 py-4 flex justify-end gap-3 border-t border-slate-800">
                        <button @click="downloadExcel(selectedBudget.id)"
                            class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl font-black uppercase tracking-widest text-xs transition-all duration-300 ease-out hover:scale-105 hover:-translate-y-0.5 bg-emerald-600 hover:bg-emerald-500 text-white shadow-[0_0_12px_rgba(16,185,129,0.3)]">
                            📊 Descargar Excel
                        </button>
                        <button @click="closeModal"
                            class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl font-bold uppercase tracking-wider text-xs transition-all duration-300 ease-out hover:scale-105 bg-transparent hover:bg-slate-800 text-slate-300 border border-slate-600 hover:border-slate-500">
                            Cerrar Archivo
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Combat Log HUD -->
        <CombatLog
            :show="notification.show"
            :message="notification.message"
            :type="notification.type"
        />

    </AuthenticatedLayout>
</template>
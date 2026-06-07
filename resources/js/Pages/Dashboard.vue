<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CombatLog from '@/Components/CombatLog.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import { formatCurrency } from '@/utils'; // La nueva herramienta global

// --- LÓGICA DE ONBOARDING (TUTORIAL) ---
const showOnboarding = ref(false);

onMounted(() => {
    // Si el usuario no tiene presupuestos guardados y no ha visto el tutorial en este navegador
    if (props.budgets.length === 0 && !localStorage.getItem('onboarding_completado')) {
        showOnboarding.value = true;
    }
});

const aceptarMision = () => {
    // Guardamos en la memoria del navegador que ya vio el tutorial
    localStorage.setItem('onboarding_completado', 'true');
    showOnboarding.value = false;
    showNotification('¡Misión aceptada! Bienvenido al campo de batalla.', 'success');
};

const props = defineProps({
    budgets: Array,
    totalDebts: { type: Number, default: 0 } // NUEVO: Recibimos las deudas activas desde Laravel
});

const income = ref('');
const fixedExpenses = ref([
    { id: 1, name: 'Casa / Alquiler', amount: '' },
    { id: 2, name: 'Comida / Supermercado', amount: '' },
    { id: 3, name: 'Luz / Servicios', amount: '' },
    { id: 4, name: 'Transporte / Gasolina', amount: '' }
]);

// NUEVO: Interruptor para deducir deudas automáticamente
const deductDebts = ref(false);

const notification = ref({ show: false, message: '', type: 'success' });
const isSubmitting = ref(false);
const showNotification = (message, type = 'success') => {
    notification.value = { show: true, message, type };
    setTimeout(() => { notification.value.show = false; }, 4000);
};

const vMoney = {
    mounted: (el) => {
        el.addEventListener('input', (e) => {
            // Si el evento fue disparado por nuestro propio código, lo ignoramos para evitar bucles
            if (!e.isTrusted) return;

            // 1. Guardamos la posición exacta del cursor
            let cursorPosition = el.selectionStart;
            let oldLength = el.value.length;

            // 2. Limpiamos todo el texto: dejamos solo números y puntos decimales
            let val = el.value.replace(/[^\d.]/g, '');

            // 3. Separamos los decimales (por si el usuario escribe un punto)
            let parts = val.split('.');

            // 4. Agregamos las comas automáticamente a la parte de los miles
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');

            // Volvemos a unir los enteros con los decimales
            let formatted = parts.join('.');

            // 5. Aplicamos el formato al instante
            if (el.value !== formatted) {
                el.value = formatted;

                // 6. Magia táctica: Ajustamos el cursor para que no salte al final al aparecer la coma
                cursorPosition += (formatted.length - oldLength);
                el.setSelectionRange(cursorPosition, cursorPosition);

                // 7. Le avisamos a Vue (v-model) que actualice sus cálculos internos
                el.dispatchEvent(new Event('input'));
            }
        });
    }
};

const cleanNum = (val) => {
    if (val === null || val === undefined || val === '') return 0;
    return parseFloat(String(val).replace(/,/g, '')) || 0;
};

// --- CÁLCULOS AUTOMÁTICOS ACTUALIZADOS ---
const totalFixed = computed(() => fixedExpenses.value.reduce((sum, item) => sum + cleanNum(item.amount), 0));

// NUEVO: El capital restante ahora resta las deudas SI el interruptor está encendido
const remaining = computed(() => {
    let base = cleanNum(income.value) - totalFixed.value;
    if (deductDebts.value) {
        base -= props.totalDebts;
    }
    return base;
});

const addFixedRow = () => fixedExpenses.value.push({ id: Date.now(), name: '', amount: '' });
const removeFixedRow = (index) => fixedExpenses.value.splice(index, 1);

const getPercentOfIncome = (amount) => cleanNum(income.value) > 0 ? ((cleanNum(amount) / cleanNum(income.value)) * 100).toFixed(1) : 0;

// --- LÓGICA DEL MODAL ---
const showModal = ref(false);
const selectedBudget = ref(null);

const openBudgetDetails = (budget) => {
    let parsedDetails = budget.details;
    if (typeof parsedDetails === 'string') parsedDetails = JSON.parse(parsedDetails);
    selectedBudget.value = { ...budget, details: parsedDetails };
    showModal.value = true;
};
const closeModal = () => {
    showModal.value = false;
    selectedBudget.value = null;
};

// --- GUARDAR EN LA BASE DE DATOS ---
const saveBudget = () => {
    if (cleanNum(income.value) <= 0) {
        showNotification("Ingresa tu quincena para poder guardar el presupuesto.", "error");
        return;
    }

    const payload = {
        title: `Quincena del ${new Date().toLocaleDateString('es-DO')}`,
        income: cleanNum(income.value),
        fixed_expenses_total: totalFixed.value,
        details: {
            fixed: fixedExpenses.value.map(item => ({ name: item.name, amount: cleanNum(item.amount) })),
            debts_deducted: deductDebts.value ? props.totalDebts : 0, // Guardamos si se dedujeron deudas
            remaining: remaining.value
        }
    };

    isSubmitting.value = true;
    router.post(route('budgets.store'), payload, {
        preserveScroll: true,
        onSuccess: () => showNotification('¡Presupuesto guardado! Tu capital libre está listo.', 'success'),
        onFinish: () => { isSubmitting.value = false; }
    });
};

const downloadAsCsv = () => {
    showNotification('Generando Excel...', 'success');
    window.location.href = route('budgets.export');
};
</script>

<template>

    <Head title="Presupuesto" />

    <transition enter-active-class="transition ease-out duration-500"
        enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        enter-to-class="opacity-100 translate-y-0 sm:scale-100" leave-active-class="transition ease-in duration-300"
        leave-from-class="opacity-100 translate-y-0 sm:scale-100"
        leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">

        <div v-if="showOnboarding"
            class="fixed inset-0 z-[100] bg-slate-950 flex items-center justify-center p-4 sm:p-6 overflow-y-auto">
            <div class="absolute top-0 left-0 w-full h-full overflow-hidden pointer-events-none">
                <div
                    class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-blue-600 rounded-full mix-blend-screen filter blur-[120px] opacity-20">
                </div>
                <div
                    class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-red-600 rounded-full mix-blend-screen filter blur-[120px] opacity-20">
                </div>
            </div>

            <div
                class="relative w-full max-w-4xl bg-slate-900/90 backdrop-blur-xl border border-slate-700 rounded-3xl shadow-[0_0_50px_rgba(0,0,0,0.5)] overflow-hidden flex flex-col md:flex-row">

                <div
                    class="w-full md:w-5/12 bg-slate-950/50 p-8 md:p-12 flex flex-col justify-center border-r border-slate-800">
                    <div
                        class="w-16 h-16 bg-blue-500/20 text-blue-400 rounded-2xl flex items-center justify-center text-4xl mb-6 shadow-inner border border-blue-500/30">
                        ⚔️</div>
                    <h1 class="text-3xl md:text-4xl font-black text-white leading-tight mb-4 tracking-tight">Maneja tus
                        finanzas como un videojuego.</h1>
                    <p class="text-slate-400 text-lg leading-relaxed mb-8">Se acabó el estrés de los números. Aquí tu
                        economía es un campo de batalla, y tú eres el Comandante.</p>
                    <button @click="aceptarMision"
                        class="w-full inline-flex items-center justify-center gap-2 px-8 py-4 rounded-xl font-black uppercase tracking-widest transition-all duration-300 ease-out hover:scale-105 hover:-translate-y-0.5 bg-blue-600 hover:bg-blue-500 text-white shadow-[0_0_20px_rgba(37,99,235,0.4)] text-lg">
                        Aceptar Misión
                    </button>
                </div>

                <div class="w-full md:w-7/12 p-8 md:p-12 flex flex-col justify-center gap-8">
                    <div class="flex items-start gap-5">
                        <div
                            class="flex-shrink-0 w-12 h-12 bg-blue-900/50 border border-blue-500/30 rounded-full flex items-center justify-center text-xl shadow-lg">
                            🛡️</div>
                        <div>
                            <h3 class="text-white font-bold text-xl mb-1">1. Organiza tus Suministros</h3>
                            <p class="text-slate-400 text-sm leading-relaxed">En la pestaña <span
                                    class="text-blue-400 font-semibold">Presupuesto</span>, distribuyes tu salario antes
                                del ataque. Asegura tu munición (gastos fijos) para ver cuánto capital libre te queda.
                            </p>
                        </div>
                    </div>
                    <div class="flex items-start gap-5">
                        <div
                            class="flex-shrink-0 w-12 h-12 bg-red-900/50 border border-red-500/30 rounded-full flex items-center justify-center text-xl shadow-lg">
                            🔥</div>
                        <div>
                            <h3 class="text-white font-bold text-xl mb-1">2. Derrota a los Jefes</h3>
                            <p class="text-slate-400 text-sm leading-relaxed">Olvida las tablas aburridas. En <span
                                    class="text-red-400 font-semibold">Deudas</span>, cada préstamo es un enemigo con
                                una barra de vida (HP). Hazles daño crítico con cada pago hasta eliminarlos.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-5">
                        <div
                            class="flex-shrink-0 w-12 h-12 bg-emerald-900/50 border border-emerald-500/30 rounded-full flex items-center justify-center text-xl shadow-lg">
                            🎯</div>
                        <div>
                            <h3 class="text-white font-bold text-xl mb-1">3. Craftea tu Inventario</h3>
                            <p class="text-slate-400 text-sm leading-relaxed">En la armería de <span
                                    class="text-emerald-400 font-semibold">Metas</span>, tus ahorros no son simples
                                números. Son proyectos que vas forjando y subiendo de nivel mes a mes.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </transition>

    <AuthenticatedLayout>
        <template #header>

            <h2 class="font-semibold text-xl text-white leading-tight tracking-tight flex items-center gap-2">
                🛡️ Panel de Finanzas
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

                <div
                    class="mb-10 bg-slate-900 rounded-3xl p-6 sm:p-10 shadow-2xl border border-slate-800 text-white relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-full h-full opacity-20 pointer-events-none">
                        <div
                            class="absolute -top-24 -left-24 w-96 h-96 bg-blue-600 rounded-full mix-blend-screen filter blur-[100px]">
                        </div>
                        <div
                            class="absolute bottom-0 right-0 w-72 h-72 bg-emerald-600 rounded-full mix-blend-screen filter blur-[100px] opacity-30">
                        </div>
                    </div>

                    <div class="relative z-10">
                        <div class="text-center mb-10">
                            <h2 class="text-xs sm:text-sm font-bold text-blue-400 tracking-widest uppercase mb-2">
                                Briefing del
                                Comandante</h2>
                            <h1 class="text-3xl md:text-4xl font-black mb-4">¿Cuál es tu próximo movimiento?</h1>
                            <p class="text-slate-400 max-w-2xl mx-auto font-medium">Mantén tu economía blindada.
                                Organiza tus
                                suministros, mejora tu equipamiento y no dejes que el enemigo tome la iniciativa.</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-6">

                            <!-- Botón 1: Presupuesto -->
                            <a href="#zona-presupuesto"
                                class="group bg-slate-950/50 hover:bg-slate-800 border border-slate-800 hover:border-blue-500 p-4 sm:p-6 rounded-2xl transition-all cursor-pointer flex flex-col items-start text-left shadow-lg hover:-translate-y-1">
                                <div
                                    class="bg-blue-500/20 border border-blue-500/30 p-2 sm:p-3 rounded-xl mb-3 sm:mb-4 group-hover:scale-110 transition-transform">
                                    <span class="text-xl sm:text-2xl">🛡️</span>
                                </div>
                                <h3 class="text-sm sm:text-lg font-bold text-white mb-1 sm:mb-2">Planificar Defensa</h3>
                                <p class="text-[11px] sm:text-xs text-slate-400 leading-relaxed">Distribuye tus
                                    suministros y
                                    capital libre.</p>
                            </a>

                            <!-- Botón 2: Deudas (EL ELEMENTO PERDIDO) -->
                            <Link :href="route('deudas')"
                                class="group bg-slate-950/50 hover:bg-slate-800 border border-slate-800 hover:border-red-500 p-4 sm:p-6 rounded-2xl transition-all cursor-pointer flex flex-col items-start text-left shadow-lg hover:-translate-y-1">
                            <div
                                class="bg-red-500/20 border border-red-500/30 p-2 sm:p-3 rounded-xl mb-3 sm:mb-4 group-hover:scale-110 transition-transform">
                                <span class="text-xl sm:text-2xl">🔥</span>
                            </div>
                            <h3 class="text-sm sm:text-lg font-bold text-white mb-1 sm:mb-2">Atacar Jefes</h3>
                            <p class="text-[11px] sm:text-xs text-slate-400 leading-relaxed">Haz daño crítico a las
                                deudas.</p>
                            </Link>

                            <!-- Botón 3: Metas -->
                            <Link :href="route('metas')"
                                class="group bg-slate-950/50 hover:bg-slate-800 border border-slate-800 hover:border-emerald-500 p-4 sm:p-6 rounded-2xl transition-all cursor-pointer flex flex-col items-start text-left shadow-lg hover:-translate-y-1">
                            <div
                                class="bg-emerald-500/20 border border-emerald-500/30 p-2 sm:p-3 rounded-xl mb-3 sm:mb-4 group-hover:scale-110 transition-transform">
                                <span class="text-xl sm:text-2xl">🎯</span>
                            </div>
                            <h3 class="text-sm sm:text-lg font-bold text-white mb-1 sm:mb-2">Mejorar Arsenal</h3>
                            <p class="text-[11px] sm:text-xs text-slate-400 leading-relaxed">Forja equipamiento fijando
                                objetivos.</p>
                            </Link>

                            <!-- Botón 4: Historial -->
                            <Link :href="route('historial')"
                                class="group bg-slate-950/50 hover:bg-slate-800 border border-slate-800 hover:border-amber-500 p-4 sm:p-6 rounded-2xl transition-all cursor-pointer flex flex-col items-start text-left shadow-lg hover:-translate-y-1">
                            <div
                                class="bg-amber-500/20 border border-amber-500/30 p-2 sm:p-3 rounded-xl mb-3 sm:mb-4 group-hover:scale-110 transition-transform">
                                <span class="text-xl sm:text-2xl">🗂️</span>
                            </div>
                            <h3 class="text-sm sm:text-lg font-bold text-white mb-1 sm:mb-2">Archivos de Guerra</h3>
                            <p class="text-[11px] sm:text-xs text-slate-400 leading-relaxed">Analiza el registro de tus
                                victorias pasadas.</p>
                            </Link>

                        </div>
                    </div>
                </div>

                <div id="zona-presupuesto" class="grid grid-cols-1 lg:grid-cols-12 gap-8 scroll-mt-24">

                    <div class="lg:col-span-8">
                        <div
                            class="bg-slate-900 overflow-hidden shadow-2xl sm:rounded-3xl p-6 md:p-8 border border-slate-800">

                            <div class="mb-8 p-6 bg-slate-950 rounded-2xl border border-slate-800 shadow-inner">
                                <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">1.
                                    Munición
                                    Base (¿Cuánto cobraste?)</label>
                                <div class="relative rounded-xl shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none">
                                        <span class="text-slate-500 font-bold sm:text-xl">RD$</span>
                                    </div>
                                    <input type="text" v-model="income" v-money
                                        class="block w-full bg-slate-800 text-white rounded-xl border-slate-700 pl-16 py-4 text-xl font-mono focus:border-blue-500 focus:ring-blue-500 shadow-inner"
                                        placeholder="0.00">
                                </div>
                            </div>

                            <div class="mb-8 p-6 border border-dashed border-slate-700 rounded-2xl bg-slate-950/50">
                                <h3 class="text-lg font-bold text-white mb-1">2. Suministros Fijos</h3>
                                <p class="text-xs text-slate-400 mb-5">Gastos ineludibles para mantener la base
                                    operativa.</p>

                                <div class="space-y-3 mb-5">
                                    <div v-for="(gasto, index) in fixedExpenses" :key="gasto.id"
                                        class="flex flex-col md:flex-row items-center gap-3 bg-slate-900 p-3 rounded-xl shadow-sm border border-slate-800 transition-all hover:border-slate-600">
                                        <input type="text" v-model="gasto.name"
                                            class="w-full md:w-1/2 border-0 border-b border-slate-700 focus:border-blue-500 focus:ring-0 font-bold text-slate-300 bg-transparent placeholder-slate-600"
                                            placeholder="Concepto (Ej. Luz)">
                                        <div class="flex items-center w-full md:w-1/2 gap-2">
                                            <span class="text-slate-500 font-bold ml-2 md:ml-0">RD$</span>
                                            <input type="text" v-model="gasto.amount" v-money
                                                class="w-full bg-slate-800 text-white border-slate-700 rounded-lg focus:ring-blue-500 focus:border-blue-500 font-mono text-sm"
                                                placeholder="0.00">
                                            <span
                                                class="bg-slate-950 text-slate-400 px-2 py-2 rounded-lg text-xs font-bold min-w-[3.5rem] text-center border border-slate-800">
                                                {{ getPercentOfIncome(gasto.amount) }}%
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

                                <div
                                    class="bg-red-900/20 border-l-4 border-red-500 p-4 rounded-xl flex flex-col sm:flex-row justify-between items-center shadow-inner">
                                    <h3 class="text-red-400 font-bold text-sm uppercase tracking-wider mb-2 sm:mb-0">
                                        Munición
                                        Comprometida:</h3>
                                    <p class="text-2xl font-black text-red-500 font-mono">{{ formatCurrency(totalFixed)
                                    }}</p>
                                </div>
                            </div>

                            <div v-if="totalDebts > 0"
                                class="mb-8 bg-amber-900/20 p-5 rounded-2xl border border-amber-500/30 flex flex-col sm:flex-row items-center justify-between gap-4 shadow-inner">
                                <div>
                                    <h4 class="font-bold text-amber-400 flex items-center gap-2">⚠️ Amenazas Activas
                                    </h4>
                                    <p class="text-sm text-amber-200/70 mt-1">Tienes <strong
                                            class="text-amber-300 font-mono">{{
                                                formatCurrency(totalDebts) }}</strong> en Jefes detectados.</p>
                                </div>
                                <label class="flex items-center cursor-pointer">
                                    <div class="relative">
                                        <input type="checkbox" v-model="deductDebts" class="sr-only">
                                        <div class="block bg-slate-800 border border-slate-600 w-14 h-8 rounded-full transition-colors"
                                            :class="{ 'bg-amber-600 border-amber-500': deductDebts }"></div>
                                        <div class="dot absolute left-1 top-1 bg-white w-6 h-6 rounded-full transition-transform"
                                            :class="{ 'transform translate-x-6': deductDebts }"></div>
                                    </div>
                                    <span class="ml-3 font-bold text-amber-300 text-sm uppercase tracking-wider">Restar
                                        del
                                        capital</span>
                                </label>
                            </div>

                            <div class="mb-8">
                                <div
                                    :class="['p-8 rounded-3xl text-center border transition-all shadow-lg relative overflow-hidden',
                                        remaining > 0 ? 'bg-blue-900/20 border-blue-500/50' : (remaining === 0 ? 'bg-slate-900 border-slate-700' : 'bg-red-900/20 border-red-500/50')]">
                                    <div v-if="remaining > 0"
                                        class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-blue-500 to-indigo-500">
                                    </div>
                                    <h3 class="text-xs font-black uppercase tracking-widest mb-3"
                                        :class="remaining > 0 ? 'text-blue-400' : (remaining === 0 ? 'text-slate-500' : 'text-red-400')">
                                        ⚔️ Tu Capital para la Guerra
                                    </h3>
                                    <div class="text-5xl font-black font-mono tracking-tight"
                                        :class="remaining > 0 ? 'text-white' : (remaining === 0 ? 'text-slate-600' : 'text-red-500')">
                                        {{ formatCurrency(remaining) }}
                                    </div>
                                </div>
                            </div>

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

                    <div class="lg:col-span-4">
                        <div class="sticky top-24">
                            <div
                                class="bg-slate-900 overflow-hidden shadow-2xl sm:rounded-3xl p-6 border border-slate-800 relative">
                                <div class="absolute top-0 left-0 w-full h-1 bg-amber-500/50"></div>
                                <h2
                                    class="text-lg font-black text-white mb-6 flex items-center gap-2 uppercase tracking-widest">
                                    🗂️ Historial Reciente</h2>

                                <div v-if="budgets && budgets.length > 0"
                                    class="flex flex-col gap-4 max-h-[600px] overflow-y-auto pr-2 custom-scrollbar">
                                    <div v-for="budget in budgets" :key="budget.id" @click="openBudgetDetails(budget)"
                                        class="p-5 border border-slate-700/50 rounded-2xl bg-slate-950 hover:bg-slate-800 hover:border-slate-600 transition-all cursor-pointer shadow-inner group">
                                        <h3 class="font-bold text-sm text-blue-400 mb-3 group-hover:text-blue-300">{{
                                            budget.title }}</h3>
                                        <div class="space-y-2">
                                            <p
                                                class="text-xs text-slate-300 flex justify-between border-b border-slate-800/50 pb-2">
                                                <span class="font-bold text-slate-500">Ingreso:</span>
                                                <span class="font-mono">{{ formatCurrency(budget.income) }}</span>
                                            </p>
                                            <p class="text-xs text-slate-300 flex justify-between">
                                                <span class="font-bold text-slate-500">G. Fijos:</span>
                                                <span class="font-mono text-red-400">{{
                                                    formatCurrency(budget.fixed_expenses_total) }}</span>
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

        <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-slate-950/90 transition-opacity backdrop-blur-md" @click="closeModal">
                </div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                <div
                    class="inline-block align-bottom bg-slate-900 rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-700">
                    <div class="bg-slate-900 px-6 pt-6 pb-4 sm:p-8">
                        <h3
                            class="text-xl font-black text-white mb-6 flex justify-between items-center border-b border-slate-700 pb-4">
                            <span>📄 {{ selectedBudget.title }}</span>
                            <button @click="closeModal"
                                class="text-slate-500 hover:text-red-500 transition-colors">&times;</button>
                        </h3>

                        <div
                            class="flex justify-between bg-slate-950 p-4 rounded-xl border border-slate-800 mb-6 shadow-inner">
                            <span class="text-slate-400 font-bold uppercase text-xs tracking-wider">Ingreso
                                Total:</span>
                            <span class="text-blue-400 font-black font-mono text-lg">{{
                                formatCurrency(selectedBudget.income)
                            }}</span>
                        </div>

                        <h4 class="font-bold text-slate-300 text-xs uppercase tracking-widest mb-3">Suministros
                            Consumidos:</h4>
                        <ul class="space-y-2 mb-4 max-h-48 overflow-y-auto pr-2 custom-scrollbar">
                            <li v-for="item in selectedBudget.details.fixed" :key="item.name"
                                class="flex justify-between text-sm bg-slate-800/50 p-3 rounded-lg border border-slate-700/50">
                                <span class="text-slate-300 font-medium">{{ item.name }}</span>
                                <span class="text-red-400 font-bold font-mono">{{ formatCurrency(item.amount) }}</span>
                            </li>
                        </ul>

                        <div v-if="selectedBudget.details.debts_deducted > 0"
                            class="flex justify-between items-center text-sm bg-amber-900/20 p-4 rounded-xl border border-amber-500/30 mt-4">
                            <span class="text-amber-400 font-bold uppercase text-xs">Ataque a Jefes:</span>
                            <span class="text-amber-500 font-black font-mono">- {{
                                formatCurrency(selectedBudget.details.debts_deducted) }}</span>
                        </div>

                        <div
                            class="mt-6 p-5 bg-blue-900/20 rounded-2xl flex justify-between items-center border border-blue-500/50 shadow-[0_0_15px_rgba(37,99,235,0.1)]">
                            <span class="font-bold text-blue-400 uppercase text-xs tracking-wider">Capital Libre:</span>
                            <span class="font-black text-white font-mono text-2xl">{{
                                formatCurrency(selectedBudget.details.remaining) }}</span>
                        </div>
                    </div>
                    <div class="bg-slate-950 px-6 py-4 flex justify-end border-t border-slate-800">
                        <button @click="closeModal"
                            class="bg-transparent hover:bg-slate-800 border border-slate-600 text-white font-bold py-2 px-8 rounded-xl transition-colors">Cerrar
                            Archivo</button>
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
<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    budgets: Array
});

// Cambiamos null por '' para que la directiva de texto funcione mejor
const income = ref('');
const fixedExpenses = ref([
    { id: 1, name: 'Casa / Alquiler', amount: '' },
    { id: 2, name: 'Comida / Supermercado', amount: '' },
    { id: 3, name: 'Luz / Servicios', amount: '' },
    { id: 4, name: 'Transporte / Gasolina', amount: '' }
]);

// --- SISTEMA DE NOTIFICACIONES MODERNAS ---
const notification = ref({ show: false, message: '', type: 'success' });

const showNotification = (message, type = 'success') => {
    notification.value = { show: true, message, type };
    setTimeout(() => { notification.value.show = false; }, 4000); // Desaparece en 4 segundos
};

// ==========================================
// 🧙‍♂️ DIRECTIVA MÁGICA DE FORMATEO (v-money)
// ==========================================
const vMoney = {
    mounted: (el) => {
        const format = () => {
            if (!el.value) return;
            let val = parseFloat(el.value.replace(/,/g, ''));
            if (!isNaN(val)) {
                el.value = val.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                el.dispatchEvent(new Event('input')); // Le avisa a Vue en silencio
            }
        };
        const unformat = () => {
            if (!el.value) return;
            el.value = el.value.replace(/,/g, '');
            el.dispatchEvent(new Event('input'));
        };

        el.addEventListener('blur', format);
        el.addEventListener('focus', unformat);
        setTimeout(format, 100);
    }
};

// Función para limpiar las comas antes de hacer cálculos matemáticos
const cleanNum = (val) => {
    if (val === null || val === undefined || val === '') return 0;
    return parseFloat(String(val).replace(/,/g, '')) || 0;
};
// ==========================================

// --- CÁLCULOS AUTOMÁTICOS (Ahora usando cleanNum) ---
const totalFixed = computed(() => fixedExpenses.value.reduce((sum, item) => sum + cleanNum(item.amount), 0));
const totalFixedPercent = computed(() => cleanNum(income.value) > 0 ? ((totalFixed.value / cleanNum(income.value)) * 100).toFixed(1) : 0);
const remaining = computed(() => cleanNum(income.value) - totalFixed.value);

const addFixedRow = () => fixedExpenses.value.push({ id: Date.now(), name: '', amount: '' });
const removeFixedRow = (index) => fixedExpenses.value.splice(index, 1);

const getPercentOfIncome = (amount) => cleanNum(income.value) > 0 ? ((cleanNum(amount) / cleanNum(income.value)) * 100).toFixed(1) : 0;
const formatMoney = (amount) => Number(amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

// --- LÓGICA DEL MODAL DE HISTORIAL ---
const showModal = ref(false);
const selectedBudget = ref(null);

const openBudgetDetails = (budget) => {
    let parsedDetails = budget.details;
    if (typeof parsedDetails === 'string') {
        parsedDetails = JSON.parse(parsedDetails);
    }
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
            // Limpiamos los arrays antes de guardarlos
            fixed: fixedExpenses.value.map(item => ({ name: item.name, amount: cleanNum(item.amount) })),
            remaining: remaining.value
        }
    };

    router.post(route('budgets.store'), payload, {
        preserveScroll: true,
        onSuccess: () => {
            showNotification('¡Presupuesto guardado! Tu capital libre está listo.', 'success');
        }
    });
};

const downloadAsCsv = () => {
    // Si acaba de hacer cambios, le avisamos que guarde primero
    showNotification('Generando Excel...', 'success');
    
    // Le decimos al navegador que visite la ruta de descarga de Laravel
    window.location.href = route('budgets.export');
};
</script>

<template>

    <Head title="Presupuesto" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Panel de Finanzas</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

                    <div class="lg:col-span-8">
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl p-6 md:p-8">
                            <h1 class="text-2xl font-extrabold text-gray-900 text-center mb-8">🛡️ Presupuesto</h1>

                            <div class="mb-8">
                                <label class="block text-sm font-bold text-gray-700 mb-2">1. ¿Cuánto cobraste?</label>
                                <div class="relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <span class="text-gray-500 font-bold sm:text-lg">RD$</span>
                                    </div>
                                    <input type="text" v-model="income" v-money
                                        class="block w-full rounded-lg border-gray-300 pl-14 py-3 text-lg focus:border-blue-500 focus:ring-blue-500"
                                        placeholder="0.00">
                                </div>
                            </div>

                            <div class="mb-8 p-5 md:p-6 border border-dashed border-gray-300 rounded-xl bg-gray-50">
                                <h3 class="text-lg font-bold text-gray-900">2. Desglosa tus Gastos Fijos</h3>
                                <div class="space-y-3 mb-5 mt-4">
                                    <div v-for="(gasto, index) in fixedExpenses" :key="gasto.id"
                                        class="flex flex-col md:flex-row items-center gap-3 bg-white p-3 rounded-lg shadow-sm border border-gray-200">
                                        <input type="text" v-model="gasto.name"
                                            class="w-full md:w-1/2 border-0 border-b-2 border-transparent focus:border-blue-500 focus:ring-0 font-semibold text-gray-700 bg-transparent"
                                            placeholder="Concepto">
                                        <div class="flex items-center w-full md:w-1/2 gap-2">
                                            <span class="text-gray-500 font-bold ml-2 md:ml-0">RD$</span>
                                            <input type="text" v-model="gasto.amount" v-money
                                                class="w-full border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                                placeholder="0.00">
                                            <span
                                                class="bg-slate-100 text-slate-600 px-3 py-2 rounded-md text-sm font-bold min-w-[3.5rem] text-center">{{
                                                    getPercentOfIncome(gasto.amount) }}%</span>
                                            <button @click="removeFixedRow(index)"
                                                class="bg-red-50 text-red-500 hover:bg-red-500 hover:text-white px-3 py-2 rounded-md font-bold transition-colors">✖</button>
                                        </div>
                                    </div>
                                </div>
                                <button @click="addFixedRow"
                                    class="w-full bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold py-2 px-4 rounded-lg transition-colors">+
                                    Añadir Gasto Fijo</button>
                                <div class="mt-5 text-right font-bold text-gray-800 text-lg">Total Fijos: RD$ {{
                                    formatMoney(totalFixed) }}</div>
                            </div>

                            <div class="mb-8">
                                <div
                                    :class="['p-6 rounded-2xl text-center border-2 transition-all shadow-sm', remaining > 0 ? 'bg-gradient-to-r from-blue-50 to-indigo-50 border-blue-200' : (remaining === 0 ? 'bg-gray-50 border-gray-200' : 'bg-red-50 border-red-200')]">
                                    <h3 class="text-sm font-bold uppercase tracking-wider mb-2"
                                        :class="remaining > 0 ? 'text-blue-600' : 'text-gray-500'">⚔️ Tu Capital para la
                                        Guerra
                                    </h3>
                                    <div class="text-4xl font-extrabold"
                                        :class="remaining > 0 ? 'text-blue-900' : (remaining === 0 ? 'text-gray-700' : 'text-red-600')">
                                        RD$ {{ formatMoney(remaining) }}</div>
                                </div>
                            </div>

                            <div class="pt-6 border-t border-gray-200 flex gap-4">
                                <button @click="saveBudget"
                                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 rounded-xl transition-colors shadow-md">💾
                                    Guardar en la Nube</button>
                                <button @click="downloadAsCsv"
                                    class="w-full bg-green-50 hover:bg-green-100 text-green-700 border border-green-200 font-bold py-4 rounded-xl transition-colors">📊
                                    Exportar a Excel</button>
                            </div>
                        </div>
                    </div>

                    <div class="lg:col-span-4">
                        <div class="sticky top-6">
                            <div
                                class="bg-white overflow-hidden shadow-sm sm:rounded-xl p-6 border-t-4 border-blue-500">
                                <h2 class="text-xl font-extrabold text-gray-900 mb-6 flex items-center gap-2">🗂️
                                    Historial de
                                    Batallas</h2>
                                <div v-if="budgets && budgets.length > 0" class="flex flex-col gap-4">
                                    <div v-for="budget in budgets" :key="budget.id" @click="openBudgetDetails(budget)"
                                        class="p-4 border border-gray-200 rounded-xl bg-gray-50 hover:bg-blue-50 transition-colors cursor-pointer shadow-sm">
                                        <h3 class="font-bold text-md text-blue-700 mb-2">{{ budget.title }}</h3>
                                        <div class="space-y-1">
                                            <p class="text-sm text-gray-600 flex justify-between"><span
                                                    class="font-semibold text-gray-500">Ingreso:</span> <span
                                                    class="text-gray-900">RD$ {{ formatMoney(budget.income) }}</span>
                                            </p>
                                            <p class="text-sm text-gray-600 flex justify-between"><span
                                                    class="font-semibold text-gray-500">G. Fijos:</span> <span
                                                    class="text-gray-900">RD$ {{
                                                    formatMoney(budget.fixed_expenses_total)
                                                    }}</span></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog"
            aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-900/75 transition-opacity backdrop-blur-sm" @click="closeModal"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div
                    class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-100">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-xl leading-6 font-extrabold text-gray-900 mb-4 flex justify-between">📄 {{
                            selectedBudget.title }} <button @click="closeModal"
                                class="text-gray-400 hover:text-red-500">&times;</button></h3>
                        <div class="flex justify-between border-b border-gray-200 pb-3 mb-3"><span
                                class="text-gray-500 font-bold uppercase text-sm">Ingreso Total:</span> <span
                                class="text-blue-700 font-extrabold text-lg">RD$ {{ formatMoney(selectedBudget.income)
                                }}</span>
                        </div>
                        <h4 class="font-bold text-gray-800 mt-5 mb-3">Desglose de Gastos Fijos:</h4>
                        <ul class="space-y-2 mb-2">
                            <li v-for="item in selectedBudget.details.fixed" :key="item.name"
                                class="flex justify-between text-sm bg-gray-50 p-3 rounded-lg border border-gray-100">
                                <span class="text-gray-700 font-medium">{{ item.name }}</span> <span
                                    class="text-gray-900 font-bold">RD$ {{ formatMoney(item.amount) }}</span>
                            </li>
                        </ul>
                        <div
                            class="mt-6 p-4 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl flex justify-between items-center border border-blue-100 shadow-inner">
                            <span class="font-bold text-blue-800">Capital Libre Generado:</span> <span
                                class="font-extrabold text-blue-900 text-xl">RD$ {{
                                    formatMoney(selectedBudget.details.remaining) }}</span>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-4 sm:px-6 flex justify-end">
                        <button @click="closeModal"
                            class="bg-slate-800 hover:bg-slate-900 text-white font-bold py-2 px-6 rounded-lg transition-colors">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>

        <transition enter-active-class="transform ease-out duration-300 transition"
            enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
            enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
            leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100"
            leave-to-class="opacity-0">
            <div v-if="notification.show"
                class="fixed bottom-10 right-10 z-50 px-6 py-4 rounded-xl shadow-2xl font-bold text-white flex items-center gap-3"
                :class="notification.type === 'success' ? 'bg-green-600' : 'bg-red-600'">
                <span class="text-xl">{{ notification.type === 'success' ? '✅' : '⚠️' }}</span>
                {{ notification.message }}
            </div>
        </transition>

    </AuthenticatedLayout>
</template>
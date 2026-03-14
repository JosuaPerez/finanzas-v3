<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

// --- RECIBIR DATOS DEL BACKEND ---
const props = defineProps({
    budgets: Array
});

const showModal = ref(false);
const selectedBudget = ref(null);

const openBudgetDetails = (budget) => {
    // Laravel a veces envía el JSON como string, aquí nos aseguramos de convertirlo a objeto
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

// --- ESTADO (VARIABLES REACTIVAS) ---
const income = ref(null);

const fixedExpenses = ref([
    { id: 1, name: 'Casa / Alquiler', amount: null },
    { id: 2, name: 'Comida / Supermercado', amount: null },
    { id: 3, name: 'Luz / Servicios', amount: null },
    { id: 4, name: 'Transporte / Gasolina', amount: null }
]);

// --- CÁLCULOS AUTOMÁTICOS ---
const totalFixed = computed(() => fixedExpenses.value.reduce((sum, item) => sum + (Number(item.amount) || 0), 0));
const totalFixedPercent = computed(() => income.value > 0 ? ((totalFixed.value / income.value) * 100).toFixed(1) : 0);
const remaining = computed(() => (Number(income.value) || 0) - totalFixed.value);

const addFixedRow = () => fixedExpenses.value.push({ id: Date.now(), name: '', amount: null });
const removeFixedRow = (index) => fixedExpenses.value.splice(index, 1);

const getPercentOfIncome = (amount) => income.value > 0 ? (((Number(amount) || 0) / income.value) * 100).toFixed(1) : 0;
const formatMoney = (amount) => Number(amount).toLocaleString('es-DO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

// --- GUARDAR EN LA BASE DE DATOS ---
const saveBudget = () => {
    if (!income.value || income.value <= 0) {
        alert("⚠️ Ingresa tu quincena para poder guardar el presupuesto.");
        return;
    }

    const payload = {
        title: `Quincena del ${new Date().toLocaleDateString('es-DO')}`,
        income: income.value,
        fixed_expenses_total: totalFixed.value,
        details: {
            fixed: fixedExpenses.value,
            remaining: remaining.value
        }
    };

    router.post(route('budgets.store'), payload, {
        preserveScroll: true,
        onSuccess: () => {
            alert('✅ ¡Presupuesto guardado! Tu capital libre está listo en la base de datos.');
        }
    });
};

// --- EXPORTAR A CSV ---
const downloadAsCsv = () => {
    let csvLines = [];
    csvLines.push("Tipo,Concepto,Monto (RD$),Porcentaje");
    csvLines.push(`Ingreso,Quincena Total,${income.value || 0},100%`);

    fixedExpenses.value.forEach(row => {
        let name = (row.name || 'Gasto Fijo').replace(/,/g, '');
        let amt = row.amount || 0;
        csvLines.push(`Gasto Fijo,${name},${amt},${getPercentOfIncome(amt)}%`);
    });

    csvLines.push(`Resumen,Capital Libre para Atacar,${remaining.value},-`);

    const csvString = csvLines.join("\n");
    const blob = new Blob(["\uFEFF" + csvString], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    link.download = `Presupuesto_${new Date().toISOString().slice(0, 10)}.csv`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
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
                            <h1 class="text-2xl font-extrabold text-gray-900 text-center mb-8">
                                🛡️ Presupuesto
                            </h1>

                            <div class="mb-8">
                                <label class="block text-sm font-bold text-gray-700 mb-2">1. ¿Cuánto cobraste?</label>
                                <div class="relative rounded-md shadow-sm">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                                        <span class="text-gray-500 font-bold sm:text-lg">RD$</span>
                                    </div>
                                    <input type="number" v-model="income"
                                        class="block w-full rounded-lg border-gray-300 pl-14 py-3 text-lg focus:border-blue-500 focus:ring-blue-500"
                                        placeholder="0.00">
                                </div>
                            </div>

                            <div class="mb-8 p-5 md:p-6 border border-dashed border-gray-300 rounded-xl bg-gray-50">
                                <h3 class="text-lg font-bold text-gray-900">2. Desglosa tus Gastos Fijos</h3>
                                <p class="text-sm text-gray-500 mb-5">Casa, comida, luz... Ingresa el monto (RD$).</p>

                                <div class="space-y-3 mb-5">
                                    <div v-for="(gasto, index) in fixedExpenses" :key="gasto.id"
                                        class="flex flex-col md:flex-row items-center gap-3 bg-white p-3 rounded-lg shadow-sm border border-gray-200">

                                        <input type="text" v-model="gasto.name"
                                            class="w-full md:w-1/2 border-0 border-b-2 border-transparent focus:border-blue-500 focus:ring-0 font-semibold text-gray-700 bg-transparent"
                                            placeholder="Concepto">

                                        <div class="flex items-center w-full md:w-1/2 gap-2">
                                            <span class="text-gray-500 font-bold ml-2 md:ml-0">RD$</span>
                                            <input type="number" v-model="gasto.amount"
                                                class="w-full border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                                placeholder="0.00" min="0">
                                            <span
                                                class="bg-slate-100 text-slate-600 px-3 py-2 rounded-md text-sm font-bold min-w-[3.5rem] text-center">
                                                {{ getPercentOfIncome(gasto.amount) }}%
                                            </span>
                                            <button @click="removeFixedRow(index)"
                                                class="bg-red-50 text-red-500 hover:bg-red-500 hover:text-white px-3 py-2 rounded-md font-bold transition-colors">
                                                ✖
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <button @click="addFixedRow"
                                    class="w-full bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold py-2 px-4 rounded-lg transition-colors">
                                    + Añadir Gasto Fijo
                                </button>

                                <div class="mt-5 text-right font-bold text-gray-800 text-lg">
                                    Total Fijos: RD$ {{ formatMoney(totalFixed) }}
                                    <span class="text-gray-500 text-sm font-normal block md:inline md:ml-2">({{
                                        totalFixedPercent }}% del ingreso)</span>
                                </div>
                            </div>

                            <div class="mb-8">
                                <div
                                    :class="['p-6 rounded-2xl text-center border-2 transition-all duration-300 transform hover:scale-[1.02] shadow-sm',
                                        remaining > 0 ? 'bg-gradient-to-r from-blue-50 to-indigo-50 border-blue-200' :
                                            (remaining === 0 ? 'bg-gray-50 border-gray-200' : 'bg-red-50 border-red-200')]">

                                    <h3 class="text-sm font-bold uppercase tracking-wider mb-2"
                                        :class="remaining > 0 ? 'text-blue-600' : 'text-gray-500'">
                                        ⚔️ Tu Capital para la Guerra
                                    </h3>

                                    <div class="text-4xl font-extrabold"
                                        :class="remaining > 0 ? 'text-blue-900' : (remaining === 0 ? 'text-gray-700' : 'text-red-600')">
                                        RD$ {{ formatMoney(remaining) }}
                                    </div>

                                    <p class="mt-3 text-sm font-medium"
                                        :class="remaining > 0 ? 'text-blue-700' : 'text-gray-600'">
                                        <span v-if="remaining > 0">
                                            ¡Excelente! Guarda este presupuesto y ve a "Deudas" o "Metas" para inyectar
                                            este
                                            capital.
                                        </span>
                                        <span v-else-if="remaining === 0 && income > 0">
                                            Quedaste en cero. No hay capital libre esta quincena.
                                        </span>
                                        <span v-else-if="remaining < 0">
                                            ⚠️ Alerta: Tus gastos fijos superan tus ingresos.
                                        </span>
                                        <span v-else>
                                            Ingresa tus datos arriba para ver tu resultado.
                                        </span>
                                    </p>
                                </div>
                            </div>

                            <div class="pt-6 border-t border-gray-200 flex flex-col md:flex-row gap-4">
                                <button @click="saveBudget"
                                    class="w-full md:w-1/2 bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 px-4 rounded-xl transition-colors flex justify-center items-center gap-2 text-lg shadow-md hover:shadow-lg">
                                    💾 Guardar en la Nube
                                </button>

                                <button @click="downloadAsCsv"
                                    class="w-full md:w-1/2 bg-green-50 hover:bg-green-100 text-green-700 border border-green-200 font-bold py-4 px-4 rounded-xl transition-colors flex justify-center items-center gap-2 text-lg">
                                    📊 Exportar a Excel
                                </button>
                            </div>

                        </div>
                    </div>

                    <div class="lg:col-span-4">
                        <div class="sticky top-6">
                            <div
                                class="bg-white overflow-hidden shadow-sm sm:rounded-xl p-6 border-t-4 border-blue-500">
                                <h2 class="text-xl font-extrabold text-gray-900 mb-6 flex items-center gap-2">
                                    🗂️ Historial de Batallas
                                </h2>

                                <div v-if="budgets && budgets.length > 0" class="flex flex-col gap-4">
                                    <div v-for="budget in budgets" :key="budget.id" @click="openBudgetDetails(budget)"
                                        class="p-4 border border-gray-200 rounded-xl bg-gray-50 hover:bg-blue-50 transition-colors relative group cursor-pointer">
                                        <h3 class="font-bold text-md text-blue-700 mb-2">{{ budget.title }}</h3>
                                        <div class="space-y-1">
                                            <p class="text-sm text-gray-600 flex justify-between">
                                                <span class="font-semibold text-gray-500">Ingreso:</span>
                                                <span class="text-gray-900">RD$ {{ formatMoney(budget.income) }}</span>
                                            </p>
                                            <p class="text-sm text-gray-600 flex justify-between">
                                                <span class="font-semibold text-gray-500">G. Fijos:</span>
                                                <span class="text-gray-900">RD$ {{
                                                    formatMoney(budget.fixed_expenses_total)
                                                }}</span>
                                            </p>
                                        </div>
                                        <div
                                            class="mt-3 pt-2 border-t border-gray-200 text-xs text-gray-400 text-right font-medium">
                                            {{ new Date(budget.created_at).toLocaleDateString('es-DO', {
                                                day: 'numeric',
                                                month:
                                                    'short', year: 'numeric'
                                            }) }}
                                        </div>
                                    </div>
                                </div>

                                <div v-else class="text-center p-6 border-2 border-dashed border-gray-200 rounded-xl">
                                    <p class="text-gray-500 text-sm">Aún no has guardado ningún presupuesto.</p>
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
                <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" @click="closeModal"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div
                    class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-100">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-xl leading-6 font-extrabold text-gray-900" id="modal-title">
                                        📄 {{ selectedBudget.title }}
                                    </h3>
                                    <button @click="closeModal"
                                        class="text-gray-400 hover:text-red-500 font-bold text-xl">&times;</button>
                                </div>

                                <div class="mt-4">
                                    <div class="flex justify-between border-b border-gray-200 pb-3 mb-3">
                                        <span class="text-gray-500 font-bold uppercase text-sm tracking-wider">Ingreso
                                            Total:</span>
                                        <span class="text-blue-700 font-extrabold text-lg">RD$ {{
                                            formatMoney(selectedBudget.income) }}</span>
                                    </div>

                                    <h4 class="font-bold text-gray-800 mt-5 mb-3">Desglose de Gastos Fijos:</h4>
                                    <ul class="space-y-2 mb-2">
                                        <li v-for="item in selectedBudget.details.fixed" :key="item.name"
                                            class="flex justify-between text-sm bg-gray-50 p-3 rounded-lg border border-gray-100">
                                            <span class="text-gray-700 font-medium">{{ item.name }}</span>
                                            <span class="text-gray-900 font-bold">RD$ {{ formatMoney(item.amount)
                                                }}</span>
                                        </li>
                                    </ul>

                                    <div
                                        class="mt-6 p-4 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl flex justify-between items-center border border-blue-100 shadow-inner">
                                        <span class="font-bold text-blue-800">Capital Libre Generado:</span>
                                        <span class="font-extrabold text-blue-900 text-xl">RD$ {{
                                            formatMoney(selectedBudget.details.remaining) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-4 sm:px-6 flex justify-end">
                        <button @click="closeModal" type="button"
                            class="bg-slate-800 hover:bg-slate-900 text-white font-bold py-2 px-6 rounded-lg transition-colors">
                            Cerrar Detalles
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
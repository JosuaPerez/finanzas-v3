<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    budgets: Array
});

const formatMoney = (amount) => Number(amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

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

const openDetails = (budget) => {
    selectedBudget.value = { ...budget, parsedDetails: parseDetails(budget.details) };
    showModal.value = true;
};
const closeModal = () => { showModal.value = false; selectedBudget.value = null; };

const downloadExcel = (id) => { window.location.href = route('budgets.export', id); };
</script>

<template>

    <Head title="Historial de Batallas" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">🗂️ Sala de Archivos (Historial)</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl p-6 md:p-8">
                    <h1 class="text-2xl font-extrabold text-gray-900 mb-2">Historial de Batallas</h1>
                    <p class="text-gray-500 mb-8">Revisa tus presupuestos, los ataques a deudas y descarga reportes en
                        Excel.
                    </p>

                    <div v-if="budgets && budgets.length > 0"
                        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div v-for="budget in budgets" :key="budget.id"
                            class="bg-white border border-gray-200 rounded-2xl shadow-sm hover:shadow-md transition-shadow overflow-hidden flex flex-col">

                            <div class="bg-gray-50 border-b border-gray-100 p-5">
                                <h3 class="font-bold text-lg text-blue-900 mb-1">📄 {{ budget.title }}</h3>
                                <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider">Guardado el: {{
                                    new
                                    Date(budget.created_at).toLocaleDateString('es-DO') }}</p>
                            </div>

                            <div class="p-5 flex-grow space-y-4">
                                <div class="flex justify-between items-center pb-3 border-b border-gray-100">
                                    <span class="text-gray-500 font-medium text-sm">Ingreso Quincenal</span>
                                    <span class="text-gray-900 font-bold">RD$ {{ formatMoney(budget.income) }}</span>
                                </div>
                                <div class="flex justify-between items-center pb-3 border-b border-gray-100">
                                    <span class="text-gray-500 font-medium text-sm">Gastos Fijos</span>
                                    <span class="text-gray-900 font-bold">RD$ {{
                                        formatMoney(budget.fixed_expenses_total)
                                        }}</span>
                                </div>

                                <div v-if="getDebtPaymentsTotal(budget.details) > 0"
                                    class="flex justify-between items-center pb-3 border-b border-gray-100">
                                    <span class="text-red-500 font-bold text-sm">Ataques a Deudas</span>
                                    <span class="text-red-600 font-black">- RD$ {{
                                        formatMoney(getDebtPaymentsTotal(budget.details)) }}</span>
                                </div>

                                <div
                                    class="flex justify-between items-center bg-blue-50 p-3 rounded-lg border border-blue-100">
                                    <span class="text-blue-800 font-bold text-sm">Capital Libre Restante</span>
                                    <span class="text-blue-900 font-black">RD$ {{
                                        formatMoney(getRemaining(budget.details))
                                        }}</span>
                                </div>
                            </div>

                            <div class="p-5 pt-0 mt-auto flex gap-2">
                                <button @click="openDetails(budget)"
                                    class="w-1/2 bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold py-3 px-2 rounded-xl text-sm transition-colors">🔍
                                    Detalles</button>
                                <button @click="downloadExcel(budget.id)"
                                    class="w-1/2 bg-green-50 hover:bg-green-100 text-green-700 border border-green-200 font-bold py-3 px-2 rounded-xl text-sm transition-colors flex justify-center items-center gap-1">📊
                                    Excel</button>
                            </div>
                        </div>
                    </div>

                    <div v-else class="text-center p-12 border-2 border-dashed border-gray-200 rounded-xl bg-gray-50">
                        <div class="text-4xl mb-3">🗄️</div>
                        <h3 class="text-lg font-bold text-gray-900">Sala de archivos vacía</h3>
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

                        <div class="flex justify-between border-b border-gray-200 pb-3 mb-3">
                            <span class="text-gray-500 font-bold uppercase text-sm">Ingreso Total:</span>
                            <span class="text-blue-700 font-extrabold text-lg">RD$ {{ formatMoney(selectedBudget.income)
                                }}</span>
                        </div>

                        <h4 class="font-bold text-gray-800 mt-5 mb-3">📉 Desglose de Gastos Fijos:</h4>
                        <ul class="space-y-2 mb-4">
                            <li v-for="item in selectedBudget.parsedDetails.fixed" :key="item.name"
                                class="flex justify-between text-sm bg-gray-50 p-3 rounded-lg border border-gray-100">
                                <span class="text-gray-700 font-medium">{{ item.name }}</span>
                                <span class="text-gray-900 font-bold">RD$ {{ formatMoney(item.amount) }}</span>
                            </li>
                        </ul>

                        <div
                            v-if="selectedBudget.parsedDetails.debt_payments && selectedBudget.parsedDetails.debt_payments.length > 0">
                            <h4 class="font-bold text-red-800 mt-5 mb-3">⚔️ Municiones Disparadas (Deudas):</h4>
                            <ul class="space-y-2 mb-4">
                                <li v-for="pago in selectedBudget.parsedDetails.debt_payments" :key="pago.name"
                                    class="flex justify-between text-sm bg-red-50 p-3 rounded-lg border border-red-100">
                                    <span class="text-red-700 font-bold">{{ pago.name }}</span>
                                    <span class="text-red-900 font-black">- RD$ {{ formatMoney(pago.amount) }}</span>
                                </li>
                            </ul>
                        </div>

                        <div
                            class="mt-6 p-4 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl flex justify-between items-center border border-blue-100 shadow-inner">
                            <span class="font-bold text-blue-800">Capital Libre Restante:</span>
                            <span class="font-extrabold text-blue-900 text-xl">RD$ {{
                                formatMoney(selectedBudget.parsedDetails.remaining) }}</span>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-4 sm:px-6 flex justify-end">
                        <button @click="closeModal"
                            class="bg-slate-800 hover:bg-slate-900 text-white font-bold py-2 px-6 rounded-lg transition-colors">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    budgets: Array
});

const formatMoney = (amount) => Number(amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

// Función para extraer el capital libre del texto comprimido
const getRemaining = (details) => {
    if (!details) return 0;

    // Si viene como texto (string), lo convertimos a objeto
    if (typeof details === 'string') {
        try {
            const parsed = JSON.parse(details);
            return parsed.remaining || 0;
        } catch (e) {
            return 0; // Si falla por alguna razón, devuelve 0
        }
    }

    // Si ya es un objeto, lo sacamos directo
    return details.remaining || 0;
};

const downloadExcel = (id) => {
    // Redirige a la ruta de descarga pasando el ID del presupuesto específico
    window.location.href = route('budgets.export', id);
};
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
                    <p class="text-gray-500 mb-8">Revisa tus presupuestos anteriores y descarga los reportes oficiales
                        en Excel.
                    </p>

                    <div v-if="budgets && budgets.length > 0"
                        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

                        <div v-for="budget in budgets" :key="budget.id"
                            class="bg-white border border-gray-200 rounded-2xl shadow-sm hover:shadow-md transition-shadow overflow-hidden flex flex-col">

                            <div class="bg-gray-50 border-b border-gray-100 p-5">
                                <h3 class="font-bold text-lg text-blue-900 mb-1">📄 {{ budget.title }}</h3>
                                <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider">
                                    Guardado el: {{ new Date(budget.created_at).toLocaleDateString('es-DO') }}
                                </p>
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
                                <div
                                    class="flex justify-between items-center bg-blue-50 p-3 rounded-lg border border-blue-100">
                                    <span class="text-blue-800 font-bold text-sm">Capital Libre</span>

                                    <span class="text-blue-900 font-black">RD$ {{
                                        formatMoney(getRemaining(budget.details))
                                        }}</span>

                                </div>
                            </div>

                            <div class="p-5 pt-0 mt-auto">
                                <button @click="downloadExcel(budget.id)"
                                    class="w-full bg-green-50 hover:bg-green-100 text-green-700 border border-green-200 font-bold py-3 px-4 rounded-xl transition-colors flex justify-center items-center gap-2">
                                    📊 Descargar Excel
                                </button>
                            </div>

                        </div>
                    </div>

                    <div v-else class="text-center p-12 border-2 border-dashed border-gray-200 rounded-xl bg-gray-50">
                        <div class="text-4xl mb-3">🗄️</div>
                        <h3 class="text-lg font-bold text-gray-900">Sala de archivos vacía</h3>
                        <p class="text-gray-500 mt-1">Aún no has guardado ningún presupuesto en la trinchera.</p>
                    </div>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
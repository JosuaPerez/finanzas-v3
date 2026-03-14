<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    debts: Array
});

const form = ref({
    name: '',
    balance: null,
    interest_rate: null,
    minimum_payment: null
});

const formatMoney = (amount) => Number(amount).toLocaleString('es-DO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

// --- LÓGICA DEL MODAL DE ATAQUE ---
const showPayModal = ref(false);
const selectedDebt = ref(null);
const paymentAmount = ref('');

const openPayModal = (debt) => {
    selectedDebt.value = debt;
    paymentAmount.value = ''; // Limpiamos el input
    showPayModal.value = true;
};

const closePayModal = () => {
    showPayModal.value = false;
    selectedDebt.value = null;
    paymentAmount.value = '';
};

// --- FUNCIONES DE ACCIÓN ---
const saveDebt = () => {
    if (!form.value.name || !form.value.balance) {
        alert("Completa el nombre y el saldo de la deuda.");
        return;
    }

    router.post(route('debts.store'), form.value, {
        preserveScroll: true,
        onSuccess: () => {
            form.value = { name: '', balance: null, interest_rate: null, minimum_payment: null };
            alert('✅ Deuda registrada en el sistema.');
        }
    });
};

const deleteDebt = (id) => {
    if (confirm('¿Estás seguro de eliminar este enemigo del campo de batalla?')) {
        router.delete(route('debts.destroy', id), { preserveScroll: true });
    }
};

const submitPayment = () => {
    const amount = Number(paymentAmount.value);

    if (!amount || amount <= 0) {
        alert("⚠️ Ingresa un monto válido mayor a 0.");
        return;
    }

    router.post(route('debts.pay', selectedDebt.value.id), { amount: amount }, {
        preserveScroll: true,
        onSuccess: () => {
            if (selectedDebt.value.balance - amount <= 0) {
                alert('🎉 ¡ENEMIGO DESTRUIDO! Has saldado esta deuda por completo.');
            } else {
                alert('💥 ¡Impacto directo! El saldo ha disminuido.');
            }
            closePayModal(); // Cerramos el modal hermoso después de disparar
        }
    });
};
</script>

<template>

    <Head title="Modo Guerra - Deudas" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">🔥 Plan de Deudas (Modo Guerra)</h2>
        </template>

        <div class="py-12 relative">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

                    <div class="lg:col-span-4">
                        <div
                            class="bg-white overflow-hidden shadow-sm sm:rounded-xl p-6 border-t-4 border-red-500 sticky top-6">
                            <h3 class="text-xl font-extrabold text-gray-900 mb-2">Añadir Enemigo</h3>
                            <p class="text-sm text-gray-500 mb-6">Registra la tarjeta o préstamo que quieres destruir.
                            </p>

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-1">Nombre de la Deuda</label>
                                    <input type="text" v-model="form.name"
                                        class="w-full rounded-lg border-gray-300 focus:ring-red-500 focus:border-red-500"
                                        placeholder="Ej. Tarjeta Banreservas">
                                </div>

                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-1">Saldo Total a
                                        Pagar</label>
                                    <div class="relative rounded-md shadow-sm">
                                        <div
                                            class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <span class="text-gray-500 font-bold">RD$</span>
                                        </div>
                                        <input type="number" v-model="form.balance"
                                            class="w-full rounded-lg border-gray-300 pl-12 focus:ring-red-500 focus:border-red-500"
                                            placeholder="0.00">
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-bold text-gray-700 mb-1">Tasa de
                                            Interés</label>
                                        <div class="relative rounded-md shadow-sm">
                                            <input type="number" v-model="form.interest_rate"
                                                class="w-full rounded-lg border-gray-300 pr-8 focus:ring-red-500 focus:border-red-500"
                                                placeholder="0">
                                            <div
                                                class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                                <span class="text-gray-500 font-bold">%</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-bold text-gray-700 mb-1">Pago Mínimo</label>
                                        <input type="number" v-model="form.minimum_payment"
                                            class="w-full rounded-lg border-gray-300 focus:ring-red-500 focus:border-red-500"
                                            placeholder="RD$ 0.00">
                                    </div>
                                </div>

                                <button @click="saveDebt"
                                    class="w-full mt-4 bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-4 rounded-xl transition-colors shadow-md">
                                    + Registrar Deuda
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="lg:col-span-8">
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl p-6 md:p-8">
                            <h2 class="text-2xl font-extrabold text-gray-900 mb-6 flex items-center gap-2">⚔️ Campo de
                                Batalla
                            </h2>

                            <div v-if="debts && debts.length > 0" class="space-y-4">

                                <div v-for="debt in debts" :key="debt.id"
                                    class="p-5 border border-red-100 rounded-xl bg-red-50 flex flex-col hover:shadow-md transition-shadow">

                                    <div
                                        class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                                        <div class="w-full md:w-auto">
                                            <h3 class="font-bold text-lg text-red-900">{{ debt.name }}</h3>
                                            <div class="flex gap-4 mt-1 text-sm">
                                                <span class="text-red-700 bg-red-100 px-2 py-1 rounded font-semibold">{{
                                                    debt.interest_rate }}% Interés</span>
                                                <span class="text-gray-600">Mínimo: RD$ {{
                                                    formatMoney(debt.minimum_payment)
                                                    }}</span>
                                            </div>
                                        </div>

                                        <div
                                            class="w-full md:w-auto text-left md:text-right bg-white p-3 rounded-lg border border-red-100">
                                            <span
                                                class="block text-xs font-bold text-gray-400 uppercase tracking-wide">Deuda
                                                Restante</span>
                                            <span class="text-xl font-extrabold text-red-600">RD$ {{
                                                formatMoney(debt.balance)
                                                }}</span>
                                        </div>
                                    </div>

                                    <div class="mt-4 pt-4 border-t border-red-200 flex justify-end gap-3">
                                        <button @click="deleteDebt(debt.id)"
                                            class="px-4 py-2 text-sm font-bold text-gray-500 hover:text-red-600 transition-colors">
                                            🗑️ Eliminar
                                        </button>

                                        <button v-if="debt.balance > 0" @click="openPayModal(debt)"
                                            class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-lg font-bold shadow-sm transition-all transform hover:scale-105 flex items-center gap-2">
                                            ⚔️ Atacar (Abonar)
                                        </button>
                                        <span v-else
                                            class="bg-green-100 text-green-700 px-6 py-2 rounded-lg font-bold flex items-center gap-2 border border-green-200">
                                            ✅ Deuda Saldada
                                        </span>
                                    </div>

                                </div>

                            </div>

                            <div v-else
                                class="text-center p-12 border-2 border-dashed border-gray-200 rounded-xl bg-gray-50">
                                <div class="text-4xl mb-3">🕊️</div>
                                <h3 class="text-lg font-bold text-gray-900">Sin enemigos a la vista</h3>
                                <p class="text-gray-500 mt-1">No tienes deudas registradas. ¡Estás en paz financiera o
                                    te falta
                                    agregarlas a la izquierda!</p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <div v-if="showPayModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog"
            aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity backdrop-blur-sm"
                    @click="closePayModal">
                </div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div
                    class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-gray-100">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">

                                <h3 class="text-xl leading-6 font-extrabold text-gray-900 flex items-center gap-2 border-b border-gray-200 pb-3"
                                    id="modal-title">
                                    ⚔️ Atacar a {{ selectedDebt?.name }}
                                </h3>

                                <div class="mt-4">
                                    <p class="text-sm text-gray-500 font-medium mb-1">Saldo Actual del Enemigo:</p>
                                    <p class="text-2xl font-black text-red-600 mb-6">RD$ {{
                                        formatMoney(selectedDebt?.balance)
                                        }}</p>

                                    <label class="block text-sm font-bold text-gray-700 mb-2">¿Con cuánto capital vas a
                                        disparar?</label>
                                    <div class="relative rounded-md shadow-sm">
                                        <div
                                            class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <span class="text-gray-500 font-bold sm:text-lg">RD$</span>
                                        </div>
                                        <input type="number" v-model="paymentAmount" autofocus
                                            class="block w-full rounded-xl border-gray-300 pl-14 py-4 text-xl focus:border-red-500 focus:ring-red-500 bg-gray-50"
                                            placeholder="0.00" min="0">
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-4 py-4 sm:px-6 flex justify-end gap-3 border-t border-gray-100">
                        <button @click="closePayModal" type="button"
                            class="bg-white hover:bg-gray-100 text-gray-700 border border-gray-300 font-bold py-2 px-4 rounded-lg transition-colors">
                            Cancelar Misión
                        </button>
                        <button @click="submitPayment" type="button"
                            class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-6 rounded-lg transition-colors shadow-md flex items-center gap-2">
                            💥 Disparar
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>
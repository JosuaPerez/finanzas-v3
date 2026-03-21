<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    debts: Array,
    ammunition: { type: Number, default: 0 }
});

// Cambiamos null por '' para que la directiva de texto funcione mejor
const form = ref({ name: '', balance: '', interest_rate: '', minimum_payment: '' });
const formatMoney = (amount) => Number(amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

// --- SISTEMA DE NOTIFICACIONES ---
const notification = ref({ show: false, message: '', type: 'success' });
const showNotification = (message, type = 'success') => {
    notification.value = { show: true, message, type };
    setTimeout(() => { notification.value.show = false; }, 4000);
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
                el.dispatchEvent(new Event('input'));
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

// Limpia las comas antes de guardar en base de datos
const cleanNum = (val) => {
    if (val === null || val === undefined || val === '') return 0;
    return parseFloat(String(val).replace(/,/g, '')) || 0;
};
// ==========================================

// --- ASESOR ESTRATÉGICO ---
const strategy = ref('avalanche');
const sortedDebts = computed(() => {
    if (!props.debts) return [];
    return [...props.debts].sort((a, b) => strategy.value === 'avalanche' ? b.interest_rate - a.interest_rate : a.balance - b.balance);
});

// --- LÓGICA DE MODALES ---
const showPayModal = ref(false);
const showDeleteModal = ref(false);
const selectedDebt = ref(null);
const paymentAmount = ref('');

const openPayModal = (debt) => {
    selectedDebt.value = debt;
    // Si hay municiones, pre-cargamos el input
    paymentAmount.value = props.ammunition > 0 ? props.ammunition.toString() : '';
    showPayModal.value = true;
};
const closePayModal = () => { showPayModal.value = false; selectedDebt.value = null; paymentAmount.value = ''; };

const confirmDelete = (debt) => {
    selectedDebt.value = debt;
    showDeleteModal.value = true;
};
const closeDeleteModal = () => { showDeleteModal.value = false; selectedDebt.value = null; };

// --- FUNCIONES DE ACCIÓN ---
const saveDebt = () => {
    const cleanBalance = cleanNum(form.value.balance);

    if (!form.value.name || cleanBalance <= 0) {
        showNotification("Completa el nombre y un saldo válido mayor a 0.", "error");
        return;
    }

    // Preparamos los datos limpios para Laravel
    const payload = {
        name: form.value.name,
        balance: cleanBalance,
        interest_rate: cleanNum(form.value.interest_rate),
        minimum_payment: cleanNum(form.value.minimum_payment)
    };

    router.post(route('debts.store'), payload, {
        preserveScroll: true,
        onSuccess: () => {
            form.value = { name: '', balance: '', interest_rate: '', minimum_payment: '' };
            showNotification('Enemigo registrado en el radar.', 'success');
        }
    });
};

const executeDelete = () => {
    router.delete(route('debts.destroy', selectedDebt.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showNotification('Enemigo eliminado del campo de batalla.', 'success');
            closeDeleteModal();
        }
    });
};

const submitPayment = () => {
    const amount = cleanNum(paymentAmount.value);

    if (!amount || amount <= 0) {
        showNotification("Ingresa un monto válido mayor a 0.", "error");
        return;
    }

    router.post(route('debts.pay', selectedDebt.value.id), { amount: amount }, {
        preserveScroll: true,
        onSuccess: () => {
            if (selectedDebt.value.balance - amount <= 0) {
                showNotification('¡ENEMIGO DESTRUIDO! Has saldado esta deuda.', 'success');
            } else {
                showNotification('¡Impacto directo! El saldo ha disminuido.', 'success');
            }
            closePayModal();
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
                                        class="w-full rounded-lg border-gray-300 focus:ring-red-500"
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
                                        <input type="text" v-model="form.balance" v-money
                                            class="w-full rounded-lg border-gray-300 pl-12 focus:ring-red-500"
                                            placeholder="0.00">
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-bold text-gray-700 mb-1">Tasa de
                                            Interés</label>
                                        <div class="relative rounded-md shadow-sm">
                                            <input type="number" v-model="form.interest_rate"
                                                class="w-full rounded-lg border-gray-300 pr-8 focus:ring-red-500"
                                                placeholder="0">
                                            <div
                                                class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                                <span class="text-gray-500 font-bold">%</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-bold text-gray-700 mb-1">Pago Mínimo</label>
                                        <input type="text" v-model="form.minimum_payment" v-money
                                            class="w-full rounded-lg border-gray-300 focus:ring-red-500"
                                            placeholder="RD$ 0.00">
                                    </div>
                                </div>
                                <button @click="saveDebt"
                                    class="w-full mt-4 bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-4 rounded-xl shadow-md">+
                                    Registrar Deuda</button>
                            </div>
                        </div>
                    </div>

                    <div class="lg:col-span-8">
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl p-6 md:p-8">

                            <div
                                class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 border-b border-gray-100 pb-4">
                                <h2 class="text-2xl font-extrabold text-gray-900 flex items-center gap-2">⚔️ Campo de
                                    Batalla
                                </h2>
                                <div v-if="debts && debts.length > 1"
                                    class="flex bg-gray-50 p-1 rounded-lg border border-gray-200 mt-3 sm:mt-0">
                                    <button @click="strategy = 'avalanche'"
                                        :class="strategy === 'avalanche' ? 'bg-white shadow-sm font-bold text-gray-900' : 'text-gray-500 hover:text-gray-700'"
                                        class="px-3 py-1 rounded-md text-xs transition-all">🌋 Avalancha</button>
                                    <button @click="strategy = 'snowball'"
                                        :class="strategy === 'snowball' ? 'bg-white shadow-sm font-bold text-gray-900' : 'text-gray-500 hover:text-gray-700'"
                                        class="px-3 py-1 rounded-md text-xs transition-all">⛄ Bola Nieve</button>
                                </div>
                            </div>

                            <div v-if="debts && debts.length > 0" class="space-y-4 mt-4">
                                <div v-for="(debt, index) in sortedDebts" :key="debt.id"
                                    class="p-5 border border-red-100 rounded-xl bg-red-50 flex flex-col hover:shadow-md transition-shadow relative"
                                    :class="{ 'ring-2 ring-yellow-400': index === 0 && debt.balance > 0 }">

                                    <div v-if="index === 0 && debt.balance > 0"
                                        class="absolute -top-3 right-4 bg-yellow-400 text-yellow-900 font-extrabold px-3 py-1 rounded-full text-[10px] shadow-sm uppercase tracking-wide">
                                        Objetivo Principal</div>

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
                                        <button @click="confirmDelete(debt)"
                                            class="px-4 py-2 text-sm font-bold text-gray-500 hover:text-red-600 transition-colors">🗑️
                                            Eliminar</button>
                                        <button v-if="debt.balance > 0" @click="openPayModal(debt)"
                                            class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-lg font-bold shadow-sm transition-transform hover:scale-105 flex items-center gap-2">⚔️
                                            Atacar (Abonar)</button>
                                        <span v-else
                                            class="bg-green-100 text-green-700 px-6 py-2 rounded-lg font-bold border border-green-200">✅
                                            Saldada</span>
                                    </div>
                                </div>
                            </div>

                            <div v-else
                                class="text-center p-12 border-2 border-dashed border-gray-200 rounded-xl bg-gray-50 mt-6">
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
                <div class="fixed inset-0 bg-gray-900/75 transition-opacity backdrop-blur-sm" @click="closePayModal">
                </div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div
                    class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-gray-100">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3
                            class="text-xl leading-6 font-extrabold text-gray-900 flex items-center gap-2 border-b border-gray-200 pb-3">
                            ⚔️ Atacar a {{ selectedDebt?.name }}</h3>
                        <div class="mt-4">
                            <p class="text-sm text-gray-500 font-medium mb-1">Saldo Actual:</p>
                            <p class="text-2xl font-black text-red-600 mb-6">RD$ {{ formatMoney(selectedDebt?.balance)
                            }}</p>
                            <label class="block text-sm font-bold text-gray-700 mb-2">¿Con cuánto capital vas a
                                disparar?</label>
                            <div class="relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none"><span
                                        class="text-gray-500 font-bold sm:text-lg">RD$</span></div>
                                <input type="text" v-model="paymentAmount" v-money autofocus
                                    class="block w-full rounded-xl border-gray-300 pl-14 py-4 text-xl focus:border-red-500 bg-gray-50"
                                    placeholder="0.00">
                            </div>
                            <p v-if="ammunition > 0" class="text-xs text-blue-600 mt-2 font-semibold">* Suministros
                                pre-cargados
                                automáticamente (RD$ {{ formatMoney(ammunition) }}).</p>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-4 sm:px-6 flex justify-end gap-3 border-t border-gray-100">
                        <button @click="closePayModal"
                            class="bg-white hover:bg-gray-100 text-gray-700 border border-gray-300 font-bold py-2 px-4 rounded-lg">Cancelar
                            Misión</button>
                        <button @click="submitPayment"
                            class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-6 rounded-lg shadow-md">💥
                            Disparar</button>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title"
            role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-900/75 transition-opacity backdrop-blur-sm" @click="closeDeleteModal">
                </div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div
                    class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-gray-100">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div
                                class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 class="text-lg leading-6 font-bold text-gray-900" id="modal-title">Eliminar Enemigo
                                </h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500">¿Estás seguro de que deseas eliminar la deuda de
                                        <strong>{{
                                            selectedDebt?.name }}</strong>? Esta acción no se puede deshacer y perderás
                                        su
                                        historial del radar.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-gray-100">
                        <button @click="executeDelete" type="button"
                            class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-bold text-white hover:bg-red-700 sm:ml-3 sm:w-auto sm:text-sm">Eliminar
                            Permanentemente</button>
                        <button @click="closeDeleteModal" type="button"
                            class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-bold text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Cancelar</button>
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
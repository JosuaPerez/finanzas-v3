<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BossCard from '@/Components/BossCard.vue';
import CombatLog from '@/Components/CombatLog.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { formatMoney, getSymbol, getHPStats, cleanNum, vMoney } from '@/composables/useDebtUtils';

const props = defineProps({
    debts: Array,
    ammunition: { type: Number, default: 0 } // Tu Capital Libre
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
    overdraft_percentage: ''
});

// formatMoney, getSymbol, getHPStats, cleanNum, vMoney → imported from @/composables/useDebtUtils

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

const openPayModal = (debt) => {
    selectedDebt.value = debt;
    paymentAmount.value = debt.minimum_payment ? debt.minimum_payment.toString() : '';
    showPayModal.value = true;
};
const closePayModal = () => { showPayModal.value = false; selectedDebt.value = null; paymentAmount.value = ''; };

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
    };

    isSubmitting.value = true;
    router.post(route('debts.store'), payload, {
        preserveScroll: true,
        onSuccess: () => {
            form.value = { type: 'loan', currency: 'DOP', name: '', balance: '', interest_rate: '', minimum_payment: '', credit_limit: '', cutoff_date: '', payment_date: '', original_amount: '', overdraft_percentage: '' };
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
    router.post(route('debts.pay', selectedDebt.value.id), { amount: amount }, {
        preserveScroll: true,
        onSuccess: () => {
            if (selectedDebt.value.balance - amount <= 0) {
                showNotification('¡GOLPE FINAL! El jefe ha sido destruido.', 'success');
            } else {
                showNotification(`¡Impacto Crítico! Le quitaste ${formatMoney(amount)} de HP.`, 'success');
            }
            closePayModal();
        },
        onFinish: () => { isSubmitting.value = false; }
    });
};
</script>

<template>
    <Head title="Modo Guerra - Deudas" />

    <AuthenticatedLayout>
        <!-- <template #header>
            <div class="flex items-center justify-between w-full">
                <h2 class="font-black text-lg sm:text-2xl text-white uppercase tracking-widest flex items-center gap-2">
                    🔥 Plan de Deudas
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

        <div class="py-12 relative">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
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

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-400 mb-1 uppercase tracking-wider">Identificación del Objetivo</label>
                                    <input type="text" v-model="form.name"
                                        class="w-full bg-slate-800 text-white rounded-lg border-slate-700 focus:ring-red-500 focus:border-red-500 placeholder-slate-500"
                                        :placeholder="form.type === 'loan' ? 'Ej. El Ogro del Banco' : 'Ej. La Bestia Visa'">
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-red-400 mb-1 uppercase tracking-wider">
                                        {{ form.type === 'loan' ? 'Puntos de Vida (HP Actual)' : 'Daño Recibido (Deuda Actual)' }}
                                    </label>
                                    <div class="relative rounded-md shadow-sm">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <span class="text-slate-400 font-bold">{{ getSymbol(form.currency) }}</span>
                                        </div>
                                        <input type="text" v-model="form.balance" v-money
                                            class="w-full bg-slate-800 text-white rounded-lg border-red-900 pl-12 focus:ring-red-500 focus:border-red-500 placeholder-slate-600 font-mono"
                                            placeholder="0.00">
                                    </div>
                                </div>

                                <div v-if="form.type === 'loan'" class="p-4 bg-slate-800/50 border border-blue-900/50 rounded-xl">
                                    <label class="block text-xs font-bold text-blue-400 mb-1 uppercase tracking-wider">Vida Máxima (Monto Original)</label>
                                    <div class="relative rounded-md shadow-sm">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <span class="text-blue-500 font-bold">{{ getSymbol(form.currency) }}</span>
                                        </div>
                                        <input type="text" v-model="form.original_amount" v-money
                                            class="w-full bg-slate-900 text-white rounded-lg border-slate-700 pl-12 focus:ring-blue-500 font-mono"
                                            placeholder="0.00">
                                    </div>
                                </div>

                                <div v-if="form.type === 'credit_card'" class="p-4 bg-slate-800/50 border border-orange-900/50 rounded-xl space-y-4">
                                    <div class="grid grid-cols-3 gap-2">
                                        <div class="col-span-2">
                                            <label class="block text-[10px] font-bold text-orange-400 mb-1 uppercase tracking-wider">Límite Aprobado (Max HP)</label>
                                            <div class="relative rounded-md shadow-sm">
                                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                    <span class="text-orange-500 font-bold">{{ getSymbol(form.currency) }}</span>
                                                </div>
                                                <input type="text" v-model="form.credit_limit" v-money
                                                    class="w-full bg-slate-900 text-white rounded-lg border-slate-700 pl-12 focus:ring-orange-500 font-mono" placeholder="0.00">
                                            </div>
                                        </div>
                                        <div class="col-span-1">
                                            <label class="block text-[10px] font-bold text-orange-400 mb-1 uppercase tracking-wider">% Sobregiro</label>
                                            <div class="relative rounded-md shadow-sm">
                                                <input type="number" v-model="form.overdraft_percentage"
                                                    class="w-full bg-slate-900 text-white rounded-lg border-slate-700 pr-6 focus:ring-orange-500 font-mono" placeholder="10">
                                                <div class="absolute inset-y-0 right-0 pr-2 flex items-center pointer-events-none">
                                                    <span class="text-orange-500 font-bold">%</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider">Día de Corte</label>
                                            <input type="number" v-model="form.cutoff_date" min="1" max="31"
                                                class="w-full bg-slate-900 text-white rounded-lg border-slate-700 focus:ring-orange-500 font-mono" placeholder="Ej: 15">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider">Día de Pago</label>
                                            <input type="number" v-model="form.payment_date" min="1" max="31"
                                                class="w-full bg-slate-900 text-white rounded-lg border-slate-700 focus:ring-orange-500 font-mono" placeholder="Ej: 5">
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-400 mb-1 uppercase tracking-wider">Tasa Interés (Ataque Enemigo)</label>
                                        <div class="relative rounded-md shadow-sm">
                                            <input type="number" v-model="form.interest_rate"
                                                class="w-full bg-slate-800 text-white rounded-lg border-slate-700 pr-8 focus:ring-red-500 font-mono" placeholder="0">
                                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                                <span class="text-slate-500 font-bold">%</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-400 mb-1 uppercase tracking-wider">
                                            {{ form.type ==='loan' ?'Cuota Fija' : 'Pago Mínimo' }}
                                        </label>
                                        <input type="text" v-model="form.minimum_payment" v-money
                                            class="w-full bg-slate-800 text-white rounded-lg border-slate-700 focus:ring-red-500 font-mono" placeholder="0.00">
                                    </div>
                                </div>
                                <button @click="saveDebt"
                                    :disabled="isSubmitting"
                                    :class="{ 'opacity-70 cursor-wait pointer-events-none': isSubmitting }"
                                    class="w-full mt-6 inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl font-black uppercase tracking-widest transition-all duration-300 ease-out hover:scale-105 hover:-translate-y-0.5 bg-red-600 hover:bg-red-500 text-white shadow-[0_0_15px_rgba(220,38,38,0.4)]">
                                    Fijar Objetivo
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

                            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 pb-4 border-b border-slate-800/80">
                                <h2 class="text-3xl font-black text-white flex items-center gap-3 tracking-tight">
                                    ⚔️ Zona de Combate
                                </h2>
                                <div v-if="debts && debts.length > 1" class="flex bg-slate-800/80 p-1 rounded-xl mt-3 sm:mt-0 border border-slate-700/50">
                                    <button @click="strategy = 'avalanche'"
                                        :class="strategy === 'avalanche' ? 'bg-slate-950 text-white font-bold shadow-md' : 'text-slate-500 hover:text-slate-300'"
                                        class="px-4 py-2 rounded-lg text-xs transition-all uppercase tracking-wider">🌋 Avalancha</button>
                                    <button @click="strategy = 'snowball'"
                                        :class="strategy === 'snowball' ? 'bg-slate-950 text-white font-bold shadow-md' : 'text-slate-500 hover:text-slate-300'"
                                        class="px-4 py-2 rounded-lg text-xs transition-all uppercase tracking-wider">⛄ Bola Nieve</button>
                                </div>
                            </div>

                            <div v-if="debts && debts.length > 0" class="space-y-6">

                                <!-- TARJETA DE ENEMIGO (JEFE) -->
                                <BossCard
                                    v-for="(debt, index) in sortedDebts"
                                    :key="debt.id"
                                    :debt="debt"
                                    :index="index"
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
                            <p class="text-[10px] text-slate-500 mt-3 font-bold uppercase tracking-wider">* Cargado con el ataque mínimo por defecto.</p>
                        </div>
                    </div>
                    <div class="bg-slate-800/50 px-6 py-4 sm:flex sm:flex-row-reverse border-t border-slate-700">
                        <button @click="submitPayment"
                            :disabled="isSubmitting"
                            :class="{ 'opacity-70 cursor-wait pointer-events-none': isSubmitting }"
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
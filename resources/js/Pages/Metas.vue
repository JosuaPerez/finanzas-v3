<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CombatLog from '@/Components/CombatLog.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { formatMoney, getSymbol, cleanNum, vMoney } from '@/composables/useDebtUtils';

const props = defineProps({
    goals: { type: Array, default: () => [] },
    ammunition: { type: Number, default: 0 } // Tu Capital Libre importado del presupuesto
});

const form = ref({
    currency: 'DOP',
    name: '',
    target_amount: '',
    current_amount: '',
    deadline: ''
});

// formatMoney, getSymbol, cleanNum, vMoney → imported from @/composables/useDebtUtils

const notification = ref({ show: false, message: '', type: 'success' });
const showNotification = (message, type = 'success') => {
    notification.value = { show: true, message, type };
    setTimeout(() => { notification.value.show = false; }, 4000);
};


// --- LÓGICA DE CRAFTEO (RPG) ---
const getForgeStats = (goal) => {
    let target = parseFloat(goal.target_amount) || 0;
    let current = parseFloat(goal.current_amount) || 0;
    
    if (current > target) current = target; // Evitar desbordamiento de barra
    
    let percent = target > 0 ? (current / target) * 100 : 0;
    
    // Asignamos "Niveles" al objeto según su progreso (Ej: Nivel 1 a 5)
    let level = Math.floor(percent / 20) + 1;
    if (level > 5) level = 5;

    return {
        current: current,
        target: target,
        percent: percent,
        level: level,
        isCompleted: percent >= 100
    };
};

// --- ESTADOS DE MODALES ---
const showForgeModal = ref(false);
const showDiscardModal = ref(false);
const selectedGoal = ref(null);
const forgeAmount = ref('');
const isSubmitting = ref(false);

const openForgeModal = (goal) => {
    selectedGoal.value = goal;
    forgeAmount.value = '';
    showForgeModal.value = true;
};
const closeForgeModal = () => { showForgeModal.value = false; selectedGoal.value = null; forgeAmount.value = ''; };

const confirmDiscard = (goal) => {
    selectedGoal.value = goal;
    showDiscardModal.value = true;
};
const closeDiscardModal = () => { showDiscardModal.value = false; selectedGoal.value = null; };

// --- ACCIONES CON LA BASE DE DATOS (RUTAS A IMPLEMENTAR EN LARAVEL) ---
const saveGoal = () => {
    const target = cleanNum(form.value.target_amount);
    if (!form.value.name || target <= 0) {
        showNotification("Nombra tu proyecto y define un costo válido.", "error");
        return;
    }

    const payload = {
        name: form.value.name,
        currency: form.value.currency,
        target_amount: target,
        current_amount: cleanNum(form.value.current_amount),
        deadline: form.value.deadline || null
    };

    isSubmitting.value = true;
    router.post(route('metas.store'), payload, {
        preserveScroll: true,
        onSuccess: () => {
            form.value = { currency: 'DOP', name: '', target_amount: '', current_amount: '', deadline: '' };
            showNotification('¡Plano añadido a la mesa de forja!', 'success');
        },
        onFinish: () => { isSubmitting.value = false; }
    });
};

const executeDiscard = () => {
    isSubmitting.value = true;
    router.delete(route('metas.destroy', selectedGoal.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showNotification('Plano destruido. Materiales liberados.', 'success');
            closeDiscardModal();
        },
        onFinish: () => { isSubmitting.value = false; }
    });
};

const submitForge = () => {
    const amount = cleanNum(forgeAmount.value);
    if (!amount || amount <= 0) {
        showNotification("Ingresa una cantidad de recursos válida.", "error");
        return;
    }

    isSubmitting.value = true;
    router.post(route('metas.add_funds', selectedGoal.value.id), { amount: amount }, {
        preserveScroll: true,
        onSuccess: () => {
            const stats = getForgeStats(selectedGoal.value);
            if (stats.current + amount >= stats.target) {
                showNotification('✨ ¡OBJETO FORJADO! Has completado tu meta.', 'success');
            } else {
                showNotification(`🔨 Has invertido ${formatMoney(amount)} materiales en el proyecto.`, 'success');
            }
            closeForgeModal();
        },
        onFinish: () => { isSubmitting.value = false; }
    });
};
</script>

<template>
    <Head title="Modo Guerra - Arsenal" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between w-full">
                <h2 class="font-black text-lg sm:text-2xl text-white uppercase tracking-widest flex items-center gap-2">
                    🎯 El Arsenal (Mesa de Forja)
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
        </template>

        <div class="py-12 relative">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

                    <!-- SECCIÓN IZQUIERDA: CREAR NUEVO PROYECTO -->
                    <div class="lg:col-span-4">
                        <div class="bg-slate-900/80 backdrop-blur-sm overflow-hidden shadow-2xl sm:rounded-3xl p-6 border border-slate-700/60 ring-1 ring-white/5 sticky top-6 relative">
                            <!-- Ambient accent line -->
                            <div class="absolute top-0 left-0 right-0 h-[2px] bg-gradient-to-r from-transparent via-emerald-500/50 to-transparent"></div>
                            
                            <div class="flex items-center gap-3 mb-6">
                                <div class="bg-emerald-500/20 p-2 rounded-xl border border-emerald-500/50">
                                    <span class="text-emerald-400 text-xl">🔨</span>
                                </div>
                                <div>
                                    <h3 class="text-lg font-black text-white tracking-wide uppercase">Nuevo Plano</h3>
                                    <p class="text-[10px] text-slate-400 uppercase tracking-widest">Diseña tu próximo objetivo</p>
                                </div>
                            </div>

                            <div class="flex p-1 bg-slate-800 rounded-xl mb-6 border border-slate-700 w-1/2 mx-auto">
                                <button @click="form.currency = 'DOP'" type="button"
                                    :class="form.currency === 'DOP' ? 'bg-slate-600 text-white font-bold' : 'text-slate-400 hover:text-white'"
                                    class="w-1/2 py-2 text-xs rounded-lg transition-all">RD$</button>
                                <button @click="form.currency = 'USD'" type="button"
                                    :class="form.currency === 'USD' ? 'bg-emerald-700 text-white font-bold' : 'text-slate-400 hover:text-white'"
                                    class="w-1/2 py-2 text-xs rounded-lg transition-all">US$</button>
                            </div>

                            <div class="space-y-5">
                                <div>
                                    <label class="block text-[10px] font-black text-emerald-400 mb-1 uppercase tracking-widest">Nombre del Proyecto / Objeto</label>
                                    <input type="text" v-model="form.name"
                                        class="w-full bg-slate-950 text-white rounded-xl border-slate-700 focus:ring-emerald-500 focus:border-emerald-500 placeholder-slate-600"
                                        placeholder="Ej. Fondo de Emergencia, Viaje...">
                                </div>

                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 mb-1 uppercase tracking-widest">
                                        Costo Total (Meta a alcanzar)
                                    </label>
                                    <div class="relative rounded-md shadow-sm">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <span class="text-slate-500 font-bold">{{ getSymbol(form.currency) }}</span>
                                        </div>
                                        <input type="text" v-model="form.target_amount" v-money
                                            class="w-full bg-slate-950 text-white rounded-xl border-slate-700 pl-14 py-3 focus:ring-emerald-500 focus:border-emerald-500 placeholder-slate-600 font-mono text-lg"
                                            placeholder="0.00">
                                    </div>
                                </div>

                                <div class="p-4 bg-slate-950/50 border border-slate-800 rounded-2xl space-y-4">
                                    <div>
                                        <label class="block text-[10px] font-black text-slate-400 mb-1 uppercase tracking-widest">Materiales Actuales (Si ya empezaste)</label>
                                        <div class="relative rounded-md shadow-sm">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <span class="text-emerald-600 font-bold">{{ getSymbol(form.currency) }}</span>
                                            </div>
                                            <input type="text" v-model="form.current_amount" v-money
                                                class="w-full bg-slate-900 text-white rounded-lg border-slate-700 pl-12 focus:ring-emerald-500 font-mono"
                                                placeholder="0.00">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-black text-slate-400 mb-1 uppercase tracking-widest">Fecha Límite (Opcional)</label>
                                        <input type="date" v-model="form.deadline"
                                            class="w-full bg-slate-900 text-slate-300 rounded-lg border-slate-700 focus:ring-emerald-500 font-mono text-sm">
                                    </div>
                                </div>

                                <button @click="saveGoal"
                                    :disabled="isSubmitting"
                                    :class="{ 'opacity-70 cursor-wait pointer-events-none': isSubmitting }"
                                    class="w-full mt-6 inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl font-black uppercase tracking-widest transition-all duration-300 ease-out hover:scale-105 hover:-translate-y-0.5 bg-emerald-600 hover:bg-emerald-500 text-white shadow-[0_0_15px_rgba(16,185,129,0.4)]">
                                    <span>⚙️</span> Diseñar Plano
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- SECCIÓN DERECHA: INVENTARIO DE METAS -->
                    <div class="lg:col-span-8">

                        <!-- HUD: Municiones (Recursos Disponibles) -->
                        <div v-if="ammunition > 0"
                            class="mb-6 bg-slate-900 border border-blue-500/30 p-5 rounded-3xl flex flex-col sm:flex-row justify-between items-center shadow-2xl relative overflow-hidden">
                            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-blue-600 to-indigo-600"></div>
                            <div class="flex items-center gap-4 mb-3 sm:mb-0 relative z-10">
                                <div class="bg-blue-600/20 p-3 rounded-xl border border-blue-500/50 text-2xl shadow-[0_0_15px_rgba(37,99,235,0.4)]">🔋</div>
                                <div>
                                    <h3 class="text-blue-400 font-black text-sm uppercase tracking-widest">Recursos Disponibles</h3>
                                    <p class="text-slate-400 text-xs font-medium">Capital libre para invertir en la forja</p>
                                </div>
                            </div>
                            <div class="text-center sm:text-right w-full sm:w-auto bg-slate-950 px-6 py-3 rounded-2xl border border-slate-800 shadow-inner relative z-10">
                                <span class="text-3xl font-black text-white font-mono tracking-tight">RD$ {{ formatMoney(ammunition) }}</span>
                            </div>
                        </div>

                        <!-- LISTA DE PROYECTOS -->
                        <div class="bg-slate-900/80 backdrop-blur-sm border border-slate-700/60 ring-1 ring-white/5 overflow-hidden shadow-2xl sm:rounded-3xl p-6 md:p-8 relative">
                            
                            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 pb-4 border-b border-slate-800/50">
                                <h2 class="text-2xl font-black text-white flex items-center gap-3 tracking-tight">
                                    🛡️ Proyectos Activos
                                </h2>
                                <p class="text-xs text-slate-500 uppercase tracking-widest font-bold mt-2 sm:mt-0">Sube de nivel tu economía</p>
                            </div>

                            <div v-if="goals && goals.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                
                                <!-- TARJETA DE META (PROYECTO) -->
                                <div v-for="goal in goals" :key="goal.id"
                                    class="p-6 bg-slate-900 rounded-3xl flex flex-col relative overflow-hidden group shadow-xl transition-all transform hover:-translate-y-1 border"
                                    :class="getForgeStats(goal).isCompleted ? 'border-emerald-500/50 shadow-[0_0_20px_rgba(16,185,129,0.2)]' : 'border-slate-700'">

                                    <!-- Efecto de Brillo si está completado -->
                                    <div v-if="getForgeStats(goal).isCompleted" class="absolute top-0 right-0 w-32 h-32 bg-emerald-500 rounded-full mix-blend-screen filter blur-[80px] opacity-20 pointer-events-none"></div>

                                    <!-- Cabecera -->
                                    <div class="flex justify-between items-start mb-6 relative z-10">
                                        <div class="flex items-center gap-4">
                                            <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-2xl shadow-inner border"
                                                 :class="getForgeStats(goal).isCompleted ? 'bg-emerald-500/20 border-emerald-500/50 text-emerald-400' : 'bg-slate-800 border-slate-600 text-slate-300'">
                                                {{ getForgeStats(goal).isCompleted ? '✨' : '🛠️' }}
                                            </div>
                                            <div>
                                                <h3 class="font-black text-xl text-white tracking-tight">{{ goal.name }}</h3>
                                                <div class="flex items-center gap-2 mt-1">
                                                    <span class="text-[10px] font-black uppercase tracking-widest px-2 py-0.5 rounded-md border"
                                                          :class="getForgeStats(goal).isCompleted ? 'text-emerald-400 bg-emerald-400/10 border-emerald-400/30' : 'text-slate-400 bg-slate-800 border-slate-700'">
                                                        Nivel {{ getForgeStats(goal).level }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- BARRA DE PROGRESO (FORJA) -->
                                    <div class="mb-6 bg-slate-950 p-4 rounded-2xl border border-slate-800 relative z-10 shadow-inner">
                                        <div class="flex justify-between items-end mb-2">
                                            <span class="font-black tracking-widest text-[10px] uppercase flex items-center gap-2"
                                                  :class="getForgeStats(goal).isCompleted ? 'text-emerald-400' : 'text-slate-500'">
                                                {{ getForgeStats(goal).isCompleted ? 'OBJETO COMPLETADO' : 'PROGRESO DE FORJA' }}
                                            </span>
                                            <span class="text-white font-mono font-bold text-sm">
                                                {{ formatMoney(getForgeStats(goal).current) }} <span class="text-slate-600">/ {{ formatMoney(getForgeStats(goal).target) }}</span>
                                            </span>
                                        </div>
                                        
                                        <div class="w-full bg-slate-800 rounded-full h-3 overflow-hidden relative border border-slate-700/50">
                                            <!-- Color de la barra animada -->
                                            <div class="bg-gradient-to-r h-full transition-all duration-1000 ease-out relative"
                                                :class="getForgeStats(goal).isCompleted ? 'from-emerald-600 to-teal-400' : 'from-blue-600 to-emerald-500'"
                                                :style="{ width: getForgeStats(goal).percent + '%' }">
                                                <div class="absolute top-0 right-0 bottom-0 w-8 bg-white/20 blur-[4px]"></div>
                                            </div>
                                        </div>
                                        
                                        <div class="flex justify-between mt-2 text-[10px] font-bold uppercase tracking-widest text-slate-500">
                                            <span>{{ getForgeStats(goal).percent.toFixed(0) }}%</span>
                                            <span v-if="goal.deadline">Límite: <span class="text-slate-400">{{ goal.deadline }}</span></span>
                                        </div>
                                    </div>

                                    <!-- Acciones -->
                                    <div class="flex justify-between items-center relative z-10 pt-4 border-t border-slate-800 mt-auto">
                                        <button @click="confirmDiscard(goal)"
                                            class="px-3 py-2 text-[10px] font-bold uppercase tracking-widest text-slate-500 hover:text-red-500 transition-colors flex items-center gap-1">
                                            <span>🗑️</span> Descartar
                                        </button>
                                        
                                        <button v-if="!getForgeStats(goal).isCompleted" @click="openForgeModal(goal)"
                                            class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl font-black uppercase tracking-widest text-xs transition-all duration-300 ease-out hover:scale-105 hover:-translate-y-0.5 bg-emerald-600 hover:bg-emerald-500 text-white shadow-[0_0_15px_rgba(16,185,129,0.3)]">
                                            <span>➕</span> Invertir
                                        </button>
                                        <span v-else
                                            class="text-emerald-500 px-5 py-2.5 text-xs font-black uppercase tracking-widest flex items-center gap-2">
                                            <span>✔️</span> Listo
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- ZONA VACÍA -->
                            <div v-else
                                class="text-center p-16 border-2 border-dashed border-slate-800 rounded-3xl bg-slate-900/50 mt-6 shadow-inner">
                                <div class="text-6xl mb-4 opacity-50">🕸️</div>
                                <h3 class="text-2xl font-black text-slate-300 tracking-tight">Armería Vacía</h3>
                                <p class="text-slate-500 mt-2 font-medium text-sm">No tienes proyectos en la mesa de forja. Utiliza los planos a la izquierda para crear tu primer objetivo.</p>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL DE INVERTIR MATERIALES (FORJA) -->
        <div v-if="showForgeModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-slate-950/90 transition-opacity backdrop-blur-md" @click="closeForgeModal"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-slate-900 rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-slate-700">
                    <div class="bg-slate-900 px-6 pt-8 pb-6">
                        <h3 class="text-xl font-black text-white flex items-center gap-3 border-b border-slate-800 pb-4 tracking-tight">
                            ⚙️ Forjando: <span class="text-emerald-400">{{ selectedGoal?.name }}</span>
                        </h3>

                        <div v-if="ammunition > 0" class="mt-6 mb-6 p-4 bg-blue-900/20 border border-blue-500/30 rounded-xl flex justify-between items-center">
                            <span class="text-[10px] font-black text-blue-400 uppercase tracking-widest flex items-center gap-2">🔋 Recursos Base:</span>
                            <span class="text-base font-black text-blue-300 font-mono">RD$ {{ formatMoney(ammunition) }}</span>
                        </div>

                        <div class="mt-4">
                            <div class="flex justify-between items-end mb-2">
                                <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest">Faltante para Nivel Max:</p>
                                <p class="text-sm font-mono text-emerald-500">{{ getSymbol(selectedGoal?.currency) }} {{ formatMoney(selectedGoal?.target_amount - selectedGoal?.current_amount) }}</p>
                            </div>

                            <label class="block text-xs font-bold text-white mb-3 tracking-wide mt-6">¿Cuántos recursos vas a inyectar?</label>
                            <div class="relative rounded-xl shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none">
                                    <span class="text-emerald-500 font-bold text-xl">➕</span>
                                </div>
                                <input type="text" v-model="forgeAmount" v-money autofocus
                                    class="block w-full rounded-2xl border-slate-600 bg-slate-950 text-emerald-400 pl-14 py-5 text-2xl font-mono font-black focus:border-emerald-500 focus:ring-emerald-500 shadow-inner"
                                    placeholder="0.00">
                            </div>
                        </div>
                    </div>
                    <div class="bg-slate-950 px-6 py-4 sm:flex sm:flex-row-reverse border-t border-slate-800">
                        <button @click="submitForge"
                            :disabled="isSubmitting"
                            :class="{ 'opacity-70 cursor-wait pointer-events-none': isSubmitting }"
                            class="w-full sm:w-auto sm:ml-3 inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl font-black uppercase tracking-widest transition-all duration-300 ease-out hover:scale-105 hover:-translate-y-0.5 bg-emerald-600 hover:bg-emerald-500 text-white shadow-[0_0_15px_rgba(16,185,129,0.4)]">
                            <span>🔨</span> Inyectar
                        </button>
                        <button @click="closeForgeModal"
                            class="mt-3 w-full inline-flex justify-center rounded-xl border border-slate-700 shadow-sm px-6 py-3 bg-slate-900 text-xs font-bold text-slate-400 hover:text-white hover:bg-slate-800 uppercase tracking-widest sm:mt-0 sm:ml-3 sm:w-auto transition-colors">
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL DE DESCARTAR PLANO -->
        <div v-if="showDiscardModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-slate-950/90 transition-opacity backdrop-blur-md" @click="closeDiscardModal"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-slate-900 rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-slate-700">
                    <div class="bg-slate-900 px-6 pt-8 pb-6">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-14 w-14 rounded-2xl bg-red-500/10 border border-red-500/30 sm:mx-0 sm:h-12 sm:w-12 text-2xl">
                                🗑️
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 class="text-xl leading-6 font-black text-white tracking-tight">Descartar Plano</h3>
                                <div class="mt-3">
                                    <p class="text-sm text-slate-400 leading-relaxed">¿Seguro que deseas destruir el proyecto <strong class="text-red-400">{{ selectedGoal?.name }}</strong>? Perderás el seguimiento de este objeto de tu inventario.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-slate-950 px-6 py-4 sm:flex sm:flex-row-reverse border-t border-slate-800">
                        <button @click="executeDiscard" type="button"
                            :disabled="isSubmitting"
                            :class="{ 'opacity-70 cursor-wait pointer-events-none': isSubmitting }"
                            class="w-full sm:w-auto sm:ml-3 inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl font-black uppercase tracking-widest transition-all duration-300 ease-out hover:scale-105 hover:-translate-y-0.5 bg-red-600 hover:bg-red-500 text-white shadow-[0_0_15px_rgba(220,38,38,0.4)]">
                            Destruir
                        </button>
                        <button @click="closeDiscardModal" type="button"
                            class="mt-3 w-full inline-flex justify-center rounded-xl border border-slate-700 shadow-sm px-6 py-3 bg-slate-900 text-xs font-bold uppercase tracking-widest text-slate-400 hover:text-white hover:bg-slate-800 sm:mt-0 sm:ml-3 sm:w-auto transition-colors">
                            Mantener
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
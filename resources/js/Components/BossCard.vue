<script setup>
const props = defineProps({
    debt: {
        type: Object,
        required: true,
    },
    index: {
        type: Number,
        required: true,
    },
    formatMoney: {
        type: Function,
        required: true,
    },
    getSymbol: {
        type: Function,
        required: true,
    },
    getHPStats: {
        type: Function,
        required: true,
    },
});

const emit = defineEmits(['attack', 'abort']);
</script>

<template>
    <div
        class="p-6 md:p-8 bg-slate-900/80 backdrop-blur-sm rounded-3xl flex flex-col relative overflow-hidden group shadow-2xl transition-all duration-300 transform hover:-translate-y-1 border border-slate-700/60 ring-1 ring-white/5"
        :class="{ 'ring-2 ring-red-500 shadow-[0_0_30px_rgba(239,68,68,0.3)]': index === 0 && debt.balance > 0 }"
    >
        <!-- Luces de Alarma para el objetivo principal -->
        <div
            v-if="index === 0 && debt.balance > 0"
            class="absolute top-0 right-0 w-64 h-64 bg-red-600 rounded-full mix-blend-screen filter blur-[100px] opacity-20 pointer-events-none animate-pulse"
        ></div>

        <div
            v-if="index === 0 && debt.balance > 0"
            class="absolute top-0 left-1/2 transform -translate-x-1/2 bg-red-600 text-white font-black px-6 py-1 rounded-b-xl text-xs shadow-md uppercase tracking-widest z-10"
        >
            Objetivo Prioritario
        </div>

        <!-- Cabecera del Jefe -->
        <div class="flex justify-between items-start mb-6 relative z-10 mt-2">
            <div class="flex items-center gap-4">
                <div
                    class="w-16 h-16 rounded-2xl flex items-center justify-center text-3xl shadow-inner border"
                    :class="debt.type === 'credit_card' ? 'bg-orange-500/20 border-orange-500/50 text-orange-400' : 'bg-blue-500/20 border-blue-500/50 text-blue-400'"
                >
                    {{ debt.type === 'credit_card' ? '👾' : '👹' }}
                </div>
                <div>
                    <h3 class="font-black text-2xl text-white tracking-tight">{{ debt.name }}</h3>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="text-xs font-bold px-2 py-1 rounded-md bg-slate-800 text-slate-400 border border-slate-700">LVL. {{ debt.interest_rate }} (Interés)</span>
                        <span
                            :class="debt.currency === 'USD' ? 'text-green-400 bg-green-400/10 border-green-400/30' : 'text-blue-400 bg-blue-400/10 border-blue-400/30'"
                            class="text-xs font-black px-2 py-1 rounded-md border"
                        >{{ debt.currency }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- BARRA DE VIDA (HP) -->
        <div class="mb-6 bg-slate-950 p-4 rounded-2xl border border-slate-800 relative z-10 shadow-inner">
            <div class="flex justify-between items-end mb-2">
                <span class="text-red-500 font-black tracking-widest text-xs uppercase flex items-center gap-2">
                    HP del Jefe
                    <span
                        v-if="getHPStats(debt).isCritical"
                        class="animate-pulse text-red-400 text-[10px] border border-red-500/50 bg-red-500/20 px-1 rounded"
                    >¡CRÍTICO!</span>
                </span>
                <span class="text-white font-mono font-bold text-sm">
                    {{ formatMoney(getHPStats(debt).current) }} <span class="text-slate-500">/ {{ formatMoney(getHPStats(debt).max) }}</span>
                </span>
            </div>
            <div class="w-full bg-slate-800 rounded-full h-4 overflow-hidden relative border border-slate-700/50">
                <!-- Color de la barra animada -->
                <div
                    class="bg-gradient-to-r from-red-700 to-red-400 h-full transition-all duration-1000 ease-out relative"
                    :style="{ width: getHPStats(debt).percent + '%' }"
                >
                    <!-- Efecto de brillo en la barra -->
                    <div class="absolute top-0 right-0 bottom-0 w-8 bg-white/20 blur-[4px]"></div>
                </div>
            </div>

            <!-- Detalles tácticos debajo de la barra -->
            <div class="flex justify-between mt-3 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                <span>{{ debt.type === 'loan' ? 'Cuota Fija' : 'Pago Mínimo' }}: <span class="text-white">{{ getSymbol(debt.currency) }} {{ formatMoney(debt.minimum_payment) }}</span></span>
                <span v-if="debt.type === 'credit_card' && debt.cutoff_date">Corte: <span class="text-orange-400">{{ debt.cutoff_date }}</span> | Pago: <span class="text-blue-400">{{ debt.payment_date }}</span></span>
            </div>
        </div>

        <!-- Acciones del Combate -->
        <div class="flex justify-between items-center relative z-10 pt-4 border-t border-slate-800">
            <button
                @click="emit('abort', debt)"
                class="px-4 py-2 text-xs font-bold uppercase tracking-wider text-slate-500 hover:text-red-500 transition-colors flex items-center gap-1"
            >
                <span>🗑️</span> Abortar
            </button>

            <button
                v-if="debt.balance > 0"
                @click="emit('attack', debt)"
                class="inline-flex items-center justify-center gap-2 px-8 py-3 rounded-xl font-black uppercase tracking-widest transition-all duration-300 ease-out hover:scale-105 hover:-translate-y-0.5 bg-blue-600 hover:bg-blue-500 text-white shadow-[0_0_15px_rgba(37,99,235,0.4)]"
            >
                ⚔️ ATACAR
            </button>
            <span
                v-else
                class="bg-emerald-500/20 text-emerald-400 px-8 py-3 rounded-xl font-black uppercase tracking-widest border border-emerald-500/50 shadow-[0_0_15px_rgba(16,185,129,0.2)]"
            >
                💀 DERROTADO
            </span>
        </div>
    </div>
</template>

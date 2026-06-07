<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { formatCurrency } from '@/utils';

const props = defineProps({
    totalDebts:       { type: Number, default: 0 },
    activeDebtCount:  { type: Number, default: 0 },
    totalGoalsSaved:  { type: Number, default: 0 },
    totalGoalsTarget: { type: Number, default: 0 },
    budgetCount:      { type: Number, default: 0 },
    lastCapitalLibre: { type: Number, default: 0 },
});

const page = usePage();

// ── Campaign Progress EXP bar ────────────────────────────────────────────────
// Progress = goals saved / (goals saved + enemy HP remaining)
// Ranges 0–100. If no data yet, show 0.
const campaignXP = computed(() => {
    const saved   = props.totalGoalsSaved;
    const threats = props.totalDebts;
    if (saved + threats === 0) return 0;
    return Math.min(100, Math.round((saved / (saved + threats)) * 100));
});

// Commander rank label based on XP
const commanderRank = computed(() => {
    const xp = campaignXP.value;
    if (xp === 0)   return { title: 'Recluta',       icon: '🪖', color: 'text-slate-400' };
    if (xp < 15)    return { title: 'Soldado Raso',  icon: '⚔️', color: 'text-slate-300' };
    if (xp < 35)    return { title: 'Sargento',      icon: '🛡️', color: 'text-blue-400'  };
    if (xp < 55)    return { title: 'Teniente',      icon: '🎖️', color: 'text-indigo-400' };
    if (xp < 75)    return { title: 'Capitán',       icon: '🏅', color: 'text-violet-400' };
    if (xp < 90)    return { title: 'Comandante',    icon: '⭐', color: 'text-amber-400'  };
    return             { title: 'General de Élite',  icon: '🏆', color: 'text-emerald-400' };
});

// XP bar gradient colour
const xpBarClass = computed(() => {
    const xp = campaignXP.value;
    if (xp < 30)  return 'from-red-600 to-orange-500';
    if (xp < 60)  return 'from-blue-600 to-indigo-500';
    if (xp < 85)  return 'from-violet-600 to-purple-400';
    return               'from-emerald-500 to-teal-400';
});

// Goals progress percentage (for the Forge bar)
const forgePercent = computed(() => {
    if (props.totalGoalsTarget <= 0) return 0;
    return Math.min(100, Math.round((props.totalGoalsSaved / props.totalGoalsTarget) * 100));
});
</script>

<template>
    <Head title="Centro de Mando" />

    <AuthenticatedLayout>
        <div class="py-10">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

                <!-- ══════════════════════════════════════════════════════════
                     BRIEFING DEL COMANDANTE — quick-action cards
                ═══════════════════════════════════════════════════════════ -->
                <div class="bg-slate-900 rounded-3xl p-6 sm:p-10 shadow-2xl border border-slate-800 text-white relative overflow-hidden">
                    <!-- Ambient orbs -->
                    <div class="absolute inset-0 pointer-events-none overflow-hidden">
                        <div class="absolute -top-24 -left-24 w-96 h-96 bg-blue-600 rounded-full mix-blend-screen filter blur-[100px] opacity-20"></div>
                        <div class="absolute bottom-0 right-0 w-72 h-72 bg-emerald-600 rounded-full mix-blend-screen filter blur-[100px] opacity-10"></div>
                    </div>

                    <div class="relative z-10">
                        <div class="text-center mb-10">
                            <h2 class="text-xs sm:text-sm font-bold text-blue-400 tracking-widest uppercase mb-2">
                                Briefing del Comandante
                            </h2>
                            <h1 class="text-3xl md:text-4xl font-black mb-4">¿Cuál es tu próximo movimiento?</h1>
                            <p class="text-slate-400 max-w-2xl mx-auto font-medium">
                                Mantén tu economía blindada. Organiza tus suministros, mejora tu equipamiento y no dejes que el enemigo tome la iniciativa.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-6">

                            <!-- Presupuesto -->
                            <Link :href="route('presupuesto')"
                                class="group bg-slate-950/50 hover:bg-slate-800 border border-slate-800 hover:border-blue-500 p-4 sm:p-6 rounded-2xl transition-all cursor-pointer flex flex-col items-start text-left shadow-lg hover:-translate-y-1">
                                <div class="bg-blue-500/20 border border-blue-500/30 p-2 sm:p-3 rounded-xl mb-3 sm:mb-4 group-hover:scale-110 transition-transform">
                                    <span class="text-xl sm:text-2xl">🛡️</span>
                                </div>
                                <h3 class="text-sm sm:text-lg font-bold text-white mb-1 sm:mb-2">Planificar Defensa</h3>
                                <p class="text-[11px] sm:text-xs text-slate-400 leading-relaxed">Distribuye tus suministros y capital libre.</p>
                            </Link>

                            <!-- Deudas -->
                            <Link :href="route('deudas')"
                                class="group bg-slate-950/50 hover:bg-slate-800 border border-slate-800 hover:border-red-500 p-4 sm:p-6 rounded-2xl transition-all cursor-pointer flex flex-col items-start text-left shadow-lg hover:-translate-y-1">
                                <div class="bg-red-500/20 border border-red-500/30 p-2 sm:p-3 rounded-xl mb-3 sm:mb-4 group-hover:scale-110 transition-transform">
                                    <span class="text-xl sm:text-2xl">🔥</span>
                                </div>
                                <h3 class="text-sm sm:text-lg font-bold text-white mb-1 sm:mb-2">Atacar Jefes</h3>
                                <p class="text-[11px] sm:text-xs text-slate-400 leading-relaxed">Haz daño crítico a las deudas.</p>
                            </Link>

                            <!-- Metas -->
                            <Link :href="route('metas')"
                                class="group bg-slate-950/50 hover:bg-slate-800 border border-slate-800 hover:border-emerald-500 p-4 sm:p-6 rounded-2xl transition-all cursor-pointer flex flex-col items-start text-left shadow-lg hover:-translate-y-1">
                                <div class="bg-emerald-500/20 border border-emerald-500/30 p-2 sm:p-3 rounded-xl mb-3 sm:mb-4 group-hover:scale-110 transition-transform">
                                    <span class="text-xl sm:text-2xl">🎯</span>
                                </div>
                                <h3 class="text-sm sm:text-lg font-bold text-white mb-1 sm:mb-2">Mejorar Arsenal</h3>
                                <p class="text-[11px] sm:text-xs text-slate-400 leading-relaxed">Forja equipamiento fijando objetivos.</p>
                            </Link>

                            <!-- Historial -->
                            <Link :href="route('historial')"
                                class="group bg-slate-950/50 hover:bg-slate-800 border border-slate-800 hover:border-amber-500 p-4 sm:p-6 rounded-2xl transition-all cursor-pointer flex flex-col items-start text-left shadow-lg hover:-translate-y-1">
                                <div class="bg-amber-500/20 border border-amber-500/30 p-2 sm:p-3 rounded-xl mb-3 sm:mb-4 group-hover:scale-110 transition-transform">
                                    <span class="text-xl sm:text-2xl">🗂️</span>
                                </div>
                                <h3 class="text-sm sm:text-lg font-bold text-white mb-1 sm:mb-2">Archivos de Guerra</h3>
                                <p class="text-[11px] sm:text-xs text-slate-400 leading-relaxed">Analiza el registro de tus victorias.</p>
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- ══════════════════════════════════════════════════════════
                     RESUMEN DE CAMPAÑA — gamified HUD
                ═══════════════════════════════════════════════════════════ -->
                <div>
                    <div class="flex items-baseline justify-between mb-4 px-1">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-widest text-slate-500">Estado actual</p>
                            <h2 class="text-xl font-black text-white">Resumen de Campaña</h2>
                        </div>
                        <Link :href="route('historial')" class="text-xs font-bold text-slate-500 hover:text-blue-400 transition-colors uppercase tracking-wider">
                            Ver Historial →
                        </Link>
                    </div>

                    <!-- ── Top stats row ── -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">

                        <!-- HP Enemigo -->
                        <div class="relative bg-slate-900/80 backdrop-blur-sm border border-red-900/40 ring-1 ring-red-500/10 rounded-3xl p-6 shadow-xl overflow-hidden group hover:-translate-y-0.5 transition-transform">
                            <div class="absolute top-0 left-0 right-0 h-px bg-gradient-to-r from-transparent via-red-500/50 to-transparent"></div>
                            <div class="absolute -top-8 -right-8 w-32 h-32 bg-red-600 rounded-full mix-blend-screen filter blur-[60px] opacity-10 group-hover:opacity-20 transition-opacity"></div>
                            <div class="flex items-start justify-between mb-4">
                                <div class="w-11 h-11 bg-red-500/15 border border-red-500/30 rounded-xl flex items-center justify-center text-2xl">
                                    💀
                                </div>
                                <span v-if="activeDebtCount > 0"
                                    class="px-2.5 py-1 bg-red-500/15 border border-red-500/30 rounded-full text-red-400 text-xs font-black uppercase tracking-wider">
                                    {{ activeDebtCount }} Jefe{{ activeDebtCount > 1 ? 's' : '' }}
                                </span>
                                <span v-else class="px-2.5 py-1 bg-emerald-500/15 border border-emerald-500/30 rounded-full text-emerald-400 text-xs font-black uppercase tracking-wider">
                                    Limpio
                                </span>
                            </div>
                            <p class="text-xs font-bold text-red-400/70 uppercase tracking-widest mb-1">HP Enemigo</p>
                            <p class="text-2xl sm:text-3xl font-black text-white font-mono leading-tight">
                                {{ formatCurrency(totalDebts) }}
                            </p>
                            <p class="text-xs text-slate-600 mt-2">Saldo total de deudas activas</p>
                        </div>

                        <!-- Recursos en la Forja -->
                        <div class="relative bg-slate-900/80 backdrop-blur-sm border border-emerald-900/40 ring-1 ring-emerald-500/10 rounded-3xl p-6 shadow-xl overflow-hidden group hover:-translate-y-0.5 transition-transform">
                            <div class="absolute top-0 left-0 right-0 h-px bg-gradient-to-r from-transparent via-emerald-500/50 to-transparent"></div>
                            <div class="absolute -top-8 -right-8 w-32 h-32 bg-emerald-600 rounded-full mix-blend-screen filter blur-[60px] opacity-10 group-hover:opacity-20 transition-opacity"></div>
                            <div class="flex items-start justify-between mb-4">
                                <div class="w-11 h-11 bg-emerald-500/15 border border-emerald-500/30 rounded-xl flex items-center justify-center text-2xl">
                                    ⚒️
                                </div>
                                <span v-if="totalGoalsTarget > 0"
                                    class="px-2.5 py-1 bg-emerald-500/15 border border-emerald-500/30 rounded-full text-emerald-400 text-xs font-black">
                                    {{ forgePercent }}%
                                </span>
                            </div>
                            <p class="text-xs font-bold text-emerald-400/70 uppercase tracking-widest mb-1">Recursos en la Forja</p>
                            <p class="text-2xl sm:text-3xl font-black text-white font-mono leading-tight">
                                {{ formatCurrency(totalGoalsSaved) }}
                            </p>
                            <!-- Mini progress bar -->
                            <div v-if="totalGoalsTarget > 0" class="mt-3">
                                <div class="w-full h-1.5 bg-slate-800 rounded-full overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-emerald-600 to-teal-400 rounded-full transition-all duration-700"
                                        :style="{ width: forgePercent + '%' }"></div>
                                </div>
                                <p class="text-xs text-slate-600 mt-1">de {{ formatCurrency(totalGoalsTarget) }} objetivo</p>
                            </div>
                            <p v-else class="text-xs text-slate-600 mt-2">Sin metas activas todavía</p>
                        </div>

                        <!-- Capital disponible (último presupuesto) -->
                        <div class="relative bg-slate-900/80 backdrop-blur-sm border border-blue-900/40 ring-1 ring-blue-500/10 rounded-3xl p-6 shadow-xl overflow-hidden group hover:-translate-y-0.5 transition-transform">
                            <div class="absolute top-0 left-0 right-0 h-px bg-gradient-to-r from-transparent via-blue-500/50 to-transparent"></div>
                            <div class="absolute -top-8 -right-8 w-32 h-32 bg-blue-600 rounded-full mix-blend-screen filter blur-[60px] opacity-10 group-hover:opacity-20 transition-opacity"></div>
                            <div class="flex items-start justify-between mb-4">
                                <div class="w-11 h-11 bg-blue-500/15 border border-blue-500/30 rounded-xl flex items-center justify-center text-2xl">
                                    💰
                                </div>
                                <span class="px-2.5 py-1 bg-slate-800 border border-slate-700 rounded-full text-slate-400 text-xs font-bold">
                                    Ult. quincena
                                </span>
                            </div>
                            <p class="text-xs font-bold text-blue-400/70 uppercase tracking-widest mb-1">Capital Libre</p>
                            <p class="text-2xl sm:text-3xl font-black font-mono leading-tight"
                                :class="lastCapitalLibre > 0 ? 'text-white' : (lastCapitalLibre < 0 ? 'text-red-400' : 'text-slate-600')">
                                {{ budgetCount > 0 ? formatCurrency(lastCapitalLibre) : '—' }}
                            </p>
                            <p class="text-xs text-slate-600 mt-2">
                                {{ budgetCount > 0 ? `${budgetCount} plan${budgetCount > 1 ? 'es' : ''} guardado${budgetCount > 1 ? 's' : ''}` : 'Aún no hay planes guardados' }}
                            </p>
                        </div>
                    </div>

                    <!-- ── Campaign Progress / EXP bar ── -->
                    <div class="relative bg-slate-900/80 backdrop-blur-sm border border-slate-700/60 ring-1 ring-white/5 rounded-3xl p-6 sm:p-8 shadow-xl overflow-hidden">
                        <div class="absolute top-0 left-0 right-0 h-px bg-gradient-to-r from-transparent via-slate-600/50 to-transparent"></div>
                        <!-- ambient glow that matches the XP colour -->
                        <div class="absolute -bottom-12 left-1/4 w-64 h-32 rounded-full mix-blend-screen filter blur-[80px] opacity-15 transition-all duration-700 pointer-events-none"
                            :class="campaignXP < 30 ? 'bg-orange-600' : campaignXP < 60 ? 'bg-blue-600' : campaignXP < 85 ? 'bg-violet-600' : 'bg-emerald-600'">
                        </div>

                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6 relative z-10">
                            <!-- Rank info -->
                            <div class="flex items-center gap-5">
                                <div class="w-16 h-16 rounded-2xl bg-slate-800 border border-slate-700 flex items-center justify-center text-3xl shadow-inner flex-shrink-0">
                                    {{ commanderRank.icon }}
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-0.5">Rango Actual</p>
                                    <p class="text-xl font-black" :class="commanderRank.color">{{ commanderRank.title }}</p>
                                    <p class="text-xs text-slate-600 mt-0.5">{{ page.props.auth.user.name }}</p>
                                </div>
                            </div>

                            <!-- XP bar -->
                            <div class="flex-1 min-w-0">
                                <div class="flex justify-between items-baseline mb-2">
                                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Progreso de Campaña</p>
                                    <span class="text-sm font-black text-white">{{ campaignXP }}<span class="text-slate-600 font-normal text-xs"> / 100 XP</span></span>
                                </div>
                                <div class="relative w-full h-4 bg-slate-800 rounded-full overflow-hidden border border-slate-700 shadow-inner">
                                    <div
                                        class="h-full rounded-full bg-gradient-to-r transition-all duration-1000 ease-out relative overflow-hidden"
                                        :class="xpBarClass"
                                        :style="{ width: campaignXP + '%' }"
                                    >
                                        <!-- shimmer -->
                                        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full animate-shimmer"></div>
                                    </div>
                                </div>
                                <p class="text-xs text-slate-600 mt-2">
                                    <span v-if="campaignXP === 0">Comienza registrando una meta de ahorro o atacando una deuda.</span>
                                    <span v-else-if="campaignXP < 50">Sigue atacando deudas y forjando tu arsenal para subir de rango.</span>
                                    <span v-else-if="campaignXP < 85">¡Buen progreso! Tus ahorros están superando al enemigo.</span>
                                    <span v-else>🏆 ¡Eres casi imparable! Tus metas dominan el campo de batalla.</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
@keyframes shimmer {
    0%   { transform: translateX(-100%); }
    100% { transform: translateX(400%); }
}
.animate-shimmer {
    animation: shimmer 2.5s ease-in-out infinite;
}
</style>
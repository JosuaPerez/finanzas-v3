<script setup>
import { ref, onMounted, onUnmounted, computed, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import QuickAttackModal from '@/Components/QuickAttackModal.vue';

let timeoutId;

// Reinicia el reloj cada vez que el usuario hace algo
const reiniciarTemporizador = () => {
    clearTimeout(timeoutId);
    // 15 minutos = 900,000 milisegundos
    timeoutId = setTimeout(() => {
        router.post(route('logout'));
    }, 900000); 
};

// Activa los sensores al cargar la pantalla
onMounted(() => {
    window.addEventListener('mousemove', reiniciarTemporizador);
    window.addEventListener('keydown', reiniciarTemporizador);
    window.addEventListener('scroll', reiniciarTemporizador);
    window.addEventListener('click', reiniciarTemporizador);
    reiniciarTemporizador();
});

// Desactiva los sensores si el usuario sale del sistema manualmente
onUnmounted(() => {
    window.removeEventListener('mousemove', reiniciarTemporizador);
    window.removeEventListener('keydown', reiniciarTemporizador);
    window.removeEventListener('scroll', reiniciarTemporizador);
    window.removeEventListener('click', reiniciarTemporizador);
    clearTimeout(timeoutId);
});

const isUserMenuOpen = ref(false);
const userMenuRef    = ref(null);

const page = usePage();

// Streak — read from the globally shared auth prop
const currentStreak = computed(() => page.props.auth?.current_streak ?? 0);

// ── Toast system (level-up + streak bonus) ──────────────────────────────
const toast = ref(null); // { type: 'level_up'|'streak', ...payload }
let toastTimer = null;

const showToast = (payload) => {
    if (toastTimer) clearTimeout(toastTimer);
    toast.value = payload;
    toastTimer = setTimeout(() => { toast.value = null; }, 4000);
};

const dismissToast = () => {
    if (toastTimer) clearTimeout(toastTimer);
    toast.value = null;
};

// React to every Inertia page visit so flash props are read after navigation.
watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.level_up) {
            showToast({ type: 'level_up', ...flash.level_up });
        } else if (flash?.streak_bonus) {
            showToast({ type: 'streak', ...flash.streak_bonus });
        } else if (flash?.quest_claimed) {
            showToast({ type: 'quest', ...flash.quest_claimed });
        }
    },
    { deep: true }
);

const logout = () => {
    isUserMenuOpen.value = false;
    router.post(route('logout'));
};

const handleOutsideClick = (e) => {
    if (userMenuRef.value && !userMenuRef.value.contains(e.target)) {
        isUserMenuOpen.value = false;
    }
};
const handleEscape = (e) => {
    if (e.key === 'Escape') isUserMenuOpen.value = false;

    // ── Global Shortcuts (K / D / P) ─────────────────────────────────────
    // Ignore when focus is inside a text field to avoid hijacking user input.
    const tag = document.activeElement?.tagName?.toLowerCase();
    if (tag === 'input' || tag === 'textarea' || tag === 'select') return;
    if (e.ctrlKey || e.metaKey || e.altKey || e.shiftKey) return;

    if (e.key === 'k' || e.key === 'K') {
        e.preventDefault();
        window.dispatchEvent(new CustomEvent('open-quick-attack'));
    } else if (e.key === 'd' || e.key === 'D') {
        router.visit(route('deudas'));
    } else if (e.key === 'p' || e.key === 'P') {
        router.visit(route('presupuesto'));
    }
};

onMounted(() => {
    document.addEventListener('click', handleOutsideClick);
    document.addEventListener('keydown', handleEscape);
});
onUnmounted(() => {
    document.removeEventListener('click', handleOutsideClick);
    document.removeEventListener('keydown', handleEscape);
});
</script>

<template>
    <div>
        <div class="min-h-screen bg-slate-950 font-sans text-slate-300">

            <!-- ═══ TOP NAVBAR ═══ -->
            <nav class="sticky top-0 z-40 bg-slate-900/80 backdrop-blur-md border-b border-slate-800 shadow-[0_4px_20px_rgba(0,0,0,0.4)]">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 items-center gap-4">

                        <!-- Left col: logo -->
                        <div class="flex flex-1 justify-start">
                            <Link :href="route('dashboard')" class="flex items-center gap-2 group flex-shrink-0">
                                <span class="text-2xl group-hover:scale-110 transition-transform">⚔️</span>
                                <span class="text-white font-black tracking-tight hidden sm:block">Finanzas<span class="text-blue-400">RPG</span></span>
                            </Link>
                        </div>

                        <!-- Center col: nav links — always visible, dead-center -->
                        <div class="hidden lg:flex flex-[2] justify-center items-center gap-2">
                            <Link :href="route('presupuesto')"
                                :class="route().current('presupuesto') ? 'bg-slate-800 text-white border-slate-700' : 'text-slate-400 hover:bg-slate-800 hover:text-white border-transparent'"
                                class="px-3 py-2 rounded-lg text-sm font-bold transition-all border flex items-center gap-1.5">
                                🛡️ Presupuesto
                            </Link>
                            <Link :href="route('deudas')"
                                :class="route().current('deudas') ? 'bg-slate-800 text-white border-slate-700' : 'text-slate-400 hover:bg-slate-800 hover:text-white border-transparent'"
                                class="px-3 py-2 rounded-lg text-sm font-bold transition-all border flex items-center gap-1.5">
                                🔥 Deudas
                            </Link>
                            <Link :href="route('metas')"
                                :class="route().current('metas') ? 'bg-slate-800 text-white border-slate-700' : 'text-slate-400 hover:bg-slate-800 hover:text-white border-transparent'"
                                class="px-3 py-2 rounded-lg text-sm font-bold transition-all border flex items-center gap-1.5">
                                🎯 Metas
                            </Link>
                            <Link :href="route('historial')"
                                :class="route().current('historial') ? 'bg-slate-800 text-white border-slate-700' : 'text-slate-400 hover:bg-slate-800 hover:text-white border-transparent'"
                                class="px-3 py-2 rounded-lg text-sm font-bold transition-all border flex items-center gap-1.5">
                                🗂️ Historial
                            </Link>
                        </div>

                        <!-- Right col: user menu (desktop) + hamburger (mobile) -->
                        <div class="flex flex-1 justify-end items-center gap-3">

                            <!-- Mobile logout — top-right, visible only on small screens -->
                            <Link
                                id="mobile-header-logout"
                                :href="route('logout')"
                                method="post"
                                as="button"
                                class="lg:hidden flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-red-500/30 bg-red-500/10 text-red-400 hover:bg-red-500/20 hover:text-red-300 hover:border-red-400/50 transition-all duration-200 text-xs font-bold active:scale-95"
                            >
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                <span class="hidden sm:inline">Salir</span>
                            </Link>


                            <!-- Desktop user dropdown -->
                            <div ref="userMenuRef" class="relative hidden lg:block">
                                <button
                                    id="user-menu-trigger"
                                    @click="isUserMenuOpen = !isUserMenuOpen"
                                    class="flex items-center gap-2.5 px-3 py-2 rounded-xl border border-slate-700 bg-slate-800/70 text-slate-300 hover:text-white hover:bg-slate-700 hover:border-slate-600 transition-all duration-200 text-sm font-bold"
                                >
                                    <!-- Streak badge -->
                                    <span
                                        :class="currentStreak > 2
                                            ? 'text-orange-500 drop-shadow-[0_0_8px_rgba(249,115,22,0.8)] font-black'
                                            : 'text-slate-500 font-semibold'"
                                        class="flex items-center gap-0.5 text-sm tabular-nums"
                                    >🔥{{ currentStreak }}</span>
                                    <!-- Tactical avatar -->
                                    <div class="w-7 h-7 rounded-lg bg-blue-600/30 border border-blue-500/40 flex items-center justify-center text-blue-400 text-xs font-black flex-shrink-0">
                                        {{ page.props.auth.user.name.charAt(0).toUpperCase() }}
                                    </div>
                                    <span class="hidden lg:block max-w-[120px] truncate">{{ page.props.auth.user.name }}</span>
                                    <svg class="w-3.5 h-3.5 text-slate-500 transition-transform duration-200" :class="{ 'rotate-180': isUserMenuOpen }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>

                                <!-- Dropdown panel -->
                                <Transition
                                    enter-active-class="transition ease-out duration-200"
                                    enter-from-class="opacity-0 translate-y-1 scale-95"
                                    enter-to-class="opacity-100 translate-y-0 scale-100"
                                    leave-active-class="transition ease-in duration-100"
                                    leave-from-class="opacity-100 translate-y-0 scale-100"
                                    leave-to-class="opacity-0 translate-y-1 scale-95"
                                >
                                    <div
                                        v-show="isUserMenuOpen"
                                        class="absolute right-0 top-full mt-2 w-56 bg-slate-900/95 backdrop-blur-md border border-slate-700/60 rounded-2xl shadow-[0_20px_50px_rgba(0,0,0,0.6)] ring-1 ring-white/5 overflow-hidden z-50"
                                    >
                                        <!-- User info header -->
                                        <div class="px-4 py-3 border-b border-slate-800">
                                            <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-0.5">Comandante</p>
                                            <p class="text-sm font-bold text-white truncate">{{ page.props.auth.user.name }}</p>
                                            <p class="text-xs text-slate-500 truncate">{{ page.props.auth.user.email }}</p>
                                        </div>
                                        <!-- Menu items -->
                                        <div class="py-1.5">
                                            <Link
                                                id="profile-menu-link"
                                                :href="route('profile.edit')"
                                                @click="isUserMenuOpen = false"
                                                class="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-slate-300 hover:text-white hover:bg-slate-800/80 transition-colors"
                                            >
                                                <span>⚙️</span>
                                                <span class="font-semibold">Base de Operaciones</span>
                                                <span class="ml-auto text-xs text-slate-600">Perfil</span>
                                            </Link>
                                        </div>
                                        <div class="py-1.5 border-t border-slate-800">
                                            <button
                                                id="logout-menu-btn"
                                                @click="logout"
                                                class="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-red-400 hover:text-red-300 hover:bg-red-500/10 transition-colors"
                                            >
                                                <span>🚪</span>
                                                <span class="font-bold">Abortar Misión</span>
                                                <span class="ml-auto text-xs text-red-800">Salir</span>
                                            </button>
                                        </div>
                                    </div>
                                </Transition>
                            </div>

                        </div><!-- end: right col -->
                    </div><!-- end: flex h-16 row -->
                </div><!-- end: max-w-7xl -->
            </nav>

            <!-- ═══ OPTIONAL PAGE HEADER ═══ -->
            <header class="bg-slate-900 border-b border-slate-800" v-if="$slots.header">
                <div class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8 text-white">
                    <slot name="header" />
                </div>
            </header>

            <main class="pb-24 lg:pb-0">
                <slot />
            </main>

            <!-- ═══ MOBILE BOTTOM NAVIGATION BAR ═══ -->
            <nav
                class="fixed bottom-0 left-0 right-0 w-full bg-slate-950/95 backdrop-blur-md border-t border-slate-800 flex justify-around items-center z-50 lg:hidden"
                style="padding-bottom: env(safe-area-inset-bottom, 0px); padding-top: 0.75rem;"
            >
                <!-- Dashboard -->
                <Link
                    id="bottom-nav-dashboard"
                    :href="route('dashboard')"
                    :class="route().current('dashboard') ? 'text-blue-400' : 'text-slate-500'"
                    class="flex flex-col items-center gap-1 px-4 py-1 rounded-xl transition-all duration-200 active:scale-90"
                >
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    <span
                        class="text-[10px] font-bold uppercase tracking-wider"
                        :class="route().current('dashboard') ? 'text-blue-400' : 'text-slate-600'"
                    >Inicio</span>
                    <!-- Active indicator dot -->
                    <span v-if="route().current('dashboard')" class="w-1 h-1 rounded-full bg-blue-400 mt-0.5"></span>
                    <span v-else class="w-1 h-1 mt-0.5"></span>
                </Link>

                <!-- Presupuesto -->
                <Link
                    id="bottom-nav-presupuesto"
                    :href="route('presupuesto')"
                    :class="route().current('presupuesto') ? 'text-blue-400' : 'text-slate-500'"
                    class="flex flex-col items-center gap-1 px-4 py-1 rounded-xl transition-all duration-200 active:scale-90"
                >
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    <span
                        class="text-[10px] font-bold uppercase tracking-wider"
                        :class="route().current('presupuesto') ? 'text-blue-400' : 'text-slate-600'"
                    >Escudo</span>
                    <span v-if="route().current('presupuesto')" class="w-1 h-1 rounded-full bg-blue-400 mt-0.5"></span>
                    <span v-else class="w-1 h-1 mt-0.5"></span>
                </Link>

                <!-- Deudas -->
                <Link
                    id="bottom-nav-deudas"
                    :href="route('deudas')"
                    :class="route().current('deudas') ? 'text-red-400' : 'text-slate-500'"
                    class="flex flex-col items-center gap-1 px-4 py-1 rounded-xl transition-all duration-200 active:scale-90"
                >
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span
                        class="text-[10px] font-bold uppercase tracking-wider"
                        :class="route().current('deudas') ? 'text-red-400' : 'text-slate-600'"
                    >Jefes</span>
                    <span v-if="route().current('deudas')" class="w-1 h-1 rounded-full bg-red-400 mt-0.5"></span>
                    <span v-else class="w-1 h-1 mt-0.5"></span>
                </Link>

                <!-- Metas -->
                <Link
                    id="bottom-nav-metas"
                    :href="route('metas')"
                    :class="route().current('metas') ? 'text-emerald-400' : 'text-slate-500'"
                    class="flex flex-col items-center gap-1 px-4 py-1 rounded-xl transition-all duration-200 active:scale-90"
                >
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="12" cy="12" r="6" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="12" cy="12" r="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span
                        class="text-[10px] font-bold uppercase tracking-wider"
                        :class="route().current('metas') ? 'text-emerald-400' : 'text-slate-600'"
                    >Metas</span>
                    <span v-if="route().current('metas')" class="w-1 h-1 rounded-full bg-emerald-400 mt-0.5"></span>
                    <span v-else class="w-1 h-1 mt-0.5"></span>
                </Link>

                <!-- Perfil -->
                <Link
                    id="bottom-nav-perfil"
                    :href="route('profile.edit')"
                    :class="route().current('profile.edit') ? 'text-violet-400' : 'text-slate-500'"
                    class="flex flex-col items-center gap-1 px-4 py-1 rounded-xl transition-all duration-200 active:scale-90"
                >
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span
                        class="text-[10px] font-bold uppercase tracking-wider"
                        :class="route().current('profile.edit') ? 'text-violet-400' : 'text-slate-600'"
                    >Perfil</span>
                    <span v-if="route().current('profile.edit')" class="w-1 h-1 rounded-full bg-violet-400 mt-0.5"></span>
                    <span v-else class="w-1 h-1 mt-0.5"></span>
                </Link>

            </nav>
            <!-- ═══ QUICK ATTACK MODAL ═══ -->
            <QuickAttackModal />

            <!-- ═══ TOAST NOTIFICATIONS ═══ -->
            <Transition
                enter-active-class="transition-all duration-500 ease-out"
                enter-from-class="opacity-0 translate-y-4 scale-95"
                enter-to-class="opacity-100 translate-y-0 scale-100"
                leave-active-class="transition-all duration-300 ease-in"
                leave-from-class="opacity-100 translate-y-0 scale-100"
                leave-to-class="opacity-0 translate-y-4 scale-95"
            >
                <div
                    v-if="toast"
                    role="alert"
                    class="fixed bottom-28 lg:bottom-6 right-4 lg:right-6 z-[200] max-w-sm w-full"
                >
                    <!-- Level-Up Toast -->
                    <div
                        v-if="toast.type === 'level_up'"
                        class="relative overflow-hidden rounded-2xl border border-blue-500/50 bg-slate-950/95 backdrop-blur-md px-5 py-4 shadow-[0_0_40px_rgba(59,130,246,0.35)] ring-1 ring-blue-500/20"
                    >
                        <!-- Neon top edge -->
                        <div class="absolute top-0 left-0 right-0 h-px bg-gradient-to-r from-transparent via-blue-400 to-transparent"></div>
                        <!-- Ambient glow -->
                        <div class="absolute -top-6 left-1/2 -translate-x-1/2 w-32 h-16 bg-blue-500 rounded-full mix-blend-screen filter blur-[40px] opacity-25 pointer-events-none"></div>

                        <div class="flex items-start gap-4 relative z-10">
                            <!-- Icon -->
                            <div class="flex-shrink-0 w-11 h-11 rounded-xl bg-blue-500/15 border border-blue-500/30 flex items-center justify-center text-2xl animate-bounce">
                                ⭐
                            </div>
                            <!-- Text -->
                            <div class="flex-1 min-w-0">
                                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-blue-400 mb-0.5">¡NIVEL SUPERIOR!
                                    <span class="animate-ping inline-flex w-1.5 h-1.5 rounded-full bg-blue-400 opacity-75 ml-1 align-middle"></span>
                                </p>
                                <p class="text-base font-black text-white leading-tight">Has ascendido a <span class="text-blue-300">{{ toast.new_rank }}</span></p>
                                <p class="text-xs text-slate-500 mt-0.5">Nuevo rango desbloqueado. El campo de batalla tiembla.</p>
                            </div>
                            <!-- Dismiss -->
                            <button @click="dismissToast" class="flex-shrink-0 text-slate-600 hover:text-slate-400 transition-colors mt-0.5">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <!-- Progress drain bar -->
                        <div class="mt-3 w-full h-0.5 bg-slate-800 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-blue-600 to-cyan-400 rounded-full animate-[shrink_4s_linear_forwards]"></div>
                        </div>
                    </div>

                    <!-- Streak Bonus Toast -->
                    <div
                        v-else-if="toast.type === 'streak'"
                        class="relative overflow-hidden rounded-2xl border border-orange-500/50 bg-slate-950/95 backdrop-blur-md px-5 py-4 shadow-[0_0_40px_rgba(249,115,22,0.30)] ring-1 ring-orange-500/20"
                    >
                        <div class="absolute top-0 left-0 right-0 h-px bg-gradient-to-r from-transparent via-orange-400 to-transparent"></div>
                        <div class="absolute -top-6 left-1/2 -translate-x-1/2 w-32 h-16 bg-orange-500 rounded-full mix-blend-screen filter blur-[40px] opacity-20 pointer-events-none"></div>

                        <div class="flex items-start gap-4 relative z-10">
                            <div class="flex-shrink-0 w-11 h-11 rounded-xl bg-orange-500/15 border border-orange-500/30 flex items-center justify-center text-2xl">
                                🔥
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-orange-400 mb-0.5">¡RACHA LEGENDARIA!</p>
                                <p class="text-base font-black text-white leading-tight">{{ toast.days }} días consecutivos</p>
                                <p class="text-xs text-slate-500 mt-0.5">+{{ toast.bonus }} XP de recompensa. Sigue el ritmo, Comandante.</p>
                            </div>
                            <button @click="dismissToast" class="flex-shrink-0 text-slate-600 hover:text-slate-400 transition-colors mt-0.5">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <div class="mt-3 w-full h-0.5 bg-slate-800 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-orange-600 to-amber-400 rounded-full animate-[shrink_4s_linear_forwards]"></div>
                        </div>
                    </div>

                    <!-- Quest Claimed Toast -->
                    <div
                        v-else-if="toast.type === 'quest'"
                        class="relative overflow-hidden rounded-2xl border border-emerald-500/50 bg-slate-950/95 backdrop-blur-md px-5 py-4 shadow-[0_0_40px_rgba(16,185,129,0.30)] ring-1 ring-emerald-500/20"
                    >
                        <div class="absolute top-0 left-0 right-0 h-px bg-gradient-to-r from-transparent via-emerald-400 to-transparent"></div>
                        <div class="absolute -top-6 left-1/2 -translate-x-1/2 w-32 h-16 bg-emerald-500 rounded-full mix-blend-screen filter blur-[40px] opacity-20 pointer-events-none"></div>

                        <div class="flex items-start gap-4 relative z-10">
                            <div class="flex-shrink-0 w-11 h-11 rounded-xl bg-emerald-500/15 border border-emerald-500/30 flex items-center justify-center text-2xl">
                                🎖️
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-emerald-400 mb-0.5">¡OBJETIVOS CUMPLIDOS!</p>
                                <p class="text-base font-black text-white leading-tight">Recompensa diaria obtenida</p>
                                <p class="text-xs text-slate-500 mt-0.5">+{{ toast.xp }} XP por completar {{ toast.count }} mision{{ toast.count > 1 ? 'es' : '' }}.</p>
                            </div>
                            <button @click="dismissToast" class="flex-shrink-0 text-slate-600 hover:text-slate-400 transition-colors mt-0.5">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <div class="mt-3 w-full h-0.5 bg-slate-800 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-emerald-600 to-teal-400 rounded-full animate-[shrink_4s_linear_forwards]"></div>
                        </div>
                    </div>
                </div>
            </Transition>
        </div>
    </div>
</template>
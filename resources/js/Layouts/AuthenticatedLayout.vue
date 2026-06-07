<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';

const isSidebarOpen  = ref(false);
const isUserMenuOpen = ref(false);
const userMenuRef    = ref(null);

const page = usePage();

const toggleBodyScroll = () => {
    document.body.style.overflow = isSidebarOpen.value ? 'hidden' : '';
};
const closeSidebar = () => { isSidebarOpen.value = false;  toggleBodyScroll(); };
const openSidebar  = () => { isSidebarOpen.value = true;   toggleBodyScroll(); };

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
                    <div class="flex h-16 items-center justify-between">

                        <!-- Left: logo + nav links -->
                        <div class="flex items-center gap-6">
                            <Link :href="route('dashboard')" class="flex items-center gap-2 group flex-shrink-0">
                                <span class="text-2xl group-hover:scale-110 transition-transform">⚔️</span>
                                <span class="text-white font-black tracking-tight hidden sm:block">Finanzas<span class="text-blue-400">RPG</span></span>
                            </Link>

                            <div v-if="!route().current('dashboard')" class="hidden sm:flex items-center gap-1">
                                <Link :href="route('dashboard')"
                                    :class="route().current('dashboard') ? 'bg-slate-800 text-white border-slate-700' : 'text-slate-400 hover:bg-slate-800 hover:text-white border-transparent'"
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
                        </div>

                        <!-- Right: user menu (desktop) + hamburger (mobile) -->
                        <div class="flex items-center gap-3">

                            <!-- Desktop user dropdown -->
                            <div ref="userMenuRef" class="relative hidden sm:block">
                                <button
                                    id="user-menu-trigger"
                                    @click="isUserMenuOpen = !isUserMenuOpen"
                                    class="flex items-center gap-2.5 px-3 py-2 rounded-xl border border-slate-700 bg-slate-800/70 text-slate-300 hover:text-white hover:bg-slate-700 hover:border-slate-600 transition-all duration-200 text-sm font-bold"
                                >
                                    <!-- Tactical avatar -->
                                    <div class="w-7 h-7 rounded-lg bg-blue-600/30 border border-blue-500/40 flex items-center justify-center text-blue-400 text-xs font-black flex-shrink-0">
                                        {{ page.props.auth.user.name.charAt(0).toUpperCase() }}
                                    </div>
                                    <span class="hidden md:block max-w-[120px] truncate">{{ page.props.auth.user.name }}</span>
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

                            <!-- Mobile hamburger -->
                            <button @click="openSidebar"
                                class="sm:hidden inline-flex items-center justify-center rounded-lg p-2 text-slate-400 hover:bg-slate-800 hover:text-white transition-colors">
                                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                </svg>
                            </button>
                        </div>

                    </div>
                </div>
            </nav>

            <!-- ═══ MOBILE SIDEBAR OVERLAY ═══ -->
            <transition
                enter-active-class="transition-opacity ease-linear duration-300"
                enter-from-class="opacity-0" enter-to-class="opacity-100"
                leave-active-class="transition-opacity ease-linear duration-300"
                leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="isSidebarOpen" @click="closeSidebar" class="fixed inset-0 bg-slate-950/80 backdrop-blur-md z-50 sm:hidden"></div>
            </transition>

            <!-- ═══ MOBILE SIDEBAR PANEL ═══ -->
            <transition
                enter-active-class="transition ease-in-out duration-300 transform"
                enter-from-class="-translate-x-full" enter-to-class="translate-x-0"
                leave-active-class="transition ease-in-out duration-300 transform"
                leave-from-class="translate-x-0" leave-to-class="-translate-x-full">

                <div v-if="isSidebarOpen" class="fixed inset-y-0 left-0 w-72 bg-slate-900 shadow-2xl z-50 sm:hidden flex flex-col border-r border-slate-800">

                    <div class="flex items-center justify-between px-6 py-5 border-b border-slate-800">
                        <div class="flex items-center gap-2">
                            <span class="text-2xl">⚔️</span>
                            <span class="text-white font-black tracking-tight">Centro de Mando</span>
                        </div>
                        <button @click="closeSidebar" class="text-slate-400 hover:text-white">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="px-6 py-4 bg-slate-800/30 border-b border-slate-800">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-blue-600/30 border border-blue-500/40 flex items-center justify-center text-blue-400 font-black text-lg">
                                {{ page.props.auth.user.name.charAt(0).toUpperCase() }}
                            </div>
                            <div>
                                <div class="text-sm font-bold text-white">{{ page.props.auth.user.name }}</div>
                                <div class="text-xs text-slate-500">{{ page.props.auth.user.email }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="flex-1 px-4 py-4 space-y-1 overflow-y-auto">
                        <Link :href="route('dashboard')" @click="closeSidebar"
                              :class="route().current('dashboard') ? 'bg-blue-600/20 border-blue-500/50 text-blue-400' : 'text-slate-400 hover:bg-slate-800 hover:text-white border-transparent'"
                              class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all border">
                            <span>🛡️</span> Presupuesto
                        </Link>
                        <Link :href="route('deudas')" @click="closeSidebar"
                              :class="route().current('deudas') ? 'bg-blue-600/20 border-blue-500/50 text-blue-400' : 'text-slate-400 hover:bg-slate-800 hover:text-white border-transparent'"
                              class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all border">
                            <span>🔥</span> Deudas
                        </Link>
                        <Link :href="route('metas')" @click="closeSidebar"
                              :class="route().current('metas') ? 'bg-blue-600/20 border-blue-500/50 text-blue-400' : 'text-slate-400 hover:bg-slate-800 hover:text-white border-transparent'"
                              class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all border">
                            <span>🎯</span> Metas
                        </Link>
                        <Link :href="route('historial')" @click="closeSidebar"
                              :class="route().current('historial') ? 'bg-blue-600/20 border-blue-500/50 text-blue-400' : 'text-slate-400 hover:bg-slate-800 hover:text-white border-transparent'"
                              class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all border">
                            <span>🗂️</span> Historial
                        </Link>
                    </div>

                    <div class="p-4 border-t border-slate-800 space-y-1">
                        <Link :href="route('profile.edit')" @click="closeSidebar"
                              class="flex items-center gap-3 px-4 py-2.5 text-slate-400 hover:text-white hover:bg-slate-800 rounded-xl text-sm font-bold transition-colors">
                            <span>⚙️</span> Base de Operaciones
                        </Link>
                        <button @click="logout"
                              class="w-full flex items-center gap-3 px-4 py-2.5 text-red-400 hover:text-red-300 hover:bg-red-500/10 rounded-xl text-sm font-bold transition-colors">
                            <span>🚪</span> Abortar Misión
                        </button>
                    </div>
                </div>
            </transition>

            <!-- ═══ OPTIONAL PAGE HEADER ═══ -->
            <header class="bg-slate-900 border-b border-slate-800" v-if="$slots.header">
                <div class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8 text-white">
                    <slot name="header" />
                </div>
            </header>

            <main>
                <slot />
            </main>
        </div>
    </div>
</template>
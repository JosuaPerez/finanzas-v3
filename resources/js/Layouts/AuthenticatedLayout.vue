<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { Link } from '@inertiajs/vue3';

const isSidebarOpen = ref(false);

const toggleBodyScroll = () => {
    if (isSidebarOpen.value) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = '';
    }
};

const closeSidebar = () => {
    isSidebarOpen.value = false;
    toggleBodyScroll();
};

const openSidebar = () => {
    isSidebarOpen.value = true;
    toggleBodyScroll();
};
</script>

<template>
    <div>
        <div class="min-h-screen bg-slate-950 font-sans text-slate-300">
            <!-- <nav class="bg-slate-900 border-b border-slate-800 sticky top-0 z-40 shadow-[0_4px_20px_rgba(0,0,0,0.5)]">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 justify-between items-center">
                        
                        <div class="flex items-center gap-8">
                            <div class="flex shrink-0 items-center">
                                <Link :href="route('dashboard')" class="flex items-center gap-2 group">
                                    <span class="text-2xl group-hover:scale-110 transition-transform">⚔️</span>
                                    <span class="text-white font-black tracking-tight hidden sm:block">Finanzas de Combate</span>
                                </Link>
                            </div>

                            <div class="hidden sm:flex space-x-2">
                                <Link :href="route('dashboard')" 
                                      :class="route().current('dashboard') ? 'bg-slate-800 text-white shadow-inner border border-slate-700' : 'text-slate-400 hover:bg-slate-800 hover:text-white border border-transparent'"
                                      class="px-4 py-2 rounded-lg text-sm font-bold transition-colors flex items-center gap-2">
                                    🛡️ Presupuesto
                                </Link>
                                <Link :href="route('deudas')" 
                                      :class="route().current('deudas') ? 'bg-slate-800 text-white shadow-inner border border-slate-700' : 'text-slate-400 hover:bg-slate-800 hover:text-white border border-transparent'"
                                      class="px-4 py-2 rounded-lg text-sm font-bold transition-colors flex items-center gap-2">
                                    🔥 Deudas
                                </Link>
                                <Link :href="route('metas')" 
                                      :class="route().current('metas') ? 'bg-slate-800 text-white shadow-inner border border-slate-700' : 'text-slate-400 hover:bg-slate-800 hover:text-white border border-transparent'"
                                      class="px-4 py-2 rounded-lg text-sm font-bold transition-colors flex items-center gap-2">
                                    🎯 Metas
                                </Link>
                                <Link :href="route('historial')" 
                                      :class="route().current('historial') ? 'bg-slate-800 text-white shadow-inner border border-slate-700' : 'text-slate-400 hover:bg-slate-800 hover:text-white border border-transparent'"
                                      class="px-4 py-2 rounded-lg text-sm font-bold transition-colors flex items-center gap-2">
                                    🗂️ Historial
                                </Link>
                            </div>
                        </div>

                        <div class="flex items-center">
                            <div class="hidden sm:flex sm:items-center">
                                <div class="relative">
                                    <Dropdown align="right" width="48">
                                        <template #trigger>
                                            <span class="inline-flex rounded-md">
                                                <button type="button"
                                                    class="inline-flex items-center rounded-lg border border-slate-700 bg-slate-800 px-4 py-2 text-sm font-bold leading-4 text-slate-300 transition duration-150 ease-in-out hover:text-white hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                                    {{ $page.props.auth.user.name }}
                                                    <svg class="-me-0.5 ms-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                    </svg>
                                                </button>
                                            </span>
                                        </template>

                                        <template #content>
                                            <DropdownLink :href="route('profile.edit')">⚙️ Perfil de Soldado</DropdownLink>
                                            <DropdownLink :href="route('logout')" method="post" as="button" class="text-red-600 font-bold hover:bg-red-50">🚪 Abandonar Base</DropdownLink>
                                        </template>
                                    </Dropdown>
                                </div>
                            </div>

                            <div class="-me-2 flex items-center sm:hidden">
                                <button @click="openSidebar"
                                    class="inline-flex items-center justify-center rounded-md p-2 text-slate-400 transition duration-150 ease-in-out hover:bg-slate-800 hover:text-white focus:bg-slate-800 focus:text-white focus:outline-none">
                                    <svg class="h-7 w-7" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </nav> -->

            <transition 
                enter-active-class="transition-opacity ease-linear duration-300" 
                enter-from-class="opacity-0" enter-to-class="opacity-100" 
                leave-active-class="transition-opacity ease-linear duration-300" 
                leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="isSidebarOpen" @click="closeSidebar" class="fixed inset-0 bg-slate-950/80 backdrop-blur-md z-50 sm:hidden"></div>
            </transition>

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
                        <button @click="closeSidebar" class="text-slate-400 hover:text-white focus:outline-none">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="px-6 py-6 bg-slate-800/30">
                        <div class="text-lg font-bold text-white">{{ $page.props.auth.user.name }}</div>
                        <div class="text-sm font-medium text-slate-400">{{ $page.props.auth.user.email }}</div>
                    </div>

                    <div class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
                        <Link :href="route('dashboard')" @click="closeSidebar"
                              :class="route().current('dashboard') ? 'bg-blue-600/20 border border-blue-500/50 text-blue-400 shadow-inner' : 'text-slate-400 hover:bg-slate-800 hover:text-white border border-transparent'"
                              class="flex items-center gap-3 px-4 py-3 rounded-xl text-base font-bold transition-all">
                            <span class="text-xl">🛡️</span> Presupuesto
                        </Link>
                        <Link :href="route('deudas')" @click="closeSidebar"
                              :class="route().current('deudas') ? 'bg-blue-600/20 border border-blue-500/50 text-blue-400 shadow-inner' : 'text-slate-400 hover:bg-slate-800 hover:text-white border border-transparent'"
                              class="flex items-center gap-3 px-4 py-3 rounded-xl text-base font-bold transition-all">
                            <span class="text-xl">🔥</span> Deudas
                        </Link>
                        <Link :href="route('metas')" @click="closeSidebar"
                              :class="route().current('metas') ? 'bg-blue-600/20 border border-blue-500/50 text-blue-400 shadow-inner' : 'text-slate-400 hover:bg-slate-800 hover:text-white border border-transparent'"
                              class="flex items-center gap-3 px-4 py-3 rounded-xl text-base font-bold transition-all">
                            <span class="text-xl">🎯</span> Metas
                        </Link>
                        <Link :href="route('historial')" @click="closeSidebar"
                              :class="route().current('historial') ? 'bg-blue-600/20 border border-blue-500/50 text-blue-400 shadow-inner' : 'text-slate-400 hover:bg-slate-800 hover:text-white border border-transparent'"
                              class="flex items-center gap-3 px-4 py-3 rounded-xl text-base font-bold transition-all">
                            <span class="text-xl">🗂️</span> Historial
                        </Link>
                    </div>

                    <div class="p-4 border-t border-slate-800 space-y-2">
                        <Link :href="route('profile.edit')" @click="closeSidebar"
                              class="block px-4 py-2 text-slate-400 hover:text-white hover:bg-slate-800 rounded-lg font-bold text-sm transition-colors">
                            ⚙️ Ajustes de Perfil
                        </Link>
                        <Link :href="route('logout')" method="post" as="button" @click="closeSidebar"
                              class="w-full text-left px-4 py-2 text-red-500 hover:text-red-400 hover:bg-red-500/10 rounded-lg font-bold text-sm transition-colors">
                            🚪 Abandonar Base
                        </Link>
                    </div>
                </div>
            </transition>

            <header class="bg-slate-900 shadow-md border-b border-slate-800" v-if="$slots.header">
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
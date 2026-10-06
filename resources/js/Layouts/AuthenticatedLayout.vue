<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from "vue";
import { Link, router, usePage } from "@inertiajs/vue3";
import QuickAttackModal from "@/Components/QuickAttackModal.vue";

const page = usePage();
const nav = [
    {
        name: "dashboard",
        label: "Inicio",
        icon: "M3 10l9-7 9 7M5 9v12h5v-7h4v7h5V9",
    },
    {
        name: "presupuesto",
        label: "Presupuesto",
        icon: "M5 3h14v18H5zM8 8h8M8 12h8M8 16h5",
    },
    {
        name: "deudas",
        label: "Deudas",
        icon: "M4 6h16M4 12h10M4 18h7M16 14v7m-3-3 3 3 3-3",
    },
    {
        name: "metas",
        label: "Metas",
        icon: "M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18M12 7a5 5 0 1 0 0 10 5 5 0 0 0 0-10",
    },
    {
        name: "profile.edit",
        label: "Perfil",
        icon: "M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18M8 18v-1a4 4 0 0 1 8 0v1M12 7a3 3 0 1 0 0 6 3 3 0 0 0 0-6",
    },
];
const toast = ref("");
let toastTimer;
let inactivityTimer;
const resetTimer = () => {
    clearTimeout(inactivityTimer);
    inactivityTimer = setTimeout(() => router.post(route("logout")), 900000);
};
const shortcut = (event) => {
    const tag = document.activeElement?.tagName?.toLowerCase();
    if (
        ["input", "textarea", "select"].includes(tag) ||
        event.ctrlKey ||
        event.metaKey ||
        event.altKey ||
        event.shiftKey ||
        document.querySelector("dialog[open]")
    )
        return;
    if (event.key.toLowerCase() === "k") {
        event.preventDefault();
        window.dispatchEvent(new CustomEvent("open-quick-attack"));
    }
};
const success = computed(() => page.props.flash?.success);
watch(
    () => page.props.flash,
    (flash) => {
        const message = flash?.level_up
            ? `Nuevo rango: ${flash.level_up.new_rank}`
            : flash?.quest_claimed
              ? `Recompensa: +${flash.quest_claimed.xp} XP`
              : flash?.streak_bonus
                ? "¡Tu constancia suma progreso!"
                : "";
        if (message) {
            clearTimeout(toastTimer);
            toast.value = message;
            toastTimer = setTimeout(() => {
                toast.value = "";
            }, 4000);
        }
    },
    { immediate: true },
);
onMounted(() => {
    ["pointerdown", "keydown", "scroll"].forEach((event) =>
        window.addEventListener(event, resetTimer, { passive: true }),
    );
    window.addEventListener("keydown", shortcut);
    resetTimer();
});
onUnmounted(() => {
    ["pointerdown", "keydown", "scroll"].forEach((event) =>
        window.removeEventListener(event, resetTimer),
    );
    window.removeEventListener("keydown", shortcut);
    clearTimeout(inactivityTimer);
    clearTimeout(toastTimer);
});
</script>

<template>
    <div class="min-h-screen bg-slate-950 font-sans text-slate-300">
        <header
            class="app-topbar sticky top-0 z-40 border-b border-slate-800 bg-slate-950"
        >
            <div
                class="mx-auto flex min-h-16 max-w-6xl items-center justify-between gap-3 px-4 sm:px-6"
            >
                <Link
                    :href="route('dashboard')"
                    class="flex min-h-11 items-center gap-2 font-semibold text-white"
                    ><span aria-hidden="true" class="text-cyan-300">✦</span>
                    Finanzas<span class="text-cyan-300">RPG</span></Link
                >
                <nav
                    class="hidden items-center gap-1 lg:flex"
                    aria-label="Navegación principal"
                >
                    <Link
                        v-for="item in nav"
                        :key="item.name"
                        :href="route(item.name)"
                        :aria-current="
                            route().current(item.name) ? 'page' : undefined
                        "
                        class="flex min-h-11 items-center rounded-xl px-3 text-sm"
                        :class="
                            route().current(item.name)
                                ? 'bg-slate-800 text-white'
                                : 'text-slate-400 hover:text-white'
                        "
                        >{{ item.label }}</Link
                    >
                </nav>
                <div class="flex items-center gap-1">
                    <Link
                        :href="route('historial')"
                        class="flex min-h-11 items-center rounded-lg px-2 text-sm text-slate-300"
                        >Historial</Link
                    >
                    <button
                        class="flex h-11 w-11 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-800"
                        aria-label="Cerrar sesión"
                        @click="router.post(route('logout'))"
                    >
                        <svg
                            class="h-5 w-5"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            aria-hidden="true"
                        >
                            <path d="M9 5H5v14h4M13 8l4 4-4 4M9 12h12" />
                        </svg>
                    </button>
                </div>
            </div>
        </header>
        <header
            v-if="$slots.header"
            class="mx-auto max-w-6xl px-4 pt-6 sm:px-6"
        >
            <slot name="header" />
        </header>
        <main class="app-main">
            <div
                v-if="success"
                role="status"
                class="mx-auto mt-4 max-w-6xl px-4 sm:px-6"
            >
                <p
                    class="rounded-xl border border-emerald-900 bg-emerald-950/40 p-4 text-sm text-emerald-200"
                >
                    {{ success }}
                </p>
            </div>
            <slot />
        </main>
        <nav
            class="app-bottom-nav fixed inset-x-0 bottom-0 z-50 grid grid-cols-5 border-t border-slate-800 bg-slate-950 lg:hidden"
            aria-label="Navegación principal móvil"
        >
            <Link
                v-for="item in nav"
                :key="item.name"
                :id="`bottom-nav-${item.name.split('.')[0]}`"
                :href="route(item.name)"
                :aria-current="route().current(item.name) ? 'page' : undefined"
                class="flex min-h-[60px] min-w-0 flex-col items-center justify-center gap-1 px-1"
                :class="
                    route().current(item.name)
                        ? 'text-cyan-300'
                        : 'text-slate-400'
                "
                ><svg
                    class="h-5 w-5"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.7"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    aria-hidden="true"
                >
                    <path :d="item.icon" /></svg
                ><span class="text-[11px] font-medium">{{
                    item.label
                }}</span></Link
            >
        </nav>
        <QuickAttackModal />
        <div
            v-if="toast"
            role="status"
            class="app-toast fixed inset-x-4 z-50 mx-auto max-w-sm rounded-xl border border-slate-700 bg-slate-900 p-4 text-sm text-white"
        >
            ✦ {{ toast }}
        </div>
    </div>
</template>

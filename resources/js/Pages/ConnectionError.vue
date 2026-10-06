<script setup>
import { router } from '@inertiajs/vue3';
defineProps({ message: String });
</script>
<template>
    <main class="min-h-dvh bg-slate-950 px-6 py-16 text-slate-100">
        <div class="mx-auto max-w-md space-y-6">
            <h1 class="text-2xl font-bold">Necesitamos conexión</h1>
            <p class="text-slate-300">{{ message }}</p>
            <button class="min-h-11 rounded-xl bg-emerald-600 px-5 font-semibold" @click="router.reload()">Volver a intentar</button>
            <p class="text-sm text-slate-400">Si estabas registrando un movimiento, revisa el historial antes de enviarlo nuevamente.</p>
        </div>
    </main>
</template>

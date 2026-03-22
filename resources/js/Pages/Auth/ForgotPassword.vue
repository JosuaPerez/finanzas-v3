<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>

    <Head title="Recuperar Contraseña" />

    <div class="min-h-screen bg-slate-900 flex flex-col justify-center items-center p-6 relative overflow-hidden">

        <div class="absolute top-0 left-0 w-full h-full overflow-hidden z-0 pointer-events-none">
            <div
                class="absolute top-10 left-10 w-96 h-96 bg-blue-600 rounded-full mix-blend-screen filter blur-[120px] opacity-20">
            </div>
            <div
                class="absolute -bottom-10 right-10 w-80 h-80 bg-slate-500 rounded-full mix-blend-screen filter blur-[100px] opacity-20">
            </div>
        </div>

        <div class="z-10 w-full max-w-md bg-white rounded-3xl shadow-2xl p-8 sm:p-10 border border-slate-100">

            <div class="text-center mb-6">
                <div
                    class="bg-blue-50 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4 shadow-inner border border-blue-100">
                    <span class="text-4xl">🔑</span>
                </div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">Recuperar Acceso</h1>
                <p class="text-slate-500 font-medium mt-3 text-sm leading-relaxed">
                    ¿Perdiste tu clave, soldado? No hay problema. Ingresa tu correo y te enviaremos un enlace seguro
                    para crear una nueva.
                </p>
            </div>

            <div v-if="status"
                class="mb-6 font-bold text-sm text-green-700 bg-green-50 border border-green-200 p-4 rounded-xl text-center shadow-sm">
                ✅ {{ status }}
            </div>

            <form @submit.prevent="submit" class="space-y-5">
                <div>
                    <label for="email" class="block text-sm font-bold text-slate-700 mb-1">Correo Electrónico</label>
                    <input id="email" type="email"
                        class="w-full rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm bg-slate-50"
                        v-model="form.email" required autofocus autocomplete="username"
                        placeholder="soldado@ejemplo.com" />
                    <p v-if="form.errors.email" class="mt-2 text-xs text-red-600 font-bold">{{ form.errors.email }}</p>
                </div>

                <button type="submit" :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                    :disabled="form.processing"
                    class="w-full mt-2 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-xl shadow-lg transition-transform hover:-translate-y-0.5 flex justify-center items-center gap-2">
                    <span>Enviar Coordenadas</span>
                </button>
            </form>

            <div class="mt-3 text-center border-t border-slate-100 pt-6">
                <Link :href="route('login')"
                    class="text-sm font-bold text-slate-500 hover:text-slate-800 transition-colors flex justify-center items-center gap-2">
                    <span>⬅️</span> Volver a la base
                </Link>
            </div>
        </div>

        <div class="z-10 mt-8 text-slate-400 text-xs font-medium">
            &copy; {{ new Date().getFullYear() }} Finanzas de Combate. Todos los derechos reservados.
        </div>
    </div>
</template>
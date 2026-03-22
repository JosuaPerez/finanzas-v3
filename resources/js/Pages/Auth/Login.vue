<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>

    <Head title="Iniciar Sesión" />

    <div class="min-h-screen bg-slate-900 flex flex-col justify-center items-center p-6 relative overflow-hidden">

        <div class="absolute top-0 left-0 w-full h-full overflow-hidden z-0 pointer-events-none">
            <div
                class="absolute -top-24 -left-24 w-96 h-96 bg-blue-600 rounded-full mix-blend-screen filter blur-[100px] opacity-30">
            </div>
            <div
                class="absolute bottom-10 right-10 w-72 h-72 bg-red-600 rounded-full mix-blend-screen filter blur-[100px] opacity-20">
            </div>
        </div>

        <div class="z-10 w-full max-w-md bg-white rounded-3xl shadow-2xl p-8 sm:p-10 border border-slate-100">

            <div class="text-center mb-8">
                <div
                    class="bg-slate-50 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4 shadow-inner border border-slate-100">
                    <span class="text-4xl">🛡️</span>
                </div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">Finanzas de Combate</h1>
                <p class="text-slate-500 font-medium mt-1 text-sm">Ingresa a tu Centro de Mando</p>
            </div>

            <div v-if="status" class="mb-4 font-medium text-sm text-green-600 bg-green-50 p-3 rounded-lg text-center">
                {{ status }}
            </div>

            <form @submit.prevent="submit" class="space-y-5">
                <div>
                    <label for="email" class="block text-sm font-bold text-slate-700 mb-1">Correo Electrónico</label>
                    <input id="email" type="email"
                        class="w-full rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm bg-slate-50"
                        v-model="form.email" required autofocus autocomplete="username"
                        placeholder="soldado@ejemplo.com" />
                    <p v-if="form.errors.email" class="mt-1 text-xs text-red-600 font-bold">{{ form.errors.email }}</p>
                </div>

                <div>
                    <label for="password" class="block text-sm font-bold text-slate-700 mb-1">Contraseña</label>
                    <input id="password" type="password"
                        class="w-full rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm bg-slate-50"
                        v-model="form.password" required autocomplete="current-password" placeholder="••••••••" />
                    <p v-if="form.errors.password" class="mt-1 text-xs text-red-600 font-bold">{{ form.errors.password
                        }}</p>
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" name="remember" v-model="form.remember"
                            class="rounded border-slate-300 text-blue-600 shadow-sm focus:ring-blue-500" />
                        <span class="ml-2 text-sm text-slate-600 font-medium">Recordarme</span>
                    </label>

                    <Link v-if="canResetPassword" :href="route('password.request')"
                        class="text-sm font-bold text-blue-600 hover:text-blue-800 transition-colors">
                        ¿Olvidaste tu clave?
                    </Link>
                </div>

                <button type="submit" :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                    :disabled="form.processing"
                    class="w-full mt-4 bg-slate-900 hover:bg-black text-white font-bold py-3 px-4 rounded-xl shadow-lg transition-transform hover:-translate-y-0.5 flex justify-center items-center gap-2">
                    <span>Entrar al Radar</span>
                </button>
            </form>

            <div class="mt-3 text-center border-t border-slate-100 pt-6">
                <p class="text-sm text-slate-500 font-medium">
                    ¿Aún no te has enlistado?
                    <Link :href="route('register')"
                        class="font-bold text-blue-600 hover:text-blue-800 transition-colors">Crear cuenta nueva</Link>
                </p>
            </div>
        </div>

        <div class="z-10 mt-8 text-slate-400 text-xs font-medium">
            &copy; {{ new Date().getFullYear() }} Finanzas de Combate. Todos los derechos reservados.
        </div>
    </div>
</template>
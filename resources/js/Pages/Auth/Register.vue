<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>

    <Head title="Reclutamiento" />

    <div class="min-h-screen bg-slate-900 flex flex-col justify-center items-center p-6 relative overflow-hidden">

        <div class="absolute top-0 left-0 w-full h-full overflow-hidden z-0 pointer-events-none">
            <div
                class="absolute -top-24 -left-24 w-96 h-96 bg-red-600 rounded-full mix-blend-screen filter blur-[100px] opacity-20">
            </div>
            <div
                class="absolute bottom-10 right-10 w-72 h-72 bg-blue-600 rounded-full mix-blend-screen filter blur-[100px] opacity-20">
            </div>
        </div>

        <div class="z-10 w-full max-w-md bg-white rounded-3xl shadow-2xl p-8 sm:p-10 border border-slate-100">

            <div class="text-center mb-8">
                <div
                    class="bg-red-50 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4 shadow-inner border border-red-100">
                    <span class="text-4xl">⚔️</span>
                </div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">Únete a la Resistencia</h1>
                <p class="text-slate-500 font-medium mt-1 text-sm">Regístrate y domina tus finanzas</p>
            </div>

            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label for="name" class="block text-sm font-bold text-slate-700 mb-1">Nombre Completo</label>
                    <input id="name" type="text"
                        class="w-full rounded-xl border-slate-300 focus:border-red-500 focus:ring-red-500 shadow-sm bg-slate-50"
                        v-model="form.name" required autofocus autocomplete="name" placeholder="Ej. Juan Perez" />
                    <p v-if="form.errors.name" class="mt-1 text-xs text-red-600 font-bold">{{ form.errors.name }}</p>
                </div>

                <div>
                    <label for="email" class="block text-sm font-bold text-slate-700 mb-1">Correo Electrónico</label>
                    <input id="email" type="email"
                        class="w-full rounded-xl border-slate-300 focus:border-red-500 focus:ring-red-500 shadow-sm bg-slate-50"
                        v-model="form.email" required autocomplete="username" placeholder="soldado@ejemplo.com" />
                    <p v-if="form.errors.email" class="mt-1 text-xs text-red-600 font-bold">{{ form.errors.email }}</p>
                </div>

                <div>
                    <label for="password" class="block text-sm font-bold text-slate-700 mb-1">Contraseña</label>
                    <input id="password" type="password"
                        class="w-full rounded-xl border-slate-300 focus:border-red-500 focus:ring-red-500 shadow-sm bg-slate-50"
                        v-model="form.password" required autocomplete="new-password" placeholder="••••••••" />
                    <p v-if="form.errors.password" class="mt-1 text-xs text-red-600 font-bold">{{ form.errors.password
                        }}</p>
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-bold text-slate-700 mb-1">Confirmar
                        Contraseña</label>
                    <input id="password_confirmation" type="password"
                        class="w-full rounded-xl border-slate-300 focus:border-red-500 focus:ring-red-500 shadow-sm bg-slate-50"
                        v-model="form.password_confirmation" required autocomplete="new-password"
                        placeholder="••••••••" />
                    <p v-if="form.errors.password_confirmation" class="mt-1 text-xs text-red-600 font-bold">{{
                        form.errors.password_confirmation }}</p>
                </div>

                <button type="submit" :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                    :disabled="form.processing"
                    class="w-full mt-6 bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-4 rounded-xl shadow-lg transition-transform hover:-translate-y-0.5 flex justify-center items-center gap-2">
                    <span>Crear Cuenta</span>
                </button>
            </form>

            <div class="mt-3 text-center border-t border-slate-100 pt-6">
                <p class="text-sm text-slate-500 font-medium">
                    ¿Ya estás enlistado?
                    <Link :href="route('login')" class="font-bold text-slate-900 hover:text-blue-600 transition-colors">
                        Entrar al radar</Link>
                </p>
            </div>
        </div>

        <div class="z-10 mt-8 text-slate-400 text-xs font-medium">
            &copy; {{ new Date().getFullYear() }} Finanzas de Combate. Todos los derechos reservados.
        </div>
    </div>
</template>
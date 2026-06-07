<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue'; // NUEVO: Importamos ref

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const showPassword = ref(false);
const togglePassword = () => {
    showPassword.value = !showPassword.value;
};

const showPasswordConfirmation = ref(false);
const togglePasswordConfirmation = () => {
    showPasswordConfirmation.value = !showPasswordConfirmation.value;
};

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
                        v-model="form.name" required autofocus autocomplete="name" placeholder="Ej: Nombre del guerrero" />
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
                    
                    <!-- Contenedor relativo para posicionar el ojito -->
                    <div class="relative">
                        <input id="password" :type="showPassword ? 'text' : 'password'"
                            class="w-full rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm bg-slate-50 pr-10"
                            v-model="form.password" required autocomplete="current-password" placeholder="••••••••" />
                        
                        <!-- Botón del ojito -->
                        <button type="button" @click="togglePassword"
                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600 focus:outline-none transition-colors">
                            
                            <!-- Icono Ojo Abierto -->
                            <svg v-if="!showPassword" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            
                            <!-- Icono Ojo Cerrado (Tachado) -->
                            <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                            </svg>
                        </button>
                    </div>

                    <p v-if="form.errors.password" class="mt-1 text-xs text-red-600 font-bold">{{ form.errors.password }}</p>
                </div>

                <div>
                    <label for="password" class="block text-sm font-bold text-slate-700 mb-1">Confirmar
                        Contraseña</label>
                    
                    <!-- Contenedor relativo para posicionar el ojito -->
                    <div class="relative">
                        <input id="password_confirmation" :type="showPasswordConfirmation ? 'text' : 'password'"
                            class="w-full rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm bg-slate-50 pr-10"
                            v-model="form.password_confirmation" required autocomplete="current-password" placeholder="••••••••" />
                        
                        <!-- Botón del ojito -->
                        <button type="button" @click="togglePasswordConfirmation"
                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600 focus:outline-none transition-colors">
                            
                            <!-- Icono Ojo Abierto -->
                            <svg v-if="!showPasswordConfirmation" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            
                            <!-- Icono Ojo Cerrado (Tachado) -->
                            <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                            </svg>
                        </button>
                    </div>

                    <p v-if="form.errors.password_confirmation" class="mt-1 text-xs text-red-600 font-bold">{{ form.errors.password_confirmation }}</p>
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
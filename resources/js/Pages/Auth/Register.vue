<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

// Este cerrojo solo se abre (retorna true) si TODAS las condiciones se cumplen
const esPasswordSeguro = computed(() => {
    const p = form.password;
    return p.length >= 8 &&
        /[A-Z]/.test(p) &&
        /[a-z]/.test(p) &&
        /[0-9]/.test(p) &&
        /[^A-Za-z0-9]/.test(p);
});

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    terms: false,
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
                        v-model="form.name" required autofocus autocomplete="name"
                        placeholder="Ej: Nombre del guerrero" />
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
                            <svg v-if="!showPassword" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>

                            <!-- Icono Ojo Cerrado (Tachado) -->
                            <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                            </svg>
                        </button>
                    </div>

                    <p v-if="form.errors.password" class="mt-1 text-xs text-red-600 font-bold">{{ form.errors.password
                    }}</p>
                </div>

                <div>
                    <label for="password" class="block text-sm font-bold text-slate-700 mb-1">Confirmar
                        Contraseña</label>

                    <!-- Contenedor relativo para posicionar el ojito -->
                    <div class="relative">
                        <input id="password_confirmation" :type="showPasswordConfirmation ? 'text' : 'password'"
                            class="w-full rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm bg-slate-50 pr-10"
                            v-model="form.password_confirmation" required autocomplete="current-password"
                            placeholder="••••••••" />

                        <!-- Botón del ojito -->
                        <button type="button" @click="togglePasswordConfirmation"
                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600 focus:outline-none transition-colors">

                            <!-- Icono Ojo Abierto -->
                            <svg v-if="!showPasswordConfirmation" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>

                            <!-- Icono Ojo Cerrado (Tachado) -->
                            <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                            </svg>
                        </button>
                    </div>

                    <div class="mt-2 text-[11px] sm:text-xs text-slate-400 flex flex-col gap-1">
                        <div class="flex items-center gap-2">
                            <svg v-if="form.password.length >= 8" class="w-3.5 h-3.5 text-emerald-500" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                            <svg v-else class="w-3.5 h-3.5 text-slate-600" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="9" />
                            </svg>
                            <span :class="{ 'text-emerald-400 font-medium': form.password.length >= 8 }">Mínimo 8
                                caracteres</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg v-if="/[A-Z]/.test(form.password)" class="w-3.5 h-3.5 text-emerald-500" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                            <svg v-else class="w-3.5 h-3.5 text-slate-600" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="9" />
                            </svg>
                            <span :class="{ 'text-emerald-400 font-medium': /[A-Z]/.test(form.password) }">Al menos 1
                                mayúscula</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg v-if="/[a-z]/.test(form.password)" class="w-3.5 h-3.5 text-emerald-500" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                            <svg v-else class="w-3.5 h-3.5 text-slate-600" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="9" />
                            </svg>
                            <span :class="{ 'text-emerald-400 font-medium': /[a-z]/.test(form.password) }">Al menos 1
                                minúscula</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg v-if="/[0-9]/.test(form.password)" class="w-3.5 h-3.5 text-emerald-500" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                            <svg v-else class="w-3.5 h-3.5 text-slate-600" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="9" />
                            </svg>
                            <span :class="{ 'text-emerald-400 font-medium': /[0-9]/.test(form.password) }">Al menos 1
                                número</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg v-if="/[^A-Za-z0-9]/.test(form.password)" class="w-3.5 h-3.5 text-emerald-500"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                            <svg v-else class="w-3.5 h-3.5 text-slate-600" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="9" />
                            </svg>
                            <span :class="{ 'text-emerald-400 font-medium': /[^A-Za-z0-9]/.test(form.password) }">Al
                                menos 1
                                carácter especial</span>
                        </div>
                    </div>

                    <p v-if="form.errors.password_confirmation" class="mt-1 text-xs text-red-600 font-bold">{{
                        form.errors.password_confirmation }}</p>
                </div>

                <!-- Aceptación de Términos y Condiciones -->
                <div>
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input id="terms" type="checkbox" v-model="form.terms"
                            class="mt-0.5 h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer" />
                        <span class="text-sm text-slate-600">
                            He leído y acepto los
                            <a :href="route('terminos')" target="_blank"
                                class="font-bold text-blue-600 hover:underline">
                                Términos y Condiciones
                            </a>
                        </span>
                    </label>
                    <p v-if="form.errors.terms" class="mt-1 text-xs text-red-600 font-bold">
                        {{ form.errors.terms }}
                    </p>
                </div>

                <button type="submit" :disabled="!esPasswordSeguro || form.processing"
                    :class="{ 'opacity-50 cursor-not-allowed grayscale': !esPasswordSeguro }"
                    class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-3 px-4 rounded-lg transition-all">
                    Crear Cuenta
                </button>
            </form>

            <!-- ── Google SSO ─────────────────────────────────────────── -->
            <div class="relative flex items-center my-5">
                <div class="flex-grow border-t border-slate-200"></div>
                <span class="flex-shrink mx-3 text-xs font-semibold text-slate-400 uppercase tracking-widest">
                    O continuar con
                </span>
                <div class="flex-grow border-t border-slate-200"></div>
            </div>

            <a href="/auth/google"
               class="w-full flex items-center justify-center gap-3 bg-white border border-slate-300 hover:border-slate-400 hover:bg-slate-50 text-slate-800 font-semibold py-3 px-4 rounded-xl shadow-sm hover:shadow-md transition-all duration-200">
                <!-- Google "G" logo SVG -->
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" class="w-5 h-5 flex-shrink-0">
                    <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v8.51h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.14z"/>
                    <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                    <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                    <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                </svg>
                <span>Continuar con Google</span>
            </a>
            <!-- ────────────────────────────────────────────────────────── -->

            <div class="mt-3 text-center border-t border-slate-100 pt-6">
                <p class="text-sm text-slate-500 font-medium">
                    ¿Ya estás enlistado?
                    <Link :href="route('login')" class="font-bold text-slate-900 hover:text-blue-600 transition-colors">
                        Entrar al radar</Link>
                </p>
            </div>
        </div>

        <div class="z-10 mt-8 text-slate-400 text-xs font-medium">
            &copy; {{ new Date().getFullYear() }} FinanzasRPG. Todos los derechos reservados.
        </div>
    </div>
</template>
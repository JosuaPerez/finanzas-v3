<script setup>
import InputError from '@/Components/InputError.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const passwordInput        = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
    current_password:      '',
    password:              '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value.focus();
            }
        },
    });
};
</script>

<template>
    <section class="bg-slate-900/80 backdrop-blur-sm border border-slate-700/60 ring-1 ring-white/5 sm:rounded-3xl shadow-xl p-6 sm:p-8">
        <header class="mb-6">
            <h2 class="text-lg font-black text-white tracking-tight flex items-center gap-2">
                🔐 Cambiar Contraseña
            </h2>
            <p class="mt-1 text-sm text-slate-400">
                Usa una contraseña larga y aleatoria para mantener tu base segura.
            </p>
        </header>

        <form @submit.prevent="updatePassword" class="space-y-5">

            <!-- Current password -->
            <div>
                <label for="current_password" class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">
                    Contraseña Actual
                </label>
                <input
                    id="current_password"
                    ref="currentPasswordInput"
                    v-model="form.current_password"
                    type="password"
                    autocomplete="current-password"
                    class="block w-full bg-slate-950 border border-slate-700 text-white rounded-xl px-4 py-3 text-sm font-medium placeholder-slate-600 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/30 transition-colors"
                    placeholder="••••••••"
                />
                <InputError :message="form.errors.current_password" class="mt-2" />
            </div>

            <!-- New password -->
            <div>
                <label for="password" class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">
                    Nueva Contraseña
                </label>
                <input
                    id="password"
                    ref="passwordInput"
                    v-model="form.password"
                    type="password"
                    autocomplete="new-password"
                    class="block w-full bg-slate-950 border border-slate-700 text-white rounded-xl px-4 py-3 text-sm font-medium placeholder-slate-600 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/30 transition-colors"
                    placeholder="••••••••"
                />
                <InputError :message="form.errors.password" class="mt-2" />
            </div>

            <!-- Confirm password -->
            <div>
                <label for="password_confirmation" class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">
                    Confirmar Nueva Contraseña
                </label>
                <input
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    autocomplete="new-password"
                    class="block w-full bg-slate-950 border border-slate-700 text-white rounded-xl px-4 py-3 text-sm font-medium placeholder-slate-600 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/30 transition-colors"
                    placeholder="••••••••"
                />
                <InputError :message="form.errors.password_confirmation" class="mt-2" />
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-4 pt-2">
                <button
                    type="submit"
                    :disabled="form.processing"
                    :class="{ 'opacity-60 cursor-wait pointer-events-none': form.processing }"
                    class="inline-flex items-center gap-2 px-6 py-3 rounded-xl font-black uppercase tracking-widest text-white bg-blue-600 hover:bg-blue-500 border border-blue-500 shadow-[0_0_14px_rgba(37,99,235,0.35)] hover:shadow-[0_0_20px_rgba(37,99,235,0.5)] transition-all duration-300 ease-out hover:scale-105 hover:-translate-y-0.5 text-sm"
                >
                    🔒 Actualizar Contraseña
                </button>

                <Transition
                    enter-active-class="transition ease-in-out duration-300"
                    enter-from-class="opacity-0 translate-x-2"
                    enter-to-class="opacity-100 translate-x-0"
                    leave-active-class="transition ease-in-out duration-200"
                    leave-to-class="opacity-0"
                >
                    <p v-if="form.recentlySuccessful" class="text-sm font-bold text-emerald-400 flex items-center gap-1.5">
                        <span>✅</span> Contraseña actualizada
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>

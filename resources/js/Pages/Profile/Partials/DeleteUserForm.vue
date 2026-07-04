<script setup>
import InputError from '@/Components/InputError.vue';
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';

const confirmingUserDeletion = ref(false);
const passwordInput          = ref(null);

const form = useForm({ password: '' });

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;
    nextTick(() => passwordInput.value?.focus());
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess:  () => closeModal(),
        onError:    () => passwordInput.value?.focus(),
        onFinish:   () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;
    form.clearErrors();
    form.reset();
};
</script>

<template>
    <section class="bg-slate-900/80 backdrop-blur-sm border border-red-900/40 ring-1 ring-red-500/10 sm:rounded-3xl shadow-xl p-6 sm:p-8">
        <header class="mb-6">
            <h2 class="text-lg font-black text-white tracking-tight flex items-center gap-2">
                💥 Protocolo de Autodestrucción
            </h2>
            <p class="mt-1 text-sm text-slate-400">
                Una vez que elimines tu cuenta, todos tus datos serán destruidos permanentemente. Esta acción no se puede deshacer.
            </p>
        </header>

        <button
            @click="confirmUserDeletion"
            class="inline-flex items-center gap-2 px-6 py-3 rounded-xl font-black uppercase tracking-widest text-white bg-red-700 hover:bg-red-600 border border-red-600 shadow-[0_0_14px_rgba(239,68,68,0.25)] hover:shadow-[0_0_20px_rgba(239,68,68,0.4)] transition-all duration-300 ease-out hover:scale-105 hover:-translate-y-0.5 text-sm"
        >
            🗑️ Eliminar Cuenta
        </button>

        <!-- Confirmation Modal -->
        <Transition
            enter-active-class="ease-out duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="ease-in duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="confirmingUserDeletion" class="fixed inset-0 z-50 flex items-center justify-center px-4">
                <!-- Backdrop -->
                <div class="absolute inset-0 bg-slate-950/80 backdrop-blur-md" @click="closeModal"></div>

                <!-- Modal panel -->
                <Transition
                    enter-active-class="ease-out duration-300"
                    enter-from-class="opacity-0 translate-y-4 scale-95"
                    enter-to-class="opacity-100 translate-y-0 scale-100"
                    leave-active-class="ease-in duration-200"
                    leave-from-class="opacity-100 translate-y-0 scale-100"
                    leave-to-class="opacity-0 translate-y-4 scale-95"
                    appear
                >
                    <div class="relative w-full max-w-lg bg-slate-900 border border-slate-700 rounded-3xl shadow-[0_0_60px_rgba(0,0,0,0.7)] ring-1 ring-white/5 overflow-hidden">
                        <!-- Red danger stripe -->
                        <div class="h-1 w-full bg-gradient-to-r from-red-600 via-red-500 to-orange-500"></div>

                        <div class="p-8">
                            <div class="text-4xl mb-4">⚠️</div>
                            <h2 class="text-xl font-black text-white mb-2">¿Confirmar eliminación?</h2>
                            <p class="text-sm text-slate-400 leading-relaxed mb-6">
                                Esta acción destruirá tu cuenta permanentemente. Todos tus presupuestos, deudas y metas serán eliminados sin posibilidad de recuperación. Ingresa tu contraseña para confirmar.
                            </p>

                            <div class="mb-2">
                                <label for="delete_password" class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">
                                    Contraseña de Confirmación
                                </label>
                                <input
                                    id="delete_password"
                                    ref="passwordInput"
                                    v-model="form.password"
                                    type="password"
                                    autocomplete="current-password"
                                    @keyup.enter="deleteUser"
                                    class="block w-full bg-slate-950 border border-slate-700 text-white rounded-xl px-4 py-3 text-sm font-medium placeholder-slate-600 focus:outline-none focus:border-red-500 focus:ring-2 focus:ring-red-500/30 transition-colors"
                                    placeholder="••••••••"
                                />
                                <InputError :message="form.errors.password" class="mt-2" />
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 px-8 pb-8">
                            <button
                                @click="closeModal"
                                type="button"
                                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-bold text-slate-400 hover:text-white border border-slate-700 hover:border-slate-500 hover:bg-slate-800 transition-all duration-200 text-sm"
                            >
                                Cancelar
                            </button>
                            <button
                                @click="deleteUser"
                                type="button"
                                :disabled="form.processing"
                                :class="{ 'opacity-60 cursor-wait pointer-events-none': form.processing }"
                                class="inline-flex items-center gap-2 px-6 py-3 rounded-xl font-black uppercase tracking-widest text-white bg-red-700 hover:bg-red-600 border border-red-600 shadow-[0_0_14px_rgba(239,68,68,0.3)] hover:shadow-[0_0_24px_rgba(239,68,68,0.5)] transition-all duration-300 ease-out hover:scale-105 hover:-translate-y-0.5 text-sm"
                            >
                                💀 Confirmar Eliminación
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </section>
</template>

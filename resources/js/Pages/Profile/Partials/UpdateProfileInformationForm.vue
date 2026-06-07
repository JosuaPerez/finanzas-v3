<script setup>
import InputError from '@/Components/InputError.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: { type: Boolean },
    status:          { type: String  },
});

const user = usePage().props.auth.user;

const form = useForm({
    name:  user.name,
    email: user.email,
});
</script>

<template>
    <section class="bg-slate-900/80 backdrop-blur-sm border border-slate-700/60 ring-1 ring-white/5 sm:rounded-3xl shadow-xl p-6 sm:p-8">
        <!-- Section header -->
        <header class="mb-6">
            <h2 class="text-lg font-black text-white tracking-tight flex items-center gap-2">
                🪪 Identidad del Comandante
            </h2>
            <p class="mt-1 text-sm text-slate-400">
                Actualiza el nombre y la dirección de correo asociados a tu cuenta.
            </p>
        </header>

        <form @submit.prevent="form.patch(route('profile.update'))" class="space-y-5">

            <!-- Name -->
            <div>
                <label for="name" class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">
                    Nombre
                </label>
                <input
                    id="name"
                    type="text"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                    class="block w-full bg-slate-950 border border-slate-700 text-white rounded-xl px-4 py-3 text-sm font-medium placeholder-slate-600 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/30 transition-colors"
                    placeholder="Tu nombre de comandante"
                />
                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <!-- Email -->
            <div>
                <label for="email" class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">
                    Correo Electrónico
                </label>
                <input
                    id="email"
                    type="email"
                    v-model="form.email"
                    required
                    autocomplete="username"
                    class="block w-full bg-slate-950 border border-slate-700 text-white rounded-xl px-4 py-3 text-sm font-medium placeholder-slate-600 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/30 transition-colors"
                    placeholder="correo@ejemplo.com"
                />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <!-- Email verification notice -->
            <div v-if="mustVerifyEmail && user.email_verified_at === null" class="p-4 bg-amber-900/20 border border-amber-500/30 rounded-xl">
                <p class="text-sm text-amber-300 font-medium">
                    Tu correo no está verificado.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="ml-1 underline text-amber-400 hover:text-amber-200 font-bold transition-colors"
                    >
                        Reenviar verificación
                    </Link>
                </p>
                <p v-show="status === 'verification-link-sent'" class="mt-2 text-sm font-bold text-emerald-400">
                    ✅ Enlace de verificación enviado a tu correo.
                </p>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-4 pt-2">
                <button
                    type="submit"
                    :disabled="form.processing"
                    :class="{ 'opacity-60 cursor-wait pointer-events-none': form.processing }"
                    class="inline-flex items-center gap-2 px-6 py-3 rounded-xl font-black uppercase tracking-widest text-white bg-blue-600 hover:bg-blue-500 border border-blue-500 shadow-[0_0_14px_rgba(37,99,235,0.35)] hover:shadow-[0_0_20px_rgba(37,99,235,0.5)] transition-all duration-300 ease-out hover:scale-105 hover:-translate-y-0.5 text-sm"
                >
                    💾 Guardar Cambios
                </button>

                <Transition
                    enter-active-class="transition ease-in-out duration-300"
                    enter-from-class="opacity-0 translate-x-2"
                    enter-to-class="opacity-100 translate-x-0"
                    leave-active-class="transition ease-in-out duration-200"
                    leave-to-class="opacity-0"
                >
                    <p v-if="form.recentlySuccessful" class="text-sm font-bold text-emerald-400 flex items-center gap-1.5">
                        <span>✅</span> Guardado
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>

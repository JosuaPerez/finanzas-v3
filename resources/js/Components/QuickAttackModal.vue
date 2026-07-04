<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';

// ── Visibility ──────────────────────────────────────────────────────────────
const isOpen = ref(false);

const open  = () => { isOpen.value = true;  document.body.style.overflow = 'hidden'; };
const close = () => { isOpen.value = false; document.body.style.overflow = ''; form.reset(); displayMonto.value = ''; };

// ── Ctrl+K / Cmd+K shortcut ─────────────────────────────────────────────────
const handleKeydown = (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        isOpen.value ? close() : open();
    }
    if (e.key === 'Escape' && isOpen.value) {
        close();
    }
};

onMounted(()   => {
    window.addEventListener('keydown', handleKeydown);
    window.addEventListener('open-quick-attack', open);
});
onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
    window.removeEventListener('open-quick-attack', open);
});

// ── Form ─────────────────────────────────────────────────────────────────────
const form = useForm({
    monto:       '',
    descripcion: '',
});

// ── Comma-formatted display for monto ────────────────────────────────────────
const displayMonto = ref('');
const fmt = new Intl.NumberFormat('en-US', { maximumFractionDigits: 2 });

const onMontoInput = (e) => {
    const raw = e.target.value.replace(/[^\d.]/g, '');
    const num = parseFloat(raw);
    form.monto = isNaN(num) ? '' : num;
    // Format display but keep cursor-friendly: only format if it's a valid number
    // and the user isn't mid-typing a decimal (e.g. "12.")
    if (raw === '' || raw.endsWith('.') || raw.endsWith('.0')) {
        displayMonto.value = raw;
    } else {
        displayMonto.value = isNaN(num) ? '' : fmt.format(num);
    }
};

const submit = () => {
    form.post(route('quick-attack.store'), {
        preserveScroll: true,
        onSuccess: () => close(),
    });
};
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <!-- Backdrop -->
            <div
                v-if="isOpen"
                class="fixed inset-0 z-[100] bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4"
                @click.self="close"
            >
                <Transition
                    enter-active-class="transition-all duration-300 ease-out"
                    enter-from-class="opacity-0 scale-95 -translate-y-4"
                    enter-to-class="opacity-100 scale-100 translate-y-0"
                    appear
                >
                    <!-- Modal panel -->
                    <div
                        v-if="isOpen"
                        class="relative w-full max-w-md bg-slate-900 border border-slate-700 rounded-2xl shadow-[0_0_60px_rgba(139,92,246,0.25)] overflow-hidden"
                    >
                        <!-- Top glow line -->
                        <div class="absolute top-0 left-0 right-0 h-px bg-gradient-to-r from-transparent via-violet-500/60 to-transparent"></div>
                        <!-- Ambient blob -->
                        <div class="absolute -top-16 -right-16 w-48 h-48 bg-violet-600 rounded-full mix-blend-screen filter blur-[80px] opacity-10 pointer-events-none"></div>

                        <!-- Header -->
                        <div class="flex items-center justify-between px-6 pt-5 pb-4 border-b border-slate-800 relative z-10">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 bg-violet-500/15 border border-violet-500/30 rounded-xl flex items-center justify-center text-lg">
                                    ⚡
                                </div>
                                <div>
                                    <p class="text-[10px] font-black tracking-[0.2em] uppercase text-violet-400">Ataque Rápido</p>
                                    <h2 class="text-sm font-black text-white leading-tight">Registrar Suministro</h2>
                                </div>
                            </div>
                            <button
                                @click="close"
                                class="w-8 h-8 rounded-lg bg-slate-800 border border-slate-700 flex items-center justify-center text-slate-400 hover:text-white hover:border-slate-600 transition-all"
                                aria-label="Cerrar"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        <!-- Form body -->
                        <form @submit.prevent="submit" class="px-6 py-5 space-y-4 relative z-10">

                            <!-- Monto -->
                            <div>
                                <label for="qa-monto" class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-1.5">
                                    Monto
                                </label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-500 font-bold text-sm">$</span>
                                    <input
                                        id="qa-monto"
                                        type="text"
                                        inputmode="decimal"
                                        :value="displayMonto"
                                        @input="onMontoInput"
                                        placeholder="0.00"
                                        autofocus
                                        class="w-full bg-slate-950 border border-slate-700 text-white rounded-xl pl-8 pr-4 py-2.5 text-sm font-semibold placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:border-violet-500 transition-all"
                                    />
                                </div>
                                <p v-if="form.errors.monto" class="mt-1 text-xs text-red-400">{{ form.errors.monto }}</p>
                            </div>

                            <!-- Descripción -->
                            <div>
                                <label for="qa-descripcion" class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-1.5">
                                    Descripción
                                </label>
                                <input
                                    id="qa-descripcion"
                                    type="text"
                                    v-model="form.descripcion"
                                    placeholder="Ej: Almuerzo, Gasolina, Medicinas…"
                                    maxlength="255"
                                    class="w-full bg-slate-950 border border-slate-700 text-white rounded-xl px-4 py-2.5 text-sm font-semibold placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:border-violet-500 transition-all"
                                />
                                <p v-if="form.errors.descripcion" class="mt-1 text-xs text-red-400">{{ form.errors.descripcion }}</p>
                            </div>

                            <!-- Submit -->
                            <button
                                id="qa-submit"
                                type="submit"
                                :disabled="form.processing || !form.monto || !form.descripcion"
                                :class="[
                                    'w-full flex items-center justify-center gap-2.5 px-5 py-3.5 rounded-xl font-black uppercase tracking-widest text-sm transition-all duration-200',
                                    form.processing || !form.monto || !form.descripcion
                                        ? 'bg-slate-800 text-slate-600 cursor-not-allowed border border-slate-700'
                                        : 'bg-violet-600 hover:bg-violet-500 text-white border border-violet-500 shadow-[0_0_20px_rgba(139,92,246,0.4)] hover:shadow-[0_0_30px_rgba(139,92,246,0.6)] hover:-translate-y-0.5 active:scale-95'
                                ]"
                            >
                                <svg v-if="form.processing" class="animate-spin w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                                </svg>
                                <span>{{ form.processing ? 'Registrando…' : '⚡ Registrar Ataque' }}</span>
                            </button>
                        </form>

                        <!-- Footer hint -->
                        <div class="px-6 pb-4 flex items-center justify-between relative z-10">
                            <p class="text-[10px] text-slate-600 uppercase tracking-widest">+15 XP por registro</p>
                            <div class="flex items-center gap-1.5">
                                <kbd class="px-1.5 py-0.5 text-[10px] font-mono font-bold bg-slate-800 border border-slate-700 rounded text-slate-500">Ctrl</kbd>
                                <span class="text-slate-700 text-[10px]">+</span>
                                <kbd class="px-1.5 py-0.5 text-[10px] font-mono font-bold bg-slate-800 border border-slate-700 rounded text-slate-500">K</kbd>
                                <span class="text-[10px] text-slate-600 ml-1">para abrir</span>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    message: {
        type: String,
        default: '',
    },
    type: {
        type: String,
        default: 'success', // 'success' | 'error'
    },
    // If true, forces critical styling regardless of message content
    forceCritical: {
        type: Boolean,
        default: false,
    },
});

// Detect Critical Hit from message content (RPG flavor keywords)
const isCritical = computed(() => {
    if (props.forceCritical) return true;
    if (props.type !== 'success') return false;
    return /crítico|critical|golpe final|objeto forjado|destruido/i.test(props.message);
});

// Split message to highlight numeric damage amounts in emerald
// Looks for patterns like "1,234.00" or "1234.00" within the message
const messageParts = computed(() => {
    if (!isCritical.value) return null;

    // Regex to find currency amounts (e.g. "1,234.00" or "50.00")
    const amountRegex = /(\d{1,3}(?:,\d{3})*(?:\.\d{2})?|\d+(?:\.\d{2})?)/g;
    const parts = [];
    let lastIndex = 0;
    let match;

    while ((match = amountRegex.exec(props.message)) !== null) {
        if (match.index > lastIndex) {
            parts.push({ text: props.message.slice(lastIndex, match.index), isAmount: false });
        }
        parts.push({ text: match[0], isAmount: true });
        lastIndex = amountRegex.lastIndex;
    }

    if (lastIndex < props.message.length) {
        parts.push({ text: props.message.slice(lastIndex), isAmount: false });
    }

    return parts.length > 1 ? parts : null; // Only split if we found amounts
});
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition ease-out duration-300"
            enter-from-class="opacity-0 translate-y-4 translate-x-4"
            enter-to-class="opacity-100 translate-y-0 translate-x-0"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="opacity-100 translate-y-0 translate-x-0"
            leave-to-class="opacity-0 translate-y-2 translate-x-2"
        >
            <div
                v-if="show"
                class="fixed bottom-6 right-6 z-[9999] max-w-sm w-full pointer-events-none"
            >
                <div
                    class="relative overflow-hidden rounded-2xl px-5 py-4 backdrop-blur-md border shadow-2xl flex items-start gap-4 transition-all"
                    :class="[
                        type === 'success'
                            ? 'bg-slate-950/85 border-emerald-500/50'
                            : 'bg-slate-950/85 border-red-500/50',
                        isCritical
                            ? 'shadow-[0_0_24px_rgba(16,185,129,0.30),0_10px_40px_rgba(0,0,0,0.6)]'
                            : 'shadow-[0_10px_40px_rgba(0,0,0,0.5)]',
                    ]"
                >
                    <!-- Ambient glow bar at top -->
                    <div
                        class="absolute top-0 left-0 right-0 h-[2px]"
                        :class="type === 'success' ? 'bg-gradient-to-r from-emerald-600 via-teal-400 to-emerald-600' : 'bg-gradient-to-r from-red-600 via-red-400 to-red-600'"
                    ></div>

                    <!-- Icon -->
                    <div
                        class="flex-shrink-0 w-9 h-9 rounded-xl flex items-center justify-center text-base border"
                        :class="
                            type === 'success'
                                ? 'bg-emerald-500/15 border-emerald-500/40 text-emerald-400'
                                : 'bg-red-500/15 border-red-500/40 text-red-400'
                        "
                    >
                        <span>{{ type === 'success' ? (isCritical ? '💥' : '🎖️') : '⚠️' }}</span>
                    </div>

                    <!-- Message content -->
                    <div class="flex-1 min-w-0">
                        <!-- Critical hit label -->
                        <div
                            v-if="isCritical"
                            class="text-[9px] font-black uppercase tracking-widest text-emerald-500 mb-1 font-mono"
                        >
                            ⚡ Critical Hit
                        </div>

                        <!-- Message with optional damage highlighting -->
                        <p class="text-sm font-bold text-white leading-snug">
                            <template v-if="messageParts">
                                <template v-for="(part, i) in messageParts" :key="i">
                                    <span
                                        v-if="part.isAmount"
                                        class="text-emerald-400 font-mono font-black tracking-tight"
                                    >{{ part.text }}</span>
                                    <span v-else>{{ part.text }}</span>
                                </template>
                            </template>
                            <template v-else>{{ message }}</template>
                        </p>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

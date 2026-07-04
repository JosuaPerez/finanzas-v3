import { ref, watch, onMounted } from 'vue';

/**
 * useCountUp — animates a number from 0 to `target` using requestAnimationFrame.
 *
 * @param {Function|Object|number} target - getter fn, Vue Ref, or plain number.
 * @param {object}  options
 * @param {number}  options.duration  - Animation duration in ms. Default: 900.
 * @param {boolean} options.immediate - Start animation on mount. Default: true.
 * @returns {{ displayed: import('vue').Ref<number> }}
 *
 * Usage:
 *   const { displayed } = useCountUp(() => props.totalDebts);
 *   // In template: {{ formatCurrency(displayed) }}
 */
export function useCountUp(target, { duration = 900, immediate = true } = {}) {
    const displayed = ref(0);
    let rafId = null;

    // easeOutExpo: fast start, smooth landing — premium feel without being slow.
    const ease = (t) => (t === 1 ? 1 : 1 - Math.pow(2, -10 * t));

    const animate = (from, to) => {
        if (rafId) cancelAnimationFrame(rafId);
        if (to === 0) { displayed.value = 0; return; }

        const start = performance.now();
        const tick = (now) => {
            const elapsed  = now - start;
            const progress = Math.min(elapsed / duration, 1);
            displayed.value = from + (to - from) * ease(progress);
            if (progress < 1) {
                rafId = requestAnimationFrame(tick);
            } else {
                displayed.value = to; // snap to exact value at end
            }
        };
        rafId = requestAnimationFrame(tick);
    };

    // Resolve target whether it is a getter fn, a Ref, or a plain number.
    const resolve = () => (typeof target === 'function' ? target() : (target?.value ?? target));

    if (immediate) {
        onMounted(() => animate(0, resolve()));
    }

    // Re-animate on value change (handles Inertia partial reloads gracefully).
    watch(resolve, (newVal, oldVal) => animate(oldVal ?? 0, newVal));

    return { displayed };
}
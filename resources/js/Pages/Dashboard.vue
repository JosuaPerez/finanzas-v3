<script setup>
import { computed, ref } from "vue";
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { useMoney } from "@/composables/useMoney";

const props = defineProps({
    available: Object,
    nextPayment: Object,
    featuredGoal: Object,
    debtTotals: { type: Array, default: () => [] },
    goalTotals: { type: Array, default: () => [] },
    combatLog: { type: Array, default: () => [] },
    achievements: { type: Array, default: () => [] },
    quests: Object,
    budgetCount: Number,
    activeDebtCount: Number,
});
const page = usePage();
const { money, date, currency } = useMoney();
const goalProgress = computed(() =>
    props.featuredGoal?.target_amount > 0
        ? Math.min(
              100,
              Math.max(
                  0,
                  (Number(props.featuredGoal.current_amount) /
                      Number(props.featuredGoal.target_amount)) *
                      100,
              ),
          )
        : 0,
);
const needsSetup = computed(
    () =>
        !page.props.auth.user.financial_preferences_set_at ||
        !props.budgetCount ||
        (!props.featuredGoal && !props.activeDebtCount),
);
const register = (debtId = null) =>
    window.dispatchEvent(
        new CustomEvent("open-quick-attack", {
            detail: debtId ? { type: "payment", debtId } : undefined,
        }),
    );
const claiming = ref(false);
// Quest rewards retain the existing endpoint and eligibility rules.
const claimQuest = () => {
    if (claiming.value) return;
    claiming.value = true;
    router.post(
        route("quests.claim"),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                claiming.value = false;
            },
        },
    );
};
</script>

<template>
    <Head title="Inicio" />
    <AuthenticatedLayout>
        <div class="mx-auto max-w-6xl space-y-6 px-4 py-6 sm:px-6 sm:py-9">
            <header
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <p class="text-sm text-slate-400">
                        Tu progreso, paso a paso
                    </p>
                    <h1
                        class="mt-1 text-2xl font-semibold tracking-tight text-white"
                    >
                        Hola, {{ page.props.auth.user.name.split(" ")[0] }}
                    </h1>
                </div>
                <button
                    id="register-movement"
                    class="finance-button w-full sm:w-auto"
                    @click="register()"
                >
                    <span aria-hidden="true" class="text-xl">+</span> Registrar
                    movimiento
                </button>
            </header>

            <section
                class="grid gap-4 lg:grid-cols-3"
                aria-label="Resumen financiero"
            >
                <article
                    class="rounded-2xl border border-cyan-900/60 bg-slate-900 p-5 sm:p-6"
                >
                    <h2 class="text-sm font-medium text-slate-300">
                        Disponible según tu presupuesto
                    </h2>
                    <p
                        v-if="available?.amount != null"
                        class="mt-4 break-words text-3xl font-semibold tabular-nums tracking-tight"
                        :class="
                            available.amount < 0 ? 'text-red-300' : 'text-white'
                        "
                    >
                        {{ money(available.amount, available.currency) }}
                    </p>
                    <p v-else class="mt-4 text-lg font-medium text-white">
                        {{
                            available
                                ? "Sin disponible calculado"
                                : "Aún no tienes un presupuesto"
                        }}
                    </p>
                    <p v-if="available" class="mt-2 text-xs text-slate-400">
                        {{ available.title }} · actualizado
                        {{ date(available.updated_at) }}
                    </p>
                    <p class="mt-4 text-sm leading-relaxed text-slate-400">
                        Es una estimación de tu presupuesto, no tu saldo
                        bancario. Los gastos rápidos se guardan en el historial
                        y no descuentan esta cifra.
                    </p>
                    <Link :href="route('presupuesto')" class="finance-link mt-3"
                        >{{
                            available
                                ? "Revisar presupuesto"
                                : "Registrar ingreso y presupuesto"
                        }}
                        <span aria-hidden="true">→</span></Link
                    >
                </article>
                <article
                    class="rounded-2xl border border-slate-800 bg-slate-900 p-5 sm:p-6"
                >
                    <h2 class="text-sm font-medium text-slate-300">
                        Próximo día de pago
                    </h2>
                    <template v-if="nextPayment">
                        <p class="mt-4 text-xl font-semibold text-white">
                            {{ date(nextPayment.date) }}
                        </p>
                        <p class="mt-2 break-words text-sm text-slate-300">
                            {{ nextPayment.name }}
                        </p>
                        <p
                            v-if="nextPayment.amount != null"
                            class="mt-3 font-semibold text-white"
                        >
                            {{
                                money(nextPayment.amount, nextPayment.currency)
                            }}
                            <span class="text-xs font-normal text-slate-400"
                                >· pago mínimo registrado</span
                            >
                        </p>
                        <p v-else class="mt-3 text-sm text-slate-400">
                            Sin importe mínimo registrado.
                        </p>
                        <p class="mt-3 text-xs leading-relaxed text-slate-400">
                            Según el día mensual que configuraste. No confirma
                            un vencimiento pendiente.
                        </p>
                        <button
                            class="finance-link mt-2"
                            @click="register(nextPayment.id)"
                        >
                            Registrar pago <span aria-hidden="true">→</span>
                        </button>
                    </template>
                    <template v-else>
                        <p class="mt-4 text-lg font-medium text-white">
                            Sin fechas de pago configuradas
                        </p>
                        <p class="mt-3 text-sm leading-relaxed text-slate-400">
                            Si tienes deudas, registra su día de pago para verlo
                            aquí. Puedes usar la app también si no tienes
                            deudas.
                        </p>
                        <Link :href="route('deudas')" class="finance-link mt-3"
                            >Ver deudas <span aria-hidden="true">→</span></Link
                        >
                    </template>
                </article>
                <article
                    class="rounded-2xl border border-slate-800 bg-slate-900 p-5 sm:p-6"
                >
                    <h2 class="text-sm font-medium text-slate-300">
                        Tu próxima meta
                    </h2>
                    <template v-if="featuredGoal">
                        <p
                            class="mt-4 break-words text-xl font-semibold text-white"
                        >
                            {{ featuredGoal.name }}
                        </p>
                        <p class="mt-3 text-sm text-slate-300">
                            {{
                                money(
                                    featuredGoal.current_amount,
                                    featuredGoal.currency,
                                )
                            }}
                            <span class="text-slate-400"
                                >de
                                {{
                                    money(
                                        featuredGoal.target_amount,
                                        featuredGoal.currency,
                                    )
                                }}</span
                            >
                        </p>
                        <div
                            role="progressbar"
                            aria-label="Progreso de tu meta"
                            :aria-valuenow="Math.round(goalProgress)"
                            aria-valuemin="0"
                            aria-valuemax="100"
                            class="mt-4 h-2 overflow-hidden rounded-full bg-slate-800"
                        >
                            <div
                                class="h-full rounded-full bg-emerald-400"
                                :style="{ width: `${goalProgress}%` }"
                            ></div>
                        </div>
                        <p class="mt-2 text-xs text-emerald-300">
                            {{ Math.round(goalProgress) }}% de tu objetivo
                        </p>
                        <Link :href="route('metas')" class="finance-link mt-3"
                            >{{
                                goalProgress === 100
                                    ? "Ver metas completadas"
                                    : "Añadir ahorro"
                            }}
                            <span aria-hidden="true">→</span></Link
                        >
                    </template>
                    <template v-else>
                        <p class="mt-4 text-lg font-medium text-white">
                            Dale un propósito a tu ahorro
                        </p>
                        <p class="mt-3 text-sm leading-relaxed text-slate-400">
                            Un fondo de emergencia, un viaje o lo que más te
                            importe. Empieza con una meta alcanzable.
                        </p>
                        <Link :href="route('metas')" class="finance-link mt-3"
                            >Crear mi primera meta
                            <span aria-hidden="true">→</span></Link
                        >
                    </template>
                </article>
            </section>

            <section
                v-if="needsSetup"
                class="rounded-2xl border border-slate-800 bg-slate-900 p-5 sm:p-6"
                aria-labelledby="first-steps"
            >
                <h2 id="first-steps" class="text-lg font-semibold text-white">
                    Empieza a tu ritmo
                </h2>
                <p class="mt-1 text-sm text-slate-400">
                    Configura tu moneda, organiza un ingreso y elige qué quieres
                    mejorar.
                </p>
                <ol class="mt-4 grid gap-3 md:grid-cols-3">
                    <li>
                        <Link
                            :href="
                                route('profile.edit') + '#financial-preferences'
                            "
                            class="flex min-h-[72px] items-center gap-3 rounded-xl border border-slate-800 px-4 py-3 hover:bg-slate-800"
                            ><span aria-hidden="true" class="text-cyan-300">{{
                                page.props.auth.user
                                    .financial_preferences_set_at
                                    ? "✓"
                                    : "1"
                            }}</span
                            ><span class="text-sm text-slate-200"
                                >Elegir moneda y formato<br /><span
                                    class="text-xs text-slate-400"
                                    >Actual: {{ currency }}</span
                                ></span
                            ></Link
                        >
                    </li>
                    <li>
                        <Link
                            :href="route('presupuesto')"
                            class="flex min-h-[72px] items-center gap-3 rounded-xl border border-slate-800 px-4 py-3 hover:bg-slate-800"
                            ><span aria-hidden="true" class="text-cyan-300">{{
                                budgetCount ? "✓" : "2"
                            }}</span
                            ><span class="text-sm text-slate-200"
                                >Registrar un ingreso<br /><span
                                    class="text-xs text-slate-400"
                                    >Planifica gastos y disponible</span
                                ></span
                            ></Link
                        >
                    </li>
                    <li>
                        <div
                            class="rounded-xl border border-slate-800 px-4 py-3"
                        >
                            <span class="text-sm text-slate-200"
                                >{{
                                    featuredGoal || activeDebtCount ? "✓" : "3"
                                }}
                                · Elige tu objetivo</span
                            >
                            <div class="flex flex-wrap gap-3">
                                <Link
                                    :href="route('metas')"
                                    class="finance-link"
                                    >Crear meta</Link
                                ><Link
                                    :href="route('deudas')"
                                    class="finance-link"
                                    >Registrar deuda</Link
                                >
                            </div>
                        </div>
                    </li>
                </ol>
            </section>

            <div class="grid gap-4 lg:grid-cols-2">
                <section
                    class="rounded-2xl border border-slate-800 bg-slate-900 p-5 sm:p-6"
                >
                    <h2 class="text-lg font-semibold text-white">
                        Deudas por moneda
                    </h2>
                    <ul
                        v-if="debtTotals.length"
                        class="mt-4 divide-y divide-slate-800"
                    >
                        <li
                            v-for="total in debtTotals"
                            :key="total.currency"
                            class="flex items-center justify-between gap-3 py-3"
                        >
                            <span class="text-sm text-slate-400">{{
                                total.currency
                            }}</span
                            ><span
                                class="font-medium tabular-nums text-white"
                                >{{ money(total.amount, total.currency) }}</span
                            >
                        </li>
                    </ul>
                    <p v-else class="mt-4 text-sm text-slate-400">
                        No tienes deudas activas registradas.
                    </p>
                    <p class="mt-3 text-xs text-slate-400">
                        Los importes se muestran separados, sin conversiones.
                    </p>
                </section>
                <section
                    class="rounded-2xl border border-slate-800 bg-slate-900 p-5 sm:p-6"
                >
                    <h2 class="text-lg font-semibold text-white">
                        Gastos recientes
                    </h2>
                    <ul
                        v-if="combatLog.length"
                        class="mt-3 divide-y divide-slate-800"
                    >
                        <li
                            v-for="(expense, index) in combatLog"
                            :key="index"
                            class="flex items-start justify-between gap-4 py-3"
                        >
                            <div class="min-w-0">
                                <p class="break-words text-sm text-slate-200">
                                    {{ expense.description }}
                                </p>
                                <p class="mt-1 text-xs text-slate-400">
                                    {{ expense.time }}
                                </p>
                            </div>
                            <span
                                class="shrink-0 text-sm font-medium tabular-nums text-white"
                                >{{
                                    money(expense.amount, expense.currency)
                                }}</span
                            >
                        </li>
                    </ul>
                    <p v-else class="mt-4 text-sm text-slate-400">
                        Aquí aparecerán los gastos que registres. Los pagos de
                        deuda se guardan en el presupuesto correspondiente.
                    </p>
                </section>
            </div>

            <details
                class="rounded-2xl border border-slate-800 bg-slate-900 p-5 sm:p-6"
            >
                <summary
                    class="min-h-11 cursor-pointer text-sm font-medium text-slate-300"
                >
                    ✦ Tu progreso RPG · Nivel {{ page.props.auth.user.level }}
                </summary>
                <p class="mt-2 text-sm text-slate-400">
                    {{ page.props.auth.user.rank_name }} ·
                    {{ page.props.auth.user.current_xp }} XP. Cada paso cuenta.
                </p>
                <ul class="mt-4 grid gap-3 sm:grid-cols-2">
                    <li
                        v-for="achievement in achievements"
                        :key="achievement.id"
                        class="rounded-xl border border-slate-800 p-3"
                    >
                        <p class="text-sm text-slate-200">
                            {{ achievement.icon_name }} {{ achievement.name }}
                        </p>
                        <p class="mt-1 text-xs text-slate-400">
                            {{
                                achievement.unlocked_at
                                    ? "Logro conseguido"
                                    : achievement.description
                            }}
                        </p>
                    </li>
                </ul>
                <div v-if="quests" class="mt-4 text-sm text-slate-300">
                    <p>
                        Misiones del día: {{ quests.completed_count }} / {{ 3 }}
                    </p>
                    <button
                        v-if="quests.can_claim"
                        class="finance-button mt-3"
                        :disabled="claiming"
                        @click="claimQuest"
                    >
                        Recoger recompensa
                    </button>
                </div>
            </details>
        </div>
    </AuthenticatedLayout>
</template>

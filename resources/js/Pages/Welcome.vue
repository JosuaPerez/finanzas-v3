<script setup>
import { Link } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head>
        <title>FinanzasRPG — Conquista tu Economía</title>
        <meta name="description" content="Maneja tus finanzas como un RPG. Derrota tus deudas, sube de nivel tus ahorros y conquista tu presupuesto." />
    </Head>

    <div class="min-h-screen bg-slate-950 text-white overflow-x-hidden">

        <!-- ═══ AMBIENT BACKGROUND ORBS ═══ -->
        <div class="fixed inset-0 pointer-events-none overflow-hidden" aria-hidden="true">
            <div class="absolute -top-40 -left-40 w-[700px] h-[700px] bg-blue-700 rounded-full mix-blend-screen filter blur-[160px] opacity-[0.12] animate-pulse-slow"></div>
            <div class="absolute top-1/3 -right-60 w-[600px] h-[600px] bg-violet-700 rounded-full mix-blend-screen filter blur-[180px] opacity-[0.10] animate-pulse-slow" style="animation-delay:2s"></div>
            <div class="absolute -bottom-40 left-1/4 w-[500px] h-[500px] bg-red-700 rounded-full mix-blend-screen filter blur-[160px] opacity-[0.08] animate-pulse-slow" style="animation-delay:4s"></div>
        </div>

        <!-- ═══ NAVBAR ═══ -->
        <nav class="relative z-20 flex items-center justify-between px-3 sm:px-10 lg:px-16 py-3 sm:py-5 border-b border-slate-800/60 backdrop-blur-md bg-slate-950/70">
            <div class="flex items-center gap-2 sm:gap-3 min-w-0">
                <div class="flex-shrink-0 w-7 h-7 sm:w-9 sm:h-9 bg-blue-500/20 border border-blue-500/40 rounded-xl flex items-center justify-center text-sm sm:text-xl shadow-[0_0_12px_rgba(59,130,246,0.3)]">
                    ⚔️
                </div>
                <span class="font-black text-[13px] sm:text-lg tracking-tight text-white truncate">Finanzas<span class="text-blue-400">RPG</span></span>
            </div>

            <div class="flex items-center gap-1.5 sm:gap-3 flex-shrink-0">
                <Link
                    id="nav-login-btn"
                    :href="route('login')"
                    class="whitespace-nowrap px-2.5 py-1.5 sm:px-5 sm:py-2 rounded-xl text-[11px] sm:text-sm font-bold text-slate-300 hover:text-white border border-slate-700 hover:border-slate-500 bg-transparent hover:bg-slate-800 transition-all duration-200"
                >
                    Iniciar Sesión
                </Link>
                <Link
                    id="nav-register-btn"
                    :href="route('register')"
                    class="whitespace-nowrap px-2.5 py-1.5 sm:px-5 sm:py-2 rounded-xl text-[11px] sm:text-sm font-bold text-white bg-blue-600 hover:bg-blue-500 border border-blue-500 hover:border-blue-400 shadow-[0_0_14px_rgba(37,99,235,0.4)] hover:shadow-[0_0_20px_rgba(37,99,235,0.6)] transition-all duration-200 hover:-translate-y-px"
                >
                    Registrarse
                </Link>
            </div>
        </nav>

        <!-- ═══ HERO ═══ -->
        <section class="relative z-10 flex flex-col items-center justify-center text-center px-6 pt-24 pb-20 sm:pt-32 sm:pb-28">

            <!-- Eyebrow badge -->
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-blue-500/10 border border-blue-500/30 text-blue-400 text-xs font-bold uppercase tracking-widest mb-8 shadow-[0_0_20px_rgba(59,130,246,0.15)]">
                <span class="w-1.5 h-1.5 rounded-full bg-blue-400 animate-ping"></span>
                Gestión Financiera · Modo Campaña
            </div>

            <!-- Headline -->
            <h1 class="text-5xl sm:text-6xl lg:text-7xl font-black leading-[1.05] tracking-tight mb-6 max-w-4xl">
                Tus finanzas.<br>
                <span class="relative inline-block">
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 via-violet-400 to-indigo-400">
                        Tu campo de batalla.
                    </span>
                </span>
            </h1>

            <!-- Sub-headline -->
            <p class="text-slate-400 text-lg sm:text-xl max-w-2xl leading-relaxed mb-12">
                Olvida las hojas de cálculo aburridas. Aquí cada <strong class="text-slate-200">deuda es un jefe</strong> con barra de vida, cada pago es un
                <strong class="text-slate-200">golpe crítico</strong>, y cada meta de ahorro es un
                <strong class="text-slate-200">nivel que conquistas</strong>.
            </p>

            <!-- CTA Buttons -->
            <div class="flex flex-col sm:flex-row items-center gap-4">
                <Link
                    id="hero-register-btn"
                    :href="route('register')"
                    class="group inline-flex items-center gap-3 px-8 py-4 rounded-2xl font-black uppercase tracking-widest text-white bg-blue-600 hover:bg-blue-500 shadow-[0_0_30px_rgba(37,99,235,0.5)] hover:shadow-[0_0_40px_rgba(37,99,235,0.7)] border border-blue-500 transition-all duration-300 hover:-translate-y-1 hover:scale-105 text-base"
                >
                    <span>⚔️</span>
                    Empezar Campaña
                    <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                </Link>
                <Link
                    id="hero-login-btn"
                    :href="route('login')"
                    class="inline-flex items-center gap-2 px-8 py-4 rounded-2xl font-bold uppercase tracking-wider text-slate-300 hover:text-white border border-slate-700 hover:border-slate-500 bg-slate-900/60 hover:bg-slate-800 backdrop-blur-sm transition-all duration-300 hover:-translate-y-1 text-base"
                >
                    Ya tengo cuenta
                </Link>
            </div>

            <!-- Social proof hint -->
            <p class="mt-8 text-xs text-slate-600 uppercase tracking-widest">100% gratis · Sin tarjeta · Sin trampas</p>
        </section>

        <!-- ═══ FEATURE CARDS ═══ -->
        <section class="relative z-10 px-6 sm:px-10 lg:px-16 pb-24">
            <div class="max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-3 gap-6">

                <!-- Card 1: Budget -->
                <div class="group relative p-8 rounded-3xl bg-slate-900/70 border border-slate-800 hover:border-blue-500/50 backdrop-blur-sm shadow-xl transition-all duration-300 hover:-translate-y-2 hover:shadow-[0_20px_50px_rgba(37,99,235,0.15)] overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-br from-blue-600/0 to-blue-600/0 group-hover:from-blue-600/5 group-hover:to-transparent transition-all duration-500 rounded-3xl pointer-events-none"></div>
                    <div class="absolute top-0 left-0 right-0 h-px bg-gradient-to-r from-transparent via-blue-500/50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    <div class="w-14 h-14 bg-blue-500/15 border border-blue-500/30 rounded-2xl flex items-center justify-center text-3xl mb-6 group-hover:scale-110 group-hover:shadow-[0_0_20px_rgba(59,130,246,0.3)] transition-all duration-300">
                        🛡️
                    </div>
                    <h3 class="text-xl font-black text-white mb-3">Planifica tu Defensa</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">
                        Registra tu ingreso, distribuye tus gastos fijos y descubre cuánto <span class="text-blue-400 font-semibold">capital libre</span> tienes para atacar. Tu presupuesto es tu mapa de guerra.
                    </p>
                    <div class="mt-6 flex items-center gap-2 text-blue-500 text-xs font-bold uppercase tracking-widest opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                        <span>Pestaña Presupuesto</span>
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                    </div>
                </div>

                <!-- Card 2: Debts — highlighted as primary -->
                <div class="group relative p-8 rounded-3xl bg-slate-900/70 border border-red-800/50 hover:border-red-500/70 backdrop-blur-sm shadow-xl shadow-red-950/20 transition-all duration-300 hover:-translate-y-2 hover:shadow-[0_20px_50px_rgba(239,68,68,0.15)] overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-br from-red-600/5 to-transparent rounded-3xl pointer-events-none"></div>
                    <div class="absolute top-0 left-0 right-0 h-px bg-gradient-to-r from-transparent via-red-500/60 to-transparent"></div>
                    <!-- "CORE" badge -->
                    <div class="absolute top-5 right-5 px-2 py-0.5 bg-red-500/20 border border-red-500/40 rounded-full text-red-400 text-[10px] font-black uppercase tracking-wider">
                        Núcleo
                    </div>
                    <div class="w-14 h-14 bg-red-500/15 border border-red-500/30 rounded-2xl flex items-center justify-center text-3xl mb-6 group-hover:scale-110 group-hover:shadow-[0_0_20px_rgba(239,68,68,0.3)] transition-all duration-300">
                        🔥
                    </div>
                    <h3 class="text-xl font-black text-white mb-3">Derrota a los Jefes</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">
                        Cada deuda tiene una <span class="text-red-400 font-semibold">barra de HP</span>. Cada pago le hace daño. Cuando llega a cero: <strong class="text-white">jefe derrotado</strong>. Así se siente pagar deudas aquí.
                    </p>
                    <div class="mt-6 flex items-center gap-2 text-red-400 text-xs font-bold uppercase tracking-widest opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                        <span>Pestaña Deudas</span>
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                    </div>
                </div>

                <!-- Card 3: Goals -->
                <div class="group relative p-8 rounded-3xl bg-slate-900/70 border border-slate-800 hover:border-emerald-500/50 backdrop-blur-sm shadow-xl transition-all duration-300 hover:-translate-y-2 hover:shadow-[0_20px_50px_rgba(16,185,129,0.12)] overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-br from-emerald-600/0 to-emerald-600/0 group-hover:from-emerald-600/5 group-hover:to-transparent transition-all duration-500 rounded-3xl pointer-events-none"></div>
                    <div class="absolute top-0 left-0 right-0 h-px bg-gradient-to-r from-transparent via-emerald-500/50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    <div class="w-14 h-14 bg-emerald-500/15 border border-emerald-500/30 rounded-2xl flex items-center justify-center text-3xl mb-6 group-hover:scale-110 group-hover:shadow-[0_0_20px_rgba(16,185,129,0.3)] transition-all duration-300">
                        🎯
                    </div>
                    <h3 class="text-xl font-black text-white mb-3">Sube tu Arsenal</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">
                        Crea metas de ahorro y abónalas quincena a quincena. Observa cómo tu <span class="text-emerald-400 font-semibold">barra de progreso</span> sube hasta alcanzar el nivel máximo.
                    </p>
                    <div class="mt-6 flex items-center gap-2 text-emerald-500 text-xs font-bold uppercase tracking-widest opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                        <span>Pestaña Metas</span>
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                    </div>
                </div>

            </div>
        </section>

        <!-- ═══ HOW IT WORKS ═══ -->
        <section class="relative z-10 px-6 sm:px-10 lg:px-16 pb-28">
            <div class="max-w-4xl mx-auto">
                <div class="text-center mb-14">
                    <p class="text-xs font-bold uppercase tracking-widest text-slate-500 mb-3">El flujo de batalla</p>
                    <h2 class="text-3xl sm:text-4xl font-black text-white">Tres pasos. Sin excusas.</h2>
                </div>

                <div class="flex flex-col">

                    <!-- Step 1 -->
                    <div class="flex items-stretch gap-6 px-6 pt-6 pb-0 rounded-t-2xl hover:bg-slate-900/50 transition-colors duration-200 group">
                        <!-- Left col: icon + connector going down -->
                        <div class="flex flex-col items-center flex-shrink-0 hidden sm:flex">
                            <div class="relative w-16 h-16 bg-blue-600/20 border border-blue-500/40 rounded-2xl flex items-center justify-center shadow-[0_0_16px_rgba(37,99,235,0.25)] group-hover:shadow-[0_0_24px_rgba(37,99,235,0.4)] transition-shadow flex-shrink-0">
                                <span class="text-2xl">🛡️</span>
                                <span class="absolute -top-2 -right-2 w-6 h-6 bg-blue-600 rounded-full text-white text-xs font-black flex items-center justify-center">1</span>
                            </div>
                            <!-- Connector to step 2 -->
                            <div class="w-px flex-1 mt-3 mb-0 bg-gradient-to-b from-blue-500/60 to-violet-500/40 min-h-[1.5rem]"></div>
                        </div>
                        <!-- Mobile: icon inline -->
                        <div class="relative flex-shrink-0 w-16 h-16 bg-blue-600/20 border border-blue-500/40 rounded-2xl flex items-center justify-center shadow-[0_0_16px_rgba(37,99,235,0.25)] group-hover:shadow-[0_0_24px_rgba(37,99,235,0.4)] transition-shadow sm:hidden">
                            <span class="text-2xl">🛡️</span>
                            <span class="absolute -top-2 -right-2 w-6 h-6 bg-blue-600 rounded-full text-white text-xs font-black flex items-center justify-center">1</span>
                        </div>
                        <div class="pt-1 pb-6">
                            <h3 class="text-lg font-black text-white mb-1">Registra tu quincena</h3>
                            <p class="text-slate-400 text-sm leading-relaxed">Ingresa cuánto ganaste, cuánto gastarás en lo fijo y observa tu capital libre listo para la ofensiva.</p>
                        </div>
                    </div>

                    <!-- Step 2 -->
                    <div class="flex items-stretch gap-6 px-6 pt-0 pb-0 hover:bg-slate-900/50 transition-colors duration-200 group">
                        <div class="flex flex-col items-center flex-shrink-0 hidden sm:flex">
                            <div class="relative w-16 h-16 bg-red-600/20 border border-red-500/40 rounded-2xl flex items-center justify-center shadow-[0_0_16px_rgba(239,68,68,0.2)] group-hover:shadow-[0_0_24px_rgba(239,68,68,0.35)] transition-shadow flex-shrink-0">
                                <span class="text-2xl">⚔️</span>
                                <span class="absolute -top-2 -right-2 w-6 h-6 bg-red-600 rounded-full text-white text-xs font-black flex items-center justify-center">2</span>
                            </div>
                            <!-- Connector to step 3 -->
                            <div class="w-px flex-1 mt-3 mb-0 bg-gradient-to-b from-violet-500/40 to-emerald-500/40 min-h-[1.5rem]"></div>
                        </div>
                        <div class="relative flex-shrink-0 w-16 h-16 bg-red-600/20 border border-red-500/40 rounded-2xl flex items-center justify-center shadow-[0_0_16px_rgba(239,68,68,0.2)] group-hover:shadow-[0_0_24px_rgba(239,68,68,0.35)] transition-shadow sm:hidden">
                            <span class="text-2xl">⚔️</span>
                            <span class="absolute -top-2 -right-2 w-6 h-6 bg-red-600 rounded-full text-white text-xs font-black flex items-center justify-center">2</span>
                        </div>
                        <div class="pt-1 pb-6">
                            <h3 class="text-lg font-black text-white mb-1">Ataca a tus deudas</h3>
                            <p class="text-slate-400 text-sm leading-relaxed">Ve al módulo de Jefes, elige cuánto atacar y observa cómo la barra de HP cae. Cada peso pagado cuenta.</p>
                        </div>
                    </div>

                    <!-- Step 3 (no connector below — final step) -->
                    <div class="flex items-start gap-6 px-6 pt-0 pb-6 rounded-b-2xl hover:bg-slate-900/50 transition-colors duration-200 group">
                        <div class="flex flex-col items-center flex-shrink-0 hidden sm:flex">
                            <div class="relative w-16 h-16 bg-emerald-600/20 border border-emerald-500/40 rounded-2xl flex items-center justify-center shadow-[0_0_16px_rgba(16,185,129,0.2)] group-hover:shadow-[0_0_24px_rgba(16,185,129,0.35)] transition-shadow flex-shrink-0">
                                <span class="text-2xl">🎯</span>
                                <span class="absolute -top-2 -right-2 w-6 h-6 bg-emerald-600 rounded-full text-white text-xs font-black flex items-center justify-center">3</span>
                            </div>
                            <!-- No connector: this is the last step -->
                        </div>
                        <div class="relative flex-shrink-0 w-16 h-16 bg-emerald-600/20 border border-emerald-500/40 rounded-2xl flex items-center justify-center shadow-[0_0_16px_rgba(16,185,129,0.2)] group-hover:shadow-[0_0_24px_rgba(16,185,129,0.35)] transition-shadow sm:hidden">
                            <span class="text-2xl">🎯</span>
                            <span class="absolute -top-2 -right-2 w-6 h-6 bg-emerald-600 rounded-full text-white text-xs font-black flex items-center justify-center">3</span>
                        </div>
                        <div class="pt-1">
                            <h3 class="text-lg font-black text-white mb-1">Forja tus metas</h3>
                            <p class="text-slate-400 text-sm leading-relaxed">Usa el capital que te sobra para construir objetivos concretos: viaje, fondo de emergencia, equipamiento nuevo.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══ CTA FINAL ═══ -->
        <section class="relative z-10 px-6 sm:px-10 lg:px-16 pb-28">
            <div class="max-w-3xl mx-auto">
                <div class="relative p-10 sm:p-14 rounded-3xl bg-gradient-to-br from-slate-900 to-slate-900/80 border border-slate-700/60 backdrop-blur-sm overflow-hidden shadow-2xl text-center">
                    <!-- Decorative glow -->
                    <div class="absolute -top-20 left-1/2 -translate-x-1/2 w-64 h-64 bg-blue-600 rounded-full mix-blend-screen filter blur-[100px] opacity-15 pointer-events-none"></div>
                    <div class="absolute top-0 left-0 right-0 h-px bg-gradient-to-r from-transparent via-blue-500/60 to-transparent"></div>

                    <div class="text-5xl mb-5">🏆</div>
                    <h2 class="text-3xl sm:text-4xl font-black text-white mb-4">¿Listo para tomar el control?</h2>
                    <p class="text-slate-400 mb-10 max-w-md mx-auto leading-relaxed">
                        Únete y empieza a ver tus finanzas como lo que son: <strong class="text-white">un juego que puedes ganar</strong>.
                    </p>
                    <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                        <Link
                            id="cta-register-btn"
                            :href="route('register')"
                            class="group inline-flex items-center gap-3 px-10 py-4 rounded-2xl font-black uppercase tracking-widest text-white bg-blue-600 hover:bg-blue-500 shadow-[0_0_30px_rgba(37,99,235,0.5)] hover:shadow-[0_0_50px_rgba(37,99,235,0.7)] border border-blue-500 transition-all duration-300 hover:-translate-y-1 hover:scale-105 text-base"
                        >
                            ⚔️ Crear Cuenta Gratis
                        </Link>
                        <Link
                            id="cta-login-btn"
                            :href="route('login')"
                            class="inline-flex items-center gap-2 px-8 py-4 rounded-2xl font-bold text-slate-400 hover:text-white transition-colors duration-200 text-sm"
                        >
                            Ya tengo cuenta →
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══ FOOTER ═══ -->
        <footer class="relative z-10 border-t border-slate-800/60 px-6 py-8 text-center">
            <div class="flex items-center justify-center gap-2 mb-2">
                <span class="text-lg">⚔️</span>
                <span class="font-black text-sm text-slate-500">Finanzas<span class="text-slate-400">RPG</span></span>
            </div>
            <p class="text-xs text-slate-700">Hecho para guerreros financieros. Tú decides cuándo atacar.</p>
        </footer>

    </div>
</template>

<style scoped>
@keyframes pulse-slow {
    0%, 100% { opacity: 0.12; transform: scale(1); }
    50%       { opacity: 0.18; transform: scale(1.05); }
}
.animate-pulse-slow {
    animation: pulse-slow 8s ease-in-out infinite;
}

/* Ping animation for the live badge dot */
@keyframes ping {
    75%, 100% { transform: scale(2); opacity: 0; }
}
.animate-ping {
    animation: ping 1.5s cubic-bezier(0, 0, 0.2, 1) infinite;
}
</style>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Términos y Condiciones — FinanzasRPG" />

    <div class="min-h-screen bg-slate-900 text-slate-300 relative overflow-hidden">

        <!-- Ambient background glows -->
        <div class="absolute top-0 left-0 w-full h-full overflow-hidden z-0 pointer-events-none">
            <div class="absolute -top-32 -left-32 w-[500px] h-[500px] bg-blue-700 rounded-full mix-blend-screen filter blur-[120px] opacity-10"></div>
            <div class="absolute bottom-0 right-0 w-[400px] h-[400px] bg-red-700 rounded-full mix-blend-screen filter blur-[120px] opacity-10"></div>
        </div>

        <!-- Header / Navigation bar -->
        <header class="relative z-10 border-b border-slate-800 bg-slate-900/80 backdrop-blur-sm sticky top-0">
            <div class="max-w-4xl mx-auto px-6 py-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">⚔️</span>
                    <span class="text-lg font-black text-white tracking-tight">FinanzasRPG</span>
                </div>
                <Link :href="route('home')"
                    class="text-sm text-slate-400 hover:text-white transition-colors font-medium flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Volver
                </Link>
            </div>
        </header>

        <!-- Main content -->
        <main class="relative z-10 max-w-4xl mx-auto px-6 py-14">

            <!-- Page Title -->
            <div class="mb-12 text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-slate-800 border border-slate-700 mb-5 shadow-lg">
                    <span class="text-3xl">📜</span>
                </div>
                <h1 class="text-3xl sm:text-4xl font-black text-white tracking-tight">Términos y Condiciones</h1>
                <p class="mt-3 text-slate-400 text-sm font-medium">
                    Última actualización: Junio 2025 · Vigente desde la fecha de registro
                </p>
                <div class="mt-4 mx-auto w-16 h-1 rounded-full bg-gradient-to-r from-blue-500 to-red-500"></div>
            </div>

            <!-- Intro card -->
            <div class="mb-8 p-5 rounded-2xl border border-blue-500/20 bg-blue-500/5">
                <p class="text-slate-300 text-sm leading-relaxed">
                    Al registrarte y utilizar <strong class="text-white">FinanzasRPG</strong>, aceptas los presentes Términos y Condiciones en su totalidad.
                    Si no estás de acuerdo con alguno de estos términos, te pedimos que no utilices la plataforma.
                    Léelos con atención antes de continuar.
                </p>
            </div>

            <!-- Sections -->
            <div class="space-y-8">

                <!-- 1 -->
                <section class="rounded-2xl border border-slate-700/60 bg-slate-800/50 overflow-hidden">
                    <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-700/60 bg-slate-800">
                        <span class="flex-shrink-0 w-8 h-8 rounded-lg bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-sm font-black text-blue-400">1</span>
                        <h2 class="text-base font-bold text-white">Naturaleza del Servicio</h2>
                    </div>
                    <div class="px-6 py-5 text-sm text-slate-400 leading-relaxed space-y-3">
                        <p>
                            <strong class="text-slate-200">FinanzasRPG</strong> es una plataforma de <strong class="text-slate-200">educación y seguimiento financiero personal</strong>.
                            Su propósito es ayudar a los usuarios a registrar, organizar y visualizar su información financiera de manera didáctica y gamificada.
                        </p>
                        <p>
                            FinanzasRPG <strong class="text-red-400">no es un servicio de asesoría financiera, inversión, contabilidad o legal</strong>.
                            El contenido generado por la plataforma (proyecciones, cálculos, sugerencias) tiene únicamente carácter informativo y educativo.
                            Ninguna información proporcionada por FinanzasRPG debe interpretarse como consejo financiero profesional.
                        </p>
                        <p>
                            Para decisiones financieras importantes, consulta siempre con un profesional certificado en finanzas, contabilidad o derecho según corresponda.
                        </p>
                    </div>
                </section>

                <!-- 2 -->
                <section class="rounded-2xl border border-slate-700/60 bg-slate-800/50 overflow-hidden">
                    <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-700/60 bg-slate-800">
                        <span class="flex-shrink-0 w-8 h-8 rounded-lg bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-sm font-black text-blue-400">2</span>
                        <h2 class="text-base font-bold text-white">Registro y Responsabilidad de la Cuenta</h2>
                    </div>
                    <div class="px-6 py-5 text-sm text-slate-400 leading-relaxed space-y-3">
                        <p>
                            Para acceder a las funciones de la plataforma, debes crear una cuenta con información veraz y actualizada.
                            Eres el único responsable de mantener la confidencialidad de tus credenciales de acceso.
                        </p>
                        <p>
                            Debes ser mayor de 18 años o contar con autorización de un tutor legal para utilizar FinanzasRPG.
                            Al crear tu cuenta, declaras que la información proporcionada es correcta y que tienes capacidad legal para aceptar estos términos.
                        </p>
                        <p>
                            Cualquier actividad realizada desde tu cuenta es tu responsabilidad exclusiva.
                            Notifica de inmediato a FinanzasRPG si detectas un acceso no autorizado a tu cuenta.
                        </p>
                    </div>
                </section>

                <!-- 3 -->
                <section class="rounded-2xl border border-slate-700/60 bg-slate-800/50 overflow-hidden">
                    <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-700/60 bg-slate-800">
                        <span class="flex-shrink-0 w-8 h-8 rounded-lg bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-sm font-black text-blue-400">3</span>
                        <h2 class="text-base font-bold text-white">Privacidad y Tratamiento de Datos</h2>
                    </div>
                    <div class="px-6 py-5 text-sm text-slate-400 leading-relaxed space-y-3">
                        <p>
                            FinanzasRPG recopila únicamente los datos necesarios para brindar el servicio: nombre, correo electrónico y la información financiera que el usuario ingresa voluntariamente (presupuestos, deudas, metas).
                        </p>
                        <p>
                            Tus datos <strong class="text-slate-200">no serán vendidos, alquilados ni compartidos</strong> con terceros con fines comerciales.
                            Solo podrán ser compartidos cuando sea estrictamente requerido por la ley o para la operación técnica del servicio (por ejemplo, servicios de infraestructura en la nube).
                        </p>
                        <p>
                            Tomamos medidas técnicas y organizativas razonables para proteger tu información contra accesos no autorizados, pérdida o divulgación indebida.
                            Sin embargo, ningún sistema de transmisión de datos por Internet es completamente seguro.
                        </p>
                        <p>
                            Puedes solicitar la eliminación de tu cuenta y datos en cualquier momento desde la configuración de tu perfil o contactando directamente al equipo de soporte.
                        </p>
                    </div>
                </section>

                <!-- 4 -->
                <section class="rounded-2xl border border-slate-700/60 bg-slate-800/50 overflow-hidden">
                    <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-700/60 bg-slate-800">
                        <span class="flex-shrink-0 w-8 h-8 rounded-lg bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-sm font-black text-blue-400">4</span>
                        <h2 class="text-base font-bold text-white">Usos Prohibidos</h2>
                    </div>
                    <div class="px-6 py-5 text-sm text-slate-400 leading-relaxed">
                        <p class="mb-3">Queda estrictamente prohibido utilizar FinanzasRPG para:</p>
                        <ul class="space-y-2 list-none">
                            <li class="flex items-start gap-2">
                                <span class="mt-0.5 text-red-500">✕</span>
                                <span>Actividades ilegales, fraudulentas o que infrinjan derechos de terceros.</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="mt-0.5 text-red-500">✕</span>
                                <span>Intentar vulnerar, hackear o comprometer la seguridad de la plataforma.</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="mt-0.5 text-red-500">✕</span>
                                <span>Crear cuentas falsas, suplantación de identidad o distribución de información engañosa.</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="mt-0.5 text-red-500">✕</span>
                                <span>Realizar ingeniería inversa, descompilar o intentar extraer el código fuente de la plataforma.</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="mt-0.5 text-red-500">✕</span>
                                <span>Usar la plataforma de una manera que pueda dañar, deshabilitar o sobrecargar los servidores.</span>
                            </li>
                        </ul>
                        <p class="mt-4">
                            El incumplimiento de estas restricciones puede resultar en la suspensión o eliminación permanente de tu cuenta sin previo aviso.
                        </p>
                    </div>
                </section>

                <!-- 5 -->
                <section class="rounded-2xl border border-slate-700/60 bg-slate-800/50 overflow-hidden">
                    <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-700/60 bg-slate-800">
                        <span class="flex-shrink-0 w-8 h-8 rounded-lg bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-sm font-black text-blue-400">5</span>
                        <h2 class="text-base font-bold text-white">Limitación de Responsabilidad</h2>
                    </div>
                    <div class="px-6 py-5 text-sm text-slate-400 leading-relaxed space-y-3">
                        <p>
                            FinanzasRPG se proporciona <strong class="text-slate-200">"tal cual" (as-is)</strong> sin garantías de ningún tipo, expresas o implícitas, incluyendo disponibilidad continua, ausencia de errores o adecuación para un fin específico.
                        </p>
                        <p>
                            En la máxima medida permitida por la ley aplicable, FinanzasRPG no será responsable por pérdidas financieras, daños indirectos, incidentales, especiales o consecuentes que resulten del uso o la imposibilidad de uso de la plataforma.
                        </p>
                        <p>
                            El usuario asume toda la responsabilidad por las decisiones financieras que tome basándose en la información registrada o presentada por la plataforma.
                        </p>
                    </div>
                </section>

                <!-- 6 -->
                <section class="rounded-2xl border border-slate-700/60 bg-slate-800/50 overflow-hidden">
                    <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-700/60 bg-slate-800">
                        <span class="flex-shrink-0 w-8 h-8 rounded-lg bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-sm font-black text-blue-400">6</span>
                        <h2 class="text-base font-bold text-white">Modificaciones a los Términos</h2>
                    </div>
                    <div class="px-6 py-5 text-sm text-slate-400 leading-relaxed space-y-3">
                        <p>
                            FinanzasRPG se reserva el derecho de actualizar o modificar estos Términos y Condiciones en cualquier momento.
                            Los cambios entrarán en vigor inmediatamente después de su publicación en la plataforma.
                        </p>
                        <p>
                            Se notificará a los usuarios registrados sobre cambios significativos mediante correo electrónico o un aviso visible dentro de la aplicación.
                            El uso continuado de la plataforma después de la publicación de cambios constituye la aceptación de los nuevos términos.
                        </p>
                    </div>
                </section>

                <!-- 7 -->
                <section class="rounded-2xl border border-slate-700/60 bg-slate-800/50 overflow-hidden">
                    <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-700/60 bg-slate-800">
                        <span class="flex-shrink-0 w-8 h-8 rounded-lg bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-sm font-black text-blue-400">7</span>
                        <h2 class="text-base font-bold text-white">Legislación Aplicable</h2>
                    </div>
                    <div class="px-6 py-5 text-sm text-slate-400 leading-relaxed">
                        <p>
                            Estos Términos y Condiciones se rigen e interpretan de conformidad con las leyes de la República Dominicana.
                            Cualquier disputa derivada del uso de la plataforma se someterá a la jurisdicción de los tribunales competentes del país.
                        </p>
                    </div>
                </section>

            </div>

            <!-- Footer CTA -->
            <div class="mt-14 text-center">
                <p class="text-slate-500 text-sm mb-5">
                    Si tienes preguntas sobre estos términos, contáctanos antes de continuar.
                </p>
                <Link :href="route('register')"
                    class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-500 text-white text-sm font-bold py-3 px-6 rounded-xl transition-all shadow-lg shadow-blue-500/20">
                    <span>⚔️</span>
                    Volver al Registro
                </Link>
            </div>

        </main>

        <!-- Page footer -->
        <footer class="relative z-10 border-t border-slate-800 mt-12 py-6 text-center">
            <p class="text-slate-600 text-xs font-medium">
                &copy; {{ new Date().getFullYear() }} FinanzasRPG. Todos los derechos reservados.
            </p>
        </footer>

    </div>
</template>

⚔️ FinanzasRPG (SaaS Gamificado)
Una aplicación web (SPA) diseñada para tomar el control absoluto de las finanzas personales, transformando la tediosa tarea de presupuestar en una experiencia táctica de rol (RPG). Destruye tus deudas como si fueran jefes, forja tus ahorros y sube de nivel tu economía.

🚀 Características Principales
Centro de Mando (HUD Gamificado): Un Dashboard estratégico que calcula en tiempo real métricas de campaña como el HP Restante del Ejército Enemigo (Deuda Total) y los Recursos en la Forja (Ahorros).

Planificación de Defensa (Presupuesto): Módulo dedicado con una calculadora reactiva que procesa suministros (ingresos) y gastos fijos para determinar tu "Capital Libre" quincenal.

Zona de Combate (Gestión de Deudas): Panel interactivo con un "Radar de Enemigos". Permite ejecutar ataques (pagos) directos a las deudas, reduciendo su barra de vida (HP) al instante y calculando golpes críticos cuando las deudas superan umbrales de riesgo.

El Arsenal (Metas Financieras): Forja objetivos a corto y largo plazo (fondos de emergencia, equipamiento) inyectando tu capital libre.

Sala de Archivos: Registro en base de datos de cada batalla financiera con modales dinámicos para descargar y auditar quincenas pasadas.

Onboarding Público: Página de aterrizaje (Landing Page) inmersiva para captar reclutas antes de acceder a la base de operaciones.

💻 Stack Tecnológico
Frontend: Vue.js 3 (Composition API)

Diseño UI: Tailwind CSS v3 (Arquitectura visual Dark Glassmorphism)

Backend: Laravel (PHP)

Conexión: Inertia.js (Single Page Application sin recargas)

Base de Datos: SQLite / MySQL

Testing: Pest PHP (Suite de pruebas de Backend)

🧠 Habilidades de Ingeniería Demostradas
Al explorar el código fuente, los reclutadores y desarrolladores notarán un enfoque en buenas prácticas y calidad de software:

Desarrollo Full-Stack Moderno: Integración fluida entre un frontend altamente reactivo (Vue 3) y un backend robusto (Laravel), controlando el acceso mediante middlewares y políticas de seguridad (403 Forbidden para manipulación cruzada de usuarios).

Arquitectura Limpia (DRY): Extracción de lógica matemática compleja a Vue Composables (useDebtUtils.js) y creación de componentes UI globales reutilizables (PageHeader.vue).

Calidad de Software (Testing): Implementación de pruebas automatizadas con Pest PHP, cubriendo validaciones de cálculo de HP, límites de seguridad (boundary tests) y ataques a bases de datos en memoria para evitar regresiones.

Optimización de Base de Datos: Creación de migraciones con índices estratégicos (user_id) en tablas transaccionales para prevenir Full Table Scans y garantizar un rendimiento óptimo a escala.

Diseño de Interfaz (UI/UX): Construcción de una estética inmersiva utilizando utilidades avanzadas de Tailwind (filtros backdrop-blur, gradientes, renderizado condicional de componentes) garantizando un diseño 100% responsivo.

⚙️ Instalación Local
Si deseas desplegar esta base de operaciones en tu entorno local:

Clona el repositorio: git clone [https://github.com/TU_USUARIO/finanzas-v3.git](https://github.com/TU_USUARIO/finanzas-v3.git)

Instala dependencias de PHP: composer install

Instala dependencias de Node: npm install

Configura el entorno: Copia .env.example a .env y genera la clave: php artisan key:generate

Prepara la Base de Datos: php artisan migrate

Ejecuta la suite de pruebas (Opcional): php artisan test

Levanta los servidores: Ejecuta php artisan serve y, en una terminal paralela, npm run dev

# 🛡️ Finanzas de Combate (SaaS)

Una aplicación web (SPA) diseñada para tomar el control absoluto de las finanzas personales, estructurar presupuestos y destruir deudas de manera estratégica y matemática.

## 🚀 Características Principales

* **Trinchera (Presupuesto Inteligente):** Calculadora reactiva que procesa ingresos y gastos fijos para determinar tu "Capital Libre" quincenal.
* **Historial Interactivo:** Registro en base de datos de cada batalla financiera con modales dinámicos para ver el desglose exacto de quincenas pasadas.
* **Modo Guerra (Gestión de Deudas):** Panel estratégico con un "Radar de Inteligencia" que calcula en tiempo real la *Deuda Total Viva* y el *Sangrado Mensual*. Permite ejecutar pagos (ataques) directos a las deudas actualizando los saldos al instante.

## 💻 Tecnologías Utilizadas (Tech Stack)

* **Frontend:** Vue.js 3 (Composition API)
* **Estilos:** Tailwind CSS v3
* **Backend:** Laravel (PHP)
* **Arquitectura:** Single Page Application (SPA) conectada mediante Inertia.js
* **Base de Datos:** SQLite / MySQL

## 🧠 Habilidades Demostradas en este Proyecto

Al explorar el código fuente de este repositorio, podrás notar mi capacidad para:

1. **Desarrollo Full-Stack Moderno:** Integración perfecta entre un frontend altamente interactivo (Vue 3) y un backend robusto y seguro (Laravel).
2. **Reactividad en Tiempo Real:** Uso avanzado de `ref` y `computed` en Vue para recalcular totales financieros y renderizar elementos del DOM al instante, sin recargar el navegador.
3. **Diseño de Interfaz (UI/UX):** Construcción de interfaces limpias, profesionales y 100% responsivas utilizando clases de utilidad de Tailwind CSS (Modales personalizados, grid layouts, tarjetas dinámicas).
4. **Diseño de Base de Datos:** Creación de migraciones, modelos y controladores (Arquitectura MVC) para gestionar y persistir información de presupuestos y deudas de forma segura usando Eloquent ORM.

## ⚙️ Instalación Local

Si deseas correr este proyecto en tu máquina local:

1. Clona el repositorio: `git clone https://github.com/TU_USUARIO/finanzas-v3.git`
2. Instala dependencias de PHP: `composer install`
3. Instala dependencias de Node: `npm install`
4. Copia el archivo `.env.example` a `.env` y genera la clave: `php artisan key:generate`
5. Ejecuta las migraciones: `php artisan migrate`
6. Levanta los servidores: `php artisan serve` y en otra terminal `npm run dev`
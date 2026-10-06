# Render, Supabase y preparación del MVP

## Arquitectura

La web y la API autenticada viven en Laravel en `https://finanzas-v3.onrender.com`. Supabase almacena los datos de Laravel mediante PostgreSQL; el cliente móvil no accede directamente a su API pública ni recibe sus claves. NativePHP conserva las pantallas Vue/Inertia y transmite operaciones HTTPS al servidor. La aplicación depende de conexión; esta fase no implementa funcionamiento sin conexión.

`routes/mobile.php` expone únicamente páginas y acciones necesarias bajo `/api/mobile/v1`. Login/registro emiten tokens Sanctum almacenados como hashes. Las demás rutas requieren token vigente con permiso `mobile`, aplican permisos por propietario, validación y límites de solicitudes. No aceptan la sesión de navegador. El gateway del dispositivo limita rutas, origen HTTPS y componentes, mantiene verificación TLS y no sigue redirecciones externas.

## Despliegue sin borrar datos

El Dockerfile construye con `composer install --no-dev` y `npm ci` desde los archivos de bloqueo, y sirve solo `public/` con Apache/PHP 8.3. No usa `artisan serve` en producción. El inicio valida PORT/APP_KEY, fuerza `APP_ENV=production` y `APP_DEBUG=false`, limpia la configuración anterior, ejecuta `php artisan migrate --force` y prepara las cachés antes de iniciar Apache. Si una migración falla, el servidor no comienza a recibir tráfico. Conserva el APP_KEY y los datos existentes.

Antes de actualizar Render:

1. Confirma una copia de seguridad/restauración de Supabase y utiliza un entorno de pruebas para revisar las migraciones. Conserva el APP_KEY actual; generar otra clave puede invalidar datos cifrados y sesiones.
2. Configura en Render `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://finanzas-v3.onrender.com`, `APP_LOCALE=es`, `SESSION_SECURE_COOKIE=true`, `SESSION_DRIVER=database`, `CACHE_STORE=database`, `QUEUE_CONNECTION=sync`. Mantén los secretos en las variables del servicio, nunca en Git o en la compilación móvil.
3. Configura `DB_CONNECTION=pgsql`, host/puerto/base/usuario/contraseña de Supabase y `DB_SSLMODE=require`. Para verificar también el certificado del servidor, usa `verify-full` con el certificado raíz y hostname correspondientes a tu conexión. No desactives TLS en producción. La configuración desactiva preparados nombrados y preserva tipos booleanos para conexiones con pooling de transacciones.
4. Para este MVP con una instancia, el arranque aplica las migraciones pendientes, incluidas las columnas de moneda y formato regional. Revisa los logs del despliegue y confirma que terminan correctamente. Si utilizas varias instancias, ejecuta primero `php artisan migrate --force` en un único trabajo previo al despliegue para evitar carreras entre migraciones; los arranques posteriores no tendrán migraciones pendientes. No uses `migrate:fresh`, no borres tablas y no migres durante la construcción de la imagen.
5. Revisa `/up`, inicio de sesión, un presupuesto, gasto, pago, recuperación y eliminación con una cuenta de prueba. Confirma que APP_DEBUG permanece desactivado y que archivos como `/.env` no son accesibles.
6. Comprueba la API con esa cuenta en un entorno de pruebas antes de compilar NativePHP; web y dispositivo deben mostrar los mismos registros. No pruebes escrituras automáticas sobre cuentas reales.

Las migraciones añaden vínculos de gastos, identificadores de operación, tokens y protección de tablas; conservan importes y balances anteriores. La protección de Supabase habilita RLS y revoca acceso de `PUBLIC`, `anon` y `authenticated` sobre las tablas internas cuando esos roles existen. Laravel necesita un rol backend propietario o con permiso de bypass de RLS; la migración rechaza un rol público o insuficiente. Su reversión no reabre permisos públicos. Revisa cualquier integración externa que antes consultara esas tablas directamente; el móvil utiliza la API Laravel.

Se comprobó el SQL en PostgreSQL 16 aislado con roles equivalentes. No se inspeccionaron los permisos, copias, región, retención ni secretos de la instalación real de Supabase/Render. Si el servicio actual tiene procesos antiguos o sesiones Redis, ajusta la transición operativa y reinicia el proceso tras cambiar configuración.

## Compilación móvil

Utiliza una `.env` de compilación sin secretos del servidor y `MOBILE_BACKEND_URL` apuntando al servidor de pruebas o al backend ya actualizado. Conserva el archivo `nativephp.lock` (PHP 8.3.31). NativePHP genera su clave del dispositivo; el APP_KEY de Render no debe empaquetarse. Verifica el contenido final del paquete y la persistencia de sesión tras cerrar, reiniciar y actualizar.

La plantilla instalada exige iOS 18.2. Un iPhone 8 Plus, limitado a iOS 16, permite validar Safari pero no este binario. Una Mac alquilada o un ejecutor macOS permite compilar sin comprar una Mac. La firma y TestFlight requieren Apple Developer y un dispositivo compatible o simulador. Aquí no se ejecutó ninguna compilación iOS ni Android.

## Verificaciones y tareas previas a publicar

La suite comprueba permisos, validación, gastos/pagos/metas, monedas, migraciones, reintentos, autenticación móvil, revocación, exportaciones y restricciones de roles. GitHub Actions ejecuta pruebas con SQLite y PostgreSQL en PHP 8.3/8.5, compilación Vite, pruebas JavaScript y auditorías de dependencias. Los avisos de auditoría cambian con el tiempo; una auditoría limpia no garantiza ausencia de vulnerabilidades.

Antes de una beta deben comprobarse entrega real de correo (recuperación), comportamiento en un binario iOS, teclado/áreas seguras, suspensión y sesiones, conexiones interrumpidas, exportaciones y eliminación. Revisa también las preferencias de moneda y saldos históricos de una copia de tus datos.

Antes de App Store debes completar política de privacidad pública y contacto del responsable, plazos reales de conservación/copias, soporte, ficha de privacidad, capturas e identificador definitivo, firma y cuenta de revisión. Los términos existentes no sustituyen esa revisión. No inventes datos legales ni declares una protección o función que no se haya verificado. Las recompensas RPG son secundarias al seguimiento financiero; el disponible es una estimación presupuestaria, no un saldo bancario.

## Evidencia local de esta entrega

- PHP 8.4: 129 pruebas pasan con SQLite (una prueba de RLS se omite); PostgreSQL 16: 130 pruebas, 706 aserciones, sin omisiones.
- Tres pruebas JavaScript, instalación `npm ci`, compilación Vite y auditorías completas de Composer/npm pasan; ambas auditorías reportan cero avisos conocidos en esta revisión.
- Chromium a 320/390/768/1440 px: registro con coma decimal, doble pulsación, gasto fijo sin otro descuento, bloqueo de monedas diferentes, conservación de borrador y vistas de presupuesto/historial.
- Contenedor Apache/PHP 8.3.35 con PostgreSQL aislado: salud, login, archivos privados inaccesibles, presupuesto, gasto, reintento y revocación al salir. Se corrigieron los permisos de lectura del checkout que inicialmente producían HTTP 403. Se comprobó también que seis envíos simultáneos del mismo gasto descuentan una sola vez.
- La última corrección de mensaje de recuperación se validó reutilizando la imagen construida y actualizando ese controlador; una reconstrucción completa posterior quedó limitada por el disco del entorno con el controlador Docker VFS. El Dockerfile completo ya había construido con los permisos corregidos. El despliegue debe construir la imagen final desde este commit.

No se desplegó en Render, no se ejecutaron migraciones sobre Supabase ni se comprobó una compilación nativa o los jobs de GitHub Actions desde este entorno.

## Corrección del despliegue y registro

Un despliegue con migraciones pendientes puede fallar al registrar usuarios por ausencia de `users.preferred_currency` y `number_locale`. El arranque ahora aplica esas migraciones antes de Apache y fuerza la depuración desactivada incluso si Render conserva `APP_DEBUG=true`. Los formatos de moneda nula muestran «moneda sin definir» sin atribuir una moneda al importe ni bloquear Inicio.

El manifiesto se sirve desde `/build/manifest.webmanifest`, el service worker desde `/sw.js`, y su precaché apunta a los archivos reales de `/build/`. Los iconos son PNG válidos. Se verificaron la compilación, 129 pruebas Laravel con SQLite (una omisión de RLS), siete pruebas JavaScript y migraciones repetidas en una base aislada. Chromium a 390 px comprobó registro, Inicio con una moneda nula simulada y activación del service worker, sin excepciones de página ni errores HTTP locales. Las fuentes externas mostraron un fallo de certificado en este entorno; no se desactivó la verificación TLS. Esta comprobación no confirma el estado de la base de Supabase ni el despliegue de Render.

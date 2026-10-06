# Pruebas en iPhone sin una Mac propia

El proyecto usa NativePHP Mobile. El entorno de trabajo actual es Linux: las pruebas de Laravel y la compilación de Vite no equivalen a una compilación iOS ni validan el comportamiento de un iPhone.

La plantilla iOS de la versión instalada exige iOS 18.2. El iPhone 8 Plus llega hasta iOS 16: sirve para probar la web en Safari, pero no este paquete nativo. Para las pruebas nativas necesitarás un dispositivo compatible o un simulador en macOS.

## Pruebas locales en Windows sin Redis

Si aparece `Predis ... actively refused ... 127.0.0.1:6379`, la aplicación está intentando conectarse a un servidor Redis que no está iniciado. El registro de gastos usa caché también en su límite de solicitudes; no hay que quitar ese límite para resolverlo.

Para el desarrollo local, cambia únicamente estas líneas en tu `.env` existente:

```dotenv
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

Después ejecuta:

```sh
php artisan config:clear
```

Detén y reinicia el servidor que estés usando (`artisan serve` o `native:jump`) y vuelve a registrar un gasto. La caché se guarda en `storage/framework/cache/data`, que debe permitir escritura. Los trabajos de logros se ejecutan durante la solicitud, sin un proceso de cola. No cambia la base de datos ni los movimientos guardados. La `.env.example` ya incluye esos valores para nuevas instalaciones; actualizar Git no modifica tu `.env` existente.

No uses `optimize:clear` como primer paso mientras la configuración todavía apunte al Redis inaccesible: también intenta vaciar la caché. En un servidor que sí tenga Redis puedes conservar `CACHE_STORE=redis` y `QUEUE_CONNECTION=redis`, con su servicio y trabajador de cola configurados.

## Primera opción: NativePHP Jump

Jump permite probar el proyecto con su aplicación de desarrollo desde un teléfono, sin compilar una aplicación iOS propia. Consulta la instalación y disponibilidad actual del cliente Jump en la [documentación de NativePHP Mobile](https://nativephp.com/docs/mobile). Necesitas un iPhone y una computadora con PHP, Composer y Node, conectados a la misma red local.

En una copia local del repositorio, con `.env`, dependencias y una base de desarrollo configurados:

```sh
composer install
npm ci
php artisan migrate
npm run build
php artisan native:jump --browser
```

Abre Jump y utiliza el código QR que muestra el comando. Si detecta una dirección incorrecta, especifica la IP local de tu computadora:

```sh
php artisan native:jump --ip=192.168.1.50 --no-mdns --browser
```

Sustituye esa IP por la real. Permite los puertos mostrados por el comando en el firewall de tu red privada. Usa datos de prueba. No expongas el servidor de desarrollo a Internet. Este entorno en la nube no comparte la red local de tu teléfono.

Jump puede ejecutar un flujo distinto al binario independiente. Verifica el indicador de runtime y el servidor utilizado con datos de prueba. No demuestra la persistencia de sesión ni las actualizaciones de una aplicación independiente.

## Comportamiento preparado para la aplicación independiente

Cuando NativePHP indica `NATIVEPHP_RUNNING=true`, la interfaz empaquetada envía las acciones financieras y la autenticación al Laravel de Render mediante `/api/mobile/v1`. Ese servidor conserva los datos en Supabase. `MOBILE_BACKEND_URL=https://finanzas-v3.onrender.com` identifica el servidor; solo se aceptan URLs HTTPS de origen, sin credenciales, ruta ni parámetros.

La API usa tokens Sanctum con permiso `mobile`, vencimiento de un día o treinta días al recordar la sesión, y revocación al cerrar sesión, cambiar/restablecer contraseña o eliminar la cuenta. Las cookies del navegador no autentican esta API. El token se guarda en la sesión de Laravel del dispositivo, cifrada con su clave local; no se envía a Vue ni se incluye en el paquete. Se reutilizan los controladores y reglas del servidor, sin crear cuentas o movimientos financieros locales. Una conexión fallida muestra un error y conserva el formulario; no se reintentan automáticamente las escrituras.

El runtime conserva SQLite para las necesidades internas de NativePHP, caché y sesiones en archivos, sin Redis. El frontend omite el service worker PWA y fuentes externas, mantiene URLs locales y oculta Google: la primera versión nativa utiliza correo y contraseña. No se ha implementado un modo sin conexión ni sincronización de bases locales. Los datos requieren conexión al servidor. La limpieza del paquete excluye credenciales de base de datos, Supabase, BPD, Google, correo, Redis, Pulse, APP_KEY del servidor y firma de App Store.

**Primero debe desplegarse en Render esta versión con sus migraciones.** La URL existente no demuestra que la API nueva esté publicada. Las pruebas de transporte usan HTTP simulado; no se escribieron datos en el servidor del usuario. Una prueba de una aplicación independiente debe comprobar inicio de sesión, conservación de sesión, reintentos, exportación y eliminación contra un entorno de pruebas publicado.

## Recorrido de prueba

- Crear una cuenta de prueba con correo y contraseña y elegir moneda y formato regional.
- Crear un presupuesto y una meta sin crear deudas. Revisar el Inicio y los estados vacíos.
- Registrar un gasto usando coma decimal. Corregir una validación sin perder el texto y pulsar dos veces para comprobar un único registro.
- Registrar un pago en la misma moneda y comprobar saldo de deuda, presupuesto e historial. Verificar la restricción para monedas diferentes.
- Abrir el teclado y comprobar botones, desplazamiento y áreas seguras. Revisar pantallas pequeñas y textos largos.
- En una compilación propia, cerrar y abrir la app, reiniciar el teléfono y probar una actualización sin perder datos. Jump no sustituye estas comprobaciones.

## Compilación propia y App Store

Necesitarás acceso a macOS con Xcode: una Mac prestada, alquilada o un servicio de CI con ejecutor macOS. Para distribuir con TestFlight/App Store también necesitas la cuenta de Apple Developer y configurar la firma. No se ha contratado ni ejecutado un servicio de compilación.

El archivo `nativephp.lock` fija PHP **8.3.31**, sin ICU. Usa PHP 8.3 para los comandos de compilación; la verificación web actual con PHP 8.4 no satisface esa comprobación del paquete. No regeneres el archivo de bloqueo solo para eludirla.

Define en el entorno de compilación `NATIVEPHP_APP_ID` (identificador permanente propio), `NATIVEPHP_APP_VERSION`, `NATIVEPHP_APP_VERSION_CODE` y, cuando corresponda, `NATIVEPHP_DEVELOPMENT_TEAM`. No guardes certificados, claves ni credenciales en Git. Usa una `.env` específica de compilación con SQLite y sin credenciales del servidor web; NativePHP empaqueta la `.env` del proyecto.

En esa Mac, revisa primero las opciones de la versión instalada:

```sh
php artisan native:install --help
php artisan native:run --help
php artisan native:build --help
```

En una copia destinada a compilación, instala el proyecto iOS con `php artisan native:install ios` y ejecuta `php artisan native:run ios` para probarlo. La instalación puede regenerar los proyectos nativos: conserva cualquier personalización antes de hacerlo. Después de validar el dispositivo, prepara la firma y TestFlight. Ninguno de estos pasos iOS se ha ejecutado en el entorno Linux.

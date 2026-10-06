# Pruebas en iPhone sin una Mac propia

El proyecto usa NativePHP Mobile. El entorno de trabajo actual es Linux: las pruebas de Laravel y la compilación de Vite no equivalen a una compilación iOS ni validan el comportamiento de un iPhone.

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

Jump conecta con el servidor de desarrollo: sus cuentas y datos corresponden a ese servidor. No demuestra la persistencia ni las actualizaciones de una aplicación independiente.

## Comportamiento preparado para la aplicación independiente

Cuando NativePHP indica `NATIVEPHP_RUNNING=true`, Laravel usa SQLite, caché y sesiones en archivos y ejecuta los trabajos existentes de forma síncrona, sin Redis ni un proceso de cola. Conserva la ruta de base de datos que asigna NativePHP en el dispositivo. La web mantiene su configuración original.

El frontend omite el service worker PWA y las fuentes externas en ese entorno. La aplicación nativa conserva las URLs locales; la web de producción mantiene HTTPS. La limpieza del paquete excluye también las credenciales configuradas de BPD, Google, Pulse, correo, Redis y App Store Connect.

Una cuenta creada en la aplicación independiente y sus datos locales no se sincronizan con la web. No se ha implementado sincronización, un modo sin conexión, ni autenticación nativa con Google. Antes de publicar hay que decidir y probar ese comportamiento. La conversión histórica USD/DOP conserva el mecanismo existente; sin credenciales BPD utiliza su tasa de respaldo, no una cotización garantizada.

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

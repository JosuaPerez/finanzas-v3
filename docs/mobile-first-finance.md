# Primera fase móvil de FinanzasRPG

Esta fase mejora la experiencia web en pantallas móviles aprovechando Laravel, Vue e Inertia. No certifica todavía una compilación de NativePHP en iOS o Android.

## Aplicar los cambios

Con las dependencias instaladas, ejecuta desde la raíz del repositorio:

```bash
php artisan config:clear
php artisan migrate
npm run build
php artisan test
node --test tests/Frontend/money.test.mjs
```

La migración añade moneda y formato regional al usuario, y moneda a presupuestos y gastos. Los registros anteriores conservan sus importes y se identifican como DOP, la moneda que usaban esos flujos. No requiere borrar ni reconstruir la base de datos.

## Comportamiento financiero

- Inicio muestra el disponible del presupuesto más reciente, el siguiente día mensual de pago configurado y una meta. Las fechas configuradas no confirman que exista una cuota pendiente.
- El disponible es una estimación del presupuesto, no un saldo bancario. Los gastos rápidos se guardan en un historial independiente y no descuentan esa cifra.
- El formulario de movimientos distingue gastos y pagos. Un pago reduce la deuda y, cuando existe, el presupuesto correspondiente; no crea un gasto adicional.
- Las preferencias se aplican a los nuevos registros y al formato de presentación. No convierten ni cambian la moneda de registros anteriores.
- Deudas y metas se agrupan por moneda. El presupuesto solo incluye deudas en su moneda al calcular una deducción.
- El nuevo formulario no convierte monedas para un pago. El flujo anterior USD/DOP sigue disponible en Deudas, con su tasa de referencia visible. Ahora descuenta del presupuesto el costo en DOP y conserva ambos importes en el recibo y la exportación.
- El siguiente día de pago respeta el día mensual registrado y el último día real del mes. Sin fecha registrada se muestra un estado vacío.
- El primer uso permite elegir una meta o una deuda; no obliga a tener deudas.

## Validación

Se validaron cálculos, preferencias, preservación de datos en la migración, permisos y registros de movimientos mediante las pruebas de Laravel y SQLite. Las pruebas de JavaScript verifican importes con coma o punto decimal y formatos regionales.

También se comprobaron en Chromium las vistas a 320, 390, 768 y 1440 píxeles, la persistencia de preferencias, la conservación del formulario ante errores y el envío mediante doble clic. Reducir la altura del navegador simula el espacio ocupado por un teclado; no sustituye una prueba en un teléfono.

## Siguiente etapa

Compilar con NativePHP en macOS/Xcode y probar en un iPhone el teclado, las áreas seguras, la navegación, la autenticación y la persistencia. La aplicación móvil necesita definir qué servicios viven en el dispositivo y cuáles en el servidor; la configuración web actual de Redis no demuestra compatibilidad móvil. Después de esas comprobaciones se podrá preparar una beta en TestFlight.

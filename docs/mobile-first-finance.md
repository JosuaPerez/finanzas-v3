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
- El disponible es una estimación del presupuesto, no un saldo bancario. Cada gasto nuevo se vincula al presupuesto más reciente y descuenta su importe en la misma moneda. Si supera el disponible, se conserva el saldo negativo. Los gastos anteriores no se descuentan retroactivamente.
- Si el gasto ya estaba incluido en los gastos fijos, marca esa opción: se registra sin volver a descontarlo. Sin presupuesto se conserva como registro independiente. Si las monedas difieren o falta un disponible calculado, el formulario pide corregirlo antes de guardar.
- El formulario envía un identificador para que un reintento no duplique el gasto, el descuento ni la recompensa. El registro y el descuento se guardan juntos en una transacción. Los recibos aparecen en el historial del presupuesto y su exportación; los gastos ya presupuestados figuran con costo adicional cero.
- Los pagos de deuda conservan los recibos de gastos al actualizar el presupuesto. Si el disponible quedó negativo, se pide revisar el presupuesto antes de registrar un pago, sin borrar el déficit.
- El formulario de movimientos distingue gastos y pagos. Un pago reduce la deuda y, cuando existe, el presupuesto correspondiente; no crea un gasto adicional.
- Las preferencias se aplican a los nuevos registros y al formato de presentación. No convierten ni cambian la moneda de registros anteriores.
- Deudas y metas se agrupan por moneda. El presupuesto solo incluye deudas en su moneda al calcular una deducción.
- Los pagos requieren la misma moneda del presupuesto. El flujo automático USD/DOP se bloquea porque podía utilizar una tasa de respaldo sin confirmar; los recibos históricos conservan ambas monedas cuando ya existían. La tasa del servicio RPG no se usa para descontar dinero.
- El siguiente día de pago respeta el día mensual registrado y el último día real del mes. Sin fecha registrada se muestra un estado vacío.
- El primer uso permite elegir una meta o una deuda; no obliga a tener deudas.

## Validación

Se validaron cálculos, preferencias, preservación de datos en la migración, permisos y registros de movimientos mediante las pruebas de Laravel y SQLite. Las pruebas de JavaScript verifican importes con coma o punto decimal y formatos regionales.

También se comprobaron en Chromium las vistas a 320, 390, 768 y 1440 píxeles, la persistencia de preferencias, la conservación del formulario ante errores y el envío mediante doble clic. Reducir la altura del navegador simula el espacio ocupado por un teclado; no sustituye una prueba en un teléfono.

## Siguiente etapa

Compilar con NativePHP en macOS/Xcode y probar en un iPhone el teclado, las áreas seguras, la navegación, la autenticación y la persistencia. La conexión nativa al backend está preparada; queda validarla en el paquete iOS contra un servidor de pruebas actualizado. Después de esas comprobaciones se podrá preparar una beta en TestFlight.

## Endurecimiento del MVP (octubre de 2026)

Los importes aceptan coma o punto decimal, hasta dos decimales y un máximo de 99.999.999,99 por registro. Los cálculos del presupuesto se derivan en el servidor: el cliente no decide el disponible ni puede enviar recibos falsos. Los nuevos gastos y pagos conservan los recibos previos. Las metas registran ahorro declarado; añadir fondos no representa una transferencia bancaria ni descuenta el presupuesto.

Los formularios de creación de presupuesto, gastos, pagos, deudas y metas, y los depósitos en metas envían un identificador de operación. Los reintentos de un envío ya guardado no vuelven a modificar saldos ni recompensas. Reutilizarlo con otros datos exige revisar el historial. La tabla `financial_submissions` conserva las respuestas de acciones financieras, sin contraseñas ni tokens; se elimina con la cuenta. Las operaciones financieras se serializan por usuario dentro de una transacción. Los clientes antiguos que no envíen identificador conservan su comportamiento, por lo que no deben reintentar automáticamente escrituras.

Se retiró la deducción anticipada de toda la deuda al crear presupuestos nuevos para evitar descontarla nuevamente al registrar pagos. Los presupuestos antiguos no se recalculan. Se bloquean pagos superiores al saldo y pagos sobre presupuestos deficitarios. Las exportaciones escriben texto como texto, incluso si comienza por `=`, para evitar fórmulas introducidas por nombres o descripciones.

Las respuestas privadas no se almacenan en caché; el service worker no guarda páginas HTML de cuentas. La recuperación de contraseña responde sin confirmar si una cuenta existe. Cambiar o recuperar contraseña revoca los tokens móviles. Eliminar la cuenta exige contraseña o confirmación reciente de Google y elimina también sesiones, recuperación y tokens. Google debe acreditar un correo verificado y no vincula automáticamente una cuenta local cuyo correo no estaba verificado.

Las dependencias se actualizaron por avisos de seguridad. Se retiraron herramientas sin uso y se migró Tailwind a la versión 4 con su herramienta oficial; Laravel, Vue e Inertia mantienen su arquitectura. Los archivos de bloqueo se modificaron por estas correcciones y para declarar Sanctum como dependencia de la API. Picomatch 4 se declara para satisfacer el requisito compartido de los analizadores de archivos de Vite; evita una resolución incompatible del paquete bloqueado.

La API móvil y el procedimiento de despliegue se documentan en [render-supabase-mvp.md](render-supabase-mvp.md). Las verificaciones automatizadas no certifican ausencia absoluta de errores ni sustituyen una revisión de producción o las pruebas de un binario iOS.

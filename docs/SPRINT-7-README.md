# TechSupport — Sprint 7

## Funcionalidades incorporadas

### HU-21 — Buscar tickets

- Búsqueda por folio y asunto.
- Para personal autorizado, búsqueda por nombre/correo del solicitante, nombre/correo del técnico y datos del equipo (código, tipo, marca, modelo y serie).
- El administrador consulta todos los tickets.
- El técnico consulta exclusivamente tickets asignados a su cuenta.
- El solicitante conserva el alcance de sus propias solicitudes.
- La consulta utiliza paginación y conserva los parámetros de búsqueda.

### HU-22 — Filtrar solicitudes

- Filtros combinables por categoría, prioridad, estado y rango de fechas.
- Se valida que la fecha final no sea anterior a la fecha inicial cuando se proporcionan ambas fechas; también se permite filtrar solo por fecha inicial o solo por fecha final.
- La consulta aplica primero los permisos del perfil y después los criterios enviados.
- Los filtros se conservan al cambiar de página.

### HU-23 — Ver panel de indicadores

- El administrador visualiza conteos reales de Pendiente, Asignado, En proceso, Resuelto y Cerrado.
- Los valores se agrupan directamente desde los registros de la base de datos.
- Cada indicador permite abrir la consulta filtrada por el estado correspondiente.

### HU-24 — Ver resumen personal

- El técnico visualiza los mismos estados, limitados a tickets asignados a su cuenta.
- El solicitante visualiza un resumen limitado a las solicitudes que registró.
- Ningún perfil recibe conteos globales fuera de su alcance.

## Mejoras complementarias

### Buscador de técnicos en asignación

- Se reemplaza el selector largo por un campo de búsqueda con sugerencias.
- La consulta se realiza al servidor después de escribir al menos dos caracteres.
- El endpoint devuelve hasta diez coincidencias de cuentas activas con rol `tecnico`.
- Se puede buscar por nombre o correo.
- Se incluye navegación por teclado, mensajes de estado y validación para impedir enviar un nombre que no se haya seleccionado.
- La ruta de búsqueda solo está disponible para el administrador y tiene límite de solicitudes.
- La asignación sigue validándose en el servidor y conserva su transacción y protección contra doble asignación.

### Navegación de Historial y Consultas

- La opción Consultas dirige al listado con búsqueda y filtros.
- La opción Historial abre un índice de cronologías.
- El administrador consulta el historial general; el técnico únicamente el de tickets asignados.
- Se mantienen las vistas de historial por ticket y por equipo ya implementadas en Sprint 6.

### Interfaz

- Se mantienen Bootstrap, los iconos SVG locales y la identidad visual moderna existente.
- Se mejoran la jerarquía de filtros, estados, tablas, indicadores y selector de técnicos.
- Se actualiza el retorno desde el detalle del ticket para que el técnico vuelva a sus asignaciones.
- Se eliminan etiquetas y mensajes de Sprints anteriores que ya no describen el estado actual.

## Archivos nuevos

- `app/Http/Controllers/DashboardController.php`
- `app/Http/Controllers/Tickets/TicketHistorialController.php`
- `resources/js/technician-picker.js`
- `resources/views/tickets/historial.blade.php`
- `tests/Feature/TicketsConsultasTest.php`
- `tests/Feature/DatabaseSeederTest.php`

## Archivos modificados

- `README.md`
- `routes/web.php`
- `app/Http/Controllers/Admin/TicketAsignacionController.php`
- `app/Http/Controllers/Tickets/TicketConsultaController.php`
- `resources/js/app.js`
- `resources/css/techsupport-modern.css`
- `resources/views/layouts/app.blade.php`
- `resources/views/dashboard.blade.php`
- `resources/views/tickets/index.blade.php`
- `resources/views/tickets/show.blade.php`
- `resources/views/tickets/create.blade.php`
- `tests/Feature/TicketsAsignacionTest.php`
- `tests/Feature/TicketsConsultaDetalleTest.php`
- `database/seeders/DatabaseSeeder.php`

## Base de datos

No se agregan tablas ni columnas. El Sprint 7 utiliza las relaciones y los campos existentes en `tickets`, `users`, `equipos`, `categorias`, `prioridades` y `estados_ticket`. Se ajusta `DatabaseSeeder` para cargar los catálogos de forma idempotente y no crear la cuenta genérica `test@example.com`. No se incorpora una cuenta administradora con contraseña fija.

## Pruebas automatizadas incorporadas o actualizadas

- Búsqueda administrativa por folio, solicitante, técnico y equipo.
- Restricción de resultados del técnico a sus propias asignaciones.
- Combinación de filtros por categoría, prioridad, estado y fecha.
- Búsqueda remota de técnicos activos y exclusión de cuentas inactivas/no técnicas.
- Protección del endpoint de búsqueda para administradores.
- Acceso al índice de historial y restricción por rol.
- Conteos administrativos por estado.
- Conteos del técnico limitados a sus asignaciones.
- Carga idempotente de catálogos desde DatabaseSeeder y ausencia de una cuenta demo fija.
- Actualización de la prueba anterior que impedía al técnico consultar el listado.
- Actualización de la prueba de asignación para verificar el nuevo buscador en lugar del selector completo.

## Comprobaciones manuales pendientes de evidencia

1. Administrador: abrir un ticket pendiente, buscar técnicos por nombre y correo, seleccionar uno y asignarlo.
2. Administrador: buscar tickets por folio, solicitante, técnico y código de equipo.
3. Administrador: combinar categoría, prioridad, estado y fechas; comprobar paginación.
4. Administrador: revisar los cinco indicadores y abrir un estado desde el panel.
5. Técnico: comprobar que consultas, historial e indicadores muestran únicamente sus tickets.
6. Solicitante: comprobar que consulta e indicadores muestran únicamente sus solicitudes y que no puede abrir el historial general.
7. Revisar navegación lateral, menú contraído, vista móvil y botones de regreso.
8. Ejecutar la suite completa y compilar Vite.

## Comandos de validación

```powershell
php artisan optimize:clear
npm run build
php artisan test
php artisan route:list
git diff --check
```

El Sprint 7 no debe considerarse cerrado hasta que las pruebas automatizadas y las comprobaciones manuales se ejecuten en el entorno local del proyecto y se incorporen las capturas al documento Word del Sprint.

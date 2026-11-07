# TechSupport — Sprint 6

## Base de trabajo

- Repositorio: `Marvin95122/TechSupport`
- Commit base revisado: `21ac4c40a8bf47a5c104d3a49aa3bb95139fcfed`
- Historias: HU-17, HU-18, HU-19 y HU-20
- Requerimientos: RF-21, RF-22, RF-23 y RF-24

## Funcionalidades incorporadas

### HU-17 — Registrar observaciones

- El técnico asignado puede registrar observaciones y actividades.
- Cada registro conserva ticket, autor, fecha, tipo y contenido.
- Los registros son append-only: los nuevos envíos no reemplazan los anteriores.
- No se permite agregar registros cuando el ticket está cerrado.

### HU-18 — Registrar solución

- El técnico asignado puede registrar diagnóstico, acciones realizadas y solución aplicada.
- Cada envío conserva ticket, autor y fecha.
- Los registros anteriores no se sobrescriben.
- Se permite registrar cuando el ticket está En proceso o Resuelto.
- No se permite registrar soluciones después del cierre.

La historia no cambia el flujo de estados del Sprint 5. El paso a Resuelto y el cierre continúan sujetos a las transiciones secuenciales ya implementadas en `TicketEstadoController`.

### HU-19 — Consultar historial del ticket

- El detalle del ticket integra una cronología con el registro inicial, cambios de estado, observaciones, actividades y soluciones.
- La cronología se ordena por fecha.
- Se mantiene la autorización existente: administrador, solicitante propietario o técnico asignado.

### HU-20 — Ver historial del equipo

- El administrador puede consultar todos los tickets relacionados con el equipo.
- Un técnico debe tener al menos un ticket asignado de ese equipo y solo consulta los tickets asignados a su cuenta.
- La consulta no filtra equipos inactivos; la baja lógica no elimina el registro ni sus antecedentes.

## Archivos nuevos

- `database/migrations/2026_11_07_000001_create_ticket_observaciones_table.php`
- `database/migrations/2026_11_07_000002_create_ticket_soluciones_table.php`
- `app/Models/TicketObservacion.php`
- `app/Models/TicketSolucion.php`
- `app/Services/TicketTimelineService.php`
- `app/Http/Controllers/Tickets/TicketObservacionController.php`
- `app/Http/Controllers/Tickets/TicketSolucionController.php`
- `app/Http/Controllers/Equipos/EquipoHistorialController.php`
- `resources/views/tickets/partials/registro-atencion.blade.php`
- `resources/views/tickets/partials/historial.blade.php`
- `resources/views/equipos/historial.blade.php`
- `tests/Feature/TicketsHistorialTest.php`

## Archivos modificados

- `routes/web.php`
- `app/Models/Ticket.php`
- `app/Models/Equipo.php`
- `app/Http/Controllers/Tickets/TicketConsultaController.php`
- `resources/views/tickets/show.blade.php`
- `resources/views/admin/equipos/show.blade.php`

## Pruebas incluidas

`tests/Feature/TicketsHistorialTest.php` contiene pruebas para:

- Registro de observaciones y actividades.
- Conservación de varios registros.
- Restricción al técnico asignado.
- Bloqueo de registros posteriores al cierre.
- Registro de diagnóstico, acciones y solución.
- Restricción de soluciones antes de iniciar atención.
- Cronología de eventos.
- Consulta de historial del equipo por administrador y técnico.
- Conservación del historial de equipos inactivos.

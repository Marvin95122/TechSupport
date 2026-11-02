# Sprint 5 — HU-14: Actualizar estado del ticket

## Objetivo

Permitir que el técnico responsable actualice el estado de un ticket asignado conforme avance la atención, conservando el historial de cada transición.

## Historia de usuario

Como técnico autorizado, quiero actualizar el estado de un ticket para reflejar el avance de su atención.

## Alcance y reglas

- Solo un usuario autenticado, activo y con rol `tecnico` puede ejecutar la actualización.
- El técnico únicamente puede consultar el detalle y actualizar tickets cuyo `tecnico_id` corresponde a su propia cuenta.
- Las transiciones son secuenciales:
  - `Asignado` → `En proceso`.
  - `En proceso` → `Resuelto`.
  - `Resuelto` → `Cerrado`.
- La transición `Pendiente` → `Asignado` corresponde a HU-13 y solo la realiza el administrador al asignar el ticket.
- No se permiten saltos, retrocesos ni cambios posteriores al estado `Cerrado`.
- Al pasar a `Resuelto` se registra `resuelto_en`.
- Al pasar a `Cerrado` se registra `cerrado_en`.
- Cada transición actualiza el ticket e inserta un registro en `cambios_estado` dentro de una misma transacción.
- El historial conserva ticket, usuario que realizó el cambio, estado anterior, estado nuevo y fecha.
- No se agrega una migración: se reutilizan `tickets`, `estados_ticket` y `cambios_estado`.

## Archivos incorporados o modificados

- `app/Http/Controllers/Tickets/TicketEstadoController.php`: valida la asignación y ejecuta las transiciones permitidas.
- `app/Http/Controllers/Tickets/TicketConsultaController.php`: permite al técnico consultar únicamente el detalle de sus tickets asignados y determina el siguiente estado.
- `routes/web.php`: protege la consulta de detalle y la actualización con middleware de autenticación, cuenta activa y rol.
- `resources/views/tickets/show.blade.php`: incorpora el panel de seguimiento técnico y la acción correspondiente al siguiente estado.
- `tests/Feature/TicketsEstadoTest.php`: verifica permisos, propiedad del ticket, transiciones, fechas e historial.

## Validación específica

Desde la raíz del proyecto, con PHP 8.4 configurado:

```powershell
php artisan test --filter=TicketsEstadoTest
```

La suite completa se ejecutará al finalizar las historias del Sprint 5, para evitar repetir verificaciones durante cada checkpoint.

## Prueba funcional desde el navegador

1. Iniciar sesión con un usuario activo que tenga rol `tecnico`.
2. Abrir el detalle de un ticket asignado a esa cuenta. Durante HU-14 se puede acceder directamente a `/tickets/{id}`; el listado de asignaciones se incorpora en HU-15.
3. Comprobar que aparece el panel `Seguimiento técnico` y únicamente la siguiente acción permitida.
4. Presionar `Iniciar atención`; comprobar que el estado cambia a `En proceso`.
5. Volver al detalle y presionar `Marcar como resuelto`; comprobar que el estado cambia a `Resuelto`.
6. Finalmente, presionar `Cerrar ticket`; comprobar que el estado cambia a `Cerrado` y ya no aparece otra acción.
7. Intentar consultar un ticket asignado a otro técnico: el servidor debe responder con acceso prohibido.
8. Verificar en MySQL los campos `resuelto_en`, `cerrado_en` y las filas generadas en `cambios_estado`.

## Consultas SQL de comprobación

Consultar el ticket:

```sql
SELECT
    t.id,
    t.folio,
    tecnico.name AS tecnico_responsable,
    estado.nombre AS estado,
    t.asignado_en,
    t.resuelto_en,
    t.cerrado_en
FROM tickets AS t
LEFT JOIN users AS tecnico
    ON tecnico.id = t.tecnico_id
INNER JOIN estados_ticket AS estado
    ON estado.id = t.estado_id
ORDER BY t.id DESC
LIMIT 10;
```

Consultar las transiciones:

```sql
SELECT
    ce.id,
    t.folio,
    usuario.name AS usuario,
    anterior.nombre AS estado_anterior,
    nuevo.nombre AS estado_nuevo,
    ce.fecha
FROM cambios_estado AS ce
INNER JOIN tickets AS t
    ON t.id = ce.ticket_id
INNER JOIN users AS usuario
    ON usuario.id = ce.usuario_id
LEFT JOIN estados_ticket AS anterior
    ON anterior.id = ce.estado_anterior_id
INNER JOIN estados_ticket AS nuevo
    ON nuevo.id = ce.estado_nuevo_id
ORDER BY ce.fecha DESC, ce.id DESC;
```

## Fuera del alcance de HU-14

La pantalla de listado `Mis asignaciones` corresponde a HU-15. La consulta general de historial y sus filtros se mantienen para el Sprint 6.

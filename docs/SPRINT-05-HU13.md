# Sprint 5 — HU-13: Asignar técnico

## Objetivo

Permitir que el administrador asigne un ticket pendiente a un técnico activo y autorizado, conservando quién realizó la asignación y cuándo ocurrió.

## Criterios de aceptación cubiertos

1. Solo usuarios con rol `tecnico` y cuenta activa aparecen como elegibles.
2. La asignación guarda el técnico responsable y cambia el estado de `Pendiente` a `Asignado`.
3. Se conserva el administrador que realizó la asignación y la fecha. También se inserta un registro en `cambios_estado` con el estado anterior, el nuevo estado, el autor y la fecha.

## Archivos incorporados o modificados

- `routes/web.php`: ruta protegida para asignar tickets, exclusiva del administrador.
- `app/Http/Controllers/Admin/TicketAsignacionController.php`: validación y operación transaccional de asignación.
- `app/Http/Controllers/Tickets/TicketConsultaController.php`: carga del técnico y del administrador que asignó; entrega técnicos activos al detalle administrativo.
- `app/Models/User.php`: relaciones de tickets asignados y tickets asignados por el administrador.
- `resources/views/tickets/show.blade.php`: formulario de asignación y visualización del responsable.
- `tests/Feature/TicketsAsignacionTest.php`: pruebas de permisos, elegibilidad, asignación e integridad del historial.

## Reglas de implementación

- La asignación solo está disponible para administradores autenticados y activos.
- El técnico debe tener `role = tecnico` e `is_active = true`.
- Solo se asignan tickets en estado `Pendiente` que todavía no tengan técnico.
- No se permite una segunda asignación desde HU-13. La reasignación no forma parte de esta historia.
- La actualización del ticket y el registro de `cambios_estado` se ejecutan dentro de una misma transacción.
- La asignación utiliza los campos existentes `tecnico_id`, `asignado_por_id` y `asignado_en`.
- No se agrega otra migración: la tabla `cambios_estado` ya fue creada y ejecutada en la preparación del Sprint 5.

## Validación local

Ejecutar desde la raíz del proyecto:

```powershell
php artisan test --filter=TicketsAsignacionTest
```

Después de que las pruebas específicas pasen, ejecutar la suite completa antes de cerrar el Sprint 5:

```powershell
php artisan test
```

## Prueba funcional desde el navegador

1. Iniciar sesión con una cuenta de administrador.
2. Abrir `Tickets` y entrar al detalle de un ticket en estado `Pendiente`.
3. Seleccionar un técnico activo y presionar `Asignar técnico`.
4. Comprobar que aparece la notificación de operación realizada.
5. Confirmar que el ticket muestra el técnico responsable y el estado `Asignado`.
6. Verificar en MySQL que `tecnico_id`, `asignado_por_id` y `asignado_en` quedaron registrados.
7. Consultar `cambios_estado` y comprobar que se guardó el estado anterior, el nuevo estado, el administrador y la fecha.

## Consulta SQL de comprobación

```sql
SELECT
    t.folio,
    t.tecnico_id,
    tecnico.name AS tecnico,
    t.asignado_por_id,
    administrador.name AS asignado_por,
    t.asignado_en,
    estado.nombre AS estado
FROM tickets AS t
LEFT JOIN users AS tecnico
    ON tecnico.id = t.tecnico_id
LEFT JOIN users AS administrador
    ON administrador.id = t.asignado_por_id
INNER JOIN estados_ticket AS estado
    ON estado.id = t.estado_id
ORDER BY t.id DESC
LIMIT 10;
```

```sql
SELECT
    ce.ticket_id,
    t.folio,
    autor.name AS usuario_responsable,
    anterior.nombre AS estado_anterior,
    nuevo.nombre AS estado_nuevo,
    ce.fecha
FROM cambios_estado AS ce
INNER JOIN tickets AS t
    ON t.id = ce.ticket_id
INNER JOIN users AS autor
    ON autor.id = ce.usuario_id
LEFT JOIN estados_ticket AS anterior
    ON anterior.id = ce.estado_anterior_id
INNER JOIN estados_ticket AS nuevo
    ON nuevo.id = ce.estado_nuevo_id
ORDER BY ce.fecha DESC, ce.id DESC;
```

## Fuera del alcance de HU-13

La actualización manual de estados por el técnico, la pantalla de mis asignaciones y la bitácora completa se desarrollarán en las historias siguientes del Sprint 5. Esta entrega no las da por terminadas.

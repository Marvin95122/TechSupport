# Sprint 5 — HU-15: Mis asignaciones

## Objetivo

Permitir que el técnico consulte los tickets que le fueron asignados para organizar las atenciones pendientes y acceder al detalle de cada solicitud.

## Historia de usuario

Como técnico, quiero ver mis tickets asignados para organizar las atenciones pendientes.

## Datos del backlog

- Usuario: Técnico.
- Prioridad: Alta.
- Riesgo: Medio.
- Estimación: 3 puntos.
- Iteración: Sprint 5.
- Dependencia: HU-13.
- Requerimiento asociado: RF-19.
- Valor esperado: Organización del trabajo técnico.

## Criterios de aceptación

1. El listado contiene únicamente los tickets asignados al técnico autenticado.
2. Cada registro muestra folio, prioridad y estado.
3. Cada registro ofrece una acción para consultar el detalle del ticket.

## Reglas de acceso y consulta

- Solo pueden ingresar usuarios autenticados, activos y con rol `tecnico`.
- La consulta filtra los registros por `tickets.tecnico_id = usuario autenticado`.
- No se reciben identificadores de técnico desde el formulario ni desde parámetros de consulta.
- Un técnico no puede visualizar tickets asignados a otra cuenta.
- Se muestran las asignaciones independientemente de su estado, incluidos los tickets resueltos y cerrados, para conservar la consulta de las atenciones realizadas.
- La acción `Ver detalle` reutiliza la ruta `tickets.show`, que ya valida que el técnico sea el responsable.
- El seguimiento y la actualización del estado se realizan dentro del detalle del ticket mediante HU-14.
- No se agrega una migración: se reutilizan las tablas `tickets`, `prioridades`, `estados_ticket` y las relaciones existentes.

## Archivos incorporados o modificados

- `app/Http/Controllers/Tickets/MisAsignacionesController.php`: consulta las asignaciones del técnico autenticado.
- `routes/web.php`: incorpora la ruta protegida `tecnico.asignaciones.index`.
- `resources/views/tickets/asignaciones.blade.php`: presenta folio, prioridad, estado y acceso al detalle.
- `resources/views/layouts/app.blade.php`: habilita el acceso lateral `Mis asignaciones` para el rol técnico. El seguimiento HU-14 permanece dentro del detalle; no se crea una pantalla duplicada.
- `resources/views/dashboard.blade.php`: habilita el acceso rápido desde el panel del técnico.
- `tests/Feature/TicketsMisAsignacionesTest.php`: verifica autenticación, roles, aislamiento por técnico, campos, detalle, estado cerrado y listado vacío.

## Validación automatizada

Desde la raíz del proyecto, con PHP 8.4 configurado:

```powershell
php artisan test --filter=TicketsMisAsignacionesTest
```

Al concluir las historias del Sprint 5, ejecutar la suite completa:

```powershell
php artisan test
```

## Prueba funcional desde el navegador

1. Iniciar sesión con un usuario activo que tenga rol `tecnico`.
2. Abrir `Mis asignaciones` desde el menú lateral.
3. Comprobar que aparecen los tickets asignados a esa cuenta.
4. Verificar que cada registro muestra folio, prioridad y estado.
5. Abrir `Ver detalle` en uno de los tickets.
6. Comprobar que se muestra el detalle y el panel `Seguimiento técnico` de HU-14.
7. Iniciar sesión con otro técnico y comprobar que no puede consultar las asignaciones de la primera cuenta.
8. Probar un técnico sin tickets asignados y comprobar el mensaje de listado vacío.

## Fuera del alcance de HU-15

- Consulta global del historial de cambios: Sprint 6.
- Métricas y reportes: Sprint 7.
- Reasignación de tickets: no contemplada en HU-13 ni HU-15.
- Una pantalla independiente de seguimiento: las acciones de HU-14 se realizan desde el detalle del ticket.

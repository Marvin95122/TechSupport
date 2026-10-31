# TechSupport — Mejora transversal de interfaz (antes del Sprint 4)

**Base utilizada:** copia `TechSupport-main.zip` del commit de integración `d8f140d` (Sprint 3).
**Documento de referencia:** `TechSupportDocumentoAvanzado.docx`, capítulos I-IV, Sprint 0-3 y cronograma definitivo 0-11.
**Alcance:** UX y presentación. No es un Sprint nuevo ni cambia las historias de usuario cerradas.

## Qué incluye

- Menú lateral contraíble en escritorio (ancho completo o barra de íconos) con preferencia conservada en `localStorage`; en móvil sigue usando el offcanvas de Bootstrap.
- Iconografía SVG local y consistente, sin CDN.
- Notificaciones de éxito o error dentro de la aplicación con cierre y desaparición automática; los errores de los campos siguen visibles hasta corregirse.
- Confirmación interna en diálogos accesibles para activar/desactivar cuentas y para dar de baja lógica a equipos; no se utiliza `window.confirm()`.
- Modernización visual de tarjetas, formularios, tablas, botones, encabezados, panel por rol y acceso de inicio de sesión.
- Corrección del enlace **Equipos tecnológicos** del dashboard: ya es operativo, porque el Sprint 3 está terminado.
- Se preservan las opciones bloqueadas de los futuros Sprints sin inventar métricas ni funciones.

## Archivos principales

- `resources/views/layouts/app.blade.php`: estructura compartida, navegación por rol, avisos.
- `resources/views/partials/icons.blade.php`: biblioteca de íconos SVG.
- `resources/views/partials/confirm-dialog.blade.php`: ventana de confirmación compartida.
- `resources/js/techsupport-ui.js`: sidebar, toast y diálogo.
- `resources/css/techsupport-modern.css`: capa visual sobre los CSS existentes.
- `resources/js/app.js`: importa los nuevos recursos.
- `resources/views/auth/login.blade.php`: acceso modernizado.
- `resources/views/dashboard.blade.php`: acceso rápido a módulos implementados.
- `resources/views/admin/users/index.blade.php`: confirmación y avisos compartidos.
- `resources/views/admin/equipos/index.blade.php`, `create.blade.php`, `show.blade.php`: avisos compartidos y baja con confirmación interna.

## Instalación recomendada en proyecto local

**Importante:** conservar `.env`, `.git`, base de datos, `storage`, `vendor` y `node_modules`. No reemplazar toda la carpeta local por el ZIP completo si existen datos/configuración local.

Se incluye un ZIP independiente `TechSupport-mejora-UI-parche.zip` con un instalador PowerShell y únicamente los archivos que cambian. Abrir una terminal PowerShell en Windows y confirmar que la rama es la adecuada y el árbol de trabajo está limpio. Se recomienda crear primero una rama independiente desde `main`:

```powershell
Set-Location "$env:USERPROFILE\Projects\techsupport"
git switch main
git pull --ff-only origin main
git switch -c mejora/interfaz-pre-sprint-04
git status --short
```

Descomprimir el ZIP del parche y ejecutar su `Instalar-Mejoras-UI.ps1`. El instalador crea un respaldo de los archivos anteriores en la carpeta Documentos, exige un directorio Git limpio y no toca `.env`, migraciones ni datos. No hace commits ni `git push` automáticamente.

Una vez copiados los recursos:

```powershell
$env:Path = "$env:USERPROFILE\php84;$env:Path"
php artisan optimize:clear
npm ci
npm run build
php artisan test
git diff --check
git status --short
```

Durante el desarrollo puede utilizarse `npm run dev` (en otra terminal) junto con `php artisan serve`.

## Comprobaciones manuales sugeridas

1. Iniciar sesión como **administrador** y contraer/expandir el menú; recargar y verificar que se conserve la preferencia. Comprobar en pantalla reducida el offcanvas de Bootstrap.
2. Abrir **Usuarios**, registrar y editar una cuenta. Comprobar la notificación flotante tras guardar.
3. Desactivar o activar una cuenta de prueba; verificar que aparezca el diálogo del sistema y que **Cancelar** no envíe el formulario. Probar un intento de desactivar la cuenta propia para verificar que sigue protegido por el servidor.
4. Abrir **Equipos**, registrar, editar y desactivar un equipo de prueba. Comprobar el mensaje flotante y el diálogo. Verificar que la baja sea lógica, sin eliminar su registro.
5. Acceder con **técnico** y **solicitante** y verificar el sidebar de cada rol y que no tengan acceso a rutas administrativas.
6. Verificar los mensajes de validación junto a los campos, navegación por teclado, Escape en los diálogos y vista móvil.

## Estado de las verificaciones de esta entrega

- `node --check`: JavaScript sin errores de sintaxis.
- `php -l`: archivos PHP de controladores, modelos, migraciones y rutas sin errores de sintaxis.
- `tinycss2`: hoja CSS adicional sin errores de análisis.
- Auditoría estática: confirmaciones nativas eliminadas, avisos de acciones concentrados en la plantilla compartida, enlace a equipos habilitado.
- **Pendiente en Windows:** ejecutar `npm ci && npm run build` y `php artisan test` porque el entorno de entrega no consiguió instalar npm y no dispone de `vendor`/Composer para correr Laravel. No se declara que estas pruebas ya hayan pasado.

## Límites de esta mejora

No se han agregado nuevas tablas, campos, endpoints, reglas de autorización ni lógica de tickets. Las funcionalidades de Sprint 4 a Sprint 11 seguirán su cronograma. No se modifica el documento académico proporcionado: este anexo permite documentar la mejora visual cuando corresponda.

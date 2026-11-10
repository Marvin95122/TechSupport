# TechSupport · Mejora del módulo de reportes (Sprint 8)

## Qué incorpora

- Identidad visual propia para el centro de análisis de reportes.
- Indicadores de atención y tasa de finalización.
- Gráficas de tendencia mensual, distribución por estado, categoría y prioridad.
- Periodos rápidos: últimos 7 días, últimos 30 días y mes actual.
- Expediente administrativo de solo lectura por solicitud, con datos generales y cronología completa de atención.
- Conservación de los filtros al abrir un expediente y regresar al reporte.
- Pruebas de experiencia, analítica, cronología y permisos.
- No agrega dependencias de npm ni Composer.

## Aplicación

1. Confirma que estás en la rama `sprint-08/validaciones-seguridad-reporte`.
2. Extrae el ZIP en la raíz del proyecto, conservando las carpetas `app`, `resources` y `tests`.
3. Abre PowerShell en la raíz del repositorio y ejecuta:

   ```powershell
   Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass
   .\apply-sprint8-report-enhancements.ps1
   ```

4. Limpia vistas y rutas, y compila los recursos:

   ```powershell
   php artisan optimize:clear
   npm run build
   ```

5. Ejecuta las pruebas del módulo:

   ```powershell
   php artisan test --filter=ReporteAtencionTest
   php artisan test --filter=ReporteAtencionExperienciaTest
   ```

6. Comprueba el estado del repositorio:

   ```powershell
   git diff --check
   git status --short --branch
   ```

El script crea una copia de seguridad de los archivos que reemplaza o modifica (incluida la vista principal de reportes y la vista de impresión) en `storage/app/` antes de aplicar los cambios. Si detecta que la estructura del código no coincide con la versión esperada, se detiene sin continuar con las sustituciones.

# TechSupport

Aplicación web de gestión de soporte técnico para administrar usuarios, equipos, solicitudes, asignaciones y seguimiento de incidencias.

## Tecnologías

- PHP 8.3 o superior.
- Laravel 13.
- Bootstrap 5.3 y JavaScript nativo.
- Vite 7 para los recursos frontend.
- Base de datos configurada mediante las variables `DB_*` del archivo `.env`.

## Perfiles

| Perfil | Funciones principales |
|---|---|
| Administrador | Administración de usuarios y equipos, consulta global de tickets, asignación de técnicos, historial e indicadores generales. |
| Técnico | Consulta de asignaciones propias, actualización secuencial del estado, registro de observaciones y soluciones, historial e indicadores personales. |
| Solicitante | Registro y consulta de sus propias solicitudes. |

## Funcionalidades implementadas

- Autenticación y control de acceso por rol; las cuentas inactivas no pueden utilizar las funciones protegidas.
- Administración de usuarios y baja lógica de equipos.
- Registro de tickets con folio único, categoría, prioridad y equipo opcional.
- Asignación de tickets pendientes a técnicos activos.
- Seguimiento secuencial: Asignado → En proceso → Resuelto → Cerrado.
- Registro acumulativo de observaciones, actividades, diagnósticos y soluciones.
- Cronología de cada ticket e historial de incidencias por equipo.
- Búsqueda y filtros combinables por folio, solicitante, técnico, equipo, categoría, prioridad, estado y fecha.
- Panel de indicadores por perfil, con datos consultados desde la base de datos.
- Búsqueda remota de técnicos para asignación, sin descargar el catálogo completo al navegador.

## Instalación local

1. Clonar el repositorio y entrar a la carpeta del proyecto.
2. Instalar dependencias PHP:

   ```bash
   composer install
   ```

3. Crear `.env` a partir de `.env.example` y configurar la conexión de base de datos.
4. Generar la clave de la aplicación:

   ```bash
   php artisan key:generate
   ```

5. Ejecutar migraciones y cargar los catálogos iniciales:

   ```bash
   php artisan migrate --seed
   ```

   El seeder general carga los catálogos de categorías, prioridades y estados. No crea cuentas de demostración ni una cuenta administradora con contraseña fija.

6. Si la base de datos es nueva, crea la primera cuenta administradora mediante Tinker y reemplaza los valores de ejemplo por credenciales propias:

   ```bash
   php artisan tinker
   ```

   ```php
   App\Models\User::create([
       'name' => 'Administrador principal',
       'email' => 'CORREO_PROPIO',
       'password' => Illuminate\Support\Facades\Hash::make('CONTRASEÑA_SEGURA_PROPIA'),
       'role' => 'administrador',
       'is_active' => true,
   ]);
   ```

   No guardes la contraseña real en el repositorio ni en documentación compartida.

7. Instalar dependencias frontend y compilar:

   ```bash
   npm ci
   npm run build
   ```

8. Iniciar el servidor local:

   ```bash
   php artisan serve
   ```

No se deben subir `.env`, contraseñas, claves ni datos personales al repositorio.

## Pruebas y calidad

```bash
php artisan test
php artisan route:list
npm run build
git diff --check
```

Las pruebas de características se encuentran en `tests/Feature`. Los cambios del Sprint 7 amplían las pruebas de consultas, filtros, indicadores, historial y búsqueda de técnicos.

## Documentación por Sprint

- `docs/SPRINT-6-README.md`: observaciones, soluciones e historial.
- `docs/SPRINT-7-README.md`: búsquedas, filtros, panel de información y mejoras de navegación.

## Seguridad y alcance

Las consultas aplican primero el alcance del usuario autenticado y después los filtros. El administrador consulta todos los tickets; el técnico consulta únicamente los que tiene asignados; el solicitante consulta únicamente los que registró. La búsqueda de técnicos está protegida para administradores y devuelve como máximo diez cuentas activas con rol `tecnico`.

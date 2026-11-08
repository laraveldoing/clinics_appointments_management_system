---
author: José Raúl Miranda Prieto
branch: feature/appointments-list
status: in-progress
---
# Login mínimo y listado de citas

> Escalada a ASDD: `rsc risk` clasificó el cambio como **tier 2** (autenticación).
> Spec vinculada: [`../sdd/specs/appointments-list.md`](../sdd/specs/appointments-list.md).
> Panel de revisión: `correctness`, `security`, `tests` (3 refutadores).

## Intent

El proyecto tiene el esquema de datos completo pero ninguna pantalla usable. Esta feature abre la
primera vertical: un usuario de clínica puede iniciar sesión y ver las citas de su propia clínica.
Es el mínimo que respeta la constitución: no se exponen datos de pacientes sin autenticación.

## Scope

**Dentro:**
- Login por email y contraseña con sesión de Laravel (`Auth::attempt`).
- Logout con invalidación de sesión.
- Ruta `/appointments` protegida por middleware `auth`.
- Listado paginado de citas con paciente, doctor, fecha, estado y motivo.
- Filtro implícito por `clinic_id` mediante el scope global `BelongsToClinic`.
- Vistas Blade con Tailwind y etiquetas accesibles.
- Límite de 5 intentos de login por minuto.

**Fuera:**
- Registro público, recuperación de contraseña, verificación de email.
- Roles y permisos por rol (admin/doctor/receptionist).
- Crear, editar o cancelar citas.
- API JSON.
- Filtros, búsqueda y ordenación configurable.

## Checklist

- [x] Login funciona con credenciales válidas y rechaza las inválidas — `php artisan test`: 16 pruebas, 62 aserciones en verde.
- [x] Un invitado que entra a `/appointments` es redirigido a `/login` — test `test_guests_are_redirected_to_login`.
- [x] El listado muestra paciente, doctor, fecha, estado y motivo — test `test_list_shows_every_column_of_the_contract`.
- [x] El listado solo muestra citas de la clínica del usuario — test con dos clínicas y un colega de la misma clínica.
- [x] Logout cierra la sesión — test `test_users_can_logout`.
- [x] Formato limpio — `vendor/bin/pint --test` en verde sobre los archivos tocados (el baseline del repo tenía avisos previos).

## Evidence

- `php artisan test` → `{"result":"passed","tests":16,"passed":16,"assertions":62}`.
- `vendor/bin/pint --test` sobre controladores, modelo, rutas y pruebas → `passed`.
- `npx rsc risk` → **tier 2** (auth) → spec `appointments-list` aprobada por `rsc spec gate`.
- Panel de 3 refutadores → 12 hallazgos: 11 corregidos, 1 aceptado (enumeración por tiempo residual).
- `php artisan route:list` → `login`, `logout`, `appointments.index` registradas.

## Next

Abrir el pull request y esperar aprobación humana (tier 2 no se auto-mergea).

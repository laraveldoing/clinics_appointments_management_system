---
author: José Raúl Miranda Prieto
branch: feature/appointments-list
status: in-progress
---
# Login mínimo y listado de citas

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

**Fuera:**
- Registro público, recuperación de contraseña, verificación de email.
- Roles y permisos por rol (admin/doctor/receptionist).
- Crear, editar o cancelar citas.
- API JSON.
- Filtros, búsqueda y ordenación configurable.

## Checklist

- [ ] Login funciona con credenciales válidas y rechaza las inválidas — prueba: tests de autenticación en verde.
- [ ] Un invitado que entra a `/appointments` es redirigido a `/login` — prueba: test de redirección.
- [ ] El listado muestra paciente, doctor, fecha, estado y motivo — prueba: test de contenido de la vista.
- [ ] El listado solo muestra citas de la clínica del usuario — prueba: test con dos clínicas.
- [ ] Logout cierra la sesión — prueba: test de logout.
- [ ] Formato limpio — prueba: `vendor/bin/pint --test` sin cambios pendientes.

## Evidence

_(se completa al ejecutar cada comprobación)_

## Next

Crear las rutas y el controlador de login.

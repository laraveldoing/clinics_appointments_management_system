# Stack técnico: Laravel

## Framework

El proyecto usa Laravel 13.x y la estructura típica de una app web MVC con Blade.

## Patrones aplicados en el código

### 1) Rutas y middleware

Las rutas web se definen en `routes/web.php`:

- rutas públicas para login y welcome
- rutas protegidas con `auth`
- throttling en login a 5 intentos por minuto

### 2) Controladores delgados

Los controladores reciben la petición, delegan validación y gestionan la respuesta final. No llevan lógica compleja de negocio dispersa.

### 3) Validación por Form Request

La creación de citas usa `StoreAppointmentRequest`, que centraliza:

- autorización
- reglas de negocio de dominio
- mensajes en español
- validación de pertenencia a clínica

### 4) Multi-tenancy por clínica

El trait `BelongsToClinic` aplica un scope global que restringe las consultas a `clinic_id` del usuario autenticado si hay sesión activa. Esto evita que un usuario de una clínica vea registros de otra.

### 5) Eloquent y vistas Blade

- Los modelos usan `HasFactory`, relaciones y casts explícitos.
- Las vistas Blade renderizan listas y formularios de la clínica autenticada.
- El listado de citas usa paginación y orden por fecha `start_time`.

## Convenciones de la app

- `auth()->check()` para decidir navegación por la home page.
- `redirect()->intended(route('appointments.index'))` después del login.
- `clinic_id` como eje de seguridad y filtrado de datos.
- `SoftDeletes` en modelos de dominio que requieren borrado lógico.

## Comandos de referencia

```bash
php artisan route:list
php artisan migrate
php artisan test
php artisan serve
```

# Stack técnico: base de datos

## Motor usado

El proyecto usa PostgreSQL 16 para desarrollo local, con configuración en `01-TOOLS/postgres`.

## Configuración por defecto

El archivo `.env.example` de la raíz del repo define:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=clinics_db
DB_USERNAME=clinics_user
DB_PASSWORD=
```

La contraseña y el estado exacto del contenedor se gestionan desde `01-TOOLS/postgres/.env`.

## Cómo iniciarla

```bash
cd 01-TOOLS/postgres
cp .env.example .env
chmod 600 .env
docker compose up -d
./test_connection.sh
```

## Patrón del esquema

- Las tablas se crean vía migraciones de Laravel.
- La base del contenedor se usa como infraestructura, no como fuente de verdad del esquema.
- El código de la app define los modelos y migraciones; ser explícitos con `clinic_id` y relaciones es clave para el multi-tenancy.

## Relacionado con la app

Los modelos de negocio que tienen pertenencia a clínica usan el trait `BelongsToClinic`, que filtra consultas por `clinic_id` del usuario autenticado cuando existe sesión activa.

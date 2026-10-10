# PostgreSQL

> Contenedor PostgreSQL 16 de desarrollo para el Clinics Appointments Management
> System (Laravel + Filament). Da la base de datos local sobre la que correrán
> `php artisan migrate` y las migraciones propias de Filament.

## When to use

Es la base de datos de desarrollo de la app de gestión de citas. Cualquier
trabajo local de Laravel (migraciones, seeders, Filament) la necesita levantada:
crea el esquema clínico sobre el que operan pacientes, citas, historiales,
facturas y usuarios.

## Setup

1. `cp .env.example .env && chmod 600 .env`
2. Pon una contraseña fuerte en `POSTGRES_PASSWORD` (`openssl rand -hex 24`).
3. `docker compose up -d`
4. `./test_connection.sh` — debe terminar con `OK — ...`.

El esquema **no** vive en este contenedor: la base arranca vacía y las tablas
las crea la aplicación Laravel con `php artisan migrate` (ver
`database/migrations/`). Así Laravel sigue siendo la única fuente de verdad del
esquema, como exige Filament.

## Scripts

| Script | What it does | Example |
|--------|--------------|---------|
| `test_connection.sh` | Smoke-test: confirma que el contenedor responde y que las credenciales de `.env` autentican (`SELECT 1`). | `./test_connection.sh` |

## Conexión desde Laravel

El bloque `.env` de la aplicación (raíz del repo). Como los contenedores de
Docker viven en una red propia, si Laravel corre en el host se usa `127.0.0.1`
con el `POSTGRES_PORT` del contenedor:

```
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=clinics_db
DB_USERNAME=clinics_user
DB_PASSWORD=<la de este .env>
```

## Comandos útiles

| Acción | Comando |
|--------|---------|
| Levantar | `docker compose up -d` |
| Ver logs | `docker compose logs -f postgres` |
| Parar (conserva datos) | `docker compose down` |
| Reset total (borra datos) | `docker compose down -v` |
| Entrar a psql | `docker exec -it clinics_postgres psql -U clinics_user -d clinics_db` |

## Operational notes

- Imagen `postgres:16-alpine`; datos en el volumen `clinics_postgres_data`.
- `docker compose down` **no** borra datos; solo `down -v` los elimina.
- `PGDATA` apunta a una subcarpeta para no ensuciar el punto de montaje.
- El `healthcheck` usa `pg_isready`; `test_connection.sh` espera a `healthy`.
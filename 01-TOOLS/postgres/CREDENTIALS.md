# Credentials — PostgreSQL

## Provider dashboard

- N/A — contenedor Docker local, no hay panel de proveedor.
- Las credenciales se definen aquí mismo, en `.env` (ignorado por git).

## Variables

| Variable | Type | Where to generate | Rotation |
|----------|------|-------------------|----------|
| `POSTGRES_DB` | config | `.env` de este tool | Cambiar solo si recreas el esquema |
| `POSTGRES_USER` | config | `.env` de este tool | Rara vez; requiere recrear el volumen |
| `POSTGRES_PASSWORD` | secret | `.env`, `openssl rand -hex 24` | Si se filtra o se comparte la máquina |
| `POSTGRES_PORT` | config | `.env` (host) | Si el 5432 del host está ocupado |

## Exceptions to the usual naming convention

> Se usa el prefijo estándar `POSTGRES_*` que consume la imagen oficial de
> PostgreSQL y `docker compose`. La app Laravel lee sus propias variables
> `DB_*` (ver README); ambas apuntan a los mismos valores.

## If a credential leaks

1. Rota `POSTGRES_PASSWORD` en `.env`.
2. `docker compose down -v` (borra volumen) y `docker compose up -d` para recrear
   el usuario con la nueva contraseña.
3. Actualiza `DB_PASSWORD` en el `.env` de Laravel.
4. Anota el incidente con fecha en este archivo.
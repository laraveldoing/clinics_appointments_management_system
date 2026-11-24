# Clinics Appointments Management System

Sistema de gestión de citas médicas para clínicas con autenticación, control de acceso por clínica y flujo de agenda para pacientes, médicos y administración.

## Stack real del proyecto

- PHP 8.3
- Laravel 13.x
- PostgreSQL 16 para desarrollo local
- Blade + Laravel routing + Eloquent
- PHPUnit + Laravel Pint

## Estado funcional actual

El proyecto ya incluye varias piezas funcionales clave:

- Login y logout con Laravel Auth
- Ruta /appointments protegida por autenticación
- Listado paginado de citas por clínica
- Creación de citas desde la interfaz con validación
- Aislamiento multi-clínica mediante `BelongsToClinic`
- CI con GitHub Actions para validación de tests y formateo

## Requisitos previos

- PHP 8.3+
- Composer
- PostgreSQL 16 (o equivalente para desarrollo local)
- Docker para levantar la base de datos local
- Node.js + npm si se quiere compilar assets con Vite

## Arranque rápido

### 1) Instalar dependencias

```bash
composer install
```

### 2) Preparar variables de entorno

```bash
cp .env.example .env
php artisan key:generate
```

### 3) Levantar la base de datos de desarrollo

La base local de PostgreSQL queda en `01-TOOLS/postgres`:

```bash
cd 01-TOOLS/postgres
cp .env.example .env
chmod 600 .env
docker compose up -d
./test_connection.sh
```

Luego deja la configuración de Laravel en la raíz del proyecto con los valores del contenedor, por ejemplo:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=clinics_db
DB_USERNAME=clinics_user
DB_PASSWORD=<tu_password>
```

### 4) Migrar y arrancar

Desde la raíz del repositorio:

```bash
php artisan migrate
npm install
npm run build
php artisan serve
```

La aplicación quedará disponible en `http://localhost:8000`.

En GitHub Codespaces, arranca el servidor escuchando en todas las interfaces para que el túnel pueda conectarse:

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

Cuando `CODESPACES=true`, Laravel confía en las cabeceras del proxy de Codespaces para generar enlaces y recursos con el dominio y protocolo públicos. En otros entornos, la confianza en proxies no se activa automáticamente.

## Comandos útiles

```bash
php artisan test
vendor/bin/pint --test
php artisan migrate:fresh --seed
```

## Estructura relevante

- `app/Http/Controllers` — controladores web
- `app/Http/Requests` — validación de formularios y reglas de negocio
- `app/Models` — entidades Eloquent
- `app/Models/Concerns/BelongsToClinic.php` — filtro de tenancy por clínica
- `database/migrations` — esquema de base de datos
- `routes/web.php` — rutas públicas y protegidas
- `resources/views` — plantillas Blade
- `tests/` — pruebas de feature
- `02-DOCS/wiki/` — documentación funcional y especificaciones

## Documentación técnica

La constitución del proyecto referencia documentación técnica bajo `02-DOCS/wiki/stack/`.
Las referencias reales del stack están aquí:

- [02-DOCS/wiki/stack/php.md](02-DOCS/wiki/stack/php.md)
- [02-DOCS/wiki/stack/database.md](02-DOCS/wiki/stack/database.md)
- [02-DOCS/wiki/stack/laravel.md](02-DOCS/wiki/stack/laravel.md)

## Nota de seguridad

No se debe commitear ningún secreto ni credencial real. Los valores sensibles se gestionan a través de `.env` local y de los archivos de `01-TOOLS`.

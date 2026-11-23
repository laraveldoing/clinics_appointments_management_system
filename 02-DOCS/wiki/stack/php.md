# Stack técnico: PHP

## Runtime

El proyecto usa PHP 8.3 como versión mínima declarada en `composer.json` y Laravel 13.x como framework base.

Composer resuelve el lockfile con `config.platform.php` fijado en `8.3.0`. Esto hace que las
dependencias bloqueadas sean compatibles con el mínimo declarado; no fija la versión real del
runtime.

## Herramientas principales

- Composer para gestión de dependencias.
- Laravel Pint para formateo y estilo de código.
- PHPStan con Larastan para análisis estático del código PHP propio y las pruebas.
- PHPUnit para pruebas automatizadas.
- Xdebug disponible localmente para depuración cuando se activa en el entorno.

## Comandos habituales

```bash
composer install
composer validate --no-check-publish --no-interaction
composer audit
composer analyse
php artisan test
vendor/bin/pint --test
php artisan key:generate
```

`composer analyse` ejecuta PHPStan en nivel 5 sobre `app/`, `bootstrap/`, `config/`, `database/`,
`public/`, `routes/`, `tests/` y `artisan`. El análisis falla ante cualquier error y no usa
baseline.

Para actualizar dependencias preservando el mínimo de PHP, revisar el diff de paquetes generado
por `composer update --with-all-dependencies --minimal-changes` y ejecutar `composer audit`.

## Convenciones del proyecto

- Mantener el código formateado antes de cada merge.
- Priorizar pruebas de feature sobre cambios de comportamiento.
- No depender de variables globales para estado del usuario autenticado; usar `Auth` y los servicios/framework de Laravel.
- Las validaciones complejas y de negocio deben vivir en `FormRequest` o capas de servicio delgadas.

## Observaciones del entorno

Las ejecuciones locales pueden mostrar avisos de Xdebug si no hay un cliente de depuración conectado; esto no bloquea la aplicación ni rompe la CI.

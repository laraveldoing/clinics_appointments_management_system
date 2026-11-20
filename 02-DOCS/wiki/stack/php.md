# Stack técnico: PHP

## Runtime

El proyecto usa PHP 8.3 como versión mínima declarada en `composer.json` y Laravel 13.x como framework base.

## Herramientas principales

- Composer para gestión de dependencias.
- Laravel Pint para formateo y estilo de código.
- PHPUnit para pruebas automatizadas.
- Xdebug disponible localmente para depuración cuando se activa en el entorno.

## Comandos habituales

```bash
composer install
php artisan test
vendor/bin/pint --test
php artisan key:generate
```

## Convenciones del proyecto

- Mantener el código formateado antes de cada merge.
- Priorizar pruebas de feature sobre cambios de comportamiento.
- No depender de variables globales para estado del usuario autenticado; usar `Auth` y los servicios/framework de Laravel.
- Las validaciones complejas y de negocio deben vivir en `FormRequest` o capas de servicio delgadas.

## Observaciones del entorno

Las ejecuciones locales pueden mostrar avisos de Xdebug si no hay un cliente de depuración conectado; esto no bloquea la aplicación ni rompe la CI.

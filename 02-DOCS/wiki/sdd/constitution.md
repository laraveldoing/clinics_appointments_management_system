---
type: constitution
title: Clinics Appointments Management System — Constitution
description: Principios no negociables que deben cumplir todas las fases rsc-sdd del sistema de gestión de citas.
tags: [laravel, sdd, constitution]
timestamp: 2026-10-06T22:13:00Z
topic: sdd
version: v1.0.0
---

# Clinics Appointments Management System — Constitution

> Version: v1.0.0 · Ratified: 2026-10-06 · Last amended: 2026-10-06
> Principios no negociables que deben cumplir todas las fases rsc-sdd. El detalle técnico va en `02-DOCS/wiki/stack/*`; esta ficha formaliza el principio y lo liga a su cercano.

## 1. Stack canon

1. **Lenguaje y runtime:** PHP 8.2–8.4, ejecutado por el *runtime* Laravel 11/12. El manifiesto (`composer.json`) fija la versión exacta; detalle en `02-DOCS/wiki/stack/php.md`.
2. **Base de datos:** MySQL 8 (con opción de PostgreSQL si el equipo prefiere). El esquema se gestiona por migraciones; detalle en `02-DOCS/wiki/stack/database.md`.
3. **Frontend:** Laravel 11/12 + Blade por defecto (o Inertia/Vue según la decisión de arquitectura). Los cambios de framework son una *amendación MAJOR*.

## 2. Quality bar

4. El código está formateado y linter-clean en cada *commit* (`pint`, cero advertencias). Detalle en `02-DOCS/wiki/stack/php.md`.
5. El *type checker* (PHPStan) pasa con `error` a cero antes del merge; niveles silenciados solo por legado dado, sin cobertura.
6. Las pruebas (Pest/PHPUnit) cierran el *merge*: rojo→verde→refactorizar, cobertura mínima por línea de código cambiada. Detalle en `02-DOCS/wiki/stack/php.md`.

## 3. Conventions

7. **Estructura de capas:** controladores delgados, *Form Request* para validación y autorización, capa de servicio para la lógica de negocio, Eloquent para el acceso a datos. Detalle en `02-DOCS/wiki/stack/laravel.md`.
8. **Eloquent:** `$fillable` como lista permitida explícita, *eager loading* para evitar *N+1*, *casts* modernos (`casts()`), sin `app/Http/Kernel.php` (Laravel 11/12).
9. **Rutas y APIs:** rutas web protegidas por CSRF; API routes stateless; la programación de tareas va en `routes/console.php` (Laravel 11/12).
10. **Comits:** *Conventional Commits* con prefijo *gitmoji* (`✨ feat(scope): subject`). Detalle en `02-DOCS/wiki/stack/commits.md`.

## 4. Branching & shipping

11. El trabajo se hace en rama desde `main`; el merge se hace por *Pull Request*. Los push directos a `main` no están permitidos.
12. **El autoría de Git es del humano.** No `Co-Authored-By` de IA, sin pie de “generated with”. Se vigila en la fase `ship`.

## 5. Security & privacy floor

13. Nunca se committan secretos. Las credenciales se cargan desde `01-TOOLS/<provider>/.env` (ignorada por Git). Detalle en `02-DOCS/wiki/stack/security.md`.
14. **Datos médicos:** se tratan con el mínimo necesario (principio de mínimo acatamiento). El cifrado y el control de acceso se configuran según el reglamento aplicable de salud; no se exponen datos sensibles en peticiones sin autenticación.
15. La autenticación y autorización se hacen con el paquete de Laravel/Auth (o el elegido) + *policies* y *gates*; no lógica de permisos dispersa en controladores.

## 6. UX / accessibility floor (if there is a UI)

14. Barra mínima de accesibilidad WCAG 2.2 AA: navegación por teclado, marcado semántico, enfoque visible. Detalle en `02-DOCS/wiki/stack/design.md`.

## 7. Performance budgets (where they matter)

15. Se aplican presupuestos de rendimiento (p. ej. *LCP* ≤ 2.5 s en la ruta principal; *TTFB* bajo el límite) cuando haya interfaz web. Detalle en `02-DOCS/wiki/stack/performance.md`.

## 8. Knowledge & decisions

16. Cada decisión arquitectónica relevante se anexa a `02-DOCS/wiki/sdd/decisions.md` (fecha, opciones, por qué). La constitución es el registro de orden superior de decisiones.

## Definition of Done (the merge bar `verify` runs against)

Un cambio se despacha solo cuando se cumplen TODO:

- [ ] Formateador + linter limpios (principio 4).
- [ ] *Type checker* pasa con cero errores (principio 5).
- [ ] Pruebas pasan; cobertura mínima en código cambiado (principio 6).
- [ ] Convenciones respetadas (principios 7–9).
- [ ] En rama, merge por *PR*, autoría humana (principios 10–11).
- [ ] Sin secreto committado; línea de seguridad cumplida (principios 13–14).
- [ ] Decisiones relevantes registradas (principio 16).

## Amendment log (append-only)

| Date | Version | Change | Why |
|------|---------|--------|-----|
| 2026-10-06 | v1.0.0 | Ratified initial constitution. | Project kickoff. |

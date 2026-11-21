---
type: spec
title: Spec — Actualización de dependencias vulnerables
description: Corregir los avisos actuales de Composer con versiones compatibles y verificables.
tags: [sdd, spec, security, dependencies]
timestamp: 2026-11-20T21:15:38Z
topic: sdd
slug: security-dependency-updates
status: implemented
branch: chore/security-dependency-updates
---

# Spec — Actualización de dependencias vulnerables

> Slug: `security-dependency-updates` · Status: implemented
> Inherits: [constitution](../constitution.md)

## Problem & why

`composer audit` reporta ocho avisos en cinco paquetes ya presentes en `composer.lock`: Laravel
Framework y componentes de Symfony (`http-foundation`, `http-kernel`, `mime` y `routing`). Las
versiones bloqueadas son Laravel 13.35.0 y Symfony 8.1.7–8.1.8. Además, Symfony 8.1 requiere PHP
8.4.1 o superior, aunque `composer.json` declara soporte desde PHP 8.3.

## Cost of not building it

La aplicación seguirá instalando componentes con avisos de seguridad conocidos. Mantener el lock
actual conserva ese riesgo para cualquier despliegue que use esas dependencias.

## The cheapest alternative

Ignorar o suprimir los avisos no cambia las versiones instaladas y deja sin resolver los hallazgos.
El manifiesto actual permite versiones corregidas dentro de la rama Laravel 13.

## Goals

- Eliminar los ocho avisos actuales de Composer con versiones corregidas compatibles con PHP 8.3
  y Laravel 13.
- Mantener la compatibilidad con el mínimo declarado de PHP 8.3 y probar también la versión 8.4
  usada actualmente por CI.
- Mantener los rangos de PHP y Laravel compatibles con Filament 5, sin instalar Filament en esta
  entrega.
- Mantener una instalación reproducible mediante `composer.lock`.
- Actualizar solo los paquetes afectados y las dependencias transitivas necesarias.
- Confirmar que la aplicación conserva su comportamiento mediante la suite existente.

## Non-goals / out of scope

- Añadir una auditoría automática a CI o cambiar la política permanente de actualización.
- Actualizar el runtime de PHP o elevar el mínimo del proyecto.
- Actualizar dependencias no relacionadas ni hacer una actualización mayor de Laravel o Symfony.
- Cambiar funcionalidades de la aplicación.
- Instalar Filament o afirmar que su integración está validada en este repositorio.
- Suprimir avisos con listas de ignorados o reducir la cobertura de `composer audit`.

## Users & context

Quienes mantienen o despliegan el sistema necesitan instalar las dependencias desde el lockfile sin
los avisos actuales. No se espera un cambio visible para las personas usuarias de la aplicación.

## What the user can do

- Instalar dependencias de forma repetible a partir de `composer.lock`.
- Ejecutar `composer audit` para comprobar los avisos del árbol de dependencias.

## What result they get

- Las ocho alertas actuales dejan de aparecer en `composer audit`.
- Composer puede instalar el conjunto actualizado sin cambiar el rango funcional de PHP o Laravel.
- Las pruebas y el formato actuales siguen pasando.

## What it must respect

- PHP `^8.3` y Laravel `^13.17`, según `composer.json`.
- Los rangos de Filament 5 para PHP y Laravel; Tailwind 4.1 queda permitido por el rango npm actual.
- Configurar la plataforma de Composer como PHP 8.3.0 para resolver dependencias compatibles con
  el mínimo declarado; el runtime real no queda limitado a esa versión.
- Usar una versión corregida de Symfony 7.4 LTS para que la instalación siga siendo compatible con
  PHP 8.3.
- No se cambia el comportamiento de la aplicación para ocultar o evitar los avisos.
- No se permite `--ignore` ni una excepción equivalente para hacer que la auditoría parezca limpia.
- El lockfile debe poder instalarse en un checkout limpio.
- Los cambios se mantienen en esta rama y se entregan por pull request.

## How it proves it

- `composer audit` termina sin avisos después de actualizar el lockfile.
- La CI ejecuta la suite con PHP 8.3 y 8.4, ambos usando sus últimos parches disponibles.
- `composer validate --no-check-publish --no-interaction` valida manifiesto y lockfile.
- `composer install --no-interaction --prefer-dist --no-progress` instala desde el lockfile.
- `php artisan test` y `vendor/bin/pint --test` pasan.

## Behaviour

- Main path: Composer resuelve con plataforma 8.3.0, actualiza Laravel y los componentes afectados
  a versiones corregidas compatibles, y registra el resultado en el lockfile.
- Edge cases: actualizaciones transitivas necesarias se permiten, pero el diff de paquetes debe
  revisarse para evitar cambios ajenos al objetivo.
- Error paths: si una versión corregida no resuelve con los rangos actuales, no se fuerzan
  dependencias ni se cambia a otra versión mayor sin una decisión explícita.

## Acceptance criteria

- Given el lockfile actualizado, When se ejecuta `composer audit`,
  Then no aparecen los ocho avisos actuales ni avisos nuevos introducidos por las versiones elegidas.
- Given PHP 8.3 como mínimo declarado, When Composer resuelve el lockfile,
  Then ninguna dependencia requiere una versión superior a PHP 8.3.
- Given la matriz de CI, When corre con PHP 8.3 y 8.4,
  Then la suite pasa en ambas versiones.
- Given una instalación limpia, When se ejecuta `composer install`,
  Then Composer instala el lockfile sin conflicto de restricciones.
- Given el conjunto actualizado, When se ejecuta `composer validate`,
  Then `composer.json` y `composer.lock` son consistentes.
- Given la aplicación con las nuevas dependencias, When se ejecutan PHPUnit y Pint,
  Then toda la suite pasa y el formato queda limpio.
- Given el diff de dependencias, When se revisa el lockfile,
  Then sus cambios se limitan a los paquetes afectados y a las actualizaciones transitivas
  necesarias para resolver los avisos.
- Given la actualización, When se comparan las funcionalidades,
  Then no hay cambios de comportamiento de la aplicación.

## Evidence

- El lockfile contiene Laravel Framework 13.35.0, Symfony HttpFoundation 8.1.8,
  HttpKernel 8.1.8, Mime 8.1.7 y Routing 8.1.8; Composer audit informó ocho avisos en cinco
  paquetes.
- Symfony 8.1.8 requiere PHP `>=8.4.1`; el lock actual no satisface el mínimo
  `^8.3` en `composer.json`.
- Con plataforma Composer 8.3.0 y `--minimal-changes`, un `composer update` dirigido en modo
  `--dry-run` resolvió Laravel 13.43.0 y Symfony 7.4.21–7.4.23, con 17 actualizaciones y un
  paquete añadido, sin cambiar archivos.
- El proyecto no declara Filament ni Livewire en `composer.json` o `composer.lock`.
  La documentación oficial de Filament 5 pide PHP 8.2+ y Laravel 11.28+; Laravel 13 pide PHP 8.3+.
- La resolución de Filament 5 no pudo completarse en el entorno local porque faltan
  `ext-intl` y `ext-zip`. No se ignoraron esos requisitos ni se instaló Filament.
- PHP 8.3 está en soporte de seguridad hasta el 31-12-2027, pero ya no
  recibe mejoras regulares. La última versión consultada es 8.3.37 (19-11-2026); 8.4.28 y 8.5.13
  también se publicaron como versiones de seguridad ese día. Los entornos deben usar el último
  parche de su rama soportada; migrar a una rama más nueva se evalúa por separado.
- Implementación: Composer fijó la plataforma en PHP 8.3.0 y bloqueó 17 actualizaciones más
  `symfony/polyfill-php83`; `composer audit` no reporta avisos. En PHP 8.3.37 y 8.4.15 pasaron
  instalación y requisitos del lockfile, las 49 pruebas (169 aserciones) y Pint. La ejecución del
  nuevo job hospedado en GitHub Actions queda pendiente de enviar la rama.

Fuentes: [versiones soportadas de PHP](https://www.php.net/supported-versions.php),
  [PHP 8.3.37](https://www.php.net/releases/index.php?json&version=8.3),
  [requisitos de Filament 5](https://filamentphp.com/docs/5.x/introduction/installation) y
  [requisitos de Laravel 13](https://laravel.com/docs/13.x/deployment#server-requirements).

## Decisions

- Mantener PHP `^8.3` y Laravel 13; añadir `config.platform.php: 8.3.0` para evitar locks
  incompatibles con el mínimo.
- La CI probará los mínimos 8.3 y la versión actual 8.4 tras actualizar el lockfile.
- Mantener el escáner CI permanente fuera de esta entrega.

## Points to clarify

No quedan decisiones pendientes. El diff real coincide con el dry-run (17 actualizaciones, un
paquete añadido, cero eliminaciones); `composer audit` no reporta avisos y la suite pasó localmente
en PHP 8.3.37 y 8.4.15. La ejecución del job PHP 8.3 en GitHub Actions se confirmará al enviar la
rama.

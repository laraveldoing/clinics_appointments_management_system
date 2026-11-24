---
type: spec
title: Spec — Puerta de análisis estático PHPStan
description: Añadir análisis estático PHP reproducible en local y como requisito de CI.
tags: [sdd, spec, phpstan, quality]
timestamp: 2026-11-20T20:30:45Z
topic: sdd
slug: phpstan-quality-gate
status: implemented
branch: chore/phpstan-quality-gate
---

# Spec — Puerta de análisis estático PHPStan

> Slug: `phpstan-quality-gate` · Status: implemented
> Inherits: [constitution](../constitution.md)

## Problem & why

La constitución exige que PHPStan no reporte errores antes del merge, pero el proyecto no tiene
un comando configurado ni un paso de análisis estático en CI. El flujo actual solo ejecuta PHPUnit
y Pint, así que los cambios pueden llegar a `main` sin cumplir esa regla.

## Cost of not building it

El requisito de calidad no se puede comprobar de forma repetible. Los errores de tipos y de uso de
APIs se detectan tarde o dependen de la revisión manual.

## The cheapest alternative

Pedir a cada persona que revise los tipos manualmente no produce una señal reproducible y no cumple
la puerta definida en la constitución.

## Objetivos

- Proveer un comando local documentado y reproducible para ejecutar PHPStan.
- Analizar el código PHP propio de la aplicación y las pruebas, sin analizar dependencias ni
  archivos generados.
- Ejecutar el mismo análisis en cada pull request y en cada push a `main`.
- Exigir cero errores para que la comprobación de calidad de CI pase.
- Corregir los errores existentes que impidan cumplir esta puerta; no ocultarlos con un baseline.

## Non-goals / out of scope

- Cambios funcionales o refactorizaciones no necesarios para corregir errores que revele el análisis.
- Análisis del código de `vendor/` o de archivos generados y cachés.
- Revisión de plantillas Blade, cobertura de código o auditoría de dependencias.
- Cambiar la constitución o rebajar su requisito de cero errores.

## Usuarios y contexto

Quienes desarrollan necesitan ejecutar el análisis antes de enviar cambios. Quienes revisan
necesitan una señal automática y visible en el pull request.

## What the user can do

- Ejecutar localmente el comando Composer documentado antes de enviar cambios.
- Ver el resultado del análisis en el job `quality` de cada pull request o push a `main`.

## What result they get

- Un comando Composer ejecuta el análisis con la configuración del proyecto.
- El job `quality` muestra el paso de análisis estático.
- Un error de PHPStan hace fallar el job, igual que una prueba fallida o un problema de formato.

## What it must respect

- Revisar los archivos PHP propios en `app/`, `bootstrap/`, `config/`, `database/`, `public/`,
  `routes/`, `tests/` y el comando `artisan`.
- Excluir dependencias y artefactos generados, sin ignorar errores del código fuente.
- Usar la versión de PHP y las dependencias compatibles con `composer.json` y `composer.lock`.
- Mantener la constitución vigente: cero errores antes del merge.
- No se permite un baseline ni una supresión global para hacer pasar el análisis. Cualquier
  excepción futura requiere una decisión explícita y documentada, sin contradecir la constitución.
- Las plantillas Blade no se consideran archivos PHP analizables por esta puerta; su validación
  permanece en las pruebas existentes.

## How it proves it

- El comando Composer de análisis termina con código cero en el código actual.
- El mismo comando se ejecuta en CI en pull requests y pushes a `main`.
- Al introducir un error de análisis en una comprobación local aislada, el comando termina con
  código distinto de cero.
- PHPUnit y Pint continúan ejecutándose sin cambios de comportamiento de la aplicación.

## Behaviour

- Main path: la persona ejecuta el comando Composer localmente; CI ejecuta ese mismo comando en
  cada pull request y push a `main`.
- Edge cases: la instalación se basa en `composer.lock`; tanto errores en código de aplicación
  como en pruebas deben fallar el análisis.
- Error paths: si PHPStan encuentra un error, el proceso termina con código distinto de cero y
  señala el archivo y el problema; no se omite ni se convierte en advertencia.

## Criterios de aceptación

- Given una instalación basada en `composer.lock`, When se ejecuta el comando documentado,
  Then PHPStan analiza las rutas de código y pruebas definidas y reporta cero errores.
- Given un pull request o un push a `main`, When se ejecuta el job `quality`,
  Then CI ejecuta el mismo comando de análisis y publica su resultado.
- Given un error estático en el código analizado, When se ejecuta el comando,
  Then falla con una salida que identifica el archivo y el problema.
- Given el análisis encuentra errores existentes, When se integra la puerta,
  Then se corrigen esos errores en vez de ocultarlos mediante un baseline o exclusiones amplias.
- Given pasan el análisis, PHPUnit y Pint, When CI termina,
  Then el job `quality` queda verde.
- Given el cambio implementa esta spec, When se revisa el diff,
  Then no cambia el comportamiento funcional de la aplicación.

## Points to clarify

- **Decidido:** se analiza todo el código PHP propio y las pruebas, no solo `app/`.
- **Decidido:** se corrigen los errores existentes y no se introduce un baseline.
- **Decidido en el plan aprobado:** usar Larastan con PHPStan en nivel 5; Composer resolverá la
  versión compatible con las restricciones del proyecto.

---
type: spec
title: Spec — Integración continua
description: WHAT and WHY for running the test suite and the formatter on every pull request.
tags: [sdd, spec]
timestamp: 2026-11-10T09:00:00Z
topic: sdd
slug: ci
status: draft
branch: chore/ci
---

# Spec — Integración continua

> Slug: `ci` · Status: draft · Created: 2026-11-10
> Inherits: [constitution](../constitution.md)

## Problem & why

Hoy la única barrera de calidad es que alguien recuerde ejecutar las pruebas y Pint en su máquina.
El PR #3 y el #4 se revisaron a mano; nada impide que un cambio futuro llegue a `main` con la suite
en rojo.

## Cost of not building it

Cada PR depende de disciplina manual. Un cambio roto en `main` se descubre tarde, cuando otra
persona intenta construir encima, y el coste de arreglarlo crece con cada commit intermedio.

## The cheapest alternative

Una lista de comprobación en el PR. Es lo que ya existe de facto y ya falló: el baseline del repo
tenía 23 archivos sin formatear y nadie lo notó hasta ejecutar Pint.

## Goals

- Cada pull request ejecuta automáticamente la suite de pruebas.
- Cada pull request verifica que el código cumple el formato del proyecto.
- El resultado queda visible en el PR como estado de GitHub.

## Non-goals / out of scope

- Despliegue, publicación de artefactos o entornos de staging.
- Cobertura mínima medida o reportada.
- Análisis estático (PHPStan) y auditoría de dependencias.
- Compilación de assets con Vite.
- Pruebas contra PostgreSQL: la suite usa SQLite en memoria.

## Users & context

Quien abre un pull request necesita saber, sin instalar nada, si su cambio rompe algo. Quien revisa
necesita una señal objetiva antes de aprobar.

## What the user can do

- Cualquier persona que abra un pull request obtiene la ejecución automática de pruebas y formato.
- Cualquier persona que haga push a `main` obtiene la misma ejecución.

## What result they get

- Un estado `quality` en el PR: verde si pruebas y formato pasan, rojo si alguno falla.
- En caso de fallo, el log muestra el comando que falló y su salida.
- Referencia acordada: el flujo local documentado en la constitución (`php artisan test` y
  `vendor/bin/pint --test`) reproducido en un runner de GitHub.

## What it must respect

- La constitución: pruebas y formato son la puerta de merge.
- El `phpunit.xml` existente: la suite corre con SQLite en memoria, sin servicios externos.
- El repositorio no guarda secretos; el workflow solo necesita permisos de lectura.
- PHP 8.4, que es la versión del entorno de desarrollo.

## How it proves it

- Validación de sintaxis YAML del workflow.
- Ejecución local de los mismos comandos que ejecuta el workflow: `composer install`,
  `php artisan test` y `vendor/bin/pint --test`.
- Evidencia: la salida de ambas comprobaciones y el estado del PR una vez abierto.

## Behaviour

- Main path: un PR dispara el workflow, instala dependencias, prepara `.env`, corre pruebas y Pint.
- Edge cases: el primer push a una rama nueva; pushes sucesivos al mismo PR (se cancela la ejecución
  anterior); un PR desde un fork (sin secretos, solo lectura).
- Error paths: si una prueba falla, el job termina en rojo y no ejecuta el paso de formato; si el
  formato falla, el job termina en rojo con el archivo señalado.

## Acceptance criteria

- Given un pull request abierto, When GitHub ejecuta el workflow, Then el job `quality` aparece en el
  PR con estado verde o rojo.
- Given el código actual, When se ejecutan los pasos del workflow en local, Then pruebas y Pint pasan.
- Given un archivo PHP mal formateado, When se ejecuta el paso de formato, Then el job falla.
- Given una prueba roja, When se ejecuta el paso de pruebas, Then el job falla.
- Given un fork sin secretos, When ejecuta el workflow, Then no requiere ningún secreto.

## Points to clarify

- **suposición tomada** — Se usa SQLite en memoria, como ya hace `phpunit.xml`, y no PostgreSQL.
  *Base:* la suite no necesita el motor real. *Riesgo:* diferencias de comportamiento de Postgres no
  se detectarían en CI.
- **suposición tomada** — No se compilan assets en CI. *Base:* la suite no los necesita y no hay
  `package-lock.json`. *Riesgo:* un fallo de Vite no se detectaría hasta el despliegue.
- **decisión diferida** — PHPStan, cobertura y auditoría de dependencias quedan para otro ciclo.

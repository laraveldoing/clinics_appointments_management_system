---
type: spec
title: Spec — Idioma español y errores accesibles en formularios
description: Contrato de comportamiento para el idioma predeterminado y los errores de validación.
tags: [sdd, spec, accessibility]
timestamp: 2026-11-26T05:15:19Z
topic: sdd
slug: spanish-language-accessible-form-errors
status: clarified
branch: develop_jjosedesarrollo_collaborating
---

# Spec — Idioma español y errores accesibles en formularios

> Slug: `spanish-language-accessible-form-errors` · Status: clarified · Created: 2026-11-26
> Inherits: [constitution](../constitution.md)

## Problem & why

La interfaz está escrita en español, pero el idioma predeterminado del documento puede declararse
como inglés. Además, tras una validación fallida, el mensaje aparece visualmente junto al campo sin
que la relación y el foco sean fiables para usuarios de teclado y lectores de pantalla.

## Cost of not building it

Los lectores de pantalla pueden pronunciar el contenido con reglas de inglés y las personas pueden
tener que buscar manualmente qué campo falló. Esto aumenta fricción y puede impedir completar
acciones de agenda o iniciar sesión.

## The cheapest alternative

Cambiar solo el idioma declarado resolvería la pronunciación, pero dejaría los errores sin una
relación clara con los campos y sin una ruta de foco. Añadir solo texto visible tampoco comunica de
forma fiable qué control está en error.

## Goals

- El idioma predeterminado declarado por las páginas coincide con el contenido principal en español.
- Cada error de validación de un campo se identifica junto al campo y queda disponible al navegar
  con teclado o tecnología de asistencia.
- Tras un envío fallido, la navegación llega al primer campo inválido o al resumen si el error no
  corresponde a un campo.

## Non-goals / out of scope

- Cambiar autenticación, permisos, reglas de validación, datos guardados o flujos de citas.
- Traducir textos no relacionados con los errores de estos formularios ni cambiar el idioma de
  respaldo.
- Resolver otros hallazgos de la auditoría, como landmarks, tablas, contexto de enlaces y diseño
  responsive.

## Users & context

El personal de administración y recepción inicia sesión y gestiona citas. Los doctores mantienen su
acceso actual de solo lectura. Necesitan entender y corregir errores sin perder el contexto del
formulario, incluso al usar teclado o lector de pantalla.

## What the user can do

- Cualquier visitante puede abrir el acceso, y el personal autorizado puede usar las páginas de
  gestión de citas que ya tiene permitidas.
- Enviar un formulario con errores y llegar al primer campo inválido para corregirlo.
- Recibir el mensaje correspondiente al enfocar ese campo; si el error es general, llegar al resumen.

## What result they get

- Las páginas declaran español como idioma principal de forma predeterminada.
- Cada mensaje visible de error de campo se identifica como descripción de su control, y el estado
  inválido solo aparece cuando ese error existe.
- Una respuesta de validación fallida mueve el foco al primer campo inválido; si no hay campo
  asociado, mueve el foco al resumen.
- **Referencia aprobada el 2026-11-26:** el contrato pedido por el usuario — idioma predeterminado
  español y errores perceptibles y alcanzables — junto con las guías Modern Web aprobadas para el
  proyecto.

## What it must respect

- La constitución exige accesibilidad WCAG 2.2 AA, navegación por teclado y foco visible.
- El contrato de [login y listado](./appointments-list.md) mantiene errores de credenciales genéricos
  y no revela si existe una cuenta.
- Las reglas de [autorización por rol](./role-based-access.md) y [ciclo de vida de citas](./appointment-lifecycle.md)
  permanecen sin cambios.
- No se añade ni expone información clínica en los errores.
- El idioma de respaldo permanece sin cambios; los textos no traducidos fuera del alcance conservan
  el comportamiento actual.

## How it proves it

- Pruebas de feature comprueban el idioma renderizado y la relación de los errores con controles en
  login, creación, reprogramación y cancelación.
- Una prueba de navegador comprueba que un envío rechazado enfoca el primer campo inválido.
- Se conservan los resultados de las pruebas, el formato y la revisión de espacios en blanco.

## Behaviour

- Main path: una página se presenta con el idioma principal español.
- Error path: al fallar la validación, se anuncia el error asociado y el foco queda en el primer
  campo inválido.
- Error path: si el error no pertenece a un campo, el foco queda en el resumen.
- Edge case: una página sin errores no marca controles como inválidos ni cambia el foco inicial.
- Edge case: las respuestas de login conservan el mensaje genérico y no revelan la existencia de
  cuentas.

## Acceptance criteria

- Given una página abierta con la configuración predeterminada, When se renderiza, Then declara
  español como idioma principal.
- Given un error de validación de campo, When se vuelve a mostrar el formulario, Then el control
  identifica su estado inválido y su mensaje asociado.
- Given varios errores de campo tras un envío, When se vuelve a mostrar el formulario, Then el foco
  llega al primer campo inválido en orden de lectura.
- Given un error general sin campo asociado, When se vuelve a mostrar el formulario, Then el foco
  llega al resumen de errores.
- Given una página sin errores, When se renderiza, Then no añade estados inválidos ni altera el
  comportamiento habitual del foco.
- Given credenciales incorrectas, When se muestra el error, Then el mensaje sigue siendo genérico y
  el correo no se revela ni confirma si existe una cuenta.

## Points to clarify

- **suposición tomada** — El idioma de respaldo permanece sin cambios. *Base:* el usuario confirmó
  que el alcance se limita al idioma predeterminado y a los errores de estos formularios. *Riesgo:*
  otros mensajes de respaldo pueden seguir en inglés y requerir una decisión de traducción aparte.
  El usuario validó esta suposición el 2026-11-26.
- **decisión diferida** — Los otros hallazgos de accesibilidad detectados en la auditoría quedan
  fuera de esta entrega.

## Clarifications

- 2026-11-26 — Q1: Se mantiene el idioma de respaldo actual y no se traducen mensajes ajenos a estos
  formularios. *Motivo:* se conserva el alcance aprobado y no se cambia el comportamiento de otras
  traducciones.

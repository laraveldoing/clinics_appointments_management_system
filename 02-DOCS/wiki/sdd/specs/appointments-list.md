---
type: spec
title: Spec — Login mínimo y listado de citas
description: WHAT and WHY for the first vertical slice — sign in and see the appointments of your own clinic.
tags: [sdd, spec]
timestamp: 2026-11-08T06:40:00Z
topic: sdd
slug: appointments-list
status: draft
branch: feature/appointments-list
---

# Spec — Login mínimo y listado de citas

> Slug: `appointments-list` · Status: draft · Created: 2026-11-08
> Inherits: [constitution](../constitution.md)

## Problem & why

El sistema ya guarda clínicas, pacientes y citas, pero nadie puede usarlo: no hay forma de entrar
ni de ver los datos. Sin esta primera pantalla, todo el esquema es invisible y no se puede validar
con personas reales.

## Cost of not building it

El proyecto sigue siendo una base de datos sin producto. Cada semana sin interfaz retrasa la
validación del modelo de citas y mantiene el riesgo de haber modelado algo que no encaja con el
trabajo real de una clínica.

## The cheapest alternative

Revisar los datos con `php artisan tinker` o con un cliente SQL. Sirve para desarrollo, pero no para
que un recepcionista o un doctor consulte su agenda, y expone datos médicos sin control de acceso.

## Goals

- Un usuario de clínica puede identificarse con su correo y contraseña.
- Ese usuario ve, en una sola pantalla, las citas de su clínica ordenadas por fecha.
- Ningún dato de otra clínica ni de un visitante anónimo es visible.

## Non-goals / out of scope

- Registro público, recuperación de contraseña y verificación de correo.
- Permisos diferenciados por rol (`admin`, `doctor`, `receptionist`).
- Crear, editar, cancelar o reprogramar citas.
- API JSON, notificaciones, recordatorios y facturación.
- Búsqueda, filtros y ordenación configurable.

## Users & context

Personal de una clínica (administración y doctores) que necesita saber qué citas hay y con quién.
Trabaja en escritorio, dentro del horario de consulta, y espera ver la agenda sin formación previa.

## What the user can do

- Cualquier visitante puede abrir la pantalla de acceso e introducir correo y contraseña.
- Un usuario con credenciales válidas entra y queda identificado durante su sesión.
- Un usuario identificado puede cerrar sesión.
- Un usuario identificado puede abrir el listado de citas de su clínica.
- Un visitante sin sesión que intenta abrir el listado es enviado a la pantalla de acceso.

## What result they get

- Acceso: si las credenciales son válidas, llega al listado de citas. Si no, vuelve al formulario
  con un mensaje de error y el correo conservado.
- Listado: una tabla con fecha y hora, nombre del paciente, nombre del doctor, estado y motivo de
  cada cita, en orden cronológico ascendente y paginada.
- Listado vacío: un mensaje que dice que no hay citas registradas en su clínica.
- Referencia aprobada: la tabla descrita arriba es el contrato observable; no hay diseño gráfico
  aprobado todavía (ver *Points to clarify*).

## What it must respect

- La constitución: los datos médicos no se exponen sin autenticación.
- El aislamiento por clínica ya modelado: toda consulta de negocio se limita a la clínica del
  usuario.
- El esquema existente: no se renombran tablas ni columnas en esta entrega.
- El idioma de la interfaz: español, coherente con el resto del producto.

## How it proves it

- Pruebas automatizadas de feature que cubren: acceso válido, acceso inválido, cierre de sesión,
  redirección de invitados, contenido del listado, orden cronológico y aislamiento entre clínicas.
- Evidencia: la salida de `php artisan test` (número de pruebas y aserciones) y el resultado de
  `vendor/bin/pint --test` sobre los archivos tocados.
- Revisión adversarial de corrección, seguridad y pruebas antes de abrir el PR.

## Behaviour

- Main path: el usuario introduce credenciales válidas, entra al listado y ve sus citas.
- Edge cases: clínica sin citas; muchas citas (paginación); cita sin motivo; nombre de paciente
  compuesto.
- Error paths: credenciales inválidas muestran un mensaje genérico, sin revelar si el correo existe;
  un invitado es redirigido al acceso; un usuario de otra clínica nunca ve esas citas.

## Acceptance criteria

- Given un visitante en `/login`, When envía correo y contraseña válidos, Then queda autenticado y
  llega al listado de citas.
- Given un visitante en `/login`, When envía una contraseña incorrecta, Then sigue sin sesión y ve un
  error genérico.
- Given un visitante sin sesión, When abre `/appointments`, Then es redirigido a `/login`.
- Given un usuario con sesión, When abre `/appointments`, Then ve solo las citas de su clínica.
- Given dos citas en fechas distintas, When abre el listado, Then la más antigua aparece primero.
- Given una clínica sin citas, When su usuario abre el listado, Then ve el mensaje de lista vacía.
- Given un usuario con sesión, When pulsa cerrar sesión, Then vuelve al acceso y pierde la sesión.

## Points to clarify

- **suposición tomada** — El acceso usa correo y contraseña, sin registro público. *Base:* el modelo
  `User` ya existe y los seeders crean usuarios con contraseña. *Riesgo:* si la clínica necesita
  alta de usuarios por invitación, habrá que añadir ese flujo después.
- **suposición tomada** — La tabla descrita es la referencia visual suficiente para esta entrega.
  *Base:* no hay diseño aprobado y la prioridad es validar el flujo. *Riesgo:* un rediseño visual
  posterior puede cambiar la pantalla sin cambiar el contrato.
- **decisión diferida** — Permisos por rol y acciones sobre citas quedan fuera de este ciclo.
- **decisión diferida** — Recuperación de contraseña y verificación de correo quedan fuera.

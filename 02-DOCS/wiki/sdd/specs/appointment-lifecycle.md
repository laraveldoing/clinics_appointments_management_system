---
type: spec
title: Reprogramar y cancelar citas
description: Contrato de comportamiento para reprogramar o cancelar citas activas.
tags: [sdd, spec, appointments]
timestamp: 2026-11-20T19:47:30Z
topic: sdd
slug: appointment-lifecycle
status: approved
branch: feature/reschedule-cancel-appointments
---

# Spec — Reprogramar y cancelar citas

> Slug: `appointment-lifecycle` · Status: approved · Created: 2026-11-20
> Inherits: [constitution](../constitution.md)

## Problem & why

El personal puede crear citas, pero no puede corregir desde la aplicación una fecha u hora que
cambió ni cancelar una cita. Esto deja la agenda desactualizada y puede impedir reservar un horario
que ya no debería estar ocupado.

## Cost of not building it

Una cita que cambió o se canceló puede seguir apareciendo como activa. Como las citas activas
reservan el horario del doctor y del paciente, una cita obsoleta también puede bloquear reservas
válidas.

## The cheapest alternative

Crear otra cita en el horario nuevo es la alternativa más pequeña. No basta: la cita anterior sigue
activa, puede aparecer como vigente y puede bloquear el mismo horario.

## Goals

- Admin y receptionist pueden reprogramar una cita sin cambiar su paciente, doctor ni motivo.
- Admin y receptionist pueden cancelar una cita y liberar su horario sin borrar el registro.
- Un cambio de horario conserva las reglas actuales de disponibilidad para el doctor y el paciente.
- Los doctores mantienen acceso de solo lectura a las citas que tienen asignadas.

## Non-goals / out of scope

- Cambiar el paciente, el doctor o el motivo de atención de una cita existente.
- Reabrir citas canceladas o modificar citas completadas o marcadas como `no_show`.
- Cambiar el flujo de atención clínica o registrar que una cita se completó o no hubo asistencia.
- Notificar a pacientes o doctores, enviar recordatorios o gestionar citas recurrentes.
- Configurar zonas horarias o cambiar el comportamiento horario existente.
- Eliminar citas o crear un historial completo de cada cambio y de quién lo hizo.

## Users & context

Admin y receptionist administran la agenda de su clínica. Un doctor consulta únicamente las citas
que tiene asignadas. La aplicación ya limita las citas por clínica y por rol.

## What the user can do

- Admin y receptionist pueden reprogramar o cancelar citas `scheduled` o `confirmed` de su clínica.
- Reprogramar solo cambia fecha, hora y duración. El paciente, el doctor, el motivo y el estado se
  conservan.
- La nueva fecha debe ser futura. Se mantienen las duraciones permitidas: 15, 30, 45, 60 o 90
  minutos.
- Cancelar cambia el estado a `cancelled`, conserva el registro y acepta una nota opcional.
- Se puede cancelar una cita activa aunque su hora ya haya pasado. No se puede reprogramar una cita
  a una hora pasada.
- No se permite modificar citas `cancelled`, `completed` o `no_show`.
- Los doctores no pueden reprogramar ni cancelar, incluso si envían una solicitud directa.

## What result they get

La lista muestra acciones de reprogramación y cancelación solo para admin y receptionist y solo en
citas activas. Al reprogramar correctamente, la lista presenta la nueva fecha, hora y duración, y
confirma el cambio. Al cancelar, presenta el estado «Cancelada» y, si se indicó una nota, la muestra
junto a la cita a las mismas personas que pueden verla. Cada doctor solo ve la nota de sus citas
asignadas.

Si la nueva hora se solapa con otra cita activa del mismo doctor o paciente, la aplicación explica
el conflicto y conserva sin cambios la cita original. Una cita cancelada deja de reservar el
horario.

Antes de cancelar, la persona confirma la acción. Reprogramar una cita a su mismo horario no se
considera un conflicto consigo misma.
Abandonar la confirmación de cancelación no modifica la cita ni guarda la nota.

## What it must respect

- La matriz aprobada en [autorización por rol](./role-based-access.md): admin y receptionist
  gestionan la agenda de su clínica; doctor solo consulta sus citas asignadas.
- El aislamiento entre clínicas en [autorización por rol](./role-based-access.md).
- Las reglas de [prevención de solapamientos](./appointment-overlaps.md): solo `scheduled` y
  `confirmed` reservan horario; los intervalos contiguos se permiten; los conflictos consideran
  tanto al doctor como al paciente.
- La clínica, el doctor y el paciente existentes en la cita no pueden alterarse mediante datos
  manipulados por el cliente.
- La disponibilidad se mantiene incluso si se solicitan cambios de horario concurrentes para el
  mismo doctor o paciente.
- La privacidad de datos médicos y el acceso autenticado exigidos por la constitución.

## How it proves it

Las pruebas de feature cubren permisos por rol y clínica, reprogramación sin cambiar los datos
conservados, conflictos por doctor y paciente, límites de fecha/duración, cancelación con y sin nota,
y la liberación del horario. Playwright verifica el flujo visible de reprogramación y cancelación;
una prueba de integración en PostgreSQL verifica la exclusión mutua al reprogramar. La evidencia de
cierre conserva la salida de `php artisan test`, `composer analyse`, `vendor/bin/pint --test` y los
checks de CI.

## Behaviour

- Main path — un usuario autorizado reprograma una cita activa a una hora futura disponible; la
  agenda muestra el nuevo horario y mantiene los datos que no forman parte de la reprogramación.
- Main path — un usuario autorizado cancela una cita activa; la cita permanece visible como
  cancelada y su horario queda libre.
- Edge case — una cita activa con fecha pasada se puede cancelar, pero no reprogramar a una hora
  pasada.
- Edge case — abandonar la confirmación de cancelación conserva la cita y cualquier nota previa.
- Edge case — conservar el horario actual de una cita no genera un conflicto consigo misma.
- Edge case — una duración distinta de 15, 30, 45, 60 o 90 minutos se rechaza.
- Error path — si la nueva hora entra en conflicto con otra cita activa del mismo doctor o paciente,
  se informa el conflicto y la cita original queda intacta.
- Error path — usuarios sin permiso o citas de otra clínica no se pueden modificar.
- Error path — citas canceladas, completadas o marcadas como `no_show` no se pueden modificar.

## Acceptance criteria

- Given una cita activa de la clínica, When admin o receptionist la reprograma a una hora futura
  disponible, Then cambian solo fecha, hora y duración.
- Given una cita con paciente, doctor y motivo establecidos, When se reprograma, Then esos datos y
  el estado original se conservan.
- Given un conflicto activo por doctor o paciente, When se intenta reprogramar, Then se muestra un
  error y la cita original no cambia.
- Given una cita reprogramada a su horario actual, When se valida el cambio, Then no se rechaza por
  conflicto consigo misma.
- Given dos reprogramaciones simultáneas que reservan intervalos solapados para el mismo doctor o
  paciente, When se procesan, Then como máximo una se acepta y la otra conserva su cita original.
- Given una cita `scheduled` o `confirmed`, When admin o receptionist la cancela con o sin nota,
  Then permanece registrada como `cancelled` y deja de reservar su horario.
- Given una cita activa, When admin o receptionist abandona la confirmación de cancelación,
  Then la cita y su nota permanecen sin cambios.
- Given una cita activa con hora pasada, When admin o receptionist la cancela, Then la cancelación
  se acepta; When intenta reprogramarla, Then se rechaza.
- Given una cita `cancelled`, `completed` o `no_show`, When se intenta reprogramar o cancelar,
  Then la solicitud se rechaza.
- Given un doctor, When intenta reprogramar o cancelar una cita, Then recibe una respuesta de
  acceso denegado y no cambia ningún dato.
- Given un usuario de otra clínica, When intenta reprogramar o cancelar una cita, Then no obtiene
  acceso a sus datos y no cambia ningún dato.
- Given una nota de cancelación, When la cita aparece en la agenda, Then la nota es visible junto
  al estado cancelado para quienes ya pueden ver esa cita; sin nota, no se muestra contenido vacío.

## Points to clarify

- **decisión diferida** — registrar quién hizo cada cambio y conservar un historial completo de
  reprogramaciones; queda fuera de esta entrega.
- **decisión diferida** — permitir revertir una cancelación; para esta entrega una cancelación es
  terminal y una nueva reserva se registra como otra cita.

## Clarifications

- 2026-11-20 — La reprogramación solo cambia fecha, hora y duración; conserva paciente, doctor,
  motivo y estado. *Motivo:* limitar la feature a reprogramar, sin ampliar la edición de la cita.
- 2026-11-20 — La nota de cancelación es opcional.
  *Motivo:* permite registrar contexto sin hacer más lento el flujo cuando no hace falta.
- 2026-11-20 — Solo se gestionan citas `scheduled` o `confirmed`; se pueden cancelar incluso si su
  hora ya pasó, pero toda nueva hora de reprogramación debe ser futura.
  *Motivo:* las citas activas reservan horario y no se debe crear una reserva en el pasado.
- 2026-11-20 — La nota de cancelación es visible para las mismas personas autorizadas a ver la cita.
  *Motivo:* se reutiliza el alcance de acceso ya aprobado; el doctor solo ve las citas asignadas.
- 2026-11-20 — Antes de cancelar, el usuario confirma la acción; no hay una acción de deshacer.
  *Motivo:* evitar cancelaciones accidentales de citas que reservan tiempo.
- 2026-11-23 — Abandonar la confirmación no modifica la cita ni guarda la nota.
  *Motivo:* confirmar es el único paso que ejecuta la cancelación.
- 2026-11-20 — La nota de cancelación tiene un máximo de 500 caracteres.
  *Motivo:* mantenerla como una explicación breve y reducir los datos almacenados.
- 2026-11-20 — Cancelar cambia el estado a `cancelled` y conserva el registro.
  *Motivo:* se reutiliza el estado existente, que libera el horario sin perder el contexto.

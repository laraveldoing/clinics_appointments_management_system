---
type: decisions
title: Decisiones — Clinics Appointments Management System
description: Registro append-only de decisiones arquitectónicas y de producto.
tags: [decisions]
timestamp: 2026-11-08T06:50:00Z
topic: sdd
---

# Decisiones

## 2026-11-08 — Primera vertical: autenticación mínima + listado de citas

- **Contexto:** el esquema existía sin interfaz; la constitución prohíbe exponer datos médicos sin
  autenticación.
- **Decisión:** incluir login por sesión en la primera entrega, no solo el listado.
- **Alternativa descartada:** listado público filtrado por parámetro de clínica (inaceptable por
  privacidad).
- **Consecuencia:** `rsc risk` clasifica el cambio como tier 2 y exige spec + panel de revisión.
- **Spec:** `specs/appointments-list.md`.

## 2026-11-08 — Límite de intentos de login

- **Contexto:** el panel de seguridad confirmó fuerza bruta ilimitada.
- **Decisión:** `throttle:5,1` en `POST /login`.
- **Consecuencia:** seis intentos por minuto desde la misma IP reciben `429`.

## 2026-11-08 — Enumeración de usuarios por tiempo (aceptada)

- **Contexto:** el panel midió ~31 ms de diferencia entre correo existente e inexistente por el
  coste de bcrypt.
- **Decisión:** no mitigar en esta entrega; el mensaje ya es genérico.
- **Riesgo residual:** un atacante con muchas muestras puede inferir qué correos existen. Se
  revisará si se añade un hash señuelo o un límite por cuenta.

## 2026-11-20 — Evitar solapamientos al crear citas

- **Contexto:** la creación de citas no impedía reservar el mismo intervalo para un médico o
  paciente que ya tenía una cita.
- **Decisión:** las citas `scheduled` y `confirmed` reservan tiempo. Se rechazan intervalos
  solapados para el mismo médico o paciente; los intervalos contiguos se permiten.
- **Alternativas descartadas:** validar solo al médico permitiría citas simultáneas del mismo
  paciente; bloquear todos los estados impediría reutilizar horarios cancelados o ya completados.
- **Concurrencia:** bloquear los registros del paciente y del médico dentro de una transacción
  serializa nuevas reservas que comparten alguno de esos recursos en PostgreSQL.
- **Spec:** `specs/appointment-overlaps.md`.

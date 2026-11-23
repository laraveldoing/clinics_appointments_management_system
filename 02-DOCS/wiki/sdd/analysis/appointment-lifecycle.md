---
type: analysis
title: Análisis pre-implementación — appointment-lifecycle
description: Consistencia entre la constitución, la spec y el plan de ciclo de vida de citas.
timestamp: 2026-11-20T19:47:30Z
topic: sdd
slug: appointment-lifecycle
status: pass
---

# Análisis pre-implementación — Reprogramar y cancelar citas

> Spec: [../specs/appointment-lifecycle.md](../specs/appointment-lifecycle.md) ·
> Plan y tareas: [../plans/appointment-lifecycle.md](../plans/appointment-lifecycle.md)

## Veredicto

**GATE: PASS — 0 open blockers.** PHPStan se resuelve mediante un PR prerequisite separado, la spec
define el resultado de abandonar la confirmación y la implementación verificada cubre la
concurrencia en PostgreSQL.

## Coverage map

| REQ-ID | Requisito de la spec | Plan | Tarea(s) | Estado |
| --- | --- | --- | --- | --- |
| AC1 | Reprogramar fecha, hora y duración | §3–6 | T001, T003, T005 | covered |
| AC2 | Conservar paciente, doctor, motivo y estado | §3–5 | T001, T003 | covered |
| AC3 | Rechazar conflictos sin cambiar la cita original | §3–5 | T001, T003 | covered |
| AC4 | No colisionar consigo misma al conservar el horario | §3, §5 | T001, T003 | covered |
| AC5 | Serializar reprogramaciones concurrentes | §2, §5, §7 | T001, T003, T006 | covered; ver riesgo PostgreSQL |
| AC6 | Cancelar, conservar registro, nota opcional, liberar horario y no mutar al abandonar confirmación | §3–6 | T002, T004, T005 | covered |
| AC7 | Cancelar citas activas pasadas y rechazar reprogramación al pasado | §3–5 | T001–T004 | covered |
| AC8 | Rechazar citas en estados terminales | §3–5 | T002, T004 | covered |
| AC9 | Doctores sin permiso de modificación | §2–5 | T002–T005 | covered |
| AC10 | Impedir acceso entre clínicas | §2–5 | T002–T004 | covered |
| AC11 | Respetar el alcance de visibilidad de la nota | §2–5 | T002, T004, T005 | covered |

No se encontró trabajo planificado sin relación con la spec. Reutilizar la regla de disponibilidad
en la creación y reprogramación evita duplicación y mantiene el criterio aprobado.

## Findings

| # | Severity | Type | Artifact A | Artifact B | Finding | Resolution |
| --- | --- | --- | --- | --- | --- | --- |
| F1 | CRITICAL | Constitution compliance | [constitution.md](../constitution.md), §2 y Definition of Done | [plan](../plans/appointment-lifecycle.md), §5 y T007; `composer.json`; `.github/workflows/ci.yml` | La constitución exige PHPStan sin errores antes del merge. | Resuelto mediante el PR prerequisite `chore/phpstan-quality-gate`; `composer analyse` pasa en la rama apilada y CI lo ejecuta. |
| F2 | MEDIUM | Ambiguity | [spec](../specs/appointment-lifecycle.md), “What result they get” y “Clarifications” | [plan](../plans/appointment-lifecycle.md), §5 y T005 | No se definía el resultado de abandonar la confirmación. | Resuelto: la spec exige que abandonar conserve la cita y no guarde la nota. |

### Por qué importa

- **F1:** el gate se mantiene como dependencia explícita para que el PR de citas no relaje la
  definición de terminado.

## Resultado de los seis análisis

1. **Constitución:** PHPStan forma parte del PR prerequisite y la feature se apila sobre él. Las
   capas y la autorización propuestas respetan las reglas de Policies, Form Requests y servicio.
2. **Cobertura:** los once criterios tienen al menos una tarea; no hay `GAP`.
3. **Deriva de alcance:** no se encontró `DRIFT`. El refactor de disponibilidad y la nota opcional
   responden a criterios explícitos de la spec.
4. **Contradicciones:** F1. No se hallaron otras contradicciones entre la spec y el plan.
5. **Duplicación:** no se encontró trabajo duplicado. La regla de disponibilidad se centraliza en
   lugar de implementarse dos veces.
6. **Ambigüedad:** F2 está resuelto. La concurrencia requiere PostgreSQL porque SQLite no prueba
   bloqueos de fila; el plan lo deja explícito y asigna T006 a esa comprobación.

## Resoluciones

- **F1 — PHPStan:** se autorizó y preparó como PR prerequisite separado para mantener el cambio de
  calidad aislado; sus commits `02964b9`, `7369e37` y `e5e0b94` están verificados. El PR de citas se
  apila sobre esa rama y debe integrarse después del prerequisite.
- **F2 — Confirmación:** la spec y el plan ya fijan que abandonar la confirmación no modifica la
  cita ni guarda la nota.

## Evidence after implementation

- `php artisan test --compact` → 69 passed, 1 PostgreSQL-only test skipped on SQLite.
- PostgreSQL 16 concurrency integration → passed with two independent processes; one overlapping
  reschedule succeeded and the other was rejected without replacing the appointment.
- Playwright E2E → 2 passed, covering appointment creation plus the reschedule/cancel user flow.
- `composer analyse` → zero errors; Pint, Composer validation, Composer audit and `git diff --check`
  passed.

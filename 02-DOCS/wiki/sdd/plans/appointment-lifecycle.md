---
type: plan
title: Plan — Reprogramar y cancelar citas
description: Plan técnico para cambios de horario y cancelación de citas activas.
tags: [sdd, plan, appointments]
timestamp: 2026-11-20T19:47:30Z
topic: sdd
slug: appointment-lifecycle
status: implemented
branch: feature/reschedule-cancel-appointments
---

# Plan — Reprogramar y cancelar citas

> Spec: [../specs/appointment-lifecycle.md](../specs/appointment-lifecycle.md) ·
> Constitution: [../constitution.md](../constitution.md) · Status: implemented
> Last updated: 2026-11-23

## 0. Global Constraints

- **Role scope:** “Admin y receptionist pueden reprogramar o cancelar citas `scheduled` o
  `confirmed` de su clínica.” “Los doctores no pueden reprogramar ni cancelar, incluso si envían
  una solicitud directa.”
- **Rescheduling:** “Reprogramar solo cambia fecha, hora y duración. El paciente, el doctor, el
  motivo y el estado se conservan.”
- **New time:** “La nueva fecha debe ser futura. Se mantienen las duraciones permitidas: 15, 30,
  45, 60 o 90 minutos.”
- **Cancellation:** “Cancelar cambia el estado a `cancelled`, conserva el registro y acepta una nota
  opcional.”
- **Confirmation:** “Antes de cancelar, la persona confirma la acción.”
- **Conflict rule:** “Solo `scheduled` y `confirmed` reservan horario; los intervalos contiguos se
  permiten; los conflictos consideran tanto al doctor como al paciente.”
- **Concurrency:** “La disponibilidad se mantiene incluso si se solicitan cambios de horario
  concurrentes para el mismo doctor o paciente.”
- **Tenant boundary:** “La clínica del usuario autenticado sigue siendo el límite principal de
  cada consulta.”
- **Privacy:** “Los datos médicos se tratan con el mínimo necesario (principio de mínimo
  acatamiento).”
- **Authorization:** “La autenticación y autorización se hacen con el paquete de Laravel/Auth (o el
  elegido) + *policies* y *gates*; no lógica de permisos dispersa en controladores.”
- **Branching:** the PHPStan prerequisite is a separate PR from `main`; this feature branch is
  stacked on that prerequisite and will target it until it merges. No direct pushes to `main`.
- **Quality:** “El código está formateado y linter-clean en cada *commit* (`pint`, cero
  advertencias).” “Las pruebas (Pest/PHPUnit) cierran el *merge*: rojo→verde→refactorizar,
  cobertura mínima por línea de código cambiada.”
- **Static analysis:** `composer analyse` must report zero errors. The feature PR depends on the
  separate PHPStan quality-gate PR.
- **Database:** PostgreSQL 16 es la base local; CI usa SQLite en memoria.

## 1. Context & constraints

- Los criterios 1–11 de la spec fijan el alcance de edición, cancelación, permisos, conflictos y
  visibilidad.
- La spec de [prevención de solapamientos](../specs/appointment-overlaps.md) ya fija los estados
  que reservan tiempo, los intervalos semiabiertos y el orden estable de bloqueos en PostgreSQL.
- La spec de [autorización por rol](../specs/role-based-access.md) ya fija el límite por clínica y
  el acceso de solo lectura de los doctores.
- La constitución requiere Form Requests para autorización/validación, servicios para lógica de
  negocio, políticas centralizadas, pruebas y formato limpio.
- La puerta de PHPStan se prepara en el PR prerequisite `chore/phpstan-quality-gate`; esta rama
  hereda el gate para cumplir la constitución sin mezclar su implementación con el ciclo de citas.
- La cancelación no borra filas ni añade un historial completo; su nota se limita a 500 caracteres.
- La CI actual ejecuta PHPUnit sobre SQLite y Pint. SQLite no permite probar los bloqueos de fila
  de PostgreSQL.
- Fuera de alcance: reasignar paciente o doctor, historial de auditoría, notificaciones, citas
  recurrentes y configuración de zona horaria.

## 2. Architecture

```text
[Appointment list and forms] (INTERNAL)
              |
              v
[Authenticated routes + AppointmentPolicy + Form Requests] (INTERNAL)
              |
              v
[AppointmentController] (INTERNAL, HTTP orchestration)
              |
              v
[AppointmentService] (INTERNAL, transactional lifecycle operations)
        |                     |
        |                     +-- shared availability rule
        v
[Appointments data]
              |
              v
[PostgreSQL / SQLite] (EXTERNAL infrastructure)
```

- **AppointmentPolicy** — authorizes list actions, rescheduling and cancellation using role and
  clinic ownership.
- **Form Requests** — validate rescheduling and cancellation inputs and reject direct requests
  from unauthorized roles.
- **AppointmentController** — serves the list/form and translates service outcomes into the
  existing redirect, validation-error and status-message behavior.
- **AppointmentService** — owns create/reschedule/cancel transactions and keeps the existing
  availability invariant shared between creation and rescheduling.
- **Blade views** — expose lifecycle actions only to authorized staff and display the optional
  cancellation note within the appointment's existing visibility scope.
- **Database** — persists the nullable cancellation note; cancellation reuses the existing status.

**Top architectural decision:** centralize appointment mutations in one service and reuse its
availability rule for creation and rescheduling. Duplicating the existing conflict query would let
new and edited appointments drift apart. The service follows the constitution's business-logic
boundary and keeps transaction/locking behavior consistent.

## 3. Interfaces & contracts

```text
AppointmentPolicy.mayReschedule(user, appointment) -> allowed | denied
  - precondition: user is authenticated
  - invariant: only admin/receptionist of the appointment's clinic are allowed

AppointmentPolicy.mayCancel(user, appointment) -> allowed | denied
  - precondition: user is authenticated
  - invariant: only admin/receptionist of the appointment's clinic are allowed

AppointmentService.reschedule(appointment, start, duration) -> updated appointment | invalid input | conflict
  - invariant: patient, doctor, reason and status are preserved
  - invariant: new start is in the future and duration is one of 15, 30, 45, 60, 90 minutes
  - invariant: the appointment does not conflict with another active appointment for its doctor or patient
  - invariant: the current appointment is excluded from its own conflict check
  - postcondition: conflict or invalid input leaves the appointment unchanged

AppointmentService.create(patient, doctor, start, duration, reason) -> new appointment | invalid input | conflict
  - invariant: uses the same availability rule as rescheduling

AppointmentService.cancel(appointment, optional note) -> cancelled appointment | invalid state
  - precondition: appointment status is scheduled or confirmed
  - postcondition: status is cancelled, record remains, and the optional note is retained

AppointmentService.assertAvailable(appointment, start, end) -> available | conflict
  - invariant: scheduled/confirmed appointments reserve semi-open intervals [start, end)
  - invariant: appointments in another clinic and the current appointment are excluded
```

Rescheduling payload accepts only `start_time` and `duration_minutes`. Cancellation accepts only an
optional `cancellation_note`. Neither request can alter `clinic_id`, `patient_id`, `user_id`,
`reason` or `status`.

## 4. Data model & flow

**Entities**

- **Appointment** — existing clinic, patient, doctor, start/end, status and reason; add optional
  `cancellation_note` with a 500-character limit.
- **User** — existing clinic and role determine the permitted action.
- **Patient** — remains attached to the appointment and participates in availability checks.

**Reschedule flow**

1. An authorized staff member opens the reschedule form for an active appointment in their clinic.
2. The form presents its current start and duration; patient, doctor and visit reason are not
   editable.
3. The submitted start and duration are validated.
4. The service locks the patient and doctor in the established order, checks active overlaps while
   excluding the current appointment, and updates start/end together in one transaction.
5. On success, the user returns to the list and sees the new schedule. On conflict, the old
   schedule remains unchanged and a field error is shown.

**Cancellation flow**

1. An authorized staff member opens a cancellation confirmation for an active appointment.
2. The staff member may enter a note and confirms or abandons the action.
3. On confirmation, the optional note is validated and stored with the appointment.
4. The service changes the status to `cancelled` without deleting the record.
5. The user returns to the list; the appointment is labelled cancelled and the note is shown only
   to users already allowed to see the appointment.

- **Consistency boundary:** resource locks, availability check and reschedule write share one
  database transaction. Cancellation status and note are written together.
- **Migration impact:** one additive nullable column; no backfill or destructive change.
- **Create flow:** move the existing creation transaction through the same service/availability
  rule, preserving its validation and response behavior.

## 5. Testing strategy

| Acceptance criterion | Level | Asserts | Fakes / mocks |
| --- | --- | --- | --- |
| AC1–2: reschedule and preserve fields | Integration | New start/end and duration persist; patient, doctor, reason and status remain unchanged | No database fake |
| AC3: doctor/patient conflict | Integration | Conflict reports a validation error and leaves the original row unchanged | No database fake |
| AC4: same appointment | Integration | Keeping the current slot does not conflict with itself | No database fake |
| AC5: simultaneous changes | PostgreSQL integration | Overlapping reschedules for a shared doctor/patient cannot both commit | Real PostgreSQL, separate connections |
| AC6: cancellation and confirmation | HTTP feature | Confirmation is shown; leaving without confirming changes nothing; confirming retains the optional note, sets status to cancelled and releases the slot | No browser fake; real database |
| AC7: past appointment | Integration | Active past appointment may be cancelled; rescheduling to a past time is rejected | Frozen application clock only |
| AC8: terminal states | Integration | Cancelled/completed/no_show appointments reject both actions | No database fake |
| AC9: doctor denied | HTTP feature | UI hides actions and direct requests are forbidden | Authenticated test user |
| AC10: clinic boundary | HTTP feature | Cross-clinic requests reveal no appointment and do not mutate data | Separate clinic fixtures |
| AC11: note visibility | HTTP feature | Staff see clinic notes; doctors see notes only for assigned appointments | Role/clinic fixtures |

- Input validation tests also cover the 500-character note limit and allowed durations.
- HTTP feature tests prove request behavior; Playwright verifies the reschedule and cancellation
  flows in the browser.
- The SQLite CI suite proves request, validation and conflict behavior. A PostgreSQL-only integration
  test verifies row-lock concurrency because SQLite cannot exercise those locks.
- Do not mock the database transaction or the availability query in tests that prove persisted
  state, tenancy, soft-delete behavior or conflict handling.

## 6. Sequencing & dependencies

1. Add failing rescheduling feature tests — no dependencies — serial.
2. Add failing cancellation, state and visibility tests — no dependencies — serial.
3. Implement shared create/reschedule availability and the rescheduling operation — depends on
   step 1 — serial.
4. Add cancellation-note persistence and the cancellation operation — depends on steps 2–3 —
   serial.
5. Add authorized list actions, the reschedule form and cancellation confirmation, including the
   no-op path when the confirmation is abandoned — depends on steps 3–4 — serial.
6. Add and run the PostgreSQL concurrency check — depends on step 3 — serial.
7. Run focused tests, PHPStan, the full suite and Pint; update evidence — depends on steps 1–6 —
   serial.

- Parallel candidates: none. The backend, policy and shared availability behavior touch the same
  lifecycle path and should be integrated sequentially.
- Hard ordering: schema support precedes cancellation persistence; the shared availability service
  precedes reschedule integration; UI follows authorized backend actions.

## 7. Risks & open decisions

**Risks**

| Risk | Trigger | Impact | Mitigation / spike to retire it |
| --- | --- | --- | --- |
| Concurrent rescheduling differs across databases | Two requests reserve overlapping intervals | Double booking or a behavior that CI cannot prove | Reuse the PostgreSQL resource-lock order; run a separate-connection integration check against PostgreSQL |
| Refactoring the existing creation path regresses conflict protection | Create and reschedule stop sharing one availability rule | Previously prevented overlaps become possible | Keep existing `AppointmentConflictTest` cases green and add cross-flow coverage |
| Cross-clinic model access leaks data | Direct update/cancel request supplies another clinic's appointment ID | Patient/appointment disclosure or unauthorized mutation | Apply policy and clinic-scoped lookup; assert no data in HTTP response and no database mutation |
| Cancellation note contains excessive clinical information | Staff enter unrelated detail in the optional note | Unnecessary sensitive-data retention | Limit to 500 characters, keep optional, and apply existing appointment visibility |
| A stale form overwrites a newer schedule | Two staff members edit the same appointment | The list no longer matches the intended change | Lock the appointment and its resources in a transaction; verify serialized update behavior |

**Open decisions**

- None. The spec leaves only explicitly deferred history and cancellation-reversal work.

## Tasks
<!-- generated by tasks on 2026-11-20; IDs are stable, do not renumber -->

| ID | [P] | Task | Done-check | Depends-on | Trace | Risk |
| --- | --- | --- | --- | --- | --- | --- |
| T001 |  | Add failing rescheduling feature tests | `php artisan test tests/Feature/AppointmentReschedulingTest.php --compact` fails on the missing lifecycle behavior, not setup | — | AC1–5, AC7 | 1 |
| T002 |  | Add failing cancellation and access tests | `php artisan test tests/Feature/AppointmentCancellationTest.php --compact` fails on the missing cancellation behavior, not setup | — | AC6–11 | 1 |
| T003 |  | Implement shared availability and rescheduling | `php artisan test tests/Feature/AppointmentReschedulingTest.php tests/Feature/AppointmentConflictTest.php --compact` passes | T001 | AC1–5, AC7, AC9–10 | 1 |
| T004 |  | Implement cancellation and note persistence | `php artisan test tests/Feature/AppointmentCancellationTest.php --compact` passes | T002, T003 | AC6–8, AC10–11 | 2 |
| T005 |  | Add authorized lifecycle forms and list actions | `php artisan test tests/Feature/AppointmentReschedulingTest.php tests/Feature/AppointmentCancellationTest.php --compact` passes, including confirmation/abandonment, duration and note visibility | T003, T004 | AC1, AC6, AC9–11 | 1 |
| T006 |  | Verify PostgreSQL concurrency behavior | `DB_CONNECTION=pgsql php artisan test tests/Feature/AppointmentConcurrencyTest.php --compact` passes against PostgreSQL 16 | T003 | AC5 | 1 |
| T007 |  | Verify the completed feature | `composer analyse && php artisan test && vendor/bin/pint --test && git diff --check` passes | T001–T006 | AC1–11 | — |

**T003 — Interfaces**

- Consumes: the existing appointment's clinic, patient, doctor and status; allowed durations
  `[15, 30, 45, 60, 90]`.
- Produces: a reschedule outcome of updated appointment, validation error or conflict; create and
  reschedule share the same doctor/patient availability rule.

**T004 — Interfaces**

- Consumes: only `scheduled`/`confirmed` appointments and an optional note of at most 500 characters.
- Produces: status `cancelled`, retained appointment record and released interval; terminal statuses
  reject the operation.

**T005 — Interfaces**

- Consumes: authorized reschedule/cancel actions from T003/T004.
- Produces: list/form pages only for staff actions; confirmation before cancellation; notes visible
  only within the appointment's existing access scope.

**T006 — Interfaces**

- Consumes: reschedule operations serialized on the same doctor or patient.
- Produces: at most one overlapping operation commits; the rejected operation leaves its original
  appointment unchanged.

## Review Workload Forecast

| Dimension | Forecast | Why |
| --- | --- | --- |
| Estimated changed lines | 300–450 | Shared lifecycle service, two requests, one migration, list/forms, focused tests and docs |
| Files / areas | 12–16 | Controller, service, policy, requests, model, migration, routes, Blade, tests and SDD docs |
| Risk | Max tier 2; 1 tier-2 task | T004 adds a database migration and changes clinic appointment state |
| Suggested delivery | Two stacked PRs | PHPStan prerequisite first, then this feature; human merge approval remains mandatory |

## Implementation evidence

- T001–T002 follow red-green: the focused tests failed before lifecycle routes and persistence
  existed, then passed after implementation.
- `php artisan test --testsuite Feature --compact` → 69 passed, 1 PostgreSQL-only test skipped on
  SQLite.
- `APPOINTMENT_CONCURRENCY_TEST=1 ... php artisan test tests/Feature/AppointmentConcurrencyTest.php --compact`
  → passed against an isolated, temporary PostgreSQL 16 container; two independent processes
  attempted overlapping reschedules and exactly one committed.
- `npm run test:e2e` → 2 Playwright tests passed, including create, reschedule and cancel.
- `composer analyse` → passed with zero errors.
- `vendor/bin/pint --test` → passed.
- `composer validate --no-check-publish --no-interaction --strict` → valid.
- `composer audit` → no known security advisories.
- `git diff --check` → passed.

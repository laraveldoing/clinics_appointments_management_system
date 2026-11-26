---
type: plan
title: Plan — Idioma español y errores accesibles en formularios
description: Diseño de los contratos de idioma y retroalimentación accesible en formularios.
tags: [sdd, plan, accessibility]
timestamp: 2026-11-26T05:24:32Z
topic: sdd
slug: spanish-language-accessible-form-errors
status: approved
branch: develop_jjosedesarrollo_collaborating
---

# Plan — Idioma español y errores accesibles en formularios

> Spec: [../specs/spanish-language-accessible-form-errors.md](../specs/spanish-language-accessible-form-errors.md) ·
> Constitution: [../constitution.md](../constitution.md) · Status: approved in autopilot
> Last updated: 2026-11-26

Ejecución en modo autopilot, elegido por el usuario el 2026-11-26. El plan no amplía el alcance
aprobado y no tiene revisión humana punto por punto.

## 0. Global Constraints

- **Idioma:** el idioma predeterminado declarado por las páginas coincide con el contenido principal
  en español.
- **Errores:** el estado inválido solo aparece cuando ese error existe.
- **Foco:** tras un envío fallido, se enfoca el primer campo inválido o el resumen si no hay campo
  asociado.
- **Autorización:** La autenticación y autorización se hacen con el paquete de Laravel/Auth (o el
  elegido) + *policies* y *gates*; no lógica de permisos dispersa en controladores.
- **Accesibilidad:** barra mínima WCAG 2.2 AA: navegación por teclado, marcado semántico y foco
  visible.
- **Privacidad:** no se expone información clínica en mensajes de error.
- **Login:** los errores de credenciales siguen siendo genéricos y no confirman si una cuenta existe.
- **Alcance:** no se cambian permisos, reglas de citas, datos guardados ni el idioma de respaldo.

## 1. Context & constraints

- Los criterios AC1–AC6 de la spec fijan idioma, relación de errores, foco y compatibilidad del login.
- La constitución fija la barra de accesibilidad y la separación de autenticación/autorización.
- Las specs de [login y listado](../specs/appointments-list.md), [autorización por rol](../specs/role-based-access.md)
  y [ciclo de vida de citas](../specs/appointment-lifecycle.md) mantienen el comportamiento de
  acceso, clínica y citas.
- Fuera de alcance: traducción general, otras mejoras de accesibilidad y cambios funcionales de
  autenticación o citas.
- La rama actual fue elegida por el usuario antes de implementar. No hay otra sesión en este
  worktree.

## 2. Architecture

```text
[Application locale] (INTERNAL)
          |
          v
[Shared document shell] (INTERNAL) ---> [Document language]
          ^
          |
[Server validation errors] (INTERNAL)
          |
          v
[Form fields + error summary] (INTERNAL)
          |
          v
[Browser focus handoff] (EXTERNAL runtime)
```

- **Application locale** (internal) supplies the page language, with Spanish as the default and
  existing explicit overrides preserved.
- **Shared document shell** (internal) exposes the selected language and routes focus after a
  server-side validation response.
- **Forms** (internal) render field messages next to their controls and a summary for general errors.
- **Browser** (external runtime) receives focus on the first invalid control or the summary.

**Top architectural decision:** keep the existing server-rendered forms and add a small progressive
focus handoff. This preserves current submissions and validation while avoiding a client-side form
rewrite or dependency.

## 3. Interfaces & contracts

```text
ApplicationLocale -> DocumentLanguage
  - default: Spanish
  - explicit configured locale remains authoritative

ValidationErrors -> FormFeedback
  - each field error has a visible message identified with its control
  - invalid state exists only while that field has a server error
  - errors without a field remain available in the summary

FailedSubmission -> FocusTarget
  - first invalid field in document order, otherwise the error summary
  - a page without errors does not receive a new focus target

InvalidCredentials -> GenericLoginError
  - message does not reveal whether the account exists
```

No persistent data or public API contracts change.

## 4. Data model & flow

**Entities**

- None. The change does not add or modify stored entities.

**Primary flow** (server-side form validation)

1. A visitor or authorized staff member opens an existing page.
2. The page uses the configured locale, defaulting to Spanish.
3. If submission fails, the server returns the existing field errors and prior input.
4. The page renders each field error with its control, plus any general error in the summary.
5. The browser focuses the first invalid control, or the summary when no field is invalid.

- Consistency boundary: none; validation errors are per-response and do not persist.
- Migration impact: none.

## 5. Testing strategy

| Acceptance criterion | Level | Asserts | Fakes / mocks |
| --- | --- | --- | --- |
| AC1: default document language | Feature | Rendered document declares Spanish under the test default | No fake view rendering |
| AC2: field error associations | Feature | Login, create, reschedule and cancel errors identify their controls and only mark failing fields invalid | No browser fake |
| AC3: first invalid field focus | Browser integration | A rejected submission with multiple field errors focuses the earliest invalid field in reading order | Real Chromium; seeded test database |
| AC4: general error fallback | Browser integration | A general-only error response moves actual browser focus to the summary, with no field marked invalid | Real Chromium; controlled response state |
| AC5: no-error behavior | Feature + browser integration | Initial page has no invalid fields, error summary or error-focus script; existing initial login focus remains | No fake view rendering; real Chromium |
| AC6: generic login error | Feature | Invalid credentials keep the generic message and do not reveal account existence | Existing auth provider and test database |

- The browser boundary is needed to verify actual focus after a full server response; field
  associations are checked in feature tests for faster coverage across all forms.
- The database remains a real isolated SQLite test database; no clinic or patient data is used.

## 6. Sequencing & dependencies

1. Configure the Spanish default and expose field-level error relationships — serial.
2. Route focus to the first invalid field or summary and preserve no-error behavior — depends on 1.
3. Run feature, browser and formatting checks; record evidence — depends on 1 and 2.
4. Run the required tier-2 review panel; stop before any merge or PR without human approval —
   depends on 3.

- Parallel candidates: none; the locale, shared form handling and validation tests overlap.
- Hard ordering: verify rendered markup before browser focus; complete review before delivery.

## 7. Risks & open decisions

**Risks** (ranked)

| Risk | Trigger | Impact | Mitigation / spike to retire it |
| --- | --- | --- | --- |
| Tier-2 path classification | Locale example and login view are in scope | Push/PR requires the linked spec, three-refuter panel and human approval | Keep the spec branch-linked; record the panel; do not ship without explicit approval |
| Browser focus differs after server navigation | Validation response has multiple errors or only a general error | Keyboard users may land at the wrong place | Check first-invalid focus in a real browser and verify summary fallback |
| Untranslated strings remain outside scope | A page uses the existing fallback locale | Some non-target messages may remain in English | Preserve fallback behavior and defer broader translation as approved |

**Open decisions**

- None. The user confirmed the fallback-language boundary during clarification.

## Tasks
<!-- generated by tasks on 2026-11-26; IDs are stable, do not renumber -->

| ID | [P] | Task | Done-check | Depends-on | Trace | Risk |
| --- | --- | --- | --- | --- | --- | --- |
| T001 |  | Set Spanish defaults and accessible form feedback | `APP_KEY='base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=' php artisan test tests/Feature/Auth/LoginTest.php tests/Feature/CreateAppointmentTest.php tests/Feature/AppointmentReschedulingTest.php tests/Feature/AppointmentCancellationTest.php && npm run test:e2e -- --grep 'multiple appointment errors focus the first invalid field and general errors focus the summary' && vendor/bin/pint --test config/app.php app/Http/Controllers/Auth/LoginController.php tests/Feature/Auth/LoginTest.php tests/Feature/CreateAppointmentTest.php tests/Feature/AppointmentReschedulingTest.php tests/Feature/AppointmentCancellationTest.php` passes | — | AC1–AC6 | 2 |
| T002 |  | Complete the three-refuter review panel | `npx @ericrisco/rsc@4.1.8 review ledger panel spanish-language-accessible-form-errors/T001 --lenses correctness,security,tests` records the panel with no unresolved blocker | T001 | AC1–AC6 | 2 |
| T003 |  | Run full verification and record evidence | `APP_KEY='base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=' php artisan test && npm run test:e2e && composer analyse && vendor/bin/pint --test && git diff --check` exits successfully | T001, T002 | AC1–AC6 | 0 |

## Review Workload Forecast

| Dimension | Forecast | Why |
| --- | --- | --- |
| Estimated changed lines | 150–200 | Locale defaults, form markup, focus handoff and focused tests |
| Files / areas | 15 files across config, auth, Blade forms, tests and docs | The same validation flow spans the shared shell and four existing forms |
| Risk | Maximum tier 2; one tier-2 implementation task | The risk classifier marks the login view and environment example as auth/secrets |
| Suggested delivery | ask-on-risk | The configured delivery strategy requires a human decision before shipping a tier-2 change |

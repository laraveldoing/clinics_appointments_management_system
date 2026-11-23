---
type: plan
title: Plan — Puerta de análisis estático PHPStan
description: Plan técnico para analizar PHP propio en local y bloquear errores estáticos en CI.
tags: [sdd, plan, phpstan, quality]
timestamp: 2026-11-20T20:30:45Z
topic: sdd
slug: phpstan-quality-gate
status: implemented
branch: chore/phpstan-quality-gate
---

# Plan — Puerta de análisis estático PHPStan

> Spec: [../specs/phpstan-quality-gate.md](../specs/phpstan-quality-gate.md) ·
> Constitution: [../constitution.md](../constitution.md) · Status: implemented
> Last updated: 2026-11-20

## 0. Global constraints

- **Scope:** analyze first-party PHP source and tests in `app/`, `bootstrap/`, `config/`,
  `database/`, `public/`, `routes/`, `tests/` and `artisan`.
- **Quality gate:** zero PHPStan errors; no baseline or broad ignore rules.
- **Legacy findings:** correct errors surfaced in the analyzed code without changing application
  behavior; do not broaden the work into unrelated refactors.
- **Runtime:** PHP `^8.3`, Laravel `^13.17`; Composer is the dependency manager.
- **CI:** use the same local analysis command on pull requests and pushes to `main`.
- **Out of scope:** `vendor/`, generated caches, Blade templates, dependency auditing and coverage.

## 1. Context and constraints

- The approved spec requires a reproducible local command and a blocking CI check.
- `composer.json` has no direct PHPStan or Larastan development dependency, and `.github/workflows/ci.yml`
  currently runs only PHPUnit and Pint.
- The lockfile resolves Laravel 13.35.0; the declared application constraints are PHP `^8.3` and
  Laravel `^13.17`.
- The existing CI job installs Composer dependencies before running its quality checks.
- The constitution requires zero static-analysis errors before merge. No change to that rule is
  proposed.

## 2. Architecture

```text
[composer analyse] (LOCAL / CI)
          |
          v
[PHPStan + Larastan configuration] (INTERNAL)
          |
          v
[First-party PHP source and tests]
          |
          v
[Exit code: zero errors = pass; any error = fail]
```

- **Larastan** — provides Laravel-aware type information for framework and Eloquent APIs.
- **PHPStan configuration** — use `phpstan.neon.dist`, include Larastan's extension, set the
  approved initial level of 5, and list the approved source/test paths.
- **Composer script** — expose the analyzer as `composer analyse`, invoking the local PHPStan
  binary with no progress output.
- **Error policy** — do not add a baseline or broad `ignoreErrors`; fix reported findings in
  first-party code.
- **CI** — invoke `composer analyse` in the existing `quality` job after dependencies are installed.
- **Documentation** — add the local command and scope to `02-DOCS/wiki/stack/php.md`, and add
  `composer analyse` to the verification commands in `02-DOCS/wiki/sdd/config.yaml`.

The approved level 5 is a moderate starting point for introducing a new gate. It still requires
zero reported errors and can be raised in a later, separately scoped change.

## 3. Interfaces and contracts

```text
composer analyse -> PHPStan process -> exit code 0 | non-zero
  - invariant: same configuration and command are used locally and in CI
  - invariant: every configured first-party path is analyzed
  - invariant: any reported error fails the command

CI quality job -> composer analyse -> visible check result
  - invariant: runs for every pull request and push to main
  - invariant: analyzer failure makes the job fail
```

`phpstan.neon.dist` will analyze the approved paths and exclude only dependencies and generated
artifacts. Blade templates remain outside PHPStan's source paths.

## 4. Data and flow

There is no application data or runtime flow change.

1. A developer installs dependencies from `composer.lock`.
2. The developer runs `composer analyse`.
3. PHPStan reports the file and diagnostic for each error and returns a failing exit code.
4. The CI quality job runs the same command after Composer installation.
5. A clean analysis, test suite and Pint check produce a green quality job.

## 5. Testing strategy

| Acceptance criterion | Level | Asserts | Fakes / mocks |
| --- | --- | --- | --- |
| Local command analyzes approved PHP paths | Tool integration | Command completes with zero errors on the corrected codebase | No mocks |
| An analyzer error blocks local use | Negative check | A temporary known type error makes the command return non-zero and identify the file | Temporary fixture only |
| CI runs the same command for PRs and `main` | Workflow validation | The quality job includes `composer analyse` for both triggers | No external service |
| Existing behavior is preserved | Regression | Full PHPUnit suite remains green | Existing test setup |
| Formatting remains clean | Regression | Pint test mode passes | No mocks |

- Run the analyzer after configuration and after every batch of type corrections.
- Use a temporary fixture for the negative check; remove it after verifying the failure result.
- Do not add application behavior tests unless a type correction changes behavior.
- Final verification runs `composer analyse`, `php artisan test`, `vendor/bin/pint --test` and
  `git diff --check`.

## 6. Sequencing and dependencies

1. Add Larastan as a development dependency and expose the local Composer command.
2. Add the Laravel-aware PHPStan configuration with the approved paths, initial level and strict
   no-baseline policy.
3. Run the analyzer and correct its existing findings without behavior changes.
4. Add the blocking CI step and document the local command.
5. Run the negative check and final analyzer, tests, Pint and diff checks.

- Parallel candidates: none. Configuration depends on the package; code corrections depend on the
  first analysis; CI and documentation should be added after the configured command passes.
- Hard ordering: dependency → configuration → corrections → CI/docs → final verification.

## 7. Risks and open decisions

**Risks**

| Risk | Trigger | Impact | Mitigation |
| --- | --- | --- | --- |
| Laravel dynamic APIs produce false positives | Framework or Eloquent types are not understood | Noisy output or pressure to suppress errors | Use Larastan and correct types before considering any narrow, explicitly approved exception |
| Existing code has many findings | First complete analysis | Scope expands into unrelated code | Fix only findings in the approved paths that block this gate; avoid behavior refactors |
| Dependency constraints conflict | Composer resolves Larastan against PHP/Laravel versions | Lockfile cannot be updated consistently | Select a Larastan release compatible with the declared PHP/Laravel constraints and verify from a clean install |
| Analysis slows CI | Full source and tests are included | Longer quality feedback | Measure the job runtime; optimize configuration without reducing source coverage |

**Open decisions**

- None in the approved behavior. Level 5 was approved with this plan; the analyzer integration
  remains Laravel-aware and baseline-free.

## Tasks
<!-- generated by tasks on 2026-11-20; IDs are stable, do not renumber -->

| ID | [P] | Task | Done-check | Depends-on | Trace | Risk |
| --- | --- | --- | --- | --- | --- | --- |
| T001 |  | Add Larastan and Composer command | Composer resolves a PHP/Laravel-compatible Larastan version and `composer analyse` invokes PHPStan | — | Goals 1, 2 | 1 |
| T002 |  | Configure PHPStan scope and level | `phpstan.neon.dist` covers all approved paths at level 5 with no baseline or broad ignores | T001 | Goals 2, 5; AC1, AC4 | 1 |
| T003 |  | Correct existing analysis findings | `composer analyse` reports zero errors without changing application behavior | T002 | Goal 5; AC1, AC3, AC4 | 2 |
| T004 |  | Enforce and document the analysis gate | CI runs `composer analyse` on PRs and pushes to `main`; `02-DOCS/wiki/stack/php.md` and `02-DOCS/wiki/sdd/config.yaml` document the command | T003 | Goals 1, 3, 4; AC1, AC2 | 1 |
| T005 |  | Verify the completed quality gate | Negative fixture fails as expected; analysis, PHPUnit, Pint and diff checks pass | T001–T004 | AC1–6 | — |

## Review workload forecast

| Dimension | Forecast | Why |
| --- | --- | --- |
| Estimated changed lines | 80–200 | Dependency, analyzer config, workflow, type corrections and documentation |
| Files / areas | 6–12 | Composer manifests, PHPStan config, CI, existing PHP findings and stack docs |
| Risk | Maximum tier 2 | A development dependency and blocking merge check are introduced |
| Suggested delivery | One PR | Dependency, local command and CI gate must land together |

## Implementation evidence

- `composer analyse` → PHPStan passed with zero errors at level 5.
- A temporary invalid return type made PHPStan fail with `return.type`; the probe was removed.
- `php artisan test` → 49 tests passed, 169 assertions.
- `vendor/bin/pint --test` → passed.
- `composer validate --no-check-publish --no-interaction` → valid.
- `composer audit` → no known security advisories.
- `git diff --check` → passed.

The implementation is complete on `chore/phpstan-quality-gate`; it remains uncommitted and has no
pull request yet.

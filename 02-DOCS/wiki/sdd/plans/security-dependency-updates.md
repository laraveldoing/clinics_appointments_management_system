---
type: plan
title: Plan — Actualización de dependencias vulnerables
description: Resolver avisos de Composer con un lock compatible con PHP 8.3 y Laravel 13.
tags: [sdd, plan, security, dependencies]
timestamp: 2026-11-20T21:15:38Z
topic: sdd
slug: security-dependency-updates
status: implemented
branch: chore/security-dependency-updates
---

# Plan — Actualización de dependencias vulnerables

> Spec: [../specs/security-dependency-updates.md](../specs/security-dependency-updates.md) ·
> Constitution: [../constitution.md](../constitution.md) · Status: implemented
> Last updated: 2026-11-20

## 0. Global constraints

- **PHP minimum:** keep `^8.3`; resolve dependencies as if running PHP 8.3.0.
- **Framework:** remain on Laravel 13; do not make a major framework upgrade.
- **Security:** remove the eight current Composer advisories; do not suppress audit results.
- **Symfony:** use patched 7.4 LTS packages so the lock remains compatible with PHP 8.3.
- **Filament:** do not install Filament; preserve its documented PHP/Laravel version compatibility.
  Its package integration is not currently present or testable in this environment.
- **Scope:** update `composer.json` platform resolution, `composer.lock`, CI compatibility checks
  and directly related PHP dependency documentation only.
- **Behavior:** no application source changes unless a compatibility failure proves one necessary;
  ask before broadening that scope.

## 1. Context and constraints

- `composer audit` reports eight advisories across Laravel Framework and four Symfony packages.
- The current lock selects Laravel 13.35.0 and Symfony 8.1.x; Symfony 8.1 requires PHP `>=8.4.1`,
  which conflicts with the declared minimum `^8.3`.
- `composer.json` already allows Laravel 13.43.0. No Laravel range change is expected.
- A dry run with Composer platform 8.3.0 and `--minimal-changes` resolved Laravel 13.43.0,
  Symfony 7.4.21–7.4.23 and `symfony/polyfill-php83`: 17 package updates and one addition.
- A dry run to add Filament 5 could not resolve locally because `ext-intl` and `ext-zip` are not
  installed. The project does not currently declare Filament.
- PHP 8.3 receives security fixes through 2027-12-31; the current CI uses PHP 8.4.

## 2. Architecture

```text
[composer.json platform.php = 8.3.0]
                |
                v
[Composer dependency resolver] (LOCKED TO MINIMUM PHP)
                |
                v
[Laravel 13.43 + patched Symfony 7.4 LTS packages]
                |
                +--> [composer audit: no current advisories]
                |
                +--> [CI: existing PHP 8.4 quality job]
                +--> [CI: PHP 8.3 compatibility job]
```

- **Composer platform** — add `config.platform.php: 8.3.0`. This constrains dependency resolution
  to the declared minimum; it does not change the actual PHP runtime.
- **Dependency update** — update Laravel and the affected Symfony packages with Composer's
  `--minimal-changes` option. Preserve unrelated locked packages where constraints allow.
- **Symfony line** — resolve to fixed Symfony 7.4 LTS versions rather than Symfony 8.1, which
  requires PHP 8.4.1+.
- **CI** — keep the existing `quality` job on PHP 8.4 unchanged to avoid renaming its check; add a
  separate PHP 8.3 compatibility job that installs the lockfile and runs PHPUnit and Pint.
- **Documentation** — document the Composer platform minimum and `composer audit` command in
  `02-DOCS/wiki/stack/php.md`; list `composer audit` in SDD verification commands.
- **Filament boundary** — keep Laravel/PHP ranges within Filament 5's published requirements.
  Do not add Filament or pretend its runtime integration was verified.

The approved dry run indicates the minimal lock change is limited to Laravel, Symfony 7.4
components and the required PHP 8.3 polyfill. The implementation must recheck the actual Composer
diff before accepting it.

## 3. Interfaces and contracts

```text
composer install -> locked dependencies -> install success on PHP 8.3 and 8.4
  - invariant: every locked package supports PHP 8.3
  - invariant: platform resolution remains at 8.3.0

composer audit -> advisory report -> exit code 0
  - invariant: current eight advisories are not ignored or suppressed

CI quality job -> existing PHP 8.4 checks
CI php83-compatibility job -> PHP 8.3 install, tests and Pint
  - invariant: existing `quality` status check name remains unchanged
```

## 4. Dependency flow

1. Composer reads the PHP `^8.3` application requirement and `platform.php = 8.3.0`.
2. Composer updates Laravel and the affected Symfony packages to patched, compatible releases.
3. The resulting lockfile installs under PHP 8.3 and 8.4 without `--ignore-platform-req`.
4. `composer audit` confirms the current advisories are absent.
5. CI verifies the same lockfile on both supported PHP versions.

There are no database, application API or runtime behavior changes in this plan.

## 5. Verification strategy

| Acceptance criterion | Level | Asserts | Fakes / mocks |
| --- | --- | --- | --- |
| Current advisories are fixed | Dependency audit | `composer audit` exits successfully without the eight current advisories | No mocks |
| PHP minimum is respected | Platform resolution | Composer locks only packages compatible with PHP 8.3.0 | Composer platform config |
| Lockfile is reproducible | Clean install | `composer install` succeeds from `composer.lock` | No mocks |
| Runtime behavior is preserved | Feature regression | Full PHPUnit suite passes on PHP 8.3 and 8.4 | Existing tests |
| Formatting remains clean | Style check | Pint test mode passes on both CI jobs | No mocks |
| Existing CI contract is preserved | Workflow review | Existing `quality` job name remains; separate PHP 8.3 job is added | No external service |

- Run the minimal Composer dry run again after setting the platform in the branch manifest; record
  every package update and addition.
- Install from the resulting lockfile and run `composer validate` and `composer audit`.
- Run PHPUnit and Pint locally on PHP 8.4; the new CI job verifies PHP 8.3.
- Do not ignore `ext-intl` or `ext-zip` in any future Filament installation check.

## 6. Sequencing and dependencies

1. Add Composer's PHP 8.3.0 platform setting.
2. Update the framework and affected Symfony packages with `--minimal-changes`; review the lock
   diff against the 17-update dry-run.
3. Add a dedicated PHP 8.3 CI compatibility job while keeping the existing PHP 8.4 `quality` job.
4. Update PHP dependency documentation and SDD verification commands.
5. Run clean install, audit, validation, PHPUnit, Pint and final diff review.

- Parallel candidates: none. Lock resolution depends on the platform setting; CI and documentation
  depend on the accepted lockfile.
- Hard ordering: platform setting → lock update → CI/docs → final verification.

## 7. Risks and open decisions

**Risks**

| Risk | Trigger | Impact | Mitigation |
| --- | --- | --- | --- |
| Symfony 8.1 to 7.4 changes framework behavior | Laravel resolves to Symfony 7.4 LTS | Regression despite matching API ranges | Run the full suite on PHP 8.3 and 8.4; inspect application use of Symfony APIs |
| Composer changes unrelated packages | Dependency resolution is broader than the dry run | Larger review surface | Use `--minimal-changes`; reject unexplained package changes |
| PHP 8.3 CI exposes latent compatibility problems | Minimum-version job runs | Quality job fails | Fix only directly related compatibility issues; otherwise revise the spec before broadening |
| Filament cannot be integration-tested | Filament is absent and local `ext-intl`/`ext-zip` are missing | No proof of panel integration | Keep claims limited to documented PHP/Laravel ranges; defer Filament installation and extensions |
| PHP 8.3 approaches security-support end | Runtime remains on 8.3 after 2027-12-31 | No further PHP security patches | Use latest 8.3 patches now and plan a separate move to a newer supported branch |

**Open decisions**

- None. PHP minimum, framework line, Symfony compatibility line and CI coverage are fixed by the
  approved spec. The exact minimal package set must be checked against the actual lock diff.

## Tasks
<!-- generated by tasks on 2026-11-20; IDs are stable, do not renumber -->

| ID | [P] | Task | Done-check | Depends-on | Trace | Risk |
| --- | --- | --- | --- | --- | --- | --- |
| T001 |  | Constraining Composer to PHP 8.3 | `composer.json` sets `config.platform.php` to `8.3.0` and validates | — | Goals 1–2; AC2 | 1 |
| T002 |  | Updating vulnerable framework dependencies | Minimal update locks Laravel 13.43 and fixed Symfony 7.4 packages; audit reports no current advisories | T001 | Goals 1, 3–4; AC1–3, AC6 | 2 |
| T003 |  | Adding PHP 8.3 compatibility CI | New PHP 8.3 job installs the lock and passes tests/Pint; existing `quality` job name remains | T002 | Goal 2; AC5 | 1 |
| T004 |  | Documenting dependency maintenance | PHP stack docs and SDD verify commands include Composer platform and audit guidance | T001–T002 | Goals 2, 4; AC1–2 | 1 |
| T005 |  | Verifying the security update | Clean install, Composer validation/audit, tests on PHP 8.3/8.4, Pint and diff checks pass | T001–T004 | AC1–6 | — |

## Review workload forecast

| Dimension | Forecast | Why |
| --- | --- | --- |
| Estimated changed lines | 50–120 | Composer platform/lock, one CI job and dependency documentation |
| Files / areas | 5–8 | Composer manifests, workflow, stack docs and SDD artifacts |
| Risk | Maximum tier 2 | Runtime framework and transitive dependency versions change |
| Suggested delivery | One PR | A compatible lockfile and PHP 8.3 verification belong together |

## Implementation evidence

- **T001 — complete:** `composer.json` pins Composer's platform to PHP `8.3.0`;
  `composer validate --no-check-publish --no-interaction` passes.
- **T002 — complete:** the lockfile contains 17 updates and one addition, with no removals.
  The updates are limited to Laravel 13.43.0 and the Symfony 7.4 packages needed for PHP 8.3;
  `composer audit --locked --no-interaction` reports no advisories.
- **T003 — complete:** the `php83-compatibility` CI job installs the lockfile and runs tests/Pint;
  the existing `quality` job remains on PHP 8.4 with its name unchanged.
- **T004 — complete:** the PHP stack guide and SDD verification commands document the platform
  constraint, Composer validation and audit.
- **T005 — complete:** clean `composer install`, Composer validation/audit, platform requirements,
  49 PHPUnit tests (169 assertions) and Pint pass on PHP 8.3.37 and 8.4.15. PHP 8.3 checks ran
  inside the official PHP CLI container. The hosted GitHub Actions run remains pending until the
  branch is pushed.

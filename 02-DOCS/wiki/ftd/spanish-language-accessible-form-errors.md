---
author: jjosedesarrollo
branch: develop_jjosedesarrollo_collaborating
status: in-progress
---
# Idioma español y errores accesibles en formularios

## Intent

Hacer que el idioma predeterminado del documento coincida con el contenido en español y que los
errores de validación sean perceptibles y alcanzables para usuarios de teclado y lectores de
pantalla.

## Scope

**Dentro:**
- Configurar español como locale predeterminado en Laravel y en `.env.example`.
- Asociar errores de validación visibles con sus controles en login, creación, reprogramación y
  cancelación de citas.
- Exponer `aria-invalid` solo cuando el campo tenga un error y llevar el foco al primer campo
  inválido o al resumen de errores.
- Añadir pruebas focalizadas para idioma, asociaciones y foco.

**Fuera:**
- Otros hallazgos de la auditoría, como landmarks, tablas, contexto de enlaces y diseño responsive.
- Cambiar el locale de respaldo o traducir mensajes no relacionados con los formularios.

## Checklist

- [x] El locale predeterminado genera `<html lang="es">` — prueba de feature.
- [x] Cada error de campo queda asociado al control correspondiente en los cuatro formularios —
  pruebas de feature.
- [x] Con varios errores, el foco llega al primer campo inválido; con un error general, llega al
  resumen — prueba de navegador.
- [x] Sin errores, no se marca ningún campo ni se altera el foco inicial.
- [x] Las suites completas, el análisis estático, Pint y `git diff --check` pasan.

## Evidence

- `APP_KEY='base64:…' php artisan test` sobre las pruebas afectadas → 39 pruebas y 158 aserciones
  pasaron. La clave temporal se proporcionó solo por entorno; no se guardó en el repositorio.
- Prueba Playwright dirigida sobre primer campo, resumen general y foco inicial → pasó en Chromium.
- `APP_KEY='base64:…' php artisan test` → 73 pruebas totales: 72 pasaron y 1 quedó omitida;
  262 aserciones.
- `npm run test:e2e` → 3 pruebas pasaron.
- `composer analyse` → PHPStan pasó, 0 errores.
- `vendor/bin/pint --test` → pasó en el proyecto completo.
- `git diff --check` → pasó.
- `php artisan serve` con ajustes efímeros y la base SQLite sembrada de E2E → `/login` responde
  200 y declara `<html lang="es">`; no se cambió la configuración persistente.
- El análisis cruzado está en
  [analysis](../sdd/analysis/spanish-language-accessible-form-errors.md) y concluye PASS, sin
  hallazgos pendientes.
- `npx @ericrisco/rsc@4.1.8 review ledger panel spanish-language-accessible-form-errors/T001
  --lenses correctness,security,tests` → registro del panel creado. La revisión directa del diff no
  encontró bloqueos; los informes independientes de los tres refutadores siguen pendientes.
- `npx @ericrisco/rsc@4.1.8 risk` → **tier 2** por el alcance de autenticación; requiere spec
  vinculada a esta rama y aprobación humana.
- El usuario aprobó la especificación aclarada y eligió autopilot para las fases restantes.

## Next

La implementación y la verificación están completas en esta rama. Falta la revisión independiente
de tier 2. No hacer push, abrir PR ni fusionar sin aprobación humana explícita.

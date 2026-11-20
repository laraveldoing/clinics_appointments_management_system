---
type: spec
title: Evitar solapamientos de citas
status: implementada
---

# Evitar solapamientos de citas

## Contexto

La creación de citas valida la clínica, el paciente, el médico, que la fecha sea futura y la
duración, pero no comprueba si el paciente o el médico ya tienen otra cita en ese horario. Esta
feature añade esa comprobación sin cambiar el esquema actual ni incorporar un flujo de edición.

## Comportamiento acordado

- Se rechaza una cita nueva si su intervalo se solapa con una cita existente del mismo médico
  **o** paciente en la misma clínica.
- Los intervalos son semiabiertos: `[start_time, end_time)`. Dos citas pueden tocarse en un extremo
  sin entrar en conflicto.
- Solo las citas `scheduled` y `confirmed` reservan horario.
- Se ignoran las citas `cancelled`, `completed` y `no_show`, además de las borradas lógicamente.
- Si hay conflicto, se devuelve un error de validación en la fecha de inicio y no se escribe una
  nueva fila.
- La disponibilidad se comprueba y la cita se guarda en una transacción. Se bloquean los registros
  seleccionados del paciente y del médico en un orden consistente para serializar en PostgreSQL
  las altas concurrentes que comparten alguno de esos recursos.

## Alcance

**Incluye**

- Validación de disponibilidad al crear una cita.
- Pruebas de conflictos por médico y paciente, intervalos contiguos, estados ignorados, borrado
  lógico y ausencia de escrituras ante conflictos.
- Documentación breve de la regla de agenda.

**No incluye**

- Editar o reprogramar citas existentes.
- Horarios de apertura, márgenes configurables ni configuración de zonas horarias.
- Restricciones de exclusión específicas de una base de datos; CI también usa SQLite.

## Criterios de aceptación

- Se rechaza una cita que se solapa con otra activa del mismo médico.
- Se rechaza una cita que se solapa con otra activa del mismo paciente, aunque cambie el médico.
- Las citas de clínicas distintas no entran en conflicto.
- Se acepta una cita que empieza exactamente cuando termina otra.
- Las citas canceladas, completadas, no presentadas y borradas lógicamente no reservan horario.
- El rechazo no crea citas e informa del conflicto mediante un error de validación.
- Pasan las pruebas de la feature, la suite completa y Pint.

## Nota de implementación

La aplicación usa PostgreSQL en desarrollo y SQLite en CI. Las pruebas verifican los conflictos en
la base configurada para tests. Los bloqueos de filas serializan las altas en PostgreSQL; SQLite no
ofrece bloqueos de fila equivalentes para probar concurrencia.

## Evidencia

- `php artisan test` → 35 pruebas correctas, 122 aserciones.
- `vendor/bin/pint --test` → correcto.
- `git diff --check` → correcto.

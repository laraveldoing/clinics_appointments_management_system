---
author: José Raúl Miranda Prieto
branch: feature/create-appointment
status: in-progress
---
# Crear una cita desde la interfaz

> Rama apilada sobre `feature/appointments-list` (PR #3). Cuando #3 se fusione, el PR de esta
> feature se reorienta a `main`.

## Intent

El listado permite consultar la agenda, pero toda cita nace en el seeder o en la base de datos. El
personal de la clínica necesita registrar una cita nueva sin tocar la consola.

## Scope

**Dentro:**
- Botón «Nueva cita» en el listado.
- Formulario con paciente, doctor, fecha y hora de inicio, duración y motivo.
- Solo se ofrecen pacientes y doctores de la clínica del usuario.
- Validación: paciente y doctor de la misma clínica, doctor con rol `doctor`, inicio futuro,
  duración permitida.
- La cita se guarda con estado `scheduled` y `end_time` calculado.
- Mensaje de confirmación al volver al listado.

**Fuera:**
- Editar, cancelar o reprogramar citas.
- Detección de solapamientos entre citas.
- Zona horaria configurable por clínica.
- Notificaciones o recordatorios.
- Crear pacientes o doctores desde este formulario.

## Checklist

- [ ] Un usuario autenticado abre el formulario y ve solo pacientes/doctores de su clínica — prueba: test de contenido.
- [ ] Una cita válida se guarda con su clínica, estado y `end_time` calculado — prueba: `assertDatabaseHas`.
- [ ] Un paciente de otra clínica es rechazado — prueba: error de validación y cero filas.
- [ ] Un doctor de otra clínica o con rol distinto es rechazado — prueba: error de validación.
- [ ] Una fecha pasada es rechazada — prueba: error de validación.
- [ ] Un invitado no puede abrir ni enviar el formulario — prueba: redirección a login.
- [ ] Formato limpio — `vendor/bin/pint --test` sobre los archivos tocados.

## Evidence

_(se completa al ejecutar cada comprobación)_

## Next

Crear el Form Request y las rutas del formulario.

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

- [x] Un usuario autenticado abre el formulario y ve solo pacientes/doctores de su clínica — test `test_form_only_offers_patients_and_doctors_of_the_users_clinic`.
- [x] Una cita válida se guarda con su clínica, estado y `end_time` calculado — test `test_user_can_create_an_appointment_with_a_computed_end_time`.
- [x] Un paciente de otra clínica es rechazado — test `test_patient_from_another_clinic_is_rejected`.
- [x] Un doctor de otra clínica o con rol distinto es rechazado — tests `test_doctor_from_another_clinic_is_rejected` y `test_user_without_doctor_role_cannot_be_assigned`.
- [x] Una fecha pasada es rechazada — test `test_start_time_must_be_in_the_future`.
- [x] Un invitado no puede abrir ni enviar el formulario — test `test_guests_cannot_open_the_form_or_store_an_appointment`.
- [x] Formato limpio — `vendor/bin/pint --test` en verde sobre los archivos tocados.

## Evidence

- `php artisan test` → `{"result":"passed","tests":26,"passed":26,"assertions":95}`.
- `vendor/bin/pint --test` sobre controlador, Form Request, rutas y prueba → `passed`.
- `npx rsc risk --base origin/feature/appointments-list` → **tier 1** → un revisor de contexto fresco.
- Revisor de corrección → **0 hallazgos confirmados**, tras atacar tenancy cruzada, soft deletes, fechas, duración, fin de día, mass assignment y cobertura real de los tests.
- Observación aceptada: `datetime-local` envía hora local del navegador y la app usa UTC. La zona horaria configurable está fuera de este ciclo; se registra como límite conocido.

## Next

Abrir el pull request apilado sobre `feature/appointments-list`.


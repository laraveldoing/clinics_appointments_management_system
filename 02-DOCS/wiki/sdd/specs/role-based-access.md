---
type: spec
title: Autorización por rol para citas
description: Matriz aprobada para limitar la agenda y la creación de citas según el rol del usuario.
tags: [sdd, spec, authorization]
topic: sdd
status: implemented
branch: feature/role-based-access
---

# Autorización por rol para citas

## Contexto

Las rutas de citas exigen autenticación y limitan los registros a la clínica del usuario, pero
cualquier usuario autenticado puede ver toda la agenda de su clínica y crear citas. El modelo ya
define los roles `admin`, `doctor` y `receptionist`.

## Objetivos

- Aplicar mínimo privilegio a las pantallas y acciones de citas.
- Mantener el aislamiento entre clínicas en consultas y escrituras.
- Hacer cumplir la autorización en el servidor, incluso si se omite o manipula la interfaz.
- Centralizar las decisiones de acceso en una Policy de Laravel.

## Matriz de permisos aprobada

| Rol | Ver citas | Alcance del listado | Abrir y enviar creación |
|---|---|---|---|
| `admin` | Sí | Todas las citas de su clínica | Sí; puede asignar cualquier doctor de su clínica |
| `receptionist` | Sí | Todas las citas de su clínica | Sí; puede asignar cualquier doctor de su clínica |
| `doctor` | Sí | Solo citas asignadas a ese doctor | No |

Un usuario autenticado con rol doctor que intente abrir o enviar directamente el formulario recibe
una respuesta `403 Forbidden`. La interfaz no muestra el enlace «Nueva cita» a ese rol.

## Reglas de seguridad

- La clínica del usuario autenticado sigue siendo el límite principal de cada consulta.
- Para un doctor, el listado filtra además por su propio `user_id`.
- La clínica, el doctor y el rol no se aceptan como autoridad desde datos enviados por el cliente.
- La Policy autoriza tanto el acceso a la lista como la creación; la validación existente conserva
  sus reglas de pertenencia a clínica.
- Los invitados siguen usando el flujo actual de redirección al login.

## Fuera de alcance

- Crear, editar o eliminar usuarios y cambiar sus roles.
- Cuentas y permisos para pacientes.
- Edición, cancelación o reprogramación de citas.
- Permisos por recurso más allá del listado y la creación actuales.

## Criterios de aceptación

- Un admin autenticado ve las citas de su clínica y puede crear una cita asignando un doctor de esa
  clínica.
- Un receptionist autenticado tiene los mismos permisos de agenda que admin.
- Un doctor solo ve las citas asignadas a su propio usuario, aunque otro doctor pertenezca a la
  misma clínica.
- Un doctor recibe `403` al solicitar directamente el formulario o enviar el POST de creación.
- Un usuario de cualquier rol no ve ni crea citas de otra clínica.
- Una solicitud manipulada no puede ampliar permisos cambiando IDs, rol o clínica en el payload.
- La interfaz muestra el enlace de creación solo a admin y receptionist.
- Los invitados conservan el comportamiento actual y son redirigidos al login.
- Pruebas adversariales cubren cada permiso y sus bypasses directos; CI y Pint pasan.

## Decisiones aprobadas

- Admin y receptionist comparten el permiso para crear citas y consultar toda la
  agenda de su clínica.
- Los doctores solo consultan sus citas asignadas y no crean citas desde este flujo.
- El listado contiene información clínica de pacientes y las operaciones actuales
  permiten asignar citas a cualquier doctor de la clínica.

## Evidencia

- `AppointmentAuthorizationTest`: doctores ven solo sus citas y reciben `403` al intentar crear;
  admins crean citas y ven el enlace de creación.
- `AppointmentListTest`: receptionist ve la agenda de su clínica, no la de otra clínica, y conserva
  el enlace de creación.
- Suite completa: 49 pruebas y 169 aserciones en verde.
- `vendor/bin/pint --test`: en verde.

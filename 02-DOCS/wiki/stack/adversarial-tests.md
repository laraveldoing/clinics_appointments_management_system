# Pruebas adversariales

## Objetivo

Las pruebas adversariales validan que la aplicación no se rompe ante entradas manipuladas, accesos cruzados, datos inconsistentes o rutas no autorizadas. Su misión es reforzar el principio de seguridad por defecto y mantener la integridad del flujo clínico.

## Qué cubren en este proyecto

En la app actual, las pruebas adversariales cubren especialmente:

- acceso cruzado entre clínicas
- pacientes o médicos borrados lógicamente
- acceso no autenticado a rutas protegidas
- fechas inválidas o exactamente en el momento actual
- duraciones fuera de rango
- IDs no numéricos o maliciosamente manipulados
- rechazo de `user_id` ajeno o no permitido

## Cómo se mantienen

1. Añadir una prueba nueva cada vez que se introduzca una regla de negocio nueva o un nuevo punto de entrada.
2. Mantener los tests centrados en comportamiento real, no en implementación interna.
3. Priorizar casos donde el atacante puede manipular el cliente: IDs, fechas, roles y clinic_id.
4. Ejecutar la suite en CI junto con el resto de feature tests.

## Convención recomendada

- Archivo: `tests/Feature/AdversarialAccessTest.php`
- Nombre de métodos: `test_..._is_rejected` o `test_..._is_blocked`
- Enfocar pruebas en “rechazo”, “bloqueo” y “no escritura” para asegurar que el sistema no crea datos inválidos.

## Reglas mínimas a mantener

- Un usuario no puede acceder a datos de otra clínica.
- Un invitado no puede abrir ni enviar formularios protegidos.
- Un paciente o doctor soft-deleted no puede ser usado en una nueva cita.
- Una cita no puede crearse con una fecha pasada o igual al instante actual.
- La duración debe estar siempre dentro de los valores permitidos.
- El sistema debe rechazar cualquier ID que no cumpla el tipo y la pertenencia esperados.

## Línea de mantenimiento futura

Cuando el proyecto crezca, conviene ampliar esta batería con:

- CSRF y override de session
- payloads con HTML o caracteres especiales en `reason`
- validación de rutas con cambios de método HTTP
- edge cases de timezone y perfil de usuario
- pruebas de “lateral movement” entre clínicas y roles

## Regla de oro

Si una funcionalidad puede ser manipulada por el cliente o por un usuario no autorizado, debe tener al menos un caso adversarial que la cubra.

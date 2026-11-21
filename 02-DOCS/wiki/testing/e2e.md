# Pruebas End-to-End

El proyecto usa Playwright en Chromium para probar flujos completos en el navegador.

## Flujo cubierto

- Inicio de sesión de una recepcionista.
- Apertura del formulario de nueva cita.
- Selección de paciente y doctor.
- Introducción de fecha, duración y motivo.
- Verificación del mensaje de éxito y de los datos guardados en el listado.

## Ejecución

```bash
npx playwright install chromium
npm run test:e2e
```

Playwright crea una base SQLite temporal, ejecuta las migraciones y carga los datos demo antes de
iniciar Laravel. Las pruebas generan artefactos bajo `test-results/` y `playwright-report/`;
ambas rutas están ignoradas por Git.

La CI ejecuta el navegador en el job `e2e` con PHP 8.4 y Chromium.

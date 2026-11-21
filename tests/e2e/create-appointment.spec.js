import { expect, test } from '@playwright/test';

test('a receptionist creates and sees an appointment', async ({ page }) => {
    await page.goto('/login');

    await page.getByLabel('Correo electrónico').fill('recepcionista@clinica.test');
    await page.getByLabel('Contraseña').fill('password');
    await page.getByRole('button', { name: 'Entrar' }).click();

    await expect(page).toHaveURL(/\/appointments$/);
    await page.getByRole('link', { name: 'Nueva cita' }).click();
    await expect(page).toHaveURL(/\/appointments\/create$/);

    await page.getByLabel('Paciente').selectOption({ label: 'Paciente E2E' });
    await page.getByLabel('Doctor').selectOption({ label: 'Doctor Demo' });

    const start = new Date();
    start.setDate(start.getDate() + 7);
    const startTime = `${start.getFullYear()}-${String(start.getMonth() + 1).padStart(2, '0')}-${String(start.getDate()).padStart(2, '0')}T16:30`;

    await page.getByLabel('Fecha y hora').fill(startTime);
    await page.getByLabel('Duración').selectOption('30');
    await page.getByLabel('Motivo').fill('Prueba E2E automatizada');
    await page.getByRole('button', { name: 'Guardar cita' }).click();

    await expect(page).toHaveURL(/\/appointments$/);
    await expect(page.getByRole('status')).toContainText('Cita creada correctamente.');
    await expect(page.getByText('Paciente E2E')).toBeVisible();
    await expect(page.getByRole('cell', { name: 'Doctor Demo' }).first()).toBeVisible();
    await expect(page.getByText('Prueba E2E automatizada')).toBeVisible();
});

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

test('a receptionist can reschedule and cancel an appointment', async ({ page }) => {
    await page.goto('/login');

    await page.getByLabel('Correo electrónico').fill('recepcionista@clinica.test');
    await page.getByLabel('Contraseña').fill('password');
    await page.getByRole('button', { name: 'Entrar' }).click();

    await page.getByRole('link', { name: 'Nueva cita' }).click();
    await page.getByLabel('Paciente').selectOption({ label: 'Paciente E2E' });
    await page.getByLabel('Doctor').selectOption({ label: 'Doctor Demo' });

    const initialDate = new Date();
    initialDate.setDate(initialDate.getDate() + 8);
    const initialStart = `${initialDate.getFullYear()}-${String(initialDate.getMonth() + 1).padStart(2, '0')}-${String(initialDate.getDate()).padStart(2, '0')}T16:30`;

    await page.getByLabel('Fecha y hora').fill(initialStart);
    await page.getByLabel('Duración').selectOption('30');
    await page.getByLabel('Motivo').fill('Cita lifecycle E2E');
    await page.getByRole('button', { name: 'Guardar cita' }).click();

    const appointmentRow = page.getByRole('row').filter({ hasText: 'Cita lifecycle E2E' });
    await expect(appointmentRow).toBeVisible();
    await appointmentRow.getByRole('link', { name: 'Reprogramar' }).click();

    const rescheduledDate = new Date();
    rescheduledDate.setDate(rescheduledDate.getDate() + 10);
    const rescheduledStart = `${rescheduledDate.getFullYear()}-${String(rescheduledDate.getMonth() + 1).padStart(2, '0')}-${String(rescheduledDate.getDate()).padStart(2, '0')}T10:15`;

    await page.getByLabel('Nueva fecha y hora').fill(rescheduledStart);
    await page.getByLabel('Duración').selectOption('45');
    await page.getByRole('button', { name: 'Guardar cambios' }).click();

    await expect(page.getByRole('status')).toContainText('Cita reprogramada correctamente.');
    await expect(appointmentRow).toContainText('Programada');
    await appointmentRow.getByRole('link', { name: 'Cancelar' }).click();

    await page.getByLabel('Nota (opcional)').fill('Cancelación comprobada por Playwright.');
    await page.getByRole('button', { name: 'Confirmar cancelación' }).click();

    await expect(page.getByRole('status')).toContainText('Cita cancelada correctamente.');
    await expect(appointmentRow).toContainText('Cancelada');
    await expect(appointmentRow).toContainText('Cancelación comprobada por Playwright.');
});

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

test('multiple appointment errors focus the first invalid field and general errors focus the summary', async ({ page }) => {
    await page.goto('/login');
    await expect(page.getByLabel('Correo electrónico')).toBeFocused();

    await page.getByLabel('Correo electrónico').fill('recepcionista@clinica.test');
    await page.getByLabel('Contraseña').fill('password');
    await page.getByRole('button', { name: 'Entrar' }).click();

    await page.getByRole('link', { name: 'Nueva cita' }).click();
    const patient = page.getByLabel('Paciente');
    await patient.evaluate(select => select.add(new Option('Paciente inexistente', '0')));
    await patient.selectOption('0');
    await page.getByLabel('Doctor').selectOption({ label: 'Doctor Demo' });

    const past = new Date();
    past.setDate(past.getDate() - 1);
    const pastStart = `${past.getFullYear()}-${String(past.getMonth() + 1).padStart(2, '0')}-${String(past.getDate()).padStart(2, '0')}T16:30`;

    await page.getByLabel('Fecha y hora').fill(pastStart);
    await page.getByLabel('Duración').selectOption('30');
    await page.getByRole('button', { name: 'Guardar cita' }).click();

    await expect(page).toHaveURL(/\/appointments\/create$/);
    await expect(patient).toHaveAttribute('aria-invalid', 'true');
    await expect(page.getByLabel('Fecha y hora')).toHaveAttribute('aria-invalid', 'true');
    await expect(patient).toHaveAttribute('aria-describedby', 'patient_id-error');
    await expect(page.locator('#patient_id-error')).toBeVisible();
    await expect(page.locator('#start_time-error')).toBeVisible();
    await expect.poll(() => page.evaluate(() => document.activeElement?.id)).toBe('patient_id');

    let fallbackResponseServed = false;
    await page.route('**/appointments', async route => {
        if (route.request().method() !== 'POST') {
            await route.continue();
            return;
        }

        const response = await route.fetch();
        const html = await response.text();
        const summaryOnlyHtml = html
            .replace(/\s+aria-invalid="true"/g, '')
            .replace(/\s+aria-describedby="[^"]+-error"/g, '')
            .replace(/<p id="[^"]+-error"[^>]*>[\s\S]*?<\/p>/g, '')
            .replace('Revisa los campos marcados.', 'No se pudo procesar la cita.');
        expect(summaryOnlyHtml).not.toBe(html);
        fallbackResponseServed = true;
        await route.fulfill({
            status: response.status(),
            contentType: response.headers()['content-type'],
            body: summaryOnlyHtml,
        });
    });

    await patient.evaluate(select => select.add(new Option('Paciente inexistente', '0')));
    await patient.selectOption('0');
    await expect(patient).toHaveValue('0');
    const appointmentForm = page.locator('form').filter({
        has: page.getByRole('button', { name: 'Guardar cita' }),
    });
    expect(await appointmentForm.evaluate(form => form.checkValidity())).toBe(true);
    await page.getByRole('button', { name: 'Guardar cita' }).click();

    const startTime = page.getByLabel('Fecha y hora');
    expect(fallbackResponseServed).toBe(true);
    await expect(page.locator('[aria-invalid="true"]')).toHaveCount(0);
    await expect(page.locator('[aria-describedby$="-error"]')).toHaveCount(0);
    await expect(page.locator('[data-error-summary]')).toContainText('No se pudo procesar la cita.');
    await expect(page.locator('[data-error-summary]')).toBeFocused();
    await expect(startTime).not.toHaveAttribute('aria-invalid', 'true');
});

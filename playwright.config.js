import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
    testDir: './tests/e2e',
    fullyParallel: false,
    forbidOnly: true,
    retries: 0,
    reporter: 'line',
    use: {
        baseURL: 'http://127.0.0.1:8000',
        trace: 'retain-on-failure',
        ...devices['Desktop Chrome'],
    },
    webServer: {
        command: "rm -f /tmp/clinics-e2e.sqlite && touch /tmp/clinics-e2e.sqlite && export APP_ENV=testing APP_KEY='base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=' DB_CONNECTION=sqlite DB_DATABASE=/tmp/clinics-e2e.sqlite SESSION_DRIVER=file CACHE_STORE=array QUEUE_CONNECTION=sync && php artisan migrate:fresh --seed --force && php artisan serve --host=127.0.0.1 --port=8000",
        url: 'http://127.0.0.1:8000/login',
        reuseExistingServer: false,
        timeout: 120_000,
    },
});

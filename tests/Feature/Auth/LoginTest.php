<?php

namespace Tests\Feature\Auth;

use App\Models\Clinic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('Iniciar sesión');
        $response->assertSee('<html lang="es">', false);
        $response->assertDontSee('aria-invalid', false);
        $response->assertDontSee('data-error-summary', false);
        $response->assertDontSee('document.querySelector', false);
    }

    public function test_users_can_authenticate_with_valid_credentials(): void
    {
        $user = User::factory()->for(Clinic::factory())->create([
            'email' => 'doctor@clinica.test',
            'password' => 'secret-password',
        ]);

        $response = $this->post('/login', [
            'email' => 'doctor@clinica.test',
            'password' => 'secret-password',
        ]);

        $response->assertRedirect(route('appointments.index'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_users_cannot_authenticate_with_invalid_password(): void
    {
        User::factory()->for(Clinic::factory())->create([
            'email' => 'doctor@clinica.test',
            'password' => 'secret-password',
        ]);

        $response = $this->from('/login')->followingRedirects()->post('/login', [
            'email' => 'doctor@clinica.test',
            'password' => 'wrong-password',
        ]);

        $response->assertOk();
        $response->assertSee('Las credenciales no coinciden con nuestros registros.');
        $response->assertSee('aria-invalid="true"', false);
        $response->assertSee('aria-describedby="email-error"', false);
        $response->assertSee('id="email-error"', false);
        $response->assertSee('value="doctor@clinica.test"', false);
        $this->assertGuest();
    }

    public function test_password_validation_error_is_associated_with_its_field(): void
    {
        $response = $this->from('/login')->followingRedirects()->post('/login', [
            'email' => 'doctor@clinica.test',
            'password' => '',
        ]);

        $response->assertOk();
        $response->assertSee('Indica tu contraseña.');
        $response->assertSee('aria-invalid="true"', false);
        $response->assertSee('aria-describedby="password-error"', false);
        $response->assertSee('id="password-error"', false);
        $this->assertGuest();
    }

    public function test_unknown_email_gets_the_same_generic_error(): void
    {
        $response = $this->from('/login')->post('/login', [
            'email' => 'nadie@clinica.test',
            'password' => 'whatever',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors([
            'email' => 'Las credenciales no coinciden con nuestros registros.',
        ]);
        $this->assertGuest();
    }

    public function test_login_attempts_are_rate_limited(): void
    {
        User::factory()->for(Clinic::factory())->create([
            'email' => 'doctor@clinica.test',
            'password' => 'secret-password',
        ]);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->from('/login')->post('/login', [
                'email' => 'doctor@clinica.test',
                'password' => 'wrong-password',
            ])->assertRedirect('/login');
        }

        $this->from('/login')->post('/login', [
            'email' => 'doctor@clinica.test',
            'password' => 'wrong-password',
        ])->assertStatus(429);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->for(Clinic::factory())->create();

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }
}

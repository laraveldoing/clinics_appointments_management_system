<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdversarialAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_cross_clinic_patient_tampering_is_rejected(): void
    {
        $clinic = Clinic::factory()->create();
        $otherClinic = Clinic::factory()->create();

        $user = User::factory()->receptionist()->for($clinic)->create();
        $doctor = User::factory()->doctor()->for($clinic)->create();
        $foreignPatient = Patient::factory()->for($otherClinic)->create();

        $this->actingAs($user)
            ->post('/appointments', [
                'patient_id' => $foreignPatient->id,
                'user_id' => $doctor->id,
                'start_time' => now()->addDay()->format('Y-m-d\TH:i'),
                'duration_minutes' => 30,
                'reason' => 'Intento de acceso cruzado',
            ])
            ->assertSessionHasErrors('patient_id');

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_soft_deleted_patient_is_rejected(): void
    {
        $clinic = Clinic::factory()->create();
        $user = User::factory()->receptionist()->for($clinic)->create();
        $doctor = User::factory()->doctor()->for($clinic)->create();
        $patient = Patient::factory()->for($clinic)->create();

        $patient->delete();

        $this->actingAs($user)
            ->post('/appointments', [
                'patient_id' => $patient->id,
                'user_id' => $doctor->id,
                'start_time' => now()->addDay()->format('Y-m-d\TH:i'),
                'duration_minutes' => 30,
                'reason' => 'Paciente borrado lógicamente',
            ])
            ->assertSessionHasErrors('patient_id');

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_soft_deleted_doctor_is_rejected(): void
    {
        $clinic = Clinic::factory()->create();
        $user = User::factory()->receptionist()->for($clinic)->create();
        $patient = Patient::factory()->for($clinic)->create();
        $doctor = User::factory()->doctor()->for($clinic)->create();

        $doctor->delete();

        $this->actingAs($user)
            ->post('/appointments', [
                'patient_id' => $patient->id,
                'user_id' => $doctor->id,
                'start_time' => now()->addDay()->format('Y-m-d\TH:i'),
                'duration_minutes' => 30,
                'reason' => 'Doctor borrado lógicamente',
            ])
            ->assertSessionHasErrors('user_id');

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_cross_clinic_doctor_tampering_is_rejected(): void
    {
        $clinic = Clinic::factory()->create();
        $otherClinic = Clinic::factory()->create();

        $user = User::factory()->receptionist()->for($clinic)->create();
        $patient = Patient::factory()->for($clinic)->create();
        $foreignDoctor = User::factory()->doctor()->for($otherClinic)->create();

        $this->actingAs($user)
            ->post('/appointments', [
                'patient_id' => $patient->id,
                'user_id' => $foreignDoctor->id,
                'start_time' => now()->addDay()->format('Y-m-d\TH:i'),
                'duration_minutes' => 30,
                'reason' => 'Intento de doctor ajeno',
            ])
            ->assertSessionHasErrors('user_id');

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_malformed_start_times_are_rejected(): void
    {
        $clinic = Clinic::factory()->create();
        $user = User::factory()->receptionist()->for($clinic)->create();
        $doctor = User::factory()->doctor()->for($clinic)->create();
        $patient = Patient::factory()->for($clinic)->create();

        $this->actingAs($user)
            ->post('/appointments', [
                'patient_id' => $patient->id,
                'user_id' => $doctor->id,
                'start_time' => 'not-a-date',
                'duration_minutes' => 30,
                'reason' => 'Fecha inválida',
            ])
            ->assertSessionHasErrors('start_time');

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_reasons_longer_than_the_limit_are_rejected(): void
    {
        $clinic = Clinic::factory()->create();
        $user = User::factory()->receptionist()->for($clinic)->create();
        $doctor = User::factory()->doctor()->for($clinic)->create();
        $patient = Patient::factory()->for($clinic)->create();

        $this->actingAs($user)
            ->post('/appointments', [
                'patient_id' => $patient->id,
                'user_id' => $doctor->id,
                'start_time' => now()->addDay()->format('Y-m-d\TH:i'),
                'duration_minutes' => 30,
                'reason' => str_repeat('x', 2001),
            ])
            ->assertSessionHasErrors('reason');

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_exact_current_time_is_rejected_because_the_appointment_must_be_in_the_future(): void
    {
        $clinic = Clinic::factory()->create();
        $user = User::factory()->receptionist()->for($clinic)->create();
        $doctor = User::factory()->doctor()->for($clinic)->create();
        $patient = Patient::factory()->for($clinic)->create();

        $this->actingAs($user)
            ->post('/appointments', [
                'patient_id' => $patient->id,
                'user_id' => $doctor->id,
                'start_time' => now()->format('Y-m-d\TH:i'),
                'duration_minutes' => 30,
                'reason' => 'Hora exacta no permitida',
            ])
            ->assertSessionHasErrors('start_time');

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_non_numeric_or_disallowed_durations_are_rejected(): void
    {
        $clinic = Clinic::factory()->create();
        $user = User::factory()->receptionist()->for($clinic)->create();
        $doctor = User::factory()->doctor()->for($clinic)->create();
        $patient = Patient::factory()->for($clinic)->create();

        $this->actingAs($user)
            ->post('/appointments', [
                'patient_id' => $patient->id,
                'user_id' => $doctor->id,
                'start_time' => now()->addDay()->format('Y-m-d\TH:i'),
                'duration_minutes' => 999,
                'reason' => 'Duración imposible',
            ])
            ->assertSessionHasErrors('duration_minutes');

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_non_numeric_ids_are_rejected_before_any_database_write(): void
    {
        $clinic = Clinic::factory()->create();
        $user = User::factory()->receptionist()->for($clinic)->create();
        $doctor = User::factory()->doctor()->for($clinic)->create();

        $this->actingAs($user)
            ->post('/appointments', [
                'patient_id' => 'abc',
                'user_id' => 'def',
                'start_time' => now()->addDay()->format('Y-m-d\TH:i'),
                'duration_minutes' => 30,
                'reason' => 'IDs inválidos',
            ])
            ->assertSessionHasErrors(['patient_id', 'user_id']);

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_guest_users_are_blocked_from_protected_appointment_routes_even_with_tampered_payloads(): void
    {
        $this->post('/appointments', [
            'patient_id' => 1,
            'user_id' => 1,
            'start_time' => now()->addDay()->format('Y-m-d\TH:i'),
            'duration_minutes' => 30,
        ])->assertRedirect(route('login'));

        $this->get('/appointments/create')->assertRedirect(route('login'));
    }
}

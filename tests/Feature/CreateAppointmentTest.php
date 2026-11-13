<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateAppointmentTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(Patient $patient, User $doctor, ?string $start = null): array
    {
        return [
            'patient_id' => $patient->id,
            'user_id' => $doctor->id,
            'start_time' => $start ?? now()->addDay()->format('Y-m-d\TH:i'),
            'duration_minutes' => 30,
            'reason' => 'Consulta de control',
        ];
    }

    public function test_guests_cannot_open_the_form_or_store_an_appointment(): void
    {
        $this->get('/appointments/create')->assertRedirect(route('login'));
        $this->post('/appointments', [])->assertRedirect(route('login'));
    }

    public function test_form_only_offers_patients_and_doctors_of_the_users_clinic(): void
    {
        $clinic = Clinic::factory()->create();
        $otherClinic = Clinic::factory()->create();

        $receptionist = User::factory()->receptionist()->for($clinic)->create();

        Patient::factory()->for($clinic)->create(['first_name' => 'Ana', 'last_name' => 'Propia']);
        User::factory()->doctor()->for($clinic)->create(['name' => 'Dra. Elena Ruiz']);

        Patient::factory()->for($otherClinic)->create(['first_name' => 'Bruno', 'last_name' => 'Ajena']);
        User::factory()->doctor()->for($otherClinic)->create(['name' => 'Dr. Ajeno']);

        $response = $this->actingAs($receptionist)->get('/appointments/create');

        $response->assertOk();
        $response->assertSee('Ana Propia');
        $response->assertSee('Dra. Elena Ruiz');
        $response->assertDontSee('Bruno Ajena');
        $response->assertDontSee('Dr. Ajeno');
    }

    public function test_user_can_create_an_appointment_with_a_computed_end_time(): void
    {
        $clinic = Clinic::factory()->create();
        $user = User::factory()->receptionist()->for($clinic)->create();
        $doctor = User::factory()->doctor()->for($clinic)->create();
        $patient = Patient::factory()->for($clinic)->create();

        $start = now()->addDay()->setTime(9, 0);
        $payload = $this->validPayload($patient, $doctor, $start->format('Y-m-d\TH:i'));

        $response = $this->actingAs($user)->post('/appointments', $payload);

        $response->assertRedirect(route('appointments.index'));
        $response->assertSessionHas('status', 'Cita creada correctamente.');

        $this->assertDatabaseHas('appointments', [
            'clinic_id' => $clinic->id,
            'patient_id' => $patient->id,
            'user_id' => $doctor->id,
            'status' => 'scheduled',
            'reason' => 'Consulta de control',
            'start_time' => $start->format('Y-m-d H:i:s'),
            'end_time' => $start->copy()->addMinutes(30)->format('Y-m-d H:i:s'),
        ]);
    }

    public function test_patient_from_another_clinic_is_rejected(): void
    {
        $clinic = Clinic::factory()->create();
        $otherClinic = Clinic::factory()->create();

        $user = User::factory()->receptionist()->for($clinic)->create();
        $doctor = User::factory()->doctor()->for($clinic)->create();
        $foreignPatient = Patient::factory()->for($otherClinic)->create();

        $response = $this->actingAs($user)->post('/appointments', $this->validPayload($foreignPatient, $doctor));

        $response->assertSessionHasErrors('patient_id');
        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_doctor_from_another_clinic_is_rejected(): void
    {
        $clinic = Clinic::factory()->create();
        $otherClinic = Clinic::factory()->create();

        $user = User::factory()->receptionist()->for($clinic)->create();
        $patient = Patient::factory()->for($clinic)->create();
        $foreignDoctor = User::factory()->doctor()->for($otherClinic)->create();

        $response = $this->actingAs($user)->post('/appointments', $this->validPayload($patient, $foreignDoctor));

        $response->assertSessionHasErrors('user_id');
        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_user_without_doctor_role_cannot_be_assigned(): void
    {
        $clinic = Clinic::factory()->create();
        $user = User::factory()->receptionist()->for($clinic)->create();
        $patient = Patient::factory()->for($clinic)->create();
        $receptionist = User::factory()->receptionist()->for($clinic)->create();

        $response = $this->actingAs($user)->post('/appointments', $this->validPayload($patient, $receptionist));

        $response->assertSessionHasErrors('user_id');
        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_start_time_must_be_in_the_future(): void
    {
        $clinic = Clinic::factory()->create();
        $user = User::factory()->receptionist()->for($clinic)->create();
        $doctor = User::factory()->doctor()->for($clinic)->create();
        $patient = Patient::factory()->for($clinic)->create();

        $past = now()->subDay();

        $response = $this->actingAs($user)->post('/appointments', $this->validPayload($patient, $doctor, $past->format('Y-m-d\TH:i')));

        $response->assertSessionHasErrors('start_time');
        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_duration_must_be_one_of_the_allowed_values(): void
    {
        $clinic = Clinic::factory()->create();
        $user = User::factory()->receptionist()->for($clinic)->create();
        $doctor = User::factory()->doctor()->for($clinic)->create();
        $patient = Patient::factory()->for($clinic)->create();

        $payload = $this->validPayload($patient, $doctor);
        $payload['duration_minutes'] = 20;

        $response = $this->actingAs($user)->post('/appointments', $payload);

        $response->assertSessionHasErrors('duration_minutes');
        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_reason_is_optional(): void
    {
        $clinic = Clinic::factory()->create();
        $user = User::factory()->receptionist()->for($clinic)->create();
        $doctor = User::factory()->doctor()->for($clinic)->create();
        $patient = Patient::factory()->for($clinic)->create();

        $payload = $this->validPayload($patient, $doctor);
        $payload['reason'] = null;

        $response = $this->actingAs($user)->post('/appointments', $payload);

        $response->assertRedirect(route('appointments.index'));
        $this->assertDatabaseHas('appointments', [
            'patient_id' => $patient->id,
            'reason' => null,
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_doctors_only_see_their_appointments_and_do_not_see_the_create_link(): void
    {
        $clinic = Clinic::factory()->create();
        $doctor = User::factory()->doctor()->for($clinic)->create();
        $colleague = User::factory()->doctor()->for($clinic)->create();
        $ownPatient = Patient::factory()->for($clinic)->create(['first_name' => 'Propia']);
        $colleaguePatient = Patient::factory()->for($clinic)->create(['first_name' => 'Colega']);

        Appointment::factory()->create([
            'clinic_id' => $clinic->id,
            'patient_id' => $ownPatient->id,
            'user_id' => $doctor->id,
        ]);
        Appointment::factory()->create([
            'clinic_id' => $clinic->id,
            'patient_id' => $colleaguePatient->id,
            'user_id' => $colleague->id,
        ]);

        $response = $this->actingAs($doctor)->get('/appointments');

        $response->assertOk();
        $response->assertSee($ownPatient->full_name);
        $response->assertDontSee($colleaguePatient->full_name);
        $response->assertDontSee(route('appointments.create'));
    }

    public function test_doctors_see_a_personal_empty_state_when_they_have_no_assigned_appointments(): void
    {
        $doctor = User::factory()->doctor()->for(Clinic::factory())->create();

        $response = $this->actingAs($doctor)->get('/appointments');

        $response->assertOk();
        $response->assertSee('No tienes citas asignadas.');
    }

    public function test_doctors_cannot_open_or_submit_the_appointment_creation_form(): void
    {
        $clinic = Clinic::factory()->create();
        $doctor = User::factory()->doctor()->for($clinic)->create();
        $patient = Patient::factory()->for($clinic)->create();

        $this->actingAs($doctor)
            ->get('/appointments/create')
            ->assertForbidden();

        $this->actingAs($doctor)
            ->post('/appointments', [
                'patient_id' => $patient->id,
                'user_id' => $doctor->id,
                'start_time' => now()->addDay()->format('Y-m-d\TH:i'),
                'duration_minutes' => 30,
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_admins_can_create_appointments_for_doctors_in_their_clinic(): void
    {
        $clinic = Clinic::factory()->create();
        $admin = User::factory()->admin()->for($clinic)->create();
        $doctor = User::factory()->doctor()->for($clinic)->create();
        $patient = Patient::factory()->for($clinic)->create();

        $response = $this->actingAs($admin)->post('/appointments', [
            'patient_id' => $patient->id,
            'user_id' => $doctor->id,
            'start_time' => now()->addDay()->format('Y-m-d\TH:i'),
            'duration_minutes' => 30,
        ]);

        $response->assertRedirect(route('appointments.index'));
        $this->assertDatabaseHas('appointments', [
            'clinic_id' => $clinic->id,
            'patient_id' => $patient->id,
            'user_id' => $doctor->id,
        ]);

        $this->actingAs($admin)
            ->get('/appointments')
            ->assertOk()
            ->assertSee(route('appointments.create'));
    }
}

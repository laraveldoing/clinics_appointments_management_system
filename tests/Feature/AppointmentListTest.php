<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentListTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/appointments')->assertRedirect(route('login'));
    }

    public function test_authenticated_users_are_sent_from_the_home_page_to_the_list(): void
    {
        $user = User::factory()->for(Clinic::factory())->create();

        $this->actingAs($user)->get('/')->assertRedirect(route('appointments.index'));
    }

    public function test_users_see_every_appointment_of_their_clinic_but_not_other_clinics(): void
    {
        $ownClinic = Clinic::factory()->create();
        $otherClinic = Clinic::factory()->create();

        $user = User::factory()->for($ownClinic)->create();
        $colleague = User::factory()->for($ownClinic)->create();
        $otherUser = User::factory()->for($otherClinic)->create();

        $ownPatient = Patient::factory()->for($ownClinic)->create([
            'first_name' => 'Ana',
            'last_name' => 'Propia',
        ]);
        $colleaguePatient = Patient::factory()->for($ownClinic)->create([
            'first_name' => 'Diego',
            'last_name' => 'Colega',
        ]);
        $otherPatient = Patient::factory()->for($otherClinic)->create([
            'first_name' => 'Bruno',
            'last_name' => 'Ajena',
        ]);

        Appointment::factory()->create([
            'clinic_id' => $ownClinic->id,
            'patient_id' => $ownPatient->id,
            'user_id' => $user->id,
        ]);

        Appointment::factory()->create([
            'clinic_id' => $ownClinic->id,
            'patient_id' => $colleaguePatient->id,
            'user_id' => $colleague->id,
        ]);

        Appointment::factory()->create([
            'clinic_id' => $otherClinic->id,
            'patient_id' => $otherPatient->id,
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($user)->get('/appointments');

        $response->assertOk();
        $response->assertSee('Ana Propia');
        $response->assertSee('Diego Colega');
        $response->assertDontSee('Bruno Ajena');
    }

    public function test_list_shows_every_column_of_the_contract(): void
    {
        $clinic = Clinic::factory()->create();
        $user = User::factory()->for($clinic)->create(['name' => 'Dra. Elena Ruiz']);
        $patient = Patient::factory()->for($clinic)->create(['first_name' => 'Carla', 'last_name' => 'Única']);

        Appointment::factory()->create([
            'clinic_id' => $clinic->id,
            'patient_id' => $patient->id,
            'user_id' => $user->id,
            'start_time' => '2026-11-09 09:15:00',
            'end_time' => '2026-11-09 09:45:00',
            'status' => 'confirmed',
            'reason' => 'Primera consulta',
        ]);

        $response = $this->actingAs($user)->get('/appointments');

        $response->assertOk();
        $response->assertSee('09/11/2026 09:15');
        $response->assertSee('Carla Única');
        $response->assertSee('Dra. Elena Ruiz');
        $response->assertSee('Confirmada');
        $response->assertSee('Primera consulta');
    }

    public function test_appointments_are_listed_in_chronological_order(): void
    {
        $clinic = Clinic::factory()->create();
        $user = User::factory()->for($clinic)->create();
        $patient = Patient::factory()->for($clinic)->create(['first_name' => 'Carla', 'last_name' => 'Única']);

        Appointment::factory()->create([
            'clinic_id' => $clinic->id,
            'patient_id' => $patient->id,
            'user_id' => $user->id,
            'start_time' => '2026-11-10 16:00:00',
            'end_time' => '2026-11-10 16:30:00',
            'reason' => 'Revisión posterior',
        ]);

        Appointment::factory()->create([
            'clinic_id' => $clinic->id,
            'patient_id' => $patient->id,
            'user_id' => $user->id,
            'start_time' => '2026-11-09 09:00:00',
            'end_time' => '2026-11-09 09:30:00',
            'reason' => 'Primera consulta',
        ]);

        $response = $this->actingAs($user)->get('/appointments');

        $response->assertOk();
        $response->assertSeeInOrder(['Primera consulta', 'Revisión posterior']);
    }

    public function test_list_shows_the_empty_state_when_there_are_no_appointments(): void
    {
        $user = User::factory()->for(Clinic::factory())->create();

        $response = $this->actingAs($user)->get('/appointments');

        $response->assertOk();
        $response->assertSee('No hay citas registradas en tu clínica.');
    }

    public function test_list_is_paginated_and_links_to_the_next_page(): void
    {
        $clinic = Clinic::factory()->create();
        $user = User::factory()->for($clinic)->create();
        $patient = Patient::factory()->for($clinic)->create();

        foreach (range(1, 16) as $number) {
            Appointment::factory()->create([
                'clinic_id' => $clinic->id,
                'patient_id' => $patient->id,
                'user_id' => $user->id,
                'start_time' => now()->addMinutes($number),
                'end_time' => now()->addMinutes($number + 30),
                'reason' => 'Cita número '.$number,
            ]);
        }

        $firstPage = $this->actingAs($user)->get('/appointments');

        $firstPage->assertOk();
        $firstPage->assertSee('Cita número 1');
        $firstPage->assertDontSee('Cita número 16');
        $firstPage->assertSee('page=2', false);

        $secondPage = $this->actingAs($user)->get('/appointments?page=2');

        $secondPage->assertOk();
        $secondPage->assertSee('Cita número 16');
        $secondPage->assertDontSee('No hay citas registradas en tu clínica.');
    }

    public function test_appointment_without_reason_shows_a_dash(): void
    {
        $clinic = Clinic::factory()->create();
        $user = User::factory()->for($clinic)->create();
        $patient = Patient::factory()->for($clinic)->create();

        Appointment::factory()->create([
            'clinic_id' => $clinic->id,
            'patient_id' => $patient->id,
            'user_id' => $user->id,
            'reason' => null,
        ]);

        $response = $this->actingAs($user)->get('/appointments');

        $response->assertOk();
        $response->assertSee('—');
    }

    public function test_soft_deleted_patient_does_not_break_the_list(): void
    {
        $clinic = Clinic::factory()->create();
        $user = User::factory()->for($clinic)->create();
        $patient = Patient::factory()->for($clinic)->create();

        Appointment::factory()->create([
            'clinic_id' => $clinic->id,
            'patient_id' => $patient->id,
            'user_id' => $user->id,
        ]);

        $patient->delete();

        $response = $this->actingAs($user)->get('/appointments');

        $response->assertOk();
        $response->assertSee('—');
    }
}

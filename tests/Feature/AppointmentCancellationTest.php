<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AppointmentCancellationTest extends TestCase
{
    use RefreshDatabase;

    public function test_receptionist_can_cancel_an_appointment_and_release_its_slot(): void
    {
        [$clinic, $receptionist, $doctor, $patient, $appointment] = $this->appointmentContext();

        $response = $this->actingAs($receptionist)->post(
            "/appointments/{$appointment->id}/cancel",
            ['cancellation_note' => 'Paciente solicitó cancelar.'],
        );

        $response->assertRedirect(route('appointments.index'));
        $response->assertSessionHas('status', 'Cita cancelada correctamente.');
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'clinic_id' => $clinic->id,
            'status' => 'cancelled',
            'cancellation_note' => 'Paciente solicitó cancelar.',
        ]);

        $createResponse = $this->post('/appointments', [
            'patient_id' => $patient->id,
            'user_id' => $doctor->id,
            'start_time' => $appointment->start_time->format('Y-m-d\TH:i'),
            'duration_minutes' => 30,
            'reason' => 'Nueva reserva',
        ]);

        $createResponse->assertRedirect(route('appointments.index'));
        $this->assertDatabaseCount('appointments', 2);
    }

    public function test_cancellation_note_is_optional(): void
    {
        [, $receptionist, , , $appointment] = $this->appointmentContext('confirmed');

        $this->actingAs($receptionist)
            ->post("/appointments/{$appointment->id}/cancel")
            ->assertRedirect(route('appointments.index'));

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'cancelled',
            'cancellation_note' => null,
        ]);
    }

    public function test_active_past_appointments_can_be_cancelled(): void
    {
        [, $receptionist, , , $appointment] = $this->appointmentContext();
        $appointment->update([
            'start_time' => now()->subDay(),
            'end_time' => now()->subDay()->addMinutes(30),
        ]);

        $this->actingAs($receptionist)
            ->post("/appointments/{$appointment->id}/cancel")
            ->assertRedirect(route('appointments.index'));

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'cancelled',
        ]);
    }

    #[DataProvider('terminalStatuses')]
    public function test_terminal_appointments_cannot_be_cancelled(string $status): void
    {
        [, $receptionist, , , $appointment] = $this->appointmentContext($status);

        $this->actingAs($receptionist)
            ->post("/appointments/{$appointment->id}/cancel", [
                'cancellation_note' => 'No debe guardarse.',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => $status,
            'cancellation_note' => null,
        ]);
    }

    public static function terminalStatuses(): array
    {
        return [
            'cancelled' => ['cancelled'],
            'completed' => ['completed'],
            'no show' => ['no_show'],
        ];
    }

    public function test_doctors_cannot_cancel_and_other_clinics_cannot_access_the_appointment(): void
    {
        [$clinic, , , , $appointment] = $this->appointmentContext();
        $doctor = User::factory()->doctor()->for($clinic)->create();
        $otherClinic = Clinic::factory()->create();
        $otherReceptionist = User::factory()->receptionist()->for($otherClinic)->create();

        $this->actingAs($doctor)
            ->post("/appointments/{$appointment->id}/cancel")
            ->assertForbidden();

        $this->actingAs($otherReceptionist)
            ->post("/appointments/{$appointment->id}/cancel")
            ->assertNotFound();

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'scheduled',
        ]);
    }

    public function test_cancellation_note_is_limited_to_500_characters(): void
    {
        [, $receptionist, , , $appointment] = $this->appointmentContext();

        $this->actingAs($receptionist)
            ->post("/appointments/{$appointment->id}/cancel", [
                'cancellation_note' => str_repeat('a', 501),
            ])
            ->assertSessionHasErrors('cancellation_note');

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'scheduled',
            'cancellation_note' => null,
        ]);
    }

    public function test_cancellation_confirmation_does_not_change_an_appointment_until_submitted(): void
    {
        [, $receptionist, , , $appointment] = $this->appointmentContext();

        $response = $this->actingAs($receptionist)
            ->get("/appointments/{$appointment->id}/cancel");

        $response->assertOk();
        $response->assertSee('Confirmar cancelación');
        $response->assertSee('cancellation_note');
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'scheduled',
            'cancellation_note' => null,
        ]);
    }

    public function test_doctors_see_cancellation_notes_only_for_their_assigned_appointments(): void
    {
        [$clinic, $receptionist, $doctor, $patient, $appointment] = $this->appointmentContext();
        $appointment->update([
            'status' => 'cancelled',
            'cancellation_note' => 'Nota visible solo en la cita asignada.',
        ]);
        $colleague = User::factory()->doctor()->for($clinic)->create();
        $otherPatient = Patient::factory()->for($clinic)->create();
        Appointment::factory()->create([
            'clinic_id' => $clinic->id,
            'patient_id' => $otherPatient->id,
            'user_id' => $colleague->id,
            'status' => 'cancelled',
            'cancellation_note' => 'Nota privada de otra cita.',
        ]);

        $this->actingAs($doctor)
            ->get('/appointments')
            ->assertOk()
            ->assertSee('Nota visible solo en la cita asignada.')
            ->assertDontSee('Nota privada de otra cita.');

        $this->actingAs($receptionist)
            ->get('/appointments')
            ->assertOk()
            ->assertSee('Nota visible solo en la cita asignada.')
            ->assertSee('Nota privada de otra cita.');
    }

    private function appointmentContext(string $status = 'scheduled'): array
    {
        $clinic = Clinic::factory()->create();
        $receptionist = User::factory()->receptionist()->for($clinic)->create();
        $doctor = User::factory()->doctor()->for($clinic)->create();
        $patient = Patient::factory()->for($clinic)->create();
        $start = now()->addDays(2)->setTime(10, 0);
        $appointment = Appointment::factory()->create([
            'clinic_id' => $clinic->id,
            'patient_id' => $patient->id,
            'user_id' => $doctor->id,
            'start_time' => $start,
            'end_time' => $start->copy()->addMinutes(30),
            'status' => $status,
            'reason' => 'Consulta de control',
        ]);

        return [$clinic, $receptionist, $doctor, $patient, $appointment];
    }
}

<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AppointmentReschedulingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_reschedule_without_changing_protected_appointment_data(): void
    {
        [$clinic, $admin, $doctor, $patient, $appointment] = $this->appointmentContext('confirmed');
        $newStart = now()->addDays(5)->setTime(14, 0);

        $response = $this->actingAs($admin)->put(
            "/appointments/{$appointment->id}/reschedule",
            [
                'start_time' => $newStart->format('Y-m-d\TH:i'),
                'duration_minutes' => 45,
            ],
        );

        $response->assertRedirect(route('appointments.index'));
        $response->assertSessionHas('status', 'Cita reprogramada correctamente.');
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'clinic_id' => $clinic->id,
            'patient_id' => $patient->id,
            'user_id' => $doctor->id,
            'start_time' => $newStart->format('Y-m-d H:i:s'),
            'end_time' => $newStart->copy()->addMinutes(45)->format('Y-m-d H:i:s'),
            'status' => 'confirmed',
            'reason' => 'Consulta de control',
        ]);
    }

    public function test_rescheduling_to_the_current_slot_does_not_conflict_with_itself(): void
    {
        [, $receptionist, , , $appointment] = $this->appointmentContext();

        $response = $this->actingAs($receptionist)->put(
            "/appointments/{$appointment->id}/reschedule",
            [
                'start_time' => $appointment->start_time->format('Y-m-d\TH:i'),
                'duration_minutes' => 30,
            ],
        );

        $response->assertRedirect(route('appointments.index'));
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'start_time' => $appointment->start_time->format('Y-m-d H:i:s'),
            'end_time' => $appointment->end_time->format('Y-m-d H:i:s'),
        ]);
    }

    #[DataProvider('conflictingResources')]
    public function test_rescheduling_conflicts_leave_the_original_appointment_unchanged(string $conflict): void
    {
        [$clinic, $receptionist, $doctor, $patient, $appointment] = $this->appointmentContext();
        $requestedStart = now()->addDays(4)->setTime(11, 0);
        $otherDoctor = User::factory()->doctor()->for($clinic)->create();
        $otherPatient = Patient::factory()->for($clinic)->create();

        Appointment::factory()->create([
            'clinic_id' => $clinic->id,
            'patient_id' => $conflict === 'patient' ? $patient->id : $otherPatient->id,
            'user_id' => $conflict === 'doctor' ? $doctor->id : $otherDoctor->id,
            'start_time' => $requestedStart,
            'end_time' => $requestedStart->copy()->addMinutes(30),
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($receptionist)->put(
            "/appointments/{$appointment->id}/reschedule",
            [
                'start_time' => $requestedStart->format('Y-m-d\TH:i'),
                'duration_minutes' => 30,
            ],
        );

        $response->assertSessionHasErrors('start_time');
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'start_time' => $appointment->start_time->format('Y-m-d H:i:s'),
            'end_time' => $appointment->end_time->format('Y-m-d H:i:s'),
        ]);
        $this->assertDatabaseCount('appointments', 2);
    }

    public static function conflictingResources(): array
    {
        return [
            'doctor' => ['doctor'],
            'patient' => ['patient'],
        ];
    }

    #[DataProvider('invalidReschedulePayloads')]
    public function test_invalid_reschedule_values_are_rejected(string $field, mixed $value): void
    {
        [, $receptionist, , , $appointment] = $this->appointmentContext();
        $payload = [
            'start_time' => now()->addDays(3)->format('Y-m-d\TH:i'),
            'duration_minutes' => 30,
        ];
        $payload[$field] = $value;

        $this->actingAs($receptionist)
            ->put("/appointments/{$appointment->id}/reschedule", $payload)
            ->assertSessionHasErrors($field);
    }

    public static function invalidReschedulePayloads(): array
    {
        return [
            'past start' => ['start_time', now()->subDay()->format('Y-m-d\TH:i')],
            'unsupported duration' => ['duration_minutes', 20],
        ];
    }

    #[DataProvider('terminalStatuses')]
    public function test_terminal_appointments_cannot_be_rescheduled(string $status): void
    {
        [, $receptionist, , , $appointment] = $this->appointmentContext($status);

        $this->actingAs($receptionist)
            ->put("/appointments/{$appointment->id}/reschedule", [
                'start_time' => now()->addDays(6)->format('Y-m-d\TH:i'),
                'duration_minutes' => 30,
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => $status,
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

    public function test_doctors_cannot_reschedule_and_other_clinics_cannot_access_the_form(): void
    {
        [$clinic, , , , $appointment] = $this->appointmentContext();
        $doctor = User::factory()->doctor()->for($clinic)->create();
        $otherClinic = Clinic::factory()->create();
        $otherReceptionist = User::factory()->receptionist()->for($otherClinic)->create();

        $this->actingAs($doctor)
            ->put("/appointments/{$appointment->id}/reschedule", [
                'start_time' => now()->addDays(6)->format('Y-m-d\TH:i'),
                'duration_minutes' => 30,
            ])
            ->assertForbidden();

        $this->actingAs($otherReceptionist)
            ->get("/appointments/{$appointment->id}/reschedule")
            ->assertNotFound();

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'scheduled',
        ]);
    }

    public function test_reschedule_form_keeps_patient_doctor_and_reason_read_only(): void
    {
        [, $receptionist, , , $appointment] = $this->appointmentContext();

        $response = $this->actingAs($receptionist)
            ->get("/appointments/{$appointment->id}/reschedule");

        $response->assertOk();
        $response->assertSee('Paciente de prueba');
        $response->assertSee('Doctor de prueba');
        $response->assertSee('Consulta de control');
        $response->assertDontSee('name="patient_id"', false);
        $response->assertDontSee('name="user_id"', false);
        $response->assertDontSee('name="reason"', false);
    }

    private function appointmentContext(string $status = 'scheduled'): array
    {
        $clinic = Clinic::factory()->create();
        $receptionist = User::factory()->receptionist()->for($clinic)->create();
        $doctor = User::factory()->doctor()->for($clinic)->create(['name' => 'Doctor de prueba']);
        $patient = Patient::factory()->for($clinic)->create([
            'first_name' => 'Paciente',
            'last_name' => 'de prueba',
        ]);
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

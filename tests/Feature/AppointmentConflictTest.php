<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AppointmentConflictTest extends TestCase
{
    use RefreshDatabase;

    public function test_overlapping_appointment_for_the_same_doctor_is_rejected(): void
    {
        [$clinic, $receptionist, $doctor, $patient] = $this->bookingContext();
        $otherPatient = Patient::factory()->for($clinic)->create();
        $start = now()->addDays(2)->setTime(10, 0);

        $this->existingAppointment($clinic, $patient, $doctor, $start);

        $this->actingAs($receptionist)
            ->post('/appointments', $this->payload($otherPatient, $doctor, $start->copy()->addMinutes(15)))
            ->assertSessionHasErrors('start_time');

        $this->assertDatabaseCount('appointments', 1);
    }

    public function test_overlapping_appointment_for_the_same_patient_is_rejected(): void
    {
        [$clinic, $receptionist, $doctor, $patient] = $this->bookingContext();
        $otherDoctor = User::factory()->doctor()->for($clinic)->create();
        $start = now()->addDays(2)->setTime(10, 0);

        $this->existingAppointment($clinic, $patient, $doctor, $start);

        $this->actingAs($receptionist)
            ->post('/appointments', $this->payload($patient, $otherDoctor, $start->copy()->addMinutes(15)))
            ->assertSessionHasErrors('start_time');

        $this->assertDatabaseCount('appointments', 1);
    }

    public function test_appointments_in_different_clinics_do_not_conflict(): void
    {
        [$clinic, , $doctor, $patient] = $this->bookingContext();
        $otherClinic = Clinic::factory()->create();
        $otherReceptionist = User::factory()->receptionist()->for($otherClinic)->create();
        $otherDoctor = User::factory()->doctor()->for($otherClinic)->create();
        $otherPatient = Patient::factory()->for($otherClinic)->create();
        $start = now()->addDays(2)->setTime(10, 0);

        $this->existingAppointment($clinic, $patient, $doctor, $start);

        $this->actingAs($otherReceptionist)
            ->post('/appointments', $this->payload($otherPatient, $otherDoctor, $start))
            ->assertRedirect(route('appointments.index'));

        $this->assertDatabaseCount('appointments', 2);
    }

    public function test_confirmed_appointments_reserve_time(): void
    {
        [$clinic, $receptionist, $doctor, $patient] = $this->bookingContext();
        $start = now()->addDays(2)->setTime(10, 0);

        $this->existingAppointment($clinic, $patient, $doctor, $start, 'confirmed');

        $this->actingAs($receptionist)
            ->post('/appointments', $this->payload($patient, $doctor, $start->copy()->addMinutes(15)))
            ->assertSessionHasErrors('start_time');

        $this->assertDatabaseCount('appointments', 1);
    }

    #[DataProvider('nonReservingStatuses')]
    public function test_non_reserving_statuses_do_not_block_booking(string $status): void
    {
        [$clinic, $receptionist, $doctor, $patient] = $this->bookingContext();
        $start = now()->addDays(2)->setTime(10, 0);

        $this->existingAppointment($clinic, $patient, $doctor, $start, $status);

        $this->actingAs($receptionist)
            ->post('/appointments', $this->payload($patient, $doctor, $start->copy()->addMinutes(15)))
            ->assertRedirect(route('appointments.index'));

        $this->assertDatabaseCount('appointments', 2);
    }

    public static function nonReservingStatuses(): array
    {
        return [
            'cancelled' => ['cancelled'],
            'completed' => ['completed'],
            'no show' => ['no_show'],
        ];
    }

    public function test_soft_deleted_appointments_do_not_block_booking(): void
    {
        [$clinic, $receptionist, $doctor, $patient] = $this->bookingContext();
        $start = now()->addDays(2)->setTime(10, 0);

        $appointment = $this->existingAppointment($clinic, $patient, $doctor, $start);
        $appointment->delete();

        $this->actingAs($receptionist)
            ->post('/appointments', $this->payload($patient, $doctor, $start->copy()->addMinutes(15)))
            ->assertRedirect(route('appointments.index'));

        $this->assertDatabaseCount('appointments', 2);
    }

    public function test_appointments_that_touch_at_an_endpoint_do_not_conflict(): void
    {
        [$clinic, $receptionist, $doctor, $patient] = $this->bookingContext();
        $start = now()->addDays(2)->setTime(10, 0);

        $this->existingAppointment($clinic, $patient, $doctor, $start);

        $this->actingAs($receptionist)
            ->post('/appointments', $this->payload($patient, $doctor, $start->copy()->addMinutes(30)))
            ->assertRedirect(route('appointments.index'));

        $this->assertDatabaseCount('appointments', 2);
    }

    private function bookingContext(): array
    {
        $clinic = Clinic::factory()->create();

        return [
            $clinic,
            User::factory()->receptionist()->for($clinic)->create(),
            User::factory()->doctor()->for($clinic)->create(),
            Patient::factory()->for($clinic)->create(),
        ];
    }

    private function existingAppointment(
        Clinic $clinic,
        Patient $patient,
        User $doctor,
        Carbon $start,
        string $status = 'scheduled',
    ): Appointment {
        return Appointment::factory()->create([
            'clinic_id' => $clinic->id,
            'patient_id' => $patient->id,
            'user_id' => $doctor->id,
            'start_time' => $start,
            'end_time' => $start->copy()->addMinutes(30),
            'status' => $status,
        ]);
    }

    private function payload(Patient $patient, User $doctor, Carbon $start): array
    {
        return [
            'patient_id' => $patient->id,
            'user_id' => $doctor->id,
            'start_time' => $start->format('Y-m-d\TH:i'),
            'duration_minutes' => 30,
            'reason' => 'Consulta de control',
        ];
    }
}

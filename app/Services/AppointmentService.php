<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AppointmentService
{
    public function create(
        int $clinicId,
        int $patientId,
        int $doctorId,
        Carbon $start,
        int $durationMinutes,
        ?string $reason,
    ): Appointment {
        $end = $start->copy()->addMinutes($durationMinutes);

        return DB::transaction(function () use ($clinicId, $patientId, $doctorId, $start, $end, $reason): Appointment {
            $this->lockResources($clinicId, $patientId, $doctorId);
            $this->assertAvailable($clinicId, $patientId, $doctorId, $start, $end);

            return Appointment::create([
                'clinic_id' => $clinicId,
                'patient_id' => $patientId,
                'user_id' => $doctorId,
                'start_time' => $start,
                'end_time' => $end,
                'status' => 'scheduled',
                'reason' => $reason,
            ]);
        });
    }

    public function reschedule(Appointment $appointment, Carbon $start, int $durationMinutes): Appointment
    {
        $end = $start->copy()->addMinutes($durationMinutes);

        return DB::transaction(function () use ($appointment, $start, $end): Appointment {
            $lockedAppointment = Appointment::query()
                ->whereKey($appointment->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->ensureActive($lockedAppointment);
            $this->lockResources(
                (int) $lockedAppointment->clinic_id,
                (int) $lockedAppointment->patient_id,
                (int) $lockedAppointment->user_id,
            );
            $this->assertAvailable(
                (int) $lockedAppointment->clinic_id,
                (int) $lockedAppointment->patient_id,
                (int) $lockedAppointment->user_id,
                $start,
                $end,
                (int) $lockedAppointment->id,
            );

            $lockedAppointment->start_time = $start;
            $lockedAppointment->end_time = $end;
            $lockedAppointment->save();

            return $lockedAppointment;
        });
    }

    public function cancel(Appointment $appointment, ?string $note): Appointment
    {
        return DB::transaction(function () use ($appointment, $note): Appointment {
            $lockedAppointment = Appointment::query()
                ->whereKey($appointment->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->ensureActive($lockedAppointment);

            $lockedAppointment->status = 'cancelled';
            $lockedAppointment->cancellation_note = $note;
            $lockedAppointment->save();

            return $lockedAppointment;
        });
    }

    private function lockResources(int $clinicId, int $patientId, int $doctorId): void
    {
        $patient = Patient::query()
            ->where('clinic_id', $clinicId)
            ->whereKey($patientId)
            ->lockForUpdate()
            ->first();

        if ($patient === null) {
            throw ValidationException::withMessages([
                'patient_id' => 'El paciente ya no está disponible en tu clínica.',
            ]);
        }

        $doctor = User::query()
            ->where('clinic_id', $clinicId)
            ->where('role', 'doctor')
            ->whereKey($doctorId)
            ->lockForUpdate()
            ->first();

        if ($doctor === null) {
            throw ValidationException::withMessages([
                'user_id' => 'El doctor ya no está disponible en tu clínica.',
            ]);
        }
    }

    private function assertAvailable(
        int $clinicId,
        int $patientId,
        int $doctorId,
        Carbon $start,
        Carbon $end,
        ?int $exceptAppointmentId = null,
    ): void {
        $query = Appointment::query()
            ->where('clinic_id', $clinicId)
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->where(function ($query) use ($patientId, $doctorId): void {
                $query->where('user_id', $doctorId)
                    ->orWhere('patient_id', $patientId);
            })
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start);

        if ($exceptAppointmentId !== null) {
            $query->where('id', '<>', $exceptAppointmentId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'start_time' => 'El doctor o el paciente ya tiene una cita en ese horario.',
            ]);
        }
    }

    private function ensureActive(Appointment $appointment): void
    {
        if (! in_array($appointment->status, ['scheduled', 'confirmed'], true)) {
            throw ValidationException::withMessages([
                'appointment' => 'Solo se pueden modificar citas programadas o confirmadas.',
            ]);
        }
    }
}

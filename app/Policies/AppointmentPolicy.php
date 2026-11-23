<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    /**
     * Determine whether the user may view the clinic appointment list.
     */
    public function viewAny(User $user): bool
    {
        return match ($user->role) {
            'admin', 'doctor', 'receptionist' => true,
        };
    }

    /**
     * Determine whether the user may create appointments.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'receptionist'], true);
    }

    public function reschedule(User $user, Appointment $appointment): bool
    {
        return $this->manageLifecycle($user, $appointment);
    }

    public function cancel(User $user, Appointment $appointment): bool
    {
        return $this->manageLifecycle($user, $appointment);
    }

    private function manageLifecycle(User $user, Appointment $appointment): bool
    {
        return in_array($user->role, ['admin', 'receptionist'], true)
            && $user->clinic_id === $appointment->clinic_id
            && in_array($appointment->status, ['scheduled', 'confirmed'], true);
    }
}

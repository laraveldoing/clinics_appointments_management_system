<?php

namespace App\Policies;

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
}

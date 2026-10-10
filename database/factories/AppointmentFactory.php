<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('now', '+1 month');

        return [
            'clinic_id' => Clinic::factory(),
            'patient_id' => Patient::factory(),
            'user_id' => User::factory(),
            'start_time' => $start,
            'end_time' => (clone $start)->modify('+30 minutes'),
            'status' => 'scheduled',
            'reason' => fake()->sentence(),
            'reminder_sent_at' => null,
        ];
    }

    /**
     * Cita dentro de una clínica concreta (paciente y doctor de esa clínica).
     */
    public function forClinic(Clinic $clinic): static
    {
        return $this->state(fn (): array => [
            'clinic_id' => $clinic->id,
            'patient_id' => Patient::factory()->for($clinic),
            'user_id' => User::factory()->for($clinic),
        ]);
    }
}
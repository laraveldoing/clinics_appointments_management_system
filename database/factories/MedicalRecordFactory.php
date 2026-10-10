<?php

namespace Database\Factories;

use App\Models\Clinic;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MedicalRecord>
 */
class MedicalRecordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'clinic_id' => Clinic::factory(),
            'patient_id' => Patient::factory(),
            'user_id' => User::factory(),
            'appointment_id' => null,
            'diagnosis' => fake()->sentence(),
            'treatment_plan' => fake()->paragraph(),
            'clinical_notes' => fake()->paragraph(),
            'ai_generated' => false,
        ];
    }

    /**
     * Historial dentro de una clínica concreta (paciente y doctor de esa clínica).
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
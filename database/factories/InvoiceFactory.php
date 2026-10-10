<?php

namespace Database\Factories;

use App\Models\Clinic;
use App\Models\Invoice;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
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
            'appointment_id' => null,
            'total_amount' => 0,
            'status' => 'pending',
            'due_date' => fake()->dateTimeBetween('now', '+1 month')->format('Y-m-d'),
            'paid_at' => null,
        ];
    }

    /**
     * Factura dentro de una clínica concreta (paciente de esa clínica).
     */
    public function forClinic(Clinic $clinic): static
    {
        return $this->state(fn (): array => [
            'clinic_id' => $clinic->id,
            'patient_id' => Patient::factory()->for($clinic),
        ]);
    }
}
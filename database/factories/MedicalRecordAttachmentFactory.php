<?php

namespace Database\Factories;

use App\Models\MedicalRecord;
use App\Models\MedicalRecordAttachment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MedicalRecordAttachment>
 */
class MedicalRecordAttachmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'medical_record_id' => MedicalRecord::factory(),
            'file_name' => fake()->word().'.pdf',
            'file_path' => 'medical-records/'.fake()->uuid().'.pdf',
            'file_type' => 'application/pdf',
            'file_size' => fake()->numberBetween(1024, 5_000_000),
        ];
    }
}
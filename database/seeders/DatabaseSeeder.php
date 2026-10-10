<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $clinic = Clinic::factory()->create([
            'name' => 'Clínica Demo',
            'slug' => 'clinica-demo',
            'email' => 'demo@clinica.test',
        ]);

        User::factory()->admin()->for($clinic)->create([
            'name' => 'Admin Demo',
            'email' => 'admin@clinica.test',
        ]);

        $doctor = User::factory()->doctor()->for($clinic)->create([
            'name' => 'Doctor Demo',
            'email' => 'doctor@clinica.test',
        ]);

        Patient::factory()->count(5)->for($clinic)->create();

        Appointment::factory()->count(5)->forClinic($clinic)->create([
            'user_id' => $doctor->id,
        ]);
    }
}

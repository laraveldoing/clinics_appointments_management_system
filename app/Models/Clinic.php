<?php

namespace App\Models;

use Database\Factories\ClinicFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'slug', 'email', 'phone', 'address', 'settings'])]
class Clinic extends Model
{
    /** @use HasFactory<ClinicFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'settings' => 'array',
        ];
    }

    /**
     * Usuarios de la clínica.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Pacientes de la clínica.
     */
    public function patients(): HasMany
    {
        return $this->hasMany(Patient::class);
    }

    /**
     * Citas de la clínica.
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Historiales médicos de la clínica.
     */
    public function medicalRecords(): HasMany
    {
        return $this->hasMany(MedicalRecord::class);
    }

    /**
     * Facturas de la clínica.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
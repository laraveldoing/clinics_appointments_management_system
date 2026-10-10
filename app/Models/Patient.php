<?php

namespace App\Models;

use App\Models\Concerns\BelongsToClinic;
use Database\Factories\PatientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'clinic_id',
    'first_name',
    'last_name',
    'email',
    'phone',
    'birth_date',
    'gender',
    'address',
    'medical_history_summary',
])]
class Patient extends Model
{
    /** @use HasFactory<PatientFactory> */
    use BelongsToClinic, HasFactory, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
        ];
    }

    /**
     * Nombre completo del paciente.
     */
    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    /**
     * Citas del paciente.
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Historiales médicos del paciente.
     */
    public function medicalRecords(): HasMany
    {
        return $this->hasMany(MedicalRecord::class);
    }

    /**
     * Facturas del paciente.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
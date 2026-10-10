<?php

namespace App\Models;

use App\Models\Concerns\BelongsToClinic;
use Database\Factories\MedicalRecordFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'clinic_id',
    'patient_id',
    'user_id',
    'appointment_id',
    'diagnosis',
    'treatment_plan',
    'clinical_notes',
    'ai_generated',
])]
class MedicalRecord extends Model
{
    /** @use HasFactory<MedicalRecordFactory> */
    use BelongsToClinic, HasFactory, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'clinical_notes' => 'encrypted',
            'ai_generated' => 'boolean',
        ];
    }

    /**
     * Paciente del historial.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Doctor responsable del historial.
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Cita de la que deriva el historial (puede ser nula).
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Adjuntos del historial.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(MedicalRecordAttachment::class);
    }
}
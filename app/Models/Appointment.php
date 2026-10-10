<?php

namespace App\Models;

use App\Models\Concerns\BelongsToClinic;
use Database\Factories\AppointmentFactory;
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
    'start_time',
    'end_time',
    'status',
    'reason',
    'reminder_sent_at',
])]
class Appointment extends Model
{
    /** @use HasFactory<AppointmentFactory> */
    use BelongsToClinic, HasFactory, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'reminder_sent_at' => 'datetime',
        ];
    }

    /**
     * Paciente citado.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Doctor asignado a la cita.
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Historiales médicos derivados de la cita.
     */
    public function medicalRecords(): HasMany
    {
        return $this->hasMany(MedicalRecord::class);
    }

    /**
     * Facturas asociadas a la cita.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToClinic;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'clinic_id',
    'patient_id',
    'appointment_id',
    'total_amount',
    'status',
    'due_date',
    'paid_at',
])]
class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use BelongsToClinic, HasFactory, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'due_date' => 'date',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * Paciente facturado.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Cita facturada (puede ser nula).
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Líneas de la factura.
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }
}
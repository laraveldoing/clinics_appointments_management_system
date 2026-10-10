<?php

namespace App\Models;

use Database\Factories\MedicalRecordAttachmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'medical_record_id',
    'file_name',
    'file_path',
    'file_type',
    'file_size',
])]
class MedicalRecordAttachment extends Model
{
    /** @use HasFactory<MedicalRecordAttachmentFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
        ];
    }

    /**
     * Historial al que pertenece el adjunto.
     */
    public function medicalRecord(): BelongsTo
    {
        return $this->belongsTo(MedicalRecord::class);
    }
}
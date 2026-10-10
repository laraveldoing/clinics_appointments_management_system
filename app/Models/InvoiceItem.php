<?php

namespace App\Models;

use Database\Factories\InvoiceItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'invoice_id',
    'description',
    'quantity',
    'unit_price',
    'subtotal',
])]
class InvoiceItem extends Model
{
    /** @use HasFactory<InvoiceItemFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    /**
     * Factura a la que pertenece la línea.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
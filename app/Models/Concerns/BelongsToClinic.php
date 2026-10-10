<?php

namespace App\Models\Concerns;

use App\Models\Clinic;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Comparte la pertenencia a una clínica (multi-tenancy por `clinic_id`).
 *
 * Aplica un scope global que filtra por la clínica del usuario autenticado.
 * En consola (Seeders, comandos, tests sin sesión) no hay usuario, por lo
 * que el scope es inerte y no rompe la siembra ni las pruebas.
 */
trait BelongsToClinic
{
    /**
     * Registra el scope global de tenancy.
     */
    protected static function bootBelongsToClinic(): void
    {
        static::addGlobalScope('clinic', function (Builder $builder): void {
            $user = auth()->user();

            if ($user !== null && $user->clinic_id !== null) {
                $builder->where($builder->getModel()->getTable().'.clinic_id', $user->clinic_id);
            }
        });
    }

    /**
     * Clínica a la que pertenece el registro.
     */
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    /**
     * Limita la consulta a una clínica concreta (uso explícito).
     */
    public function scopeForClinic(Builder $query, int $clinicId): Builder
    {
        return $query->where($this->getTable().'.clinic_id', $clinicId);
    }
}
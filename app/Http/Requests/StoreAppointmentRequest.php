<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAppointmentRequest extends FormRequest
{
    /**
     * Cualquier usuario autenticado puede crear citas en su clínica.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Reglas de validación de una cita nueva.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $clinicId = $this->user()->clinic_id;

        return [
            'patient_id' => [
                'required',
                'integer',
                Rule::exists('patients', 'id')->where(
                    fn ($query) => $query->where('clinic_id', $clinicId)->whereNull('deleted_at')
                ),
            ],
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(
                    fn ($query) => $query->where('clinic_id', $clinicId)
                        ->where('role', 'doctor')
                        ->whereNull('deleted_at')
                ),
            ],
            'start_time' => ['required', 'date', 'after:now'],
            'duration_minutes' => ['required', 'integer', Rule::in([15, 30, 45, 60, 90])],
            'reason' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Mensajes en español.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'patient_id.required' => 'Selecciona un paciente.',
            'patient_id.exists' => 'El paciente no pertenece a tu clínica.',
            'user_id.required' => 'Selecciona un doctor.',
            'user_id.exists' => 'El doctor no pertenece a tu clínica o no tiene ese rol.',
            'start_time.required' => 'Indica la fecha y hora de la cita.',
            'start_time.date' => 'La fecha y hora no son válidas.',
            'start_time.after' => 'La cita debe programarse en el futuro.',
            'duration_minutes.required' => 'Selecciona una duración.',
            'duration_minutes.integer' => 'La duración no es válida.',
            'duration_minutes.in' => 'La duración debe ser 15, 30, 45, 60 o 90 minutos.',
            'reason.max' => 'El motivo no puede superar los 2000 caracteres.',
        ];
    }

    /**
     * Nombres legibles de los campos.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'patient_id' => 'paciente',
            'user_id' => 'doctor',
            'start_time' => 'fecha y hora',
            'duration_minutes' => 'duración',
            'reason' => 'motivo',
        ];
    }
}

<?php

namespace App\Http\Requests;

use App\Models\Appointment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RescheduleAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $appointment = $this->route('appointment');

        return $appointment instanceof Appointment
            && ($this->user()?->can('reschedule', $appointment) ?? false);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'start_time' => ['required', 'date', 'after:now'],
            'duration_minutes' => ['required', 'integer', Rule::in([15, 30, 45, 60, 90])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'start_time.required' => 'Indica la fecha y hora de la cita.',
            'start_time.date' => 'La fecha y hora no son válidas.',
            'start_time.after' => 'La cita debe programarse en el futuro.',
            'duration_minutes.required' => 'Selecciona una duración.',
            'duration_minutes.integer' => 'La duración no es válida.',
            'duration_minutes.in' => 'La duración debe ser 15, 30, 45, 60 o 90 minutos.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'start_time' => 'fecha y hora',
            'duration_minutes' => 'duración',
        ];
    }
}

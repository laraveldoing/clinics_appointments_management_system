<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAppointmentRequest;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    /**
     * Lista las citas de la clínica del usuario autenticado.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Appointment::class);

        $query = Appointment::query()
            ->with(['patient', 'doctor'])
            ->orderBy('start_time');

        if ($request->user()->role === 'doctor') {
            $query->where('user_id', $request->user()->id);
        }

        $appointments = $query->paginate(15)->withQueryString();

        return view('appointments.index', [
            'appointments' => $appointments,
            'emptyMessage' => $request->user()->role === 'doctor'
                ? 'No tienes citas asignadas.'
                : 'No hay citas registradas en tu clínica.',
        ]);
    }

    /**
     * Muestra el formulario de una cita nueva.
     */
    public function create(Request $request): View
    {
        Gate::authorize('create', Appointment::class);

        return view('appointments.create', [
            'patients' => Patient::query()
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get(),
            'doctors' => User::query()
                ->where('clinic_id', $request->user()->clinic_id)
                ->where('role', 'doctor')
                ->orderBy('name')
                ->get(),
        ]);
    }

    /**
     * Guarda una cita nueva en la clínica del usuario.
     */
    public function store(StoreAppointmentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $start = Carbon::parse($data['start_time']);
        $end = $start->copy()->addMinutes((int) $data['duration_minutes']);
        $clinicId = $request->user()->clinic_id;

        DB::transaction(function () use ($data, $start, $end, $clinicId): void {
            $patient = Patient::query()
                ->where('clinic_id', $clinicId)
                ->whereKey($data['patient_id'])
                ->lockForUpdate()
                ->first();

            if ($patient === null) {
                throw ValidationException::withMessages([
                    'patient_id' => 'El paciente ya no está disponible en tu clínica.',
                ]);
            }

            $doctor = User::query()
                ->where('clinic_id', $clinicId)
                ->where('role', 'doctor')
                ->whereKey($data['user_id'])
                ->lockForUpdate()
                ->first();

            if ($doctor === null) {
                throw ValidationException::withMessages([
                    'user_id' => 'El doctor ya no está disponible en tu clínica.',
                ]);
            }

            $hasConflict = Appointment::query()
                ->where('clinic_id', $clinicId)
                ->whereIn('status', ['scheduled', 'confirmed'])
                ->where(function ($query) use ($data): void {
                    $query->where('user_id', $data['user_id'])
                        ->orWhere('patient_id', $data['patient_id']);
                })
                ->where('start_time', '<', $end)
                ->where('end_time', '>', $start)
                ->exists();

            if ($hasConflict) {
                throw ValidationException::withMessages([
                    'start_time' => 'El doctor o el paciente ya tiene una cita en ese horario.',
                ]);
            }

            Appointment::create([
                'clinic_id' => $clinicId,
                'patient_id' => $patient->id,
                'user_id' => $doctor->id,
                'start_time' => $start,
                'end_time' => $end,
                'status' => 'scheduled',
                'reason' => $data['reason'] ?? null,
            ]);
        });

        return redirect()
            ->route('appointments.index')
            ->with('status', 'Cita creada correctamente.');
    }
}

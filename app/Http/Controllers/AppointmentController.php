<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAppointmentRequest;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    /**
     * Lista las citas de la clínica del usuario autenticado.
     */
    public function index(Request $request): View
    {
        $appointments = Appointment::query()
            ->with(['patient', 'doctor'])
            ->orderBy('start_time')
            ->paginate(15)
            ->withQueryString();

        return view('appointments.index', [
            'appointments' => $appointments,
        ]);
    }

    /**
     * Muestra el formulario de una cita nueva.
     */
    public function create(Request $request): View
    {
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

        Appointment::create([
            'clinic_id' => $request->user()->clinic_id,
            'patient_id' => $data['patient_id'],
            'user_id' => $data['user_id'],
            'start_time' => $start,
            'end_time' => $start->copy()->addMinutes((int) $data['duration_minutes']),
            'status' => 'scheduled',
            'reason' => $data['reason'] ?? null,
        ]);

        return redirect()
            ->route('appointments.index')
            ->with('status', 'Cita creada correctamente.');
    }
}

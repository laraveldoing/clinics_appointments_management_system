<?php

namespace App\Http\Controllers;

use App\Http\Requests\CancelAppointmentRequest;
use App\Http\Requests\RescheduleAppointmentRequest;
use App\Http\Requests\StoreAppointmentRequest;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use App\Services\AppointmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
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
    public function store(StoreAppointmentRequest $request, AppointmentService $service): RedirectResponse
    {
        $data = $request->validated();
        $service->create(
            (int) $request->user()->clinic_id,
            (int) $data['patient_id'],
            (int) $data['user_id'],
            Carbon::parse((string) $data['start_time']),
            (int) $data['duration_minutes'],
            isset($data['reason']) ? (string) $data['reason'] : null,
        );

        return redirect()
            ->route('appointments.index')
            ->with('status', 'Cita creada correctamente.');
    }

    /**
     * Muestra el formulario para cambiar la fecha y duración de una cita.
     */
    public function editReschedule(Appointment $appointment): View
    {
        Gate::authorize('reschedule', $appointment);

        return view('appointments.reschedule', [
            'appointment' => $appointment->load(['patient', 'doctor']),
        ]);
    }

    /**
     * Cambia la fecha y duración sin modificar el resto de datos de la cita.
     */
    public function updateReschedule(
        RescheduleAppointmentRequest $request,
        Appointment $appointment,
        AppointmentService $service,
    ): RedirectResponse {
        $data = $request->validated();
        $service->reschedule(
            $appointment,
            Carbon::parse((string) $data['start_time']),
            (int) $data['duration_minutes'],
        );

        return redirect()
            ->route('appointments.index')
            ->with('status', 'Cita reprogramada correctamente.');
    }

    /**
     * Muestra la confirmación de cancelación sin modificar la cita.
     */
    public function confirmCancellation(Appointment $appointment): View
    {
        Gate::authorize('cancel', $appointment);

        return view('appointments.cancel', [
            'appointment' => $appointment->load(['patient', 'doctor']),
        ]);
    }

    /**
     * Cancela la cita y guarda opcionalmente una nota.
     */
    public function cancel(
        CancelAppointmentRequest $request,
        Appointment $appointment,
        AppointmentService $service,
    ): RedirectResponse {
        $data = $request->validated();
        $note = isset($data['cancellation_note']) ? (string) $data['cancellation_note'] : null;

        $service->cancel($appointment, $note);

        return redirect()
            ->route('appointments.index')
            ->with('status', 'Cita cancelada correctamente.');
    }
}

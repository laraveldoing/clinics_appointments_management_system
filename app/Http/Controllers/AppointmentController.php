<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Illuminate\Http\Request;
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
}

@extends('layouts.app')

@section('title', 'Citas')

@section('content')
    <div class="card">
        @if (session('status'))
            <div role="status" style="background:#f0fdf4;color:#166534;border-radius:.375rem;padding:.75rem;margin-bottom:1rem;">
                {{ session('status') }}
            </div>
        @endif

        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
            <h1 style="font-size:1.25rem;font-weight:600;margin:0;">Citas</h1>
            <a href="{{ route('appointments.create') }}" style="background:#0f172a;color:#fff;border-radius:.375rem;padding:.5rem .75rem;font-size:.875rem;font-weight:600;text-decoration:none;">
                Nueva cita
            </a>
        </div>

        @if ($appointments->total() === 0)
            <p style="color:#475569;">No hay citas registradas en tu clínica.</p>
        @elseif ($appointments->isEmpty())
            <p style="color:#475569;">Esta página no tiene resultados. Vuelve a la primera página.</p>
        @else
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:.875rem;">
                    <thead>
                        <tr style="text-align:left;border-bottom:1px solid #e2e8f0;">
                            <th scope="col" style="padding:.5rem;">Fecha</th>
                            <th scope="col" style="padding:.5rem;">Paciente</th>
                            <th scope="col" style="padding:.5rem;">Doctor</th>
                            <th scope="col" style="padding:.5rem;">Estado</th>
                            <th scope="col" style="padding:.5rem;">Motivo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($appointments as $appointment)
                            <tr style="border-bottom:1px solid #f1f5f9;">
                                <td style="padding:.5rem;white-space:nowrap;">{{ $appointment->start_time->format('d/m/Y H:i') }}</td>
                                <td style="padding:.5rem;">{{ $appointment->patient?->full_name ?? '—' }}</td>
                                <td style="padding:.5rem;">{{ $appointment->doctor?->name ?? '—' }}</td>
                                <td style="padding:.5rem;">{{ $appointment->statusLabel() }}</td>
                                <td style="padding:.5rem;">{{ $appointment->reason ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div style="margin-top:1rem;">
                {{ $appointments->links() }}
            </div>
        @endif
    </div>
@endsection

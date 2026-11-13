@extends('layouts.app')

@section('title', 'Citas')

@section('content')
    <div class="card">
        <h1 style="font-size:1.25rem;font-weight:600;margin-top:0;margin-bottom:1rem;">Citas</h1>

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

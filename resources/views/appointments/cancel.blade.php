@extends('layouts.app')

@section('title', 'Cancelar cita')

@section('content')
    <div class="card" style="max-width:40rem;margin:0 auto;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;">
            <h1 style="font-size:1.25rem;font-weight:600;margin:0;">Cancelar cita</h1>
            <a href="{{ route('appointments.index') }}" style="font-size:.875rem;color:#475569;">Volver al listado</a>
        </div>

        <p style="color:#475569;">Confirma si deseas cancelar esta cita. Si abandonas esta pantalla, la cita no cambiará.</p>

        <dl style="display:grid;grid-template-columns:1fr 2fr;gap:.5rem;margin:0 0 1.5rem;">
            <dt style="font-weight:600;">Fecha</dt>
            <dd style="margin:0;">{{ $appointment->start_time->format('d/m/Y H:i') }}</dd>
            <dt style="font-weight:600;">Paciente</dt>
            <dd style="margin:0;">{{ $appointment->patient?->full_name ?? '—' }}</dd>
            <dt style="font-weight:600;">Doctor</dt>
            <dd style="margin:0;">{{ $appointment->doctor?->name ?? '—' }}</dd>
            <dt style="font-weight:600;">Motivo</dt>
            <dd style="margin:0;">{{ $appointment->reason ?? '—' }}</dd>
        </dl>

        @if ($errors->any())
            <div role="alert" tabindex="-1" data-error-summary style="background:#fef2f2;color:#991b1b;border-radius:.375rem;padding:.75rem;margin-bottom:1rem;">
                Revisa la nota de cancelación.
                @error('appointment')
                    <p style="margin:.25rem 0 0;">{{ $message }}</p>
                @enderror
            </div>
        @endif

        <form method="POST" action="{{ route('appointments.cancel.store', $appointment) }}">
            @csrf

            <div style="margin-bottom:1.5rem;">
                <label for="cancellation_note" style="display:block;font-size:.875rem;font-weight:500;margin-bottom:.25rem;">Nota (opcional)</label>
                <textarea
                    id="cancellation_note"
                    name="cancellation_note"
                    rows="3"
                    maxlength="500"
                    @error('cancellation_note') aria-invalid="true" aria-describedby="cancellation_note-error" @enderror
                    style="width:100%;box-sizing:border-box;border:1px solid #cbd5e1;border-radius:.375rem;padding:.5rem;"
                >{{ old('cancellation_note') }}</textarea>
                @error('cancellation_note')
                    <p id="cancellation_note-error" style="color:#b91c1c;font-size:.8125rem;margin:.25rem 0 0;">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" style="background:#b91c1c;color:#fff;border:0;border-radius:.375rem;padding:.625rem 1rem;font-size:.875rem;font-weight:600;cursor:pointer;">
                Confirmar cancelación
            </button>
        </form>
    </div>
@endsection

@extends('layouts.app')

@section('title', 'Reprogramar cita')

@section('content')
    <div class="card" style="max-width:40rem;margin:0 auto;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;">
            <h1 style="font-size:1.25rem;font-weight:600;margin:0;">Reprogramar cita</h1>
            <a href="{{ route('appointments.index') }}" style="font-size:.875rem;color:#475569;">Volver al listado</a>
        </div>

        @if ($errors->any())
            <div role="alert" tabindex="-1" data-error-summary style="background:#fef2f2;color:#991b1b;border-radius:.375rem;padding:.75rem;margin-bottom:1rem;">
                Revisa los campos marcados.
                @error('appointment')
                    <p style="margin:.25rem 0 0;">{{ $message }}</p>
                @enderror
            </div>
        @endif

        <dl style="display:grid;grid-template-columns:1fr 2fr;gap:.5rem;margin:0 0 1.5rem;">
            <dt style="font-weight:600;">Paciente</dt>
            <dd style="margin:0;">{{ $appointment->patient?->full_name ?? '—' }}</dd>
            <dt style="font-weight:600;">Doctor</dt>
            <dd style="margin:0;">{{ $appointment->doctor?->name ?? '—' }}</dd>
            <dt style="font-weight:600;">Motivo</dt>
            <dd style="margin:0;">{{ $appointment->reason ?? '—' }}</dd>
        </dl>

        <form method="POST" action="{{ route('appointments.reschedule.update', $appointment) }}">
            @csrf
            @method('PUT')

            <div style="display:grid;grid-template-columns:2fr 1fr;gap:1rem;margin-bottom:1.5rem;">
                <div>
                    <label for="start_time" style="display:block;font-size:.875rem;font-weight:500;margin-bottom:.25rem;">Nueva fecha y hora</label>
                    <input
                        id="start_time"
                        name="start_time"
                        type="datetime-local"
                        value="{{ old('start_time', $appointment->start_time->format('Y-m-d\TH:i')) }}"
                        required
                        @error('start_time') aria-invalid="true" aria-describedby="start_time-error" @enderror
                        style="width:100%;box-sizing:border-box;border:1px solid #cbd5e1;border-radius:.375rem;padding:.5rem;"
                    >
                    @error('start_time')
                        <p id="start_time-error" style="color:#b91c1c;font-size:.8125rem;margin:.25rem 0 0;">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="duration_minutes" style="display:block;font-size:.875rem;font-weight:500;margin-bottom:.25rem;">Duración</label>
                    <select id="duration_minutes" name="duration_minutes" required @error('duration_minutes') aria-invalid="true" aria-describedby="duration_minutes-error" @enderror style="width:100%;box-sizing:border-box;border:1px solid #cbd5e1;border-radius:.375rem;padding:.5rem;">
                        @foreach ([15, 30, 45, 60, 90] as $minutes)
                            <option value="{{ $minutes }}" @selected(old('duration_minutes', $appointment->start_time->diffInMinutes($appointment->end_time)) == $minutes)>
                                {{ $minutes }} minutos
                            </option>
                        @endforeach
                    </select>
                    @error('duration_minutes')
                        <p id="duration_minutes-error" style="color:#b91c1c;font-size:.8125rem;margin:.25rem 0 0;">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <button type="submit" style="background:#0f172a;color:#fff;border:0;border-radius:.375rem;padding:.625rem 1rem;font-size:.875rem;font-weight:600;cursor:pointer;">
                Guardar cambios
            </button>
        </form>
    </div>
@endsection

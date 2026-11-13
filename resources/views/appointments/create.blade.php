@extends('layouts.app')

@section('title', 'Nueva cita')

@section('content')
    <div class="card" style="max-width:40rem;margin:0 auto;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;">
            <h1 style="font-size:1.25rem;font-weight:600;margin:0;">Nueva cita</h1>
            <a href="{{ route('appointments.index') }}" style="font-size:.875rem;color:#475569;">Volver al listado</a>
        </div>

        @if ($errors->any())
            <div role="alert" style="background:#fef2f2;color:#991b1b;border-radius:.375rem;padding:.75rem;margin-bottom:1rem;">
                Revisa los campos marcados.
            </div>
        @endif

        <form method="POST" action="{{ route('appointments.store') }}">
            @csrf

            <div style="margin-bottom:1rem;">
                <label for="patient_id" style="display:block;font-size:.875rem;font-weight:500;margin-bottom:.25rem;">Paciente</label>
                <select id="patient_id" name="patient_id" required style="width:100%;box-sizing:border-box;border:1px solid #cbd5e1;border-radius:.375rem;padding:.5rem;">
                    <option value="">Selecciona un paciente</option>
                    @foreach ($patients as $patient)
                        <option value="{{ $patient->id }}" @selected(old('patient_id') == $patient->id)>
                            {{ $patient->full_name }}
                        </option>
                    @endforeach
                </select>
                @error('patient_id')
                    <p style="color:#b91c1c;font-size:.8125rem;margin:.25rem 0 0;">{{ $message }}</p>
                @enderror
            </div>

            <div style="margin-bottom:1rem;">
                <label for="user_id" style="display:block;font-size:.875rem;font-weight:500;margin-bottom:.25rem;">Doctor</label>
                <select id="user_id" name="user_id" required style="width:100%;box-sizing:border-box;border:1px solid #cbd5e1;border-radius:.375rem;padding:.5rem;">
                    <option value="">Selecciona un doctor</option>
                    @foreach ($doctors as $doctor)
                        <option value="{{ $doctor->id }}" @selected(old('user_id') == $doctor->id)>
                            {{ $doctor->name }}
                        </option>
                    @endforeach
                </select>
                @error('user_id')
                    <p style="color:#b91c1c;font-size:.8125rem;margin:.25rem 0 0;">{{ $message }}</p>
                @enderror
            </div>

            <div style="display:grid;grid-template-columns:2fr 1fr;gap:1rem;margin-bottom:1rem;">
                <div>
                    <label for="start_time" style="display:block;font-size:.875rem;font-weight:500;margin-bottom:.25rem;">Fecha y hora</label>
                    <input
                        id="start_time"
                        name="start_time"
                        type="datetime-local"
                        value="{{ old('start_time') }}"
                        required
                        style="width:100%;box-sizing:border-box;border:1px solid #cbd5e1;border-radius:.375rem;padding:.5rem;"
                    >
                    @error('start_time')
                        <p style="color:#b91c1c;font-size:.8125rem;margin:.25rem 0 0;">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="duration_minutes" style="display:block;font-size:.875rem;font-weight:500;margin-bottom:.25rem;">Duración</label>
                    <select id="duration_minutes" name="duration_minutes" required style="width:100%;box-sizing:border-box;border:1px solid #cbd5e1;border-radius:.375rem;padding:.5rem;">
                        @foreach ([15, 30, 45, 60, 90] as $minutes)
                            <option value="{{ $minutes }}" @selected(old('duration_minutes', 30) == $minutes)>
                                {{ $minutes }} minutos
                            </option>
                        @endforeach
                    </select>
                    @error('duration_minutes')
                        <p style="color:#b91c1c;font-size:.8125rem;margin:.25rem 0 0;">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div style="margin-bottom:1.5rem;">
                <label for="reason" style="display:block;font-size:.875rem;font-weight:500;margin-bottom:.25rem;">Motivo</label>
                <textarea
                    id="reason"
                    name="reason"
                    rows="3"
                    maxlength="2000"
                    style="width:100%;box-sizing:border-box;border:1px solid #cbd5e1;border-radius:.375rem;padding:.5rem;"
                >{{ old('reason') }}</textarea>
                @error('reason')
                    <p style="color:#b91c1c;font-size:.8125rem;margin:.25rem 0 0;">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" style="background:#0f172a;color:#fff;border:0;border-radius:.375rem;padding:.625rem 1rem;font-size:.875rem;font-weight:600;cursor:pointer;">
                Guardar cita
            </button>
        </form>
    </div>
@endsection

@extends('layouts.app')

@section('title', 'Iniciar sesión')

@section('content')
    <div class="card" style="max-width:24rem;margin:3rem auto;">
        <h1 style="font-size:1.25rem;font-weight:600;margin-top:0;">Iniciar sesión</h1>

        @if ($errors->any())
            <div role="alert" style="background:#fef2f2;color:#991b1b;border-radius:.375rem;padding:.75rem;margin-bottom:1rem;">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div style="margin-bottom:1rem;">
                <label for="email" style="display:block;font-size:.875rem;font-weight:500;margin-bottom:.25rem;">Correo electrónico</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    autocomplete="username"
                    style="width:100%;box-sizing:border-box;border:1px solid #cbd5e1;border-radius:.375rem;padding:.5rem;"
                >
            </div>

            <div style="margin-bottom:1rem;">
                <label for="password" style="display:block;font-size:.875rem;font-weight:500;margin-bottom:.25rem;">Contraseña</label>
                <input
                    id="password"
                    name="password"
                    type="password"
                    required
                    autocomplete="current-password"
                    style="width:100%;box-sizing:border-box;border:1px solid #cbd5e1;border-radius:.375rem;padding:.5rem;"
                >
            </div>

            <label style="display:flex;align-items:center;gap:.5rem;font-size:.875rem;margin-bottom:1rem;">
                <input type="checkbox" name="remember" value="1">
                Recordarme
            </label>

            <button type="submit" style="width:100%;background:#0f172a;color:#fff;border:0;border-radius:.375rem;padding:.625rem;font-size:.875rem;font-weight:600;cursor:pointer;">
                Entrar
            </button>
        </form>
    </div>
@endsection

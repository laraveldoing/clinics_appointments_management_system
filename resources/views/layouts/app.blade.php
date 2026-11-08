<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title', config('app.name', 'Laravel'))</title>

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            <style>
                body { margin: 0; font-family: system-ui, sans-serif; background: #f8fafc; color: #0f172a; }
                .container { max-width: 72rem; margin: 0 auto; padding: 1.5rem; }
                .card { background: #fff; border-radius: .5rem; box-shadow: 0 1px 3px rgba(0, 0, 0, .1); padding: 1.5rem; }
            </style>
        @endif
    </head>
    <body>
        <div class="container">
            <header style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;">
                <a href="{{ url('/') }}" style="font-weight:600;text-decoration:none;color:inherit;">
                    {{ config('app.name', 'Laravel') }}
                </a>

                @auth
                    <div style="display:flex;align-items:center;gap:1rem;">
                        <span style="font-size:.875rem;color:#475569;">{{ auth()->user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" style="font-size:.875rem;background:none;border:0;color:#dc2626;cursor:pointer;">
                                Cerrar sesión
                            </button>
                        </form>
                    </div>
                @endauth
            </header>

            @yield('content')
        </div>
    </body>
</html>

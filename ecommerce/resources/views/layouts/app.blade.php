<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'E-Commerce')</title>

    {{-- Bootstrap 5.3 CSS --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    {{-- Bootstrap Icons --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    {{-- Google Fonts: Inter --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* ── Global base ─────────────────────────────────── */
        body {
            font-family: 'Inter', sans-serif;
            font-size: 0.9375rem;
        }

        :root {
            --ec-primary: #4f46e5;
        }

        /* ── Button effects ──────────────────────────────── */
        .btn {
            transition: transform .15s ease, box-shadow .15s ease;
        }

        .btn:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, .10);
        }

        .btn:active:not(:disabled) {
            transform: translateY(0);
            box-shadow: none;
        }

        /* ── Card hover ───────────────────────────────────── */
        .card-hover {
            transition: transform .15s ease, box-shadow .15s ease;
            cursor: pointer;
        }

        .card-hover:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, .08) !important;
        }

        /* ── Fade-in ──────────────────────────────────────── */
        .fade-in {
            animation: fadeIn .25s ease forwards;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(8px)
            }

            to {
                opacity: 1;
                transform: translateY(0)
            }
        }

        /* ── Text utilities ───────────────────────────────── */
        .text-label {
            font-size: .75rem;
            font-weight: 600;
            letter-spacing: .05em;
            text-transform: uppercase;
            color: #6c757d;
        }

        /* ── Alpine cloak ─────────────────────────────────── */
        [x-cloak] {
            display: none !important;
        }
    </style>

    @stack('head')
    @stack('styles')
</head>

<body class="bg-light">

    {{-- Navbar (IMP-031) --}}
    @include('partials.navbar')

    {{-- Toast notifications (IMP-009) --}}
    @include('partials.toast')

    {{-- Page content --}}
    <main class="container py-4">
        @yield('content')
    </main>

    {{-- Bootstrap 5.3 JS bundle --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    {{-- Alpine.js --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>

    <script>
        // Enable Bootstrap tooltips globally
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[data-bs-toggle="tooltip"]')
                .forEach(el => new bootstrap.Tooltip(el));
        });
    </script>

    @stack('scripts')
</body>

</html>
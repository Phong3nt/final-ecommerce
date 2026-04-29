<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin — E-Commerce')</title>

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
            background: #f1f5f9;
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

        /* ── Admin sidebar ───────────────────────────────── */
        #adminSidebar {
            width: 240px;
            min-height: 100vh;
            background: #1e293b;
            position: fixed;
            top: 0;
            left: 0;
            overflow-y: auto;
            z-index: 200;
            display: flex;
            flex-direction: column;
        }

        #adminSidebar .sidebar-brand {
            padding: 1.25rem 1rem 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, .08);
        }

        #adminSidebar .nav-link {
            font-size: 0.875rem;
            border-radius: 6px;
            transition: background 0.15s ease, color 0.15s ease;
        }

        #adminSidebar .nav-link:not(.active):hover {
            background: rgba(255, 255, 255, .08);
            color: #fff !important;
        }

        #adminSidebar .sidebar-section-label {
            font-size: .65rem;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, .3);
            padding: .75rem 1rem .25rem;
        }

        /* ── Admin main area ─────────────────────────────── */
        #adminMain {
            margin-left: 240px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ── Admin topbar ────────────────────────────────── */
        #adminTopbar {
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        /* ── Mobile: hide sidebar, show toggle ───────────── */
        @media (max-width: 991.98px) {
            #adminSidebar {
                transform: translateX(-100%);
                transition: transform .25s ease;
            }

            #adminSidebar.show {
                transform: translateX(0);
            }

            #adminMain {
                margin-left: 0;
            }
        }
    </style>

    @stack('styles')
</head>

<body>

    {{-- Admin Sidebar --}}
    <nav id="adminSidebar">
        {{-- Brand --}}
        <div class="sidebar-brand">
            <a href="{{ route('admin.dashboard') }}"
                class="d-flex align-items-center gap-2 text-white text-decoration-none fw-bold fs-6">
                <i class="bi bi-shop-window fs-5"></i>
                <span>Admin Panel</span>
            </a>
        </div>

        {{-- Nav links --}}
        <div class="d-flex flex-column gap-1 p-3 flex-grow-1">
            <span class="sidebar-section-label">Main</span>

            <a href="{{ route('admin.dashboard') }}" class="nav-link px-3 py-2 d-flex align-items-center gap-2
                      {{ request()->is('admin/dashboard') ? 'bg-primary text-white active' : 'text-white-50' }}">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>

            <span class="sidebar-section-label mt-2">Catalog</span>

            <a href="{{ route('admin.products.index') }}" class="nav-link px-3 py-2 d-flex align-items-center gap-2
                      {{ request()->is('admin/products*') ? 'bg-primary text-white active' : 'text-white-50' }}">
                <i class="bi bi-box-seam"></i> Products
            </a>

            <a href="{{ route('admin.categories.index') }}" class="nav-link px-3 py-2 d-flex align-items-center gap-2
                      {{ request()->is('admin/categories*') ? 'bg-primary text-white active' : 'text-white-50' }}">
                <i class="bi bi-tags"></i> Categories
            </a>

            <a href="{{ route('admin.brands.index') }}" class="nav-link px-3 py-2 d-flex align-items-center gap-2
                      {{ request()->is('admin/brands*') ? 'bg-primary text-white active' : 'text-white-50' }}">
                <i class="bi bi-award"></i> Brands
            </a>

            <span class="sidebar-section-label mt-2">Commerce</span>

            <a href="{{ route('admin.orders.index') }}" class="nav-link px-3 py-2 d-flex align-items-center gap-2
                      {{ request()->is('admin/orders*') ? 'bg-primary text-white active' : 'text-white-50' }}">
                <i class="bi bi-bag-check"></i> Orders
            </a>

            <a href="{{ route('admin.coupons.index') }}" class="nav-link px-3 py-2 d-flex align-items-center gap-2
                      {{ request()->is('admin/coupons*') ? 'bg-primary text-white active' : 'text-white-50' }}">
                <i class="bi bi-tag"></i> Coupons
            </a>

            <span class="sidebar-section-label mt-2">Users</span>

            <a href="{{ route('admin.users.index') }}" class="nav-link px-3 py-2 d-flex align-items-center gap-2
                      {{ request()->is('admin/users*') ? 'bg-primary text-white active' : 'text-white-50' }}">
                <i class="bi bi-people"></i> Users
            </a>

            <span class="sidebar-section-label mt-2">Analytics</span>

            <a href="{{ route('admin.revenue.index') }}" class="nav-link px-3 py-2 d-flex align-items-center gap-2
                      {{ request()->is('admin/revenue*') ? 'bg-primary text-white active' : 'text-white-50' }}">
                <i class="bi bi-graph-up"></i> Revenue
            </a>

            <span class="sidebar-section-label mt-2">System</span>

            <a href="{{ route('admin.audit-log.index') }}" class="nav-link px-3 py-2 d-flex align-items-center gap-2
                      {{ request()->is('admin/audit-log*') ? 'bg-primary text-white active' : 'text-white-50' }}">
                <i class="bi bi-journal-text"></i> Audit Log
            </a>

            <span class="sidebar-section-label mt-2">Demo</span>

            <a href="{{ route('admin.demo.index') }}" class="nav-link px-3 py-2 d-flex align-items-center gap-2
                      {{ request()->is('admin/demo*') ? 'bg-warning text-dark active' : 'text-white-50' }}">
                <i class="bi bi-rocket-takeoff"></i> Demo Sandbox
                <span
                    style="font-size:.6rem;font-weight:700;background:#fbbf24;color:#78350f;padding:1px 5px;border-radius:3px;margin-left:auto">[DEMO]</span>
            </a>
        </div>

        {{-- Sidebar footer: logout --}}
        <div class="p-3 border-top" style="border-color:rgba(255,255,255,.08) !important;">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="button"
                    class="nav-link px-3 py-2 d-flex align-items-center gap-2 text-white-50 w-100 border-0 bg-transparent"
                    onclick="this.closest('form').submit()">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </button>
            </form>
        </div>
    </nav>

    {{-- Admin Main Area --}}
    <div id="adminMain">

        {{-- Topbar --}}
        <div id="adminTopbar">
            <div class="d-flex align-items-center gap-3">
                {{-- Mobile sidebar toggle --}}
                <button class="btn btn-sm btn-outline-secondary d-lg-none border-0"
                    onclick="document.getElementById('adminSidebar').classList.toggle('show')">
                    <i class="bi bi-list fs-5"></i>
                </button>
                <h6 class="mb-0 fw-semibold text-dark">@yield('page-title', 'Dashboard')</h6>
            </div>

            <div class="d-flex align-items-center gap-3">
                {{-- Notification bell --}}
                <div style="position:relative;">
                    @include('admin.partials.notification-bell')
                </div>

                {{-- Admin user --}}
                @auth
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center
                                            justify-content-center fw-bold flex-shrink-0"
                            style="width:30px;height:30px;font-size:0.75rem;">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                        <span class="small fw-medium text-muted d-none d-sm-inline">
                            {{ auth()->user()->name }}
                        </span>
                    </div>
                @endauth
            </div>
        </div>

        {{-- Toast notifications (IMP-009) --}}
        @include('partials.toast')

        {{-- Page content --}}
        <main class="container-fluid py-4">
            @yield('content')
        </main>

    </div>

    {{-- Bootstrap 5.3 JS bundle --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    {{-- Alpine.js --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[data-bs-toggle="tooltip"]')
                .forEach(el => new bootstrap.Tooltip(el));
        });
    </script>

    @stack('scripts')
</body>

</html>
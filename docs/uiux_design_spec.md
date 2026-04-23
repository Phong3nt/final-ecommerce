# UIUX Design Specification — Laravel E-Commerce

> **Audience:** Agent implementing any `[UIUX_MODE]` or `[FULL_STACK_MODE]` IMP task.  
> **Stack constraint:** Bootstrap 5.3 CDN + Alpine.js 3 CDN. No Tailwind. No Vue. No React. No new npm packages.  
> **Reference tasks:** IMP-018 through IMP-031 in [backlog.md](backlog.md).

---

## Architecture: Two Layouts = Two Stylesheets

There are **no separate `.css` files** in this project. Bootstrap 5.3 CDN loaded once in each shared layout IS the entire stylesheet system:

| Layout                                    | Bootstrap 5 CDN            | Covers                                                                                            |
| ----------------------------------------- | -------------------------- | ------------------------------------------------------------------------------------------------- |
| `resources/views/layouts/app.blade.php`   | ✅ Loaded once in `<head>` | All user-facing pages (`/login`, `/products`, `/cart`, `/orders`, `/profile`, `/dashboard`, etc.) |
| `resources/views/layouts/admin.blade.php` | ✅ Loaded once in `<head>` | All admin pages (`/admin/*`)                                                                      |

Every page view must `@extends` one of these — and must **never** import Bootstrap again.

---

## RULE 0 — Task Type + Old CSS Cleanup (MANDATORY first step)

### Step 0-A — Classify the target page before doing anything

| Task Type         | Definition                                                                                        | Pages in this project                                                                                                                                                                            | Work required                                                                                                  |
| ----------------- | ------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ | -------------------------------------------------------------------------------------------------------------- |
| **Migration**     | Page already uses Bootstrap 5 CDN standalone (has its own `<!DOCTYPE html>` + Bootstrap `<link>`) | `products/index`, `products/show`, `checkout/index`, `checkout/guest`, `admin/audit-log`                                                                                                         | Remove standalone HTML wrapper + duplicate CDN link only; keep all Bootstrap classes unchanged; add `@extends` |
| **Full Redesign** | Page has no CSS, only inline `<style>`, or no Bootstrap at all                                    | `auth/login`, `auth/register`, `auth/forgot-password`, `auth/reset-password`, `dashboard`, `profile/show`, `orders/index`, `orders/show`, `cart/index`, `admin/dashboard`, all other admin pages | Remove all old CSS (step 0-B), then rewrite markup using Bootstrap 5 per rules below                           |

> **Migration pages:** after adding `@extends` + removing duplicate Bootstrap CDN link, the page is done — skip visual redesign rules.  
> **Full redesign pages:** follow all rules below.

### Step 0-B — Remove old CSS (apply to ALL pages, both types)

Before writing a single line of new CSS for any IMP task, the agent MUST:

1. **Open the target Blade file(s).**
2. **Search for ALL of the following and remove every match:**
   - Inline `<style>` blocks (`<style>...</style>`) inside `<head>` or `<body>`
   - `style="..."` attributes on individual HTML elements
   - Any `<link rel="stylesheet">` that is being replaced by the new layout
   - Hardcoded font-family declarations (`font-family: sans-serif` etc.)
   - Hardcoded color values in HTML attributes (`bgcolor`, `color`, `text`)
3. **Do NOT remove:**
   - `<script>` tags for Alpine.js, Stripe.js, Chart.js
   - `<meta>` tags
   - `@csrf`, `@include`, `@extends`, `@section` directives
   - Classes applied from Bootstrap (`class="btn btn-primary"` etc.) — keep those
   - IMP-007 Alpine.js interaction classes (`.imp007-spinner` etc.) — keep in a dedicated `<style>` block labeled `/* IMP-007: keep */`

4. **After removal, run `php artisan test --filter=<affected test class>` to verify no test regression before adding new CSS.**

5. **Document removed CSS in the EVAL block under "Cleanup Log":**
   ```
   Cleanup Log:
     - Removed <style> block (42 lines) from auth/login.blade.php
     - Removed style="" attributes on 3 <button> elements in dashboard.blade.php
     - Removed inline font-family declarations from orders/index.blade.php
   ```

---

## RULE 1 — Layout Inheritance

Every page in this project MUST extend one of two shared layouts:

| Layout File                               | Used By                                                                                                                             | When Created |
| ----------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------- | ------------ |
| `resources/views/layouts/app.blade.php`   | All user-facing pages (`/login`, `/register`, `/dashboard`, `/products`, `/cart`, `/orders`, `/profile`, `/addresses`, `/checkout`) | IMP-018      |
| `resources/views/layouts/admin.blade.php` | All admin pages (`/admin/*`)                                                                                                        | IMP-026      |

**Pattern to use in every Blade view:**

```blade
@extends('layouts.app')   {{-- or layouts.admin --}}

@section('title', 'Page Title — E-Commerce')

@section('content')
    {{-- page body here --}}
@endsection
```

**Pattern to NOT use (anti-pattern — causes inconsistency):**

```blade
<!DOCTYPE html>          {{-- ❌ never standalone DOCTYPE in a page view --}}
<html lang="en">
<head>
    <style>...</style>   {{-- ❌ never page-level style blocks --}}
```

---

## RULE 2 — Design System: Color Palette

Use ONLY Bootstrap 5 semantic color classes. Do not hardcode hex values.

| Semantic Role        | Bootstrap Class                                     | Usage                              |
| -------------------- | --------------------------------------------------- | ---------------------------------- |
| Primary action       | `btn-primary` / `bg-primary` / `text-primary`       | Main CTA buttons, active nav links |
| Danger / Destructive | `btn-danger` / `text-danger`                        | Delete, Cancel order, Logout       |
| Success              | `btn-success` / `text-success` / `badge bg-success` | Order confirmed, payment success   |
| Warning              | `btn-warning` / `badge bg-warning text-dark`        | Pending order, low stock           |
| Muted text           | `text-muted`                                        | Secondary labels, captions         |
| Page background      | `bg-light` on `<body>`                              | Light grey surface (`#f8f9fa`)     |
| Card background      | `bg-white`                                          | Cards, forms, modals               |
| Border               | `border` / `border-light`                           | Card borders                       |

**Custom accent (only one allowed — add to layout `<style>` block):**

```css
:root {
  --ec-primary: #4f46e5; /* indigo — used for brand/logo only */
}
```

---

## RULE 3 — Typography

All font declarations go in `layouts/app.blade.php` only — never in individual page views.

```html
<!-- In layouts/app.blade.php <head> -->
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link
  href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap"
  rel="stylesheet"
/>
<style>
  body {
    font-family: "Inter", sans-serif;
    font-size: 0.9375rem;
    color: #212529;
  }
  h1,
  h2,
  h3 {
    font-weight: 700;
  }
  h4,
  h5,
  h6 {
    font-weight: 600;
  }
  .text-label {
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    color: #6c757d;
  }
</style>
```

---

## RULE 4 — Buttons

### Standard Button Classes

| Use Case          | Class                                               | Example                       |
| ----------------- | --------------------------------------------------- | ----------------------------- |
| Primary submit    | `btn btn-primary px-4`                              | Login, Register, Save profile |
| Outline secondary | `btn btn-outline-secondary`                         | Cancel, Back                  |
| Danger            | `btn btn-danger btn-sm`                             | Delete, Remove item           |
| Success           | `btn btn-success`                                   | Confirm order                 |
| Icon-only         | `btn btn-outline-secondary btn-sm` + Bootstrap Icon | Edit pencil, Trash            |

### Hover & Transition Effect (add to layout `<style>` block)

```css
/* Smooth lift on all buttons */
.btn {
  transition:
    transform 0.15s ease,
    box-shadow 0.15s ease;
}
.btn:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}
.btn:active:not(:disabled) {
  transform: translateY(0);
  box-shadow: none;
}
```

### Loading State (Alpine.js — use on all form submit buttons)

```blade
<button type="submit" class="btn btn-primary px-4"
        x-data="{ loading: false }"
        @click="loading = true"
        :disabled="loading">
    <span x-show="loading" class="spinner-border spinner-border-sm me-2" role="status"></span>
    <span x-text="loading ? 'Please wait…' : 'Login'">Login</span>
</button>
```

---

## RULE 5 — Cards

All content containers (forms, dashboards, settings panels) must use Bootstrap cards:

```blade
<div class="card shadow-sm border-0 rounded-3">
    <div class="card-body p-4">
        {{-- content --}}
    </div>
</div>
```

For card grids (dashboard quick-links, KPI widgets):

```blade
<div class="row g-3">
    <div class="col-md-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body d-flex align-items-center gap-3">
                {{-- icon + stat --}}
            </div>
        </div>
    </div>
</div>
```

Card hover effect (add to layout `<style>` block):

```css
.card-hover {
  transition:
    transform 0.15s ease,
    box-shadow 0.15s ease;
  cursor: pointer;
}
.card-hover:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08) !important;
}
```

---

## RULE 6 — Forms

### Input Fields

All inputs must use `form-control` and wrap inside `mb-3`:

```blade
<div class="mb-3">
    <label for="email" class="form-label fw-semibold">Email address</label>
    <input type="email" id="email" name="email"
           class="form-control @error('email') is-invalid @enderror"
           value="{{ old('email') }}" required>
    @error('email')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
```

Never use `<input>` without `form-control`. Never write custom input border/padding CSS.

### Focus Ring

Do NOT override Bootstrap's default focus ring. Bootstrap 5.3's default `--bs-focus-ring-color` is sufficient.

---

## RULE 7 — Auth Pages Layout (IMP-019, IMP-020, IMP-024, IMP-030)

Auth pages (login, register, forgot-password, reset-password, verify-email) use this centered card pattern:

```blade
@extends('layouts.app')
@section('content')
<div class="min-vh-100 d-flex align-items-center justify-content-center bg-light py-5">
    <div class="card shadow-sm border-0 rounded-4" style="width: 100%; max-width: 420px;">
        <div class="card-body p-4 p-md-5">

            {{-- Brand header --}}
            <div class="text-center mb-4">
                <div class="d-inline-flex align-items-center justify-content-center
                            bg-primary bg-opacity-10 rounded-circle mb-3"
                     style="width:56px;height:56px;">
                    <i class="bi bi-shop fs-4 text-primary"></i>
                </div>
                <h1 class="h4 fw-bold mb-0">E-Commerce</h1>
                <p class="text-muted small">Sign in to your account</p>
            </div>

            {{-- Form content here --}}

        </div>
    </div>
</div>
@endsection
```

**Requirements checklist for auth pages:**

- [ ] Card max-width 420px, centered vertically and horizontally
- [ ] Brand icon (Bootstrap Icons `bi-shop`) above title
- [ ] All inputs use `form-control` with `@error` → `is-invalid`
- [ ] Submit button uses loading state (Rule 4)
- [ ] Google OAuth button: `btn btn-outline-dark w-100` with Google logo SVG inline (no external img)
- [ ] "Forgot password" / "Create account" links below form: `text-center mt-3 small`
- [ ] No inline `<style>` block — all layout comes from Bootstrap classes

---

## RULE 8 — Navigation Bar (IMP-031, IMP-018)

### User-Facing Navbar (`layouts/app.blade.php`)

```blade
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
    <div class="container">
        {{-- Brand --}}
        <a class="navbar-brand fw-bold text-primary d-flex align-items-center gap-2" href="{{ url('/') }}">
            <i class="bi bi-shop"></i> ShopName
        </a>

        {{-- Mobile toggle --}}
        <button class="navbar-toggler border-0" type="button"
                data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            {{-- Left links --}}
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('products*') ? 'active fw-semibold' : '' }}"
                       href="{{ route('products.index') }}">Shop</a>
                </li>
            </ul>

            {{-- Right: cart + user --}}
            <ul class="navbar-nav align-items-center gap-2">
                {{-- Cart icon with badge --}}
                <li class="nav-item">
                    <a class="nav-link position-relative" href="{{ route('cart.index') }}">
                        <i class="bi bi-cart3 fs-5"></i>
                        @if(session('cart_count', 0) > 0)
                            <span class="position-absolute top-0 start-100 translate-middle
                                         badge rounded-pill bg-danger">
                                {{ session('cart_count') }}
                            </span>
                        @endif
                    </a>
                </li>

                @auth
                    {{-- User dropdown --}}
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center gap-2"
                           href="#" data-bs-toggle="dropdown">
                            @if(auth()->user()->avatar)
                                <img src="{{ Storage::url(auth()->user()->avatar) }}"
                                     class="rounded-circle" width="28" height="28"
                                     style="object-fit:cover;" alt="">
                            @else
                                <div class="rounded-circle bg-primary text-white d-flex
                                            align-items-center justify-content-center fw-bold"
                                     style="width:28px;height:28px;font-size:0.75rem;">
                                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                </div>
                            @endif
                            {{ auth()->user()->name }}
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                            <li><a class="dropdown-item" href="{{ route('dashboard') }}"><i class="bi bi-grid me-2"></i>Dashboard</a></li>
                            <li><a class="dropdown-item" href="{{ route('profile.show') }}"><i class="bi bi-person me-2"></i>Profile</a></li>
                            <li><a class="dropdown-item" href="{{ route('orders.index') }}"><i class="bi bi-bag me-2"></i>My Orders</a></li>
                            <li><a class="dropdown-item" href="{{ route('addresses.index') }}"><i class="bi bi-geo-alt me-2"></i>Addresses</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>Logout</button>
                                </form>
                            </li>
                        </ul>
                    </li>
                @else
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('login') }}">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-primary btn-sm px-3" href="{{ route('register') }}">Register</a>
                    </li>
                @endauth
            </ul>
        </div>
    </div>
</nav>
```

**Active link detection:** Use `request()->is('products*')` pattern — never hardcode URLs.  
**Mobile:** Bootstrap's collapse + `navbar-toggler` handles hamburger. No custom JS needed.  
**Sticky:** `sticky-top` keeps navbar visible on scroll.

---

## RULE 9 — Admin Sidebar (IMP-026)

### Admin Layout Structure (`layouts/admin.blade.php`)

```
┌─────────────────────────────────────────────────────────────┐
│ TOPBAR: Logo | Page title | Notification bell | Admin name  │
├──────────┬──────────────────────────────────────────────────┤
│          │                                                  │
│ SIDEBAR  │  @yield('content')                               │
│ (240px)  │                                                  │
│          │                                                  │
└──────────┴──────────────────────────────────────────────────┘
```

Sidebar nav items with active state:

```blade
<nav class="d-flex flex-column gap-1 p-3" style="width:240px;min-height:100vh;background:#1e293b;">
    <a href="{{ route('admin.dashboard') }}"
       class="nav-link px-3 py-2 rounded-2 d-flex align-items-center gap-2
              {{ request()->is('admin/dashboard') ? 'bg-primary text-white' : 'text-white-50' }}">
        <i class="bi bi-speedometer2"></i> Dashboard
    </a>
    <a href="{{ route('admin.orders.index') }}"
       class="nav-link px-3 py-2 rounded-2 d-flex align-items-center gap-2
              {{ request()->is('admin/orders*') ? 'bg-primary text-white' : 'text-white-50' }}">
        <i class="bi bi-bag-check"></i> Orders
    </a>
    {{-- ... remaining nav items --}}
</nav>
```

Sidebar hover (add to admin layout `<style>` block):

```css
/* Sidebar nav links */
#adminSidebar .nav-link:not(.active):hover {
  background: rgba(255, 255, 255, 0.08);
  color: #fff !important;
  transition: background 0.15s ease;
}
```

---

## RULE 10 — Event / Interaction Effects (Alpine.js)

These patterns must be used consistently. Do not invent new animation patterns.

### 1. Fade-in on page load

```blade
<div x-data x-init="$el.classList.add('fade-in')">
    {{-- content --}}
</div>
```

```css
/* Add to layout <style> block */
.fade-in {
  animation: fadeIn 0.25s ease forwards;
}
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(8px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
```

### 2. Confirm before destructive action (delete, cancel order)

```blade
<button x-data
        @click="if(!confirm('Are you sure?')) $event.preventDefault()"
        class="btn btn-danger btn-sm">
    Delete
</button>
```

### 3. Toggle visibility (edit inline, show/hide form)

```blade
<div x-data="{ open: false }">
    <button @click="open = !open" class="btn btn-outline-secondary btn-sm">Edit</button>
    <div x-show="open" x-transition>
        {{-- edit form --}}
    </div>
</div>
```

### 4. Loading spinner on form submit (already in Rule 4)

Use Rule 4's Alpine loading pattern on ALL forms that POST to the server.

### 5. Tooltip (Bootstrap 5 built-in — no Alpine needed)

```blade
<button data-bs-toggle="tooltip" data-bs-title="Export to CSV" class="btn btn-outline-secondary btn-sm">
    <i class="bi bi-download"></i>
</button>
```

Enable globally in the layout's `<script>` block:

```html
<script>
  document.addEventListener("DOMContentLoaded", () => {
    document
      .querySelectorAll('[data-bs-toggle="tooltip"]')
      .forEach((el) => new bootstrap.Tooltip(el));
  });
</script>
```

---

## RULE 11 — Page Background & Spacing

| Context         | Rule                                                                                      |
| --------------- | ----------------------------------------------------------------------------------------- |
| All user pages  | `<body class="bg-light">` — Bootstrap light grey `#f8f9fa`                                |
| Auth pages      | Centered card on `bg-light` (Rule 7) — do NOT use dark backgrounds                        |
| Admin pages     | `<body style="background:#f1f5f9;">` — slightly cooler grey than user                     |
| Page wrapper    | `<main class="container py-4">` or `<main class="container-fluid py-4">` for admin        |
| Section spacing | Use Bootstrap spacing utilities (`mb-4`, `mt-3`, `p-4`) — never write `margin: 40px auto` |

---

## RULE 12 — Bootstrap Icons

All icons in this project use **Bootstrap Icons CDN** (loaded once in the layout `<head>`):

```html
<link
  rel="stylesheet"
  href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
/>
```

Common icon reference:

| Use           | Icon class                     |
| ------------- | ------------------------------ |
| Shop / brand  | `bi-shop`                      |
| Cart          | `bi-cart3`                     |
| User          | `bi-person`                    |
| Orders / bag  | `bi-bag-check`                 |
| Dashboard     | `bi-speedometer2`              |
| Products      | `bi-box-seam`                  |
| Settings      | `bi-gear`                      |
| Logout        | `bi-box-arrow-right`           |
| Edit          | `bi-pencil`                    |
| Delete        | `bi-trash`                     |
| Address       | `bi-geo-alt`                   |
| Notifications | `bi-bell`                      |
| Revenue       | `bi-graph-up`                  |
| Coupon        | `bi-tag`                       |
| Audit         | `bi-journal-text`              |
| Success check | `bi-check-circle-fill`         |
| Warning       | `bi-exclamation-triangle-fill` |
| Info          | `bi-info-circle-fill`          |

---

## RULE 13 — Shared Layout File Template (`layouts/app.blade.php`)

```blade
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
      body { font-family:'Inter',sans-serif; font-size:0.9375rem; }
      :root { --ec-primary:#4f46e5; }

      /* ── Button effects ──────────────────────────────── */
      .btn { transition:transform .15s ease,box-shadow .15s ease; }
      .btn:hover:not(:disabled) { transform:translateY(-1px); box-shadow:0 4px 12px rgba(0,0,0,.10); }
      .btn:active:not(:disabled) { transform:translateY(0); box-shadow:none; }

      /* ── Card hover ───────────────────────────────────── */
      .card-hover { transition:transform .15s ease,box-shadow .15s ease; cursor:pointer; }
      .card-hover:hover { transform:translateY(-3px); box-shadow:0 8px 24px rgba(0,0,0,.08)!important; }

      /* ── Fade-in ──────────────────────────────────────── */
      .fade-in { animation:fadeIn .25s ease forwards; }
      @keyframes fadeIn { from{opacity:0;transform:translateY(8px)} to{opacity:1;transform:translateY(0)} }

      /* ── Text utilities ───────────────────────────────── */
      .text-label { font-size:.75rem;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:#6c757d; }
    </style>

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
      document.addEventListener('DOMContentLoaded',()=>{
        document.querySelectorAll('[data-bs-toggle="tooltip"]')
          .forEach(el=>new bootstrap.Tooltip(el));
      });
    </script>

    @stack('scripts')
</body>
</html>
```

---

## RULE 14 — Implementation Order for IMP-018 to IMP-031

The agent MUST implement in this exact dependency order:

```
Phase 1 — Foundations + MIGRATION of already-Bootstrap pages
  IMP-018: Create layouts/app.blade.php (Bootstrap 5 CDN entry point for all user pages)
           + MIGRATE (task type = Migration):
             - products/index.blade.php    [IMP-001, already Bootstrap 5]
             - products/show.blade.php     [IMP-010, already Bootstrap 5]
             - checkout/index.blade.php    [IMP-003, already Bootstrap 5]
             - checkout/guest.blade.php    [IMP-004, already Bootstrap 5]
  IMP-026: Create layouts/admin.blade.php (Bootstrap 5 CDN entry point for all admin pages)
           + MIGRATE (task type = Migration):
             - admin/audit-log/index.blade.php  [IMP-016, already Bootstrap 5]
  IMP-031: Create partials/navbar.blade.php  ← included by layouts/app.blade.php

Phase 2 — Auth pages REDESIGN (standalone, no cross-page deps)
  IMP-019: Login          [task type = Full Redesign]
  IMP-020: Register       [task type = Full Redesign]
  IMP-024: Forgot Password + Reset Password  [task type = Full Redesign]
  IMP-030: Email Verify + Google OAuth callback  [task type = Full Redesign]

Phase 3 — User pages REDESIGN (depend on IMP-018 layout)
  IMP-021: User Dashboard  [task type = Full Redesign]
  IMP-022: Profile         [task type = Full Redesign]
  IMP-023: Order History + Order Detail  [task type = Full Redesign]
  IMP-025: Addresses       [task type = Full Redesign]

Phase 4 — Admin pages REDESIGN (depend on IMP-026 layout)
  IMP-027: Admin Dashboard  [task type = Full Redesign]

Phase 5 — Remaining pages
  IMP-028: Welcome / Homepage  [task type = Full Redesign — replace Laravel default]
  IMP-029: Cart page           [task type = Full Redesign — cart has NO Bootstrap, only IMP-007 Alpine.js]
```

**Never implement a Phase N+1 task before all Phase N tasks are Done.**

---

## RULE 15 — What NOT to Do

| Forbidden                                                | Reason                                                          |
| -------------------------------------------------------- | --------------------------------------------------------------- |
| Adding Tailwind CSS                                      | Project already uses Bootstrap 5; mixing causes class conflicts |
| Writing custom grid CSS (`display:grid`, `display:flex`) | Use Bootstrap's `row`/`col-*`/`d-flex` utilities instead        |
| Using `<table>` for layout (not data)                    | Use Bootstrap grid                                              |
| `style="color:#...;margin:...px"` on any element         | Use Bootstrap utilities (`text-danger`, `mb-3`)                 |
| Creating a new `<style>` block in a page view            | All CSS lives in the layout or a `@push('styles')` stack        |
| Importing a second copy of Bootstrap                     | One CDN link in the layout — never in page views                |
| Importing Alpine.js in page views                        | Already loaded in the layout                                    |
| Using `jquery`                                           | Bootstrap 5 uses vanilla JS; no jQuery in this project          |

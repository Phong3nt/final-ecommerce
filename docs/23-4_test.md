# 23/4 — Environment & UI Audit Report

> Generated: 2026-04-23  
> Project: Laravel E-Commerce · `ecommerce/` directory

---

## PART 1 — Complete URL / Route List

### Public Routes (no auth required)

| Method | URL                        | Route Name           | Controller                 | Notes                           |
| ------ | -------------------------- | -------------------- | -------------------------- | ------------------------------- |
| GET    | `/`                        | —                    | closure → `welcome` view   | Laravel default welcome page    |
| GET    | `/products`                | `products.index`     | `ProductController@index`  | PC-001 product catalog          |
| GET    | `/products/search`         | `products.search`    | `ProductController@search` | PC-002 search                   |
| GET    | `/products/{slug}`         | `products.show`      | `ProductController@show`   | PC-005 product detail           |
| POST   | `/products/{slug}/reviews` | `reviews.store`      | `ReviewController@store`   | RV-001 — auth required          |
| POST   | `/cart`                    | `cart.store`         | `CartController@store`     | SC-001 add to cart (guest+auth) |
| GET    | `/cart`                    | `cart.index`         | `CartController@index`     | SC-002 view cart                |
| POST   | `/cart/coupon`             | `cart.coupon.apply`  | `CouponController@apply`   | SC-005                          |
| DELETE | `/cart/coupon`             | `cart.coupon.remove` | `CouponController@remove`  | SC-005                          |
| PATCH  | `/cart/{productId}`        | `cart.update`        | `CartController@update`    | SC-003                          |
| DELETE | `/cart/{productId}`        | `cart.destroy`       | `CartController@destroy`   | SC-004                          |

### Guest-Only Auth Routes (redirects if already logged in)

| Method | URL                       | Route Name             | Controller                       | Notes         |
| ------ | ------------------------- | ---------------------- | -------------------------------- | ------------- |
| GET    | `/register`               | `register`             | `RegisterController@show`        | AU-001        |
| POST   | `/register`               | `register.store`       | `RegisterController@store`       | throttle:10,1 |
| GET    | `/login`                  | `login`                | `LoginController@show`           | AU-002        |
| POST   | `/login`                  | `login.store`          | `LoginController@store`          | throttle:10,1 |
| GET    | `/forgot-password`        | `password.request`     | `ForgotPasswordController@show`  | AU-005        |
| POST   | `/forgot-password`        | `password.email`       | `ForgotPasswordController@store` | throttle:10,1 |
| GET    | `/reset-password/{token}` | `password.reset`       | `ResetPasswordController@show`   | AU-005        |
| POST   | `/reset-password`         | `password.update`      | `ResetPasswordController@store`  | AU-005        |
| GET    | `/auth/google/redirect`   | `auth.google.redirect` | `GoogleController@redirect`      | AU-003        |
| GET    | `/auth/google/callback`   | `auth.google.callback` | `GoogleController@callback`      | AU-003        |

### Authenticated User Routes (middleware: auth)

| Method | URL                                | Route Name                | Controller                           | Notes            |
| ------ | ---------------------------------- | ------------------------- | ------------------------------------ | ---------------- |
| POST   | `/logout`                          | `logout`                  | `LoginController@destroy`            | AU-004           |
| GET    | `/email/verify`                    | `verification.notice`     | `EmailVerificationController@notice` | AU-002           |
| GET    | `/email/verify/{id}/{hash}`        | `verification.verify`     | `EmailVerificationController@verify` | signed           |
| POST   | `/email/verification-notification` | `verification.send`       | `EmailVerificationController@resend` | throttle:6,1     |
| GET    | `/dashboard`                       | `dashboard`               | closure → `dashboard` view           | user home        |
| GET    | `/profile`                         | `profile.show`            | `ProfileController@show`             | UP-001           |
| PUT    | `/profile`                         | `profile.update`          | `ProfileController@update`           | UP-001           |
| GET    | `/addresses`                       | `addresses.index`         | `UserAddressController@index`        | UP-002           |
| POST   | `/addresses`                       | `addresses.store`         | `UserAddressController@store`        | UP-002           |
| PUT    | `/addresses/{address}`             | `addresses.update`        | `UserAddressController@update`       | UP-002           |
| DELETE | `/addresses/{address}`             | `addresses.destroy`       | `UserAddressController@destroy`      | UP-002           |
| PATCH  | `/addresses/{address}/default`     | `addresses.setDefault`    | `UserAddressController@setDefault`   | UP-002           |
| GET    | `/orders`                          | `orders.index`            | `OrderController@index`              | OH-001           |
| GET    | `/orders/{order}`                  | `orders.show`             | `OrderController@show`               | OH-002           |
| POST   | `/orders/{order}/cancel`           | `orders.cancel`           | `OrderController@cancel`             | OH-004           |
| GET    | `/checkout`                        | `checkout.index`          | `CheckoutController@showCheckout`    | IMP-003 one-page |
| POST   | `/checkout/session`                | `checkout.session.store`  | `CheckoutController@storeSession`    | IMP-003          |
| GET    | `/checkout/address`                | `checkout.address`        | `CheckoutController@showAddress`     | CP-001           |
| POST   | `/checkout/address`                | `checkout.address.store`  | `CheckoutController@storeAddress`    | CP-001           |
| GET    | `/checkout/shipping`               | `checkout.shipping`       | `CheckoutController@showShipping`    | CP-002           |
| POST   | `/checkout/shipping`               | `checkout.shipping.store` | `CheckoutController@storeShipping`   | CP-002           |
| GET    | `/checkout/review`                 | `checkout.review`         | `CheckoutController@showReview`      | CP-003           |
| POST   | `/checkout/review`                 | `checkout.place-order`    | `CheckoutController@placeOrder`      | CP-003           |
| GET    | `/checkout/success`                | `checkout.success`        | `CheckoutController@showSuccess`     | CP-005           |

### Guest Checkout Routes (no auth, IMP-004)

| Method | URL                       | Route Name                     | Controller                             |
| ------ | ------------------------- | ------------------------------ | -------------------------------------- |
| GET    | `/checkout/guest`         | `checkout.guest.index`         | `CheckoutController@showGuestCheckout` |
| POST   | `/checkout/guest/session` | `checkout.guest.session.store` | `CheckoutController@storeGuestSession` |
| POST   | `/checkout/guest/order`   | `checkout.guest.place-order`   | `CheckoutController@placeGuestOrder`   |
| GET    | `/checkout/guest/success` | `checkout.guest.success`       | `CheckoutController@showGuestSuccess`  |

### Admin Routes (middleware: auth + role:admin, prefix: /admin)

| Method | URL                                          | Route Name                        | Controller                                | Notes   |
| ------ | -------------------------------------------- | --------------------------------- | ----------------------------------------- | ------- |
| GET    | `/admin/dashboard`                           | `admin.dashboard`                 | `AdminController@dashboard`               | AD-001  |
| GET    | `/admin/chart-data`                          | `admin.chart-data`                | `AdminController@chartData`               | AD-002  |
| GET    | `/admin/orders`                              | `admin.orders.index`              | `AdminOrderController@index`              | OM-001  |
| GET    | `/admin/orders/export`                       | `admin.orders.export`             | `AdminOrderController@export`             | OM-004  |
| GET    | `/admin/orders/{order}`                      | `admin.orders.show`               | `AdminOrderController@show`               | OM-002  |
| PATCH  | `/admin/orders/{order}/status`               | `admin.orders.status`             | `OrderStatusController@update`            | OH-003  |
| POST   | `/admin/orders/{order}/refund`               | `admin.orders.refund`             | `RefundController@store`                  | OM-005  |
| GET    | `/admin/products`                            | `admin.products.index`            | `AdminProductController@index`            | PM-001  |
| GET    | `/admin/products/create`                     | `admin.products.create`           | `AdminProductController@create`           | PM-001  |
| POST   | `/admin/products`                            | `admin.products.store`            | `AdminProductController@store`            | PM-001  |
| POST   | `/admin/products/import`                     | `admin.products.import`           | `AdminProductController@import`           | PM-005  |
| GET    | `/admin/products/{product}/edit`             | `admin.products.edit`             | `AdminProductController@edit`             | PM-002  |
| PATCH  | `/admin/products/{product}`                  | `admin.products.update`           | `AdminProductController@update`           | PM-002  |
| DELETE | `/admin/products/{product}`                  | `admin.products.destroy`          | `AdminProductController@destroy`          | PM-003  |
| GET    | `/admin/products/{product}/images`           | `admin.products.images`           | `AdminProductController@images`           | PM-006  |
| POST   | `/admin/products/{product}/images/reorder`   | `admin.products.images.reorder`   | `AdminProductController@reorderImages`    | PM-006  |
| POST   | `/admin/products/{product}/images/thumbnail` | `admin.products.images.thumbnail` | `AdminProductController@setThumbnail`     | PM-006  |
| DELETE | `/admin/products/{product}/images/{index}`   | `admin.products.images.destroy`   | `AdminProductController@destroyImage`     | PM-006  |
| GET    | `/admin/users`                               | `admin.users.index`               | `AdminUserController@index`               | UM-001  |
| GET    | `/admin/users/{user}`                        | `admin.users.show`                | `AdminUserController@show`                | UM-002  |
| PATCH  | `/admin/users/{user}/toggle-status`          | `admin.users.toggle-status`       | `AdminUserController@toggleStatus`        | UM-003  |
| PATCH  | `/admin/users/{user}/assign-role`            | `admin.users.assign-role`         | `AdminUserController@assignRole`          | UM-004  |
| GET    | `/admin/revenue`                             | `admin.revenue.index`             | `RevenueController@index`                 | RM-001  |
| GET    | `/admin/revenue/products`                    | `admin.revenue.products`          | `RevenueController@products`              | RM-002  |
| GET    | `/admin/revenue/products/export`             | `admin.revenue.products.export`   | `RevenueController@exportProducts`        | RM-002  |
| GET    | `/admin/categories`                          | `admin.categories.index`          | `AdminCategoryController@index`           | PM-004  |
| GET    | `/admin/categories/create`                   | `admin.categories.create`         | `AdminCategoryController@create`          | PM-004  |
| POST   | `/admin/categories`                          | `admin.categories.store`          | `AdminCategoryController@store`           | PM-004  |
| GET    | `/admin/categories/{category}/edit`          | `admin.categories.edit`           | `AdminCategoryController@edit`            | PM-004  |
| PATCH  | `/admin/categories/{category}`               | `admin.categories.update`         | `AdminCategoryController@update`          | PM-004  |
| DELETE | `/admin/categories/{category}`               | `admin.categories.destroy`        | `AdminCategoryController@destroy`         | PM-004  |
| GET    | `/admin/coupons`                             | `admin.coupons.index`             | `AdminCouponController@index`             | RM-003  |
| GET    | `/admin/coupons/create`                      | `admin.coupons.create`            | `AdminCouponController@create`            | RM-003  |
| POST   | `/admin/coupons`                             | `admin.coupons.store`             | `AdminCouponController@store`             | RM-003  |
| GET    | `/admin/coupons/{coupon}/edit`               | `admin.coupons.edit`              | `AdminCouponController@edit`              | RM-003  |
| PATCH  | `/admin/coupons/{coupon}`                    | `admin.coupons.update`            | `AdminCouponController@update`            | RM-003  |
| DELETE | `/admin/coupons/{coupon}`                    | `admin.coupons.destroy`           | `AdminCouponController@destroy`           | RM-003  |
| PATCH  | `/admin/coupons/{coupon}/toggle`             | `admin.coupons.toggle`            | `AdminCouponController@toggle`            | RM-003  |
| GET    | `/admin/notifications`                       | `admin.notifications.index`       | `AdminNotificationController@index`       | NT-002  |
| PATCH  | `/admin/notifications/read-all`              | `admin.notifications.read-all`    | `AdminNotificationController@markAllRead` | NT-002  |
| PATCH  | `/admin/notifications/{notification}/read`   | `admin.notifications.read`        | `AdminNotificationController@markRead`    | NT-002  |
| GET    | `/admin/audit-log`                           | `admin.audit-log.index`           | `AuditLogController@index`                | IMP-016 |

### API Routes

| Method | URL         | Middleware     | Notes                                |
| ------ | ----------- | -------------- | ------------------------------------ |
| GET    | `/api/user` | `auth:sanctum` | Returns authenticated user (Sanctum) |

### Webhook Routes (public, no CSRF)

| Method | URL               | Route Name       | Notes                                        |
| ------ | ----------------- | ---------------- | -------------------------------------------- |
| POST   | `/webhook/stripe` | `webhook.stripe` | CP-003 — Stripe signs payload, CSRF excluded |

---

## PART 2 — Why `http://127.0.0.1:8000/` Works but `http://localhost/ecommerce/public` Does Not

### Root Cause: Two Different Web Servers

The project has **two separate server setups** that are completely independent:

```
┌─────────────────────────────────────────────────────────────────┐
│  php artisan serve                                              │
│  → Built-in PHP dev server                                      │
│  → Listens on 127.0.0.1:8000                                    │
│  → Uses ecommerce/ as document root automatically               │
│  → Does NOT need Apache, does NOT care about APP_URL            │
│  → Result: http://127.0.0.1:8000/ ✅ WORKS                      │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│  XAMPP Apache                                                   │
│  → Listens on localhost:80                                      │
│  → Document root: C:\xampp\htdocs\                              │
│  → Expects the project at C:\xampp\htdocs\ecommerce\public\     │
│  → Result: http://localhost/ecommerce/public ❌ FAILS            │
└─────────────────────────────────────────────────────────────────┘
```

### Why `localhost/ecommerce/public` Fails — All Possible Causes

| #   | Cause                                                                                                                       | How to Verify                                   | Fix                                                           |
| --- | --------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------- | ------------------------------------------------------------- |
| 1   | **Project not in `htdocs`** — project is at `E:\TCD1104...\ecommerce`, NOT under `C:\xampp\htdocs\`                         | Check if `C:\xampp\htdocs\ecommerce\` exists    | Copy/symlink project to htdocs, OR use virtual host           |
| 2   | **Apache not running** — XAMPP Apache module stopped                                                                        | XAMPP Control Panel → Apache status             | Click Start next to Apache                                    |
| 3   | **`mod_rewrite` not enabled** — Laravel needs `.htaccess` URL rewriting                                                     | `public/.htaccess` exists but Apache ignores it | Enable `mod_rewrite` in `httpd.conf`; add `AllowOverride All` |
| 4   | **`APP_URL` mismatch** — .env says `http://localhost/ecommerce/public` but `php artisan serve` uses `http://127.0.0.1:8000` | They conflict                                   | Each server needs its own APP_URL                             |
| 5   | **PHP not enabled in Apache** — Apache serves static files only                                                             | Browse any `.php` file directly                 | Enable PHP module in XAMPP                                    |

### The `APP_URL` Problem (causes asset 404s even if Apache works)

Current `.env`:

```
APP_URL=http://localhost/ecommerce/public
```

When running `php artisan serve`, the built-in server uses port 8000 but APP_URL points to XAMPP. This means:

- `asset()`, `url()`, `route()` helpers generate URLs like `http://localhost/ecommerce/public/...`
- Browser requests those assets on port 80 (Apache), not port 8000 (artisan serve)
- **Result:** CSS/JS/images all 404 even though the page loads

### ✅ Resolved — Using `http://127.0.0.1:8000` exclusively

**Decision made:** `php artisan serve` is the chosen development server. XAMPP Apache configuration is not required.

Applied fix:

- `.env` updated: `APP_URL=http://127.0.0.1:8000`
- Ran: `php artisan config:clear`

Development URL: **`http://127.0.0.1:8000`** — use this for all testing and browser access.

---

## PART 3 — Why Login & Dashboard Pages Look Ugly

### What the Views Actually Contain

| View                        | CSS Framework            | Styling                                                    |
| --------------------------- | ------------------------ | ---------------------------------------------------------- |
| `auth/login.blade.php`      | ❌ None                  | Zero CSS — bare `<h1>`, raw `<input>`, unstyled `<button>` |
| `auth/register.blade.php`   | ❌ Inline `<style>` only | 15 lines of hand-written CSS, no framework                 |
| `dashboard.blade.php`       | ❌ Inline `<style>` only | Minimal inline CSS, plain text layout                      |
| `profile/show.blade.php`    | ❌ None                  | Completely unstyled HTML form                              |
| `orders/index.blade.php`    | ❌ Inline `<style>` only | Hand-written table CSS                                     |
| `orders/show.blade.php`     | ❌ Inline `<style>` only | Minimal hand-written CSS                                   |
| `products/index.blade.php`  | ✅ Bootstrap 5           | IMP-001 Bento Grid — looks good                            |
| `products/show.blade.php`   | ✅ Bootstrap 5           | IMP-010 lightbox — looks good                              |
| `cart/index.blade.php`      | Partial (inline)         | IMP-007 Alpine.js but minimal CSS                          |
| `checkout/index.blade.php`  | ✅ Bootstrap 5           | IMP-003 one-page — reasonable                              |
| `admin/dashboard.blade.php` | ❌ Inline `<style>` only | Plain KPI cards, no framework                              |

### Root Cause: IMP Tasks Were Scoped to Specific Epics

Looking at every IMP task in `backlog.md`:

| IMP     | Target Pages                  |
| ------- | ----------------------------- |
| IMP-001 | Product Catalog (`/products`) |
| IMP-002 | Catalog async areas + Admin   |
| IMP-003 | Checkout (one-page)           |
| IMP-004 | Guest Checkout                |
| IMP-005 | Cart drawer                   |
| IMP-007 | Cart interactions             |
| IMP-009 | Toast notifications (global)  |
| IMP-010 | Product detail lightbox       |
| IMP-011 | Order status stepper          |
| IMP-012 | Star rating input             |
| IMP-013 | Admin tables                  |
| IMP-016 | Audit log                     |
| IMP-017 | Admin real-time notifications |

**No IMP task was ever created for:**

- `/login` — `auth/login.blade.php`
- `/register` — `auth/register.blade.php`
- `/dashboard` — `dashboard.blade.php`
- `/profile` — `profile/show.blade.php`
- `/addresses` — `user/addresses/`

The backlog IMP section was built feature-by-feature. Auth pages and the user dashboard were completed under tasks `AU-001`, `AU-002`, `UP-001` — those tasks were judged "Done" once logic/tests passed, **without a UI quality bar**. No one ever proposed an `IMP` to redesign them.

Additionally, there is **no shared layout** (`layouts/app.blade.php`). Each page imports its own CSS independently, leading to inconsistent design:

- Some pages use Bootstrap 5 CDN
- Some use inline `<style>` blocks
- Login has zero CSS
- No navbar, no footer, no consistent navigation across pages

---

## PART 4 — All Pages Design Audit + Improvement List

### Design Quality Rating Key

- ✅ Modern — Bootstrap 5 or equivalent, responsive, visually polished
- ⚠️ Minimal — inline CSS only, functional but plain
- ❌ Bare — zero or near-zero styling, looks like 1995 HTML

### Page-by-Page Audit

| Page                | URL                       | View File                        | Rating        | Issues                                                                                                      |
| ------------------- | ------------------------- | -------------------------------- | ------------- | ----------------------------------------------------------------------------------------------------------- |
| Home / Welcome      | `/`                       | `welcome.blade.php`              | ✅ Modern     | Laravel default welcome — fine for now but irrelevant to an e-commerce app                                  |
| Login               | `/login`                  | `auth/login.blade.php`           | ❌ Bare       | No CSS at all. Raw `<h1>` and `<input>`. No logo, no branding, no background.                               |
| Register            | `/register`               | `auth/register.blade.php`        | ⚠️ Minimal    | 15 lines inline CSS. No card layout, no brand header, no social login button visibility.                    |
| Forgot Password     | `/forgot-password`        | `auth/forgot-password.blade.php` | Unknown       | Not checked — likely same pattern as login                                                                  |
| Reset Password      | `/reset-password/{token}` | `auth/reset-password.blade.php`  | Unknown       | Not checked — likely same pattern as login                                                                  |
| Email Verify        | `/email/verify`           | `auth/verify-email.blade.php`    | Unknown       | Not checked                                                                                                 |
| User Dashboard      | `/dashboard`              | `dashboard.blade.php`            | ❌ Bare       | Plain "Welcome, {name}!" with inline CSS. No navigation, no links to orders/profile/cart, no meaningful UI. |
| Profile             | `/profile`                | `profile/show.blade.php`         | ❌ Bare       | Zero CSS. No card wrapper, no avatar preview styling.                                                       |
| Addresses           | `/addresses`              | `user/addresses/`                | Unknown       | Not checked                                                                                                 |
| Product Catalog     | `/products`               | `products/index.blade.php`       | ✅ Modern     | Bootstrap 5 + Bento Grid (IMP-001) — best-designed page                                                     |
| Product Search      | `/products/search`        | `products/search.blade.php`      | Unknown       | Not checked                                                                                                 |
| Product Detail      | `/products/{slug}`        | `products/show.blade.php`        | ✅ Modern     | Bootstrap 5 + lightbox (IMP-010)                                                                            |
| Cart                | `/cart`                   | `cart/index.blade.php`           | ⚠️ Minimal    | Alpine.js interactions (IMP-007) but no nav header, minimal base CSS                                        |
| Checkout            | `/checkout`               | `checkout/index.blade.php`       | ⚠️ Reasonable | Bootstrap 5, functional but no top nav/breadcrumb                                                           |
| Order History       | `/orders`                 | `orders/index.blade.php`         | ⚠️ Minimal    | Inline CSS table, no nav, no search/filter UI                                                               |
| Order Detail        | `/orders/{order}`         | `orders/show.blade.php`          | ⚠️ Minimal    | Inline CSS, no nav header                                                                                   |
| Admin Dashboard     | `/admin/dashboard`        | `admin/dashboard.blade.php`      | ⚠️ Minimal    | 2-column KPI grid, inline CSS, no sidebar nav                                                               |
| Admin Orders        | `/admin/orders`           | `admin/orders/`                  | Unknown       | IMP-013 added sorting — partially modern                                                                    |
| Admin Products      | `/admin/products`         | `admin/products/`                | Unknown       | IMP-013 applied                                                                                             |
| Admin Users         | `/admin/users`            | `admin/users/`                   | Unknown       | IMP-013 applied                                                                                             |
| Admin Revenue       | `/admin/revenue`          | `admin/revenue/`                 | Unknown       | RM-001 / RM-002                                                                                             |
| Admin Categories    | `/admin/categories`       | `admin/categories/`              | Unknown       | PM-004                                                                                                      |
| Admin Coupons       | `/admin/coupons`          | `admin/coupons/`                 | Unknown       | RM-003                                                                                                      |
| Admin Notifications | `/admin/notifications`    | `admin/audit-log/`               | Unknown       | NT-002 / IMP-017                                                                                            |
| Audit Log           | `/admin/audit-log`        | `admin/audit-log/`               | Unknown       | IMP-016                                                                                                     |

---

## Summary of Issues Found Today

> URL choice resolved: using `http://127.0.0.1:8000` via `php artisan serve`. Apache/XAMPP path not needed.  
> All IMP proposals (IMP-018 → IMP-031) have been added to [backlog.md](backlog.md).  
> Design specification for agent written in [uiux_design_spec.md](uiux_design_spec.md).

| #   | Issue                                                        | Severity | Status                             |
| --- | ------------------------------------------------------------ | -------- | ---------------------------------- |
| 1   | `APP_URL` updated to `http://127.0.0.1:8000`, config cleared | High     | ✅ Resolved                        |
| 2   | Login page has zero CSS                                      | High     | → IMP-019 in backlog (Not Started) |
| 3   | Register page has minimal inline CSS, no brand               | High     | → IMP-020 in backlog (Not Started) |
| 4   | User Dashboard is a plain text page with no navigation       | High     | → IMP-021 in backlog (Not Started) |
| 5   | No shared layout file — every page is standalone HTML        | Critical | → IMP-018 in backlog (Not Started) |
| 6   | No global navbar / menu — user must type URLs to navigate    | Critical | → IMP-031 in backlog (Not Started) |
| 7   | Profile, Addresses, Order History have no design consistency | Medium   | → IMP-022/023/025 in backlog       |
| 8   | Admin has no sidebar/topbar layout                           | Medium   | → IMP-026 in backlog (Not Started) |
| 9   | Welcome page shows Laravel default, not e-commerce homepage  | Low      | → IMP-028 in backlog (Not Started) |

---

## 24/4 — Bug Fixes Applied

> Fixed 2026-04-24. `php artisan config:clear` run after changes.

| #   | Bug                                                     | Fix Applied                                                                        | Status                              |
| --- | ------------------------------------------------------- | ---------------------------------------------------------------------------------- | ----------------------------------- |
| 1   | 405 / error debug page shows on wrong-method requests   | `Handler.php` — redirects back + flash instead of Whoops                           | ✅ Fixed in code                    |
| 2   | WebSocket ping error in `stripe listen` terminal        | Normal Stripe CLI disconnect — not a code bug                                      | ℹ️ Informational                    |
| 3   | Google OAuth: `redirect_uri_mismatch` (Error 400)       | `.env` GOOGLE_REDIRECT_URI updated to `http://127.0.0.1:8000/auth/google/callback` | ⚠️ Needs Google Console (see below) |
| 4   | Login/Register button spins forever, form never submits | `x-data` + `@click` moved from `<button>` to `<form>` using `@submit`              | ✅ Fixed in code                    |
| 4.2 | Button clickable with empty fields, no feedback         | HTML5 `required` works again now that Alpine bug is fixed                          | ✅ Fixed (same)                     |
| 5   | URL with `//` causes 404 or error page                  | New `NormalizeUrl` middleware added to global stack                                | ✅ Fixed in code                    |

### Bug #2 — WebSocket error explanation

```
[Fri, 24 Apr 2026 01:17:52 +07] ERROR websocket.Client.writePump: Error when writing ping message: websocket: close sent
```

This comes from the **`stripe listen`** terminal (Stripe CLI), not from the Laravel app. It means the CLI lost its WebSocket connection to Stripe's servers (idle timeout, sleep, or network drop). It automatically reconnects. **No code change needed** — just restart `stripe listen` if it stops forwarding events.

### Bug #3 — Google OAuth needs Google Console action ⚠️

The `.env` has been updated:

```
GOOGLE_REDIRECT_URI=http://127.0.0.1:8000/auth/google/callback
```

**You must also add this URI in Google Console:**

1. Go to → https://console.cloud.google.com/apis/credentials
2. Open your OAuth 2.0 Client ID
3. Under **Authorized redirect URIs**, add: `http://127.0.0.1:8000/auth/google/callback`
4. Save
5. Wait ~5 minutes for changes to propagate

> ⚠️ Google OAuth will remain broken until step 3–4 are done. Email/password login works now.

---

## PART 5 — Local Testing Roadmap

> Start the server: `cd ecommerce && php artisan serve`  
> Base URL: **`http://127.0.0.1:8000`**  
> Also run (optional for payments): `stripe listen --forward-to http://127.0.0.1:8000/webhook/stripe`

### Flow A — Guest / Public Pages

| Step | Page            | URL                                          | What to Test                                                   |
| ---- | --------------- | -------------------------------------------- | -------------------------------------------------------------- |
| A1   | Welcome         | http://127.0.0.1:8000/                       | Page loads, links work                                         |
| A2   | Product Catalog | http://127.0.0.1:8000/products               | Grid renders, pagination works, filter/sort dropdowns work     |
| A3   | Product Filter  | http://127.0.0.1:8000/products?category=1    | Filter by category, price, rating                              |
| A4   | Product Search  | http://127.0.0.1:8000/products/search?q=shoe | Search results show, "no results" for garbage query            |
| A5   | Product Detail  | http://127.0.0.1:8000/products/{slug}        | Images, lightbox click, Add to Cart button, reviews section    |
| A6   | Cart (guest)    | http://127.0.0.1:8000/cart                   | Add product first via A5 → cart shows item, qty update, remove |

### Flow B — Register & Login

| Step | Page            | URL                                        | What to Test                                                                                                         |
| ---- | --------------- | ------------------------------------------ | -------------------------------------------------------------------------------------------------------------------- |
| B1   | Register        | http://127.0.0.1:8000/register             | Submit empty → browser validation shows. Submit weak password → server error. Submit valid → redirected to dashboard |
| B2   | Login           | http://127.0.0.1:8000/login                | Wrong password → "Invalid credentials" flash. Correct → dashboard                                                    |
| B3   | Forgot Password | http://127.0.0.1:8000/forgot-password      | Enter email → success flash (email queued). Check logs/Mailpit                                                       |
| B4   | Google OAuth    | http://127.0.0.1:8000/auth/google/redirect | ⚠️ Only works after Google Console redirect URI is added (see Bug #3)                                                |
| B5   | Logout          | click Logout in navbar                     | Session cleared, redirected to home                                                                                  |

### Flow C — Authenticated User

> Login first at B2, then continue:

| Step | Page          | URL                             | What to Test                                                           |
| ---- | ------------- | ------------------------------- | ---------------------------------------------------------------------- |
| C1   | Dashboard     | http://127.0.0.1:8000/dashboard | Welcome message, nav links visible                                     |
| C2   | Profile       | http://127.0.0.1:8000/profile   | Update name/email → saved. Upload avatar → preview updates             |
| C3   | Addresses     | http://127.0.0.1:8000/addresses | Add address, set as default, edit, delete                              |
| C4   | Order History | http://127.0.0.1:8000/orders    | Shows past orders (empty if none)                                      |
| C5   | Cart (auth)   | http://127.0.0.1:8000/cart      | Items persist across sessions (DB-backed). Badge in navbar shows count |

### Flow D — Checkout (requires auth + cart item)

> Add a product to cart first, then:

| Step | Page              | URL                                     | What to Test                                                                                        |
| ---- | ----------------- | --------------------------------------- | --------------------------------------------------------------------------------------------------- |
| D1   | Checkout Address  | http://127.0.0.1:8000/checkout/address  | Fill new address or select saved → Next                                                             |
| D2   | Checkout Shipping | http://127.0.0.1:8000/checkout/shipping | Select Standard or Express → Next                                                                   |
| D3   | Checkout Review   | http://127.0.0.1:8000/checkout/review   | Review summary, Stripe card form loads. Use test card `4242 4242 4242 4242`, exp `12/29`, CVC `123` |
| D4   | Success           | http://127.0.0.1:8000/checkout/success  | Order created, confirmation shown                                                                   |

### Flow E — Admin Panel

> Login as admin account, then:

| Step | Page              | URL                                          | What to Test                                                        |
| ---- | ----------------- | -------------------------------------------- | ------------------------------------------------------------------- |
| E1   | Admin Dashboard   | http://127.0.0.1:8000/admin/dashboard        | KPI cards, revenue chart, top products, recent orders               |
| E2   | Manage Orders     | http://127.0.0.1:8000/admin/orders           | List, sort, advance status (Processing → Shipped → Delivered)       |
| E3   | Manage Products   | http://127.0.0.1:8000/admin/products         | Create product, edit, delete, upload images, reorder, set thumbnail |
| E4   | Manage Categories | http://127.0.0.1:8000/admin/categories       | Create, edit, delete categories                                     |
| E5   | Manage Users      | http://127.0.0.1:8000/admin/users            | Search users, toggle suspend/activate, assign role                  |
| E6   | Revenue Report    | http://127.0.0.1:8000/admin/revenue          | Filter by date range, period (daily/weekly/monthly), export CSV     |
| E7   | Product Revenue   | http://127.0.0.1:8000/admin/revenue/products | Sort by revenue/units, filter by category, export CSV               |
| E8   | Coupons           | http://127.0.0.1:8000/admin/coupons          | Create % and fixed coupons, toggle active/inactive                  |
| E9   | Notifications     | http://127.0.0.1:8000/admin/notifications    | Mark as read, mark all read                                         |
| E10  | Audit Log         | http://127.0.0.1:8000/admin/audit-log        | Filter by action type, see timestamps                               |

### Flow F — Error Handling

| Step | Test                   | URL                                        | Expected Result                                 |
| ---- | ---------------------- | ------------------------------------------ | ----------------------------------------------- |
| F1   | 404 page               | http://127.0.0.1:8000/this-does-not-exist  | Clean 404 page with "Browse Products" button    |
| F2   | Double-slash URL       | http://127.0.0.1:8000//products            | 301 redirect → `http://127.0.0.1:8000/products` |
| F3   | Triple-slash           | http://127.0.0.1:8000///login              | 301 redirect → `http://127.0.0.1:8000/login`    |
| F4   | 405 Method Not Allowed | Open browser to a POST-only route directly | Redirected back, no Whoops debug page           |

### Notes on Running Services

```
Terminal 1 — Laravel app:
  cd ecommerce
  php artisan serve
  → http://127.0.0.1:8000

Terminal 2 — Stripe webhooks (for checkout):
  stripe listen --forward-to http://127.0.0.1:8000/webhook/stripe

Terminal 3 — Vite (if editing frontend assets):
  cd ecommerce
  npm run dev

Terminal 4 — Queue worker (for emails/jobs):
  cd ecommerce
  php artisan queue:work
```

> **WebSocket ping error in stripe listen** (`websocket.Client.writePump`) = connection timeout. Just press Ctrl+C and re-run `stripe listen`. Not a bug.

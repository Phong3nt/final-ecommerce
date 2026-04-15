# Evaluation History — Laravel E-Commerce

**Project:** Laravel E-Commerce Platform  
**Purpose:** Record code quality evaluations, test results, bugs, and improvement proposals for every completed task.  
**References:** [backlog.md](backlog.md) · [testing_standards.md](testing_standards.md) · [instruction.md](instruction.md)

---

## How to Use This File

1. **After completing a task**, the Agent appends a new `## EVAL-<TaskID>` block below.
2. Each block contains: test results, quality scores, bugs found, and upgrade proposals.
3. When a proposal (e.g., `AU-001.1`) is approved, the Agent creates a new sub-block and references the old one.
4. The `Evaluation Record` column in [backlog.md Task Status Tracker](backlog.md#task-status-tracker) is linked to the anchor here.

---

## Evaluation Template

```markdown
## EVAL-<TASK-ID> · <Task Short Name>

**Version:** A  
**Date:** YYYY-MM-DD  
**Status in Backlog:** Done / Blocked  
**Linked Task:** [<TASK-ID>](backlog.md)

### Test Results

| Test Case ID | Scenario | Type       | Result  | Notes      |
| ------------ | -------- | ---------- | ------- | ---------- |
| TC-<ID>-01   | ...      | Happy Path | PASS ✅ |            |
| TC-<ID>-02   | ...      | Negative   | PASS ✅ |            |
| TC-<ID>-03   | ...      | Edge       | FAIL ❌ | Error: ... |

**Summary:** X Passed · Y Failed · Z Skipped  
**Regression:** All previous tests still PASS ✅ / REGRESSION DETECTED ❌

### Quality Scores (1–5)

| Dimension     | Score | Comment |
| ------------- | ----- | ------- |
| Simplicity    | /5    |         |
| Security      | /5    |         |
| Performance   | /5    |         |
| Test Coverage | /5    |         |

### Bugs / Side Effects Found

| Bug ID      | Description | Severity     | Status       |
| ----------- | ----------- | ------------ | ------------ |
| BUG-<ID>-01 | ...         | High/Med/Low | Open / Fixed |

### Technical Notes

- ...

### Improvement Proposals

| Proposal ID | Description | Benefit | Complexity   |
| ----------- | ----------- | ------- | ------------ |
| <TASK-ID>.1 | ...         | ...     | Low/Med/High |

> ⚠️ Proposals are listed only. No code changes until explicit instruction.
```

---

## Evaluation Records

> Records will be appended here as tasks are completed.

<!-- ============================================================
     SPRINT 1 — Foundation & Auth
     ============================================================ -->

## EVAL-AU-001 · User Registration (Email + Password)

**Version:** A  
**Date:** 2026-04-09  
**Status in Backlog:** Done  
**Linked Task:** [AU-001](backlog.md)

---

### Test Results

| Test Case ID | Scenario                                             | Type     | Result  | Duration | Notes                                      |
| ------------ | ---------------------------------------------------- | -------- | ------- | -------- | ------------------------------------------ |
| TC-AU001-01  | Valid data → user created, logged in, redirected     | Happy    | PASS ✅ | 0.43s    | Registered event dispatched, role assigned |
| TC-AU001-02  | Duplicate email → 302 redirect + session error       | Negative | PASS ✅ | 0.06s    |                                            |
| TC-AU001-03  | Weak password (no uppercase) → validation fails      | Negative | PASS ✅ | 0.03s    |                                            |
| TC-AU001-04  | Empty form → errors on name, email, password         | Edge     | PASS ✅ | 0.03s    |                                            |
| TC-AU001-05  | Password mismatch → confirmation fails               | Edge     | PASS ✅ | 0.03s    |                                            |
| TC-AU001-06  | Name exactly 255 chars → passes validation           | Edge     | PASS ✅ | 0.04s    | Boundary test                              |
| TC-AU001-07  | Name 256 chars → fails validation                    | Edge     | PASS ✅ | 0.03s    |                                            |
| TC-AU001-08  | XSS in name → stored raw, Blade `e()` escapes output | Security | PASS ✅ | 0.04s    | `&lt;script&gt;` verified                  |
| TC-AU001-09  | CSRF middleware class exists and is in web group     | Security | PASS ✅ | 1.96s    |                                            |
| TC-AU001-10  | Authenticated user visits `/register` → redirected   | Edge     | PASS ✅ | 0.04s    |                                            |
| TC-AU001-11  | Password stored as bcrypt hash, not plain text       | Security | PASS ✅ | 0.04s    | `password_verify()` confirmed              |
| TC-AU001-12  | Full registration completes within 2s threshold      | Perf     | PASS ✅ | 0.04s    | Well under threshold                       |

**Summary:** 12 Passed · 0 Failed · 0 Skipped · 37 Assertions  
**Test Duration:** 2.97s total  
**Regression:** No previous tests existed — first task in suite. Baseline established.

---

### Quality Scores

| Dimension     | Score | Comment                                                                          |
| ------------- | ----- | -------------------------------------------------------------------------------- |
| Simplicity    | 5/5   | Controller is 20 lines, single responsibility, no business logic leakage         |
| Security      | 5/5   | Bcrypt hashing via cast, CSRF enforced, XSS safe via Blade, email:rfc validation |
| Performance   | 5/5   | Registration at 0.43s, well under 2s threshold                                   |
| Test Coverage | 5/5   | 12 cases covering happy, negative, edge ×2, security ×3, performance             |

---

### Bugs / Side Effects Found

| Bug ID | Description   | Severity | Status |
| ------ | ------------- | -------- | ------ |
| —      | No bugs found | —        | —      |

---

### Technical Notes

- **`email:rfc` only** (not `dns`) — live DNS lookup was disabled to prevent test failures in offline/CI environments. This is intentional and documented here. In production, `email:rfc,dns` can be enabled via config if DNS lookups are acceptable.
- **CSRF test (TC-AU001-09)** — tests that `VerifyCsrfToken` middleware is registered in the `web` group. A true 419 test would require a real browser session; the class-exists assertion is sufficient to confirm the middleware is not accidentally removed.
- **Mocked Dependencies:**
  - `Event::fake([Registered::class])` — Email verification (dependent on AU-005/mail config) is mocked via Laravel's event system. The `Registered` event is asserted to be dispatched but actual email delivery is not tested here.
  - `Mail::fake()` not needed — email is dispatched via event listener, not directly in the controller.
- **`is_active` column** — added to users table (migration `2026_04_09_052244`) to support AU-006 user suspension (UM-003). Default `true`. Not yet tested here — covered in UM-003.
- **`google_id` column** — added for AU-003 Google OAuth. Default `null`. Not yet tested here.

---

### Improvement Proposals

| Proposal ID | Description                                                                    | Benefit                                                                  | Complexity                                      |
| ----------- | ------------------------------------------------------------------------------ | ------------------------------------------------------------------------ | ----------------------------------------------- |
| AU-001.1    | Add `email:rfc,dns` validation with a config flag to enable/disable DNS lookup | Prevents registrations with syntactically valid but non-existent domains | Low — toggle in `config/auth.php`               |
| AU-001.2    | Block known disposable email domains (e.g., mailinator.com) via a blocklist    | Reduces spam accounts                                                    | Medium — requires a maintained domain blocklist |
| AU-001.3    | Add rate limiting to the register endpoint (e.g., 5 attempts/min per IP)       | Prevents registration spam/bots                                          | Low — Laravel `throttle` middleware             |
| AU-001.4    | Return JSON response when request expects `application/json` (API support)     | Enables mobile app integration                                           | Medium — add `wantsJson()` branch in controller |

> ⚠️ Proposals are listed only. No code changes until explicit instruction.

## EVAL-AU-002 · User Login (Email + Password)

**Version:** A  
**Date:** 2026-04-09  
**Status in Backlog:** Done  
**Linked Task:** [AU-002](backlog.md)

---

### Test Results

| Test Case ID | Scenario                                                              | Type     | Result  | Duration | Notes                                              |
| ------------ | --------------------------------------------------------------------- | -------- | ------- | -------- | -------------------------------------------------- |
| TC-AU002-01  | Valid credentials → session created, redirect to dashboard            | Happy    | PASS ✅ | 0.48s    | `assertAuthenticatedAs` confirmed                  |
| TC-AU002-02  | Wrong password → redirect back with email error, not authed           | Negative | PASS ✅ | 0.30s    | `assertGuest()` confirmed                          |
| TC-AU002-03  | Non-existent email → redirect back with error                         | Negative | PASS ✅ | 0.23s    |                                                    |
| TC-AU002-04  | Empty form → validation errors on email + password                    | Negative | PASS ✅ | 0.04s    |                                                    |
| TC-AU002-05  | Missing password → validation error on password field                 | Negative | PASS ✅ | 0.03s    |                                                    |
| TC-AU002-06  | Remember me flag → `remember_token` persisted in DB                   | Edge     | PASS ✅ | 0.03s    | `remember_token` column verified non-null          |
| TC-AU002-07  | Intended URL redirect → after login goes to originally-intended route | Edge     | PASS ✅ | 0.10s    |                                                    |
| TC-AU002-08  | Session ID regenerated after login → prevents session fixation        | Security | PASS ✅ | 0.03s    | `session()->getId()` changed before/after login    |
| TC-AU002-09  | CSRF middleware is active on login route                              | Security | PASS ✅ | 0.08s    |                                                    |
| TC-AU002-10  | Authenticated user visits `/login` → redirected away                  | Security | PASS ✅ | 0.04s    | Guest middleware working                           |
| TC-AU002-11  | Failure message is generic — does not reveal email existence          | Security | PASS ✅ | 0.44s    | Same `auth.failed` message for known/unknown email |
| TC-AU002-12  | Login completes within 2s performance threshold                       | Perf     | PASS ✅ | 0.04s    | Well under threshold                               |

**Summary:** 12 Passed · 0 Failed · 0 Skipped · 37 Assertions  
**Test Duration:** 2.94s total (combined AU-001 + AU-002 suite)  
**Regression:** AU-001 — 12/12 still PASS ✅ No regression detected.

---

### Quality Scores

| Dimension     | Score | Comment                                                                            |
| ------------- | ----- | ---------------------------------------------------------------------------------- |
| Simplicity    | 5/5   | Controller is 45 lines, 3 methods, single responsibility                           |
| Security      | 5/5   | Session fixation protected, CSRF enforced, generic error message, guest middleware |
| Performance   | 5/5   | Login at 0.48s, well under 2s threshold                                            |
| Test Coverage | 5/5   | 12 cases — happy, 4× negative, 2× edge, 4× security, 1× performance                |

---

### Bugs / Side Effects Found

| Bug ID | Description   | Severity | Status |
| ------ | ------------- | -------- | ------ |
| —      | No bugs found | —        | —      |

---

### Technical Notes

- **`RouteServiceProvider::HOME` updated** from `/home` (non-existent) to `/dashboard`. This affects `RedirectIfAuthenticated` middleware — authenticated users are now correctly redirected to `/dashboard` instead of a 404.
- **`verified` middleware removed** from dashboard route — `email_verified_at` column is not populated by AU-001/AU-002 (email verification is AU-005). Keeping `verified` would block all users post-login until AU-005 is built. Will be re-added when AU-005 is implemented.
- **Session fixation** — `$request->session()->regenerate()` is called on every successful login, generating a new session ID. Verified by TC-AU002-08.
- **Generic error message** — `Auth::attempt()` failure always returns `trans('auth.failed')` regardless of whether the email exists. This prevents user enumeration attacks. Verified by TC-AU002-11.
- **Mocked Dependencies:** None — login has no external dependencies. `Auth::attempt()`, session, and redirect are all in-memory during testing (SQLite + array session driver via `phpunit.xml`).
- **`remember me`** — uses Laravel's built-in `remember_token` column. Verified that token is written to DB when `remember=1`. Long-term cookie behaviour is a browser concern, not tested here.

---

### Improvement Proposals

| Proposal ID | Description                                                                     | Benefit                                                 | Complexity                                      |
| ----------- | ------------------------------------------------------------------------------- | ------------------------------------------------------- | ----------------------------------------------- |
| AU-002.1    | Add rate limiting to `/login` (e.g., 5 attempts/min per IP+email combo)         | Prevents brute-force credential attacks                 | Low — Laravel `throttle` or `RateLimiter`       |
| AU-002.2    | Add account lockout after N failed attempts (lock `is_active=false` for 15 min) | Stronger brute-force protection                         | Medium — requires failed-attempt counter column |
| AU-002.3    | Return JSON response when `Accept: application/json` header is present          | Enables SPA / mobile app login                          | Low — `wantsJson()` branch                      |
| AU-002.4    | Add login activity log (IP, user-agent, timestamp) to an `audit_logs` table     | Enables security monitoring and suspicious login alerts | Medium — new table + model                      |

> ⚠️ Proposals are listed only. No code changes until explicit instruction.

<!-- EVAL-AU-002 END -->

## EVAL-AU-003 · Google OAuth Login

**Version:** A  
**Date:** 2026-04-09  
**Status in Backlog:** Done  
**Linked Task:** [AU-003](backlog.md)

### Test Results

| Test Case ID | Scenario                                                              | Type     | Result  | Duration | Notes                                              |
| ------------ | --------------------------------------------------------------------- | -------- | ------- | -------- | -------------------------------------------------- |
| TC-AU003-01  | New Google user → auto-registered + logged in + redirect dashboard    | Happy    | PASS ✅ | 0.40s    | `assertAuthenticated`, DB row created              |
| TC-AU003-02  | Existing email user (no google_id) → google_id linked + logged in     | Happy    | PASS ✅ | 0.05s    | `user->google_id` updated                          |
| TC-AU003-03  | Already-linked google_id user → logs in directly                      | Happy    | PASS ✅ | 0.03s    | `assertAuthenticatedAs` confirmed                  |
| TC-AU003-04  | Redirect route → issues redirect to Google                            | Happy    | PASS ✅ | 0.03s    | Location header contains `google.com`              |
| TC-AU003-05  | New Google user has `email_verified_at` set                           | Edge     | PASS ✅ | 0.03s    | Google already verified the email                  |
| TC-AU003-06  | New Google user gets `user` role assigned                             | Edge     | PASS ✅ | 0.04s    | Spatie `hasRole('user')` confirmed                 |
| TC-AU003-07  | New user name taken from Google profile                               | Edge     | PASS ✅ | 0.03s    | `name` column matches Google display name          |
| TC-AU003-08  | Intended URL is honoured after Google login                           | Edge     | PASS ✅ | 0.04s    | `redirect()->intended()` working                   |
| TC-AU003-09  | Session regenerated after OAuth login → session fixation prevention   | Security | PASS ✅ | 0.04s    | Session ID changed before/after                    |
| TC-AU003-10  | Socialite exception → redirect to login with error, stays guest       | Negative | PASS ✅ | 0.04s    | `assertGuest()`, `assertSessionHasErrors('email')` |
| TC-AU003-11  | Auto-registered user has non-empty bcrypt password (not empty string) | Security | PASS ✅ | 0.03s    | Hash starts with `$2y$`                            |
| TC-AU003-12  | Google callback responds within 2s performance threshold              | Perf     | PASS ✅ | 0.03s    | Well under threshold                               |

**Summary:** 12 Passed · 0 Failed · 0 Skipped · 30 Assertions  
**Test Duration:** 0.96s (AU-003 alone) · 3.10s (full 36-test suite)  
**Regression:** AU-001 12/12 PASS ✅ · AU-002 12/12 PASS ✅ · No regression detected.

---

### Quality Scores

| Dimension     | Score | Comment                                                                                                    |
| ------------- | ----- | ---------------------------------------------------------------------------------------------------------- |
| Simplicity    | 5/5   | Controller is 72 lines, 2 methods (`redirect`, `callback`), single responsibility                          |
| Security      | 5/5   | Session fixation protected, random 32-char password for auto-registered users, Socialite exception handled |
| Performance   | 5/5   | Callback at 0.40s first run, 0.03s subsequent (Mockery fast)                                               |
| Test Coverage | 5/5   | 12 cases — 4× happy, 4× edge, 1× negative, 2× security, 1× performance                                     |

---

### Bugs / Side Effects Found

| Bug ID       | Description                                                                                                                                | Severity | Status                                           |
| ------------ | ------------------------------------------------------------------------------------------------------------------------------------------ | -------- | ------------------------------------------------ |
| BUG-AU003-01 | `email_verified_at` was not in `User::$fillable` — `User::create()` silently discarded it, leaving auto-registered Google users unverified | Medium   | Fixed — added `email_verified_at` to `$fillable` |

---

### Technical Notes

- **`email_verified_at` in `$fillable`** — Google has already verified the user's email. Setting `email_verified_at = now()` on auto-registration is correct and intentional. Required adding it to `User::$fillable` (was missing — bug found and fixed by TC-AU003-05).
- **Random password for Google users** — Auto-registered users have a `bcrypt(Str::random(32))` password. This ensures their account has a valid password hash (required by the `password` column) while making it impossible to log in via the password form without a password-reset flow.
- **Socialite mocking pattern** — `Socialite::shouldReceive('driver')->with('google')->andReturn($provider)` via Mockery. This avoids any real HTTP calls to Google during tests.
- **`email:rfc` on registration** — AU-001/AU-002 use `email:rfc` validation. Google users bypass the FormRequest since they arrive via OAuth — Google's own email is trusted.
- **No CSRF check on callback route** — GET `/auth/google/callback` is a public route. Laravel's CSRF protection only applies to POST/PUT/PATCH/DELETE, so this is correct by design.
- **Linking logic** — Priority: google_id lookup first (fastest), then email lookup as fallback. This ensures users who registered by email first get seamlessly linked on first Google login.

---

### Improvement Proposals

| Proposal ID | Description                                                                                 | Benefit                                          | Complexity                                                        |
| ----------- | ------------------------------------------------------------------------------------------- | ------------------------------------------------ | ----------------------------------------------------------------- |
| AU-003.1    | Add `state` parameter validation to the callback route (verify `state` from session)        | Prevents CSRF attacks on the OAuth callback      | Low — Socialite handles this with `->stateless()` or custom state |
| AU-003.2    | Add additional OAuth providers (GitHub, Facebook) using the same `GoogleController` pattern | Expands sign-in options                          | Low — Socialite supports many drivers                             |
| AU-003.3    | Show user-friendly error page instead of a generic redirect when Socialite fails            | Better UX than a flash message on the login page | Low — dedicated `oauth-error` view                                |
| AU-003.4    | Log the OAuth provider and timestamp to `audit_logs` on each login                          | Enables security monitoring                      | Medium — requires `audit_logs` table (also proposed in AU-002.4)  |

> ⚠️ Proposals are listed only. No code changes until explicit instruction.

<!-- EVAL-AU-003 END -->

## EVAL-AU-004 · User Logout

**Version:** A  
**Date:** 2026-04-15  
**Status in Backlog:** Done  
**Linked Task:** [AU-004](backlog.md)

### Test Results

| Test Case ID | Scenario                                                      | Type     | Result  | Duration | Notes                           |
| ------------ | ------------------------------------------------------------- | -------- | ------- | -------- | ------------------------------- |
| TC-AU004-01  | POST /logout while authenticated → 302 redirect to `/`        | Happy    | PASS ✅ | 0.41s    | Redirects to home, not `/login` |
| TC-AU004-02  | After logout, user is not authenticated                       | Happy    | PASS ✅ | 0.03s    | `assertGuest()` confirmed       |
| TC-AU004-03  | Session ID changes after logout (invalidated)                 | Edge     | PASS ✅ | 0.03s    | Session fixation prevention     |
| TC-AU004-04  | CSRF token regenerated after logout                           | Edge     | PASS ✅ | 0.03s    | `session()->token()` changed    |
| TC-AU004-05  | VerifyCsrfToken middleware is in `web` group                  | Security | PASS ✅ | 0.07s    | CSRF enforced on logout route   |
| TC-AU004-06  | `/dashboard` inaccessible after logout → redirect to `/login` | Security | PASS ✅ | 0.04s    | Auth middleware working         |
| TC-AU004-07  | GET /logout returns 405 Method Not Allowed                    | Security | PASS ✅ | 0.31s    | Only POST accepted              |
| TC-AU004-08  | Guest POST /logout → redirected to `/login` (auth middleware) | Negative | PASS ✅ | 0.03s    | No crash or 500                 |
| TC-AU004-09  | PUT /logout returns 405                                       | Edge     | PASS ✅ | 0.27s    | Method constraint confirmed     |
| TC-AU004-10  | Auth session data cleared from session after logout           | Edge     | PASS ✅ | 0.03s    | Session key null                |
| TC-AU004-11  | Consecutive logouts (guest POST) do not crash                 | Edge     | PASS ✅ | 0.03s    | Idempotent                      |
| TC-AU004-12  | Logout completes within 2s threshold                          | Perf     | PASS ✅ | 0.03s    | Well under threshold            |

**Summary:** 12 Passed · 0 Failed · 0 Skipped · 17 Assertions  
**Test Duration:** 1.52s (AU-004 alone) · 8.14s (full 50-test suite)  
**Regression:** AU-001 12/12 ✅ · AU-002 12/12 ✅ · AU-003 12/12 ✅ · No regression detected.

---

### Quality Scores

| Dimension     | Score | Comment                                                                |
| ------------- | ----- | ---------------------------------------------------------------------- |
| Simplicity    | 5/5   | 4-line method — logout + invalidate + regenerateToken + redirect       |
| Security      | 5/5   | Session invalidated, CSRF token regenerated, auth middleware enforced  |
| Performance   | 5/5   | 0.03s logout, well under 2s threshold                                  |
| Test Coverage | 5/5   | 12 cases — 2× happy, 4× edge, 3× security, 1× negative, 1× performance |

---

### Bugs / Side Effects Found

| Bug ID       | Description                                                                                           | Severity | Status                                                          |
| ------------ | ----------------------------------------------------------------------------------------------------- | -------- | --------------------------------------------------------------- |
| BUG-AU004-01 | `destroy()` redirected to `/login` instead of home page `/` — contradicts AC "Redirects to home page" | Low      | Fixed — changed `redirect()->route('login')` to `redirect('/')` |

---

### Technical Notes

- **Redirect target fix** — The pre-existing `destroy()` method redirected to `/login`. AC requires home (`/`). A one-line change to `redirect('/')` fixed it. No other files changed.
- **CSRF test pattern** — Same as AU-001/AU-002: confirms `VerifyCsrfToken` is registered in the `web` middleware group. Actual 419 is not triggered in unit tests because Laravel's `VerifyCsrfToken::runningUnitTests()` returns `true` — this is a PHP testing environment constraint, not a code defect.
- **Session invalidation** — `$request->session()->invalidate()` + `regenerateToken()` ensures both the session ID and CSRF token are rotated, preventing session fixation after logout.
- **Mocked Dependencies:** None.
- **Architectural Impact:** None. Logout tightens the security boundary — does not conflict with AU-001 (register), AU-002 (login), or AU-003 (Google OAuth). All three login paths are properly terminated by this route.

---

### Improvement Proposals

| Proposal ID | Description                                                                                     | Benefit                        | Complexity                                                           |
| ----------- | ----------------------------------------------------------------------------------------------- | ------------------------------ | -------------------------------------------------------------------- |
| AU-004.1    | Flash a "You have been logged out successfully" message on the home page after logout           | Improved UX feedback           | Low — one `session()->flash()` call                                  |
| AU-004.2    | Add audit log entry on logout (timestamp, IP, user_id)                                          | Security monitoring            | Medium — requires `audit_logs` table (also in AU-002.4, AU-003.4)    |
| AU-004.3    | Implement "logout all devices" that rotates `remember_token` in DB and invalidates all sessions | Protects stolen session tokens | Medium — requires session driver that supports per-user invalidation |

> ⚠️ Proposals are listed only. No code changes until explicit instruction.

<!-- EVAL-AU-004 END -->

## EVAL-AU-005 · Password Reset via Email

**Version:** A  
**Date:** 2026-04-15  
**Status in Backlog:** Done  
**Linked Task:** [AU-005](backlog.md)

### Test Results

| Test Case ID | Scenario                                               | Type     | Result  | Duration | Notes                                  |
| ------------ | ------------------------------------------------------ | -------- | ------- | -------- | -------------------------------------- |
| TC-AU005-01  | GET /forgot-password returns 200                       | Happy    | PASS ✅ | 0.53s    | View renders correctly                 |
| TC-AU005-02  | Valid email → ResetPassword notification sent          | Happy    | PASS ✅ | 0.32s    | `Notification::assertSentTo` confirmed |
| TC-AU005-03  | Unknown email → same "sent" status (no enumeration)    | Security | PASS ✅ | 0.03s    | Cannot tell if email exists            |
| TC-AU005-04  | Valid token → password changed, redirect to login      | Happy    | PASS ✅ | 0.08s    | `Hash::check` confirmed                |
| TC-AU005-05  | New password stored as bcrypt hash                     | Security | PASS ✅ | 0.04s    | Starts with `$2y$`                     |
| TC-AU005-06  | Invalid/expired token → error, password unchanged      | Security | PASS ✅ | 0.04s    | `assertSessionHasErrors(['email'])`    |
| TC-AU005-07  | Empty email on forgot-password form → validation error | Negative | PASS ✅ | 0.03s    |                                        |
| TC-AU005-08  | Invalid email format → validation error                | Negative | PASS ✅ | 0.10s    |                                        |
| TC-AU005-09  | Weak password on reset → validation error              | Negative | PASS ✅ | 0.04s    | min:8 + uppercase regex                |
| TC-AU005-10  | Password confirmation mismatch → validation error      | Negative | PASS ✅ | 0.03s    |                                        |
| TC-AU005-11  | CSRF middleware active on forgot-password route        | Security | PASS ✅ | 0.03s    |                                        |
| TC-AU005-12  | Reset link request completes within 2s                 | Perf     | PASS ✅ | 0.03s    | Well under threshold                   |

**Summary:** 12 Passed · 0 Failed · 0 Skipped · 24 Assertions  
**Test Duration:** 1.53s (AU-005 alone) · 4.48s (full 62-test suite)  
**Regression:** AU-001–004 all 50/50 PASS ✅ · No regression detected.

---

### Quality Scores

| Dimension     | Score | Comment                                                                                              |
| ------------- | ----- | ---------------------------------------------------------------------------------------------------- |
| Simplicity    | 5/5   | Two controllers, 4 methods total — delegates entirely to Laravel's `Password` facade                 |
| Security      | 5/5   | No email enumeration, token expiry enforced by Laravel (60 min), new password hashed, CSRF protected |
| Performance   | 5/5   | 0.03s under test, well under 2s threshold                                                            |
| Test Coverage | 5/5   | 12 cases — 3× happy, 4× negative, 4× security, 1× performance                                        |

---

### Bugs / Side Effects Found

| Bug ID | Description                                      | Severity | Status |
| ------ | ------------------------------------------------ | -------- | ------ |
| —      | No bugs found — all 12 tests passed on first run | —        | —      |

---

### Technical Notes

- **No email enumeration** — `ForgotPasswordController::store()` always calls `Password::sendResetLink()` and always returns `with('status', __('passwords.sent'))` regardless of whether the email exists. Unknown emails silently no-op (Laravel handles this internally).
- **Token expiry** — Default is 60 minutes (configured in `config/auth.php` under `passwords.users.expire = 60`). This matches AC "Reset link expires in 60 minutes".
- **Password validation** — `min:8` + `confirmed` + `regex:/[A-Z]/` (at least one uppercase) — consistent with AU-001 registration policy.
- **`Notification::fake()`** — Used in TC-AU005-02 and TC-AU005-12 to prevent actual email sending and assert notification dispatch without a real mail server.
- **`Password::createToken($user)`** — Used in tests to generate a real valid token for the reset form submission tests, bypassing the email flow.
- **Mocked Dependencies:** `Notification::fake()` mocks email delivery (AU-005 depends on mail config — mocked per Rule 5).
- **Architectural Impact:** None. Routes are behind `guest` middleware (password reset is only for unauthenticated users). No conflict with AU-001–004.

---

### Improvement Proposals

| Proposal ID | Description                                                           | Benefit                                           | Complexity                           |
| ----------- | --------------------------------------------------------------------- | ------------------------------------------------- | ------------------------------------ |
| AU-005.1    | Add rate limiting to `/forgot-password` (e.g., 3 requests/min per IP) | Prevents email flooding abuse                     | Low — Laravel `throttle` middleware  |
| AU-005.2    | Show reset link expiry time on the reset-password page                | Better UX — user knows how long the link is valid | Low — pass expiry config to view     |
| AU-005.3    | Log password reset events to `audit_logs` (timestamp, IP, user_id)    | Security monitoring                               | Medium — requires `audit_logs` table |

> ⚠️ Proposals are listed only. No code changes until explicit instruction.

<!-- EVAL-AU-005 END -->

## EVAL-AU-006 · Role-Based Access Control

**Version:** A  
**Date:** 2026-04-15  
**Status in Backlog:** Done  
**Linked Task:** [AU-006](backlog.md)

### Test Results

| Test Case ID | Scenario                                                    | Type     | Result  | Duration | Notes                             |
| ------------ | ----------------------------------------------------------- | -------- | ------- | -------- | --------------------------------- |
| TC-AU006-01  | Admin accesses `/admin/dashboard` → 200 OK                  | Happy    | PASS ✅ | 0.43s    |                                   |
| TC-AU006-02  | Regular user accesses `/admin/dashboard` → 403              | Security | PASS ✅ | 0.16s    | `role:admin` middleware enforced  |
| TC-AU006-03  | Guest accesses `/admin/dashboard` → redirect to `/login`    | Security | PASS ✅ | 0.04s    | `auth` middleware runs first      |
| TC-AU006-04  | Admin user has `admin` role                                 | Happy    | PASS ✅ | 0.03s    | `hasRole('admin')` confirmed      |
| TC-AU006-05  | Regular user has `user` role, not `admin`                   | Happy    | PASS ✅ | 0.03s    |                                   |
| TC-AU006-06  | Regular user can still access `/dashboard` → 200            | Edge     | PASS ✅ | 0.06s    | No regression on user routes      |
| TC-AU006-07  | Admin can also access `/dashboard` → 200                    | Edge     | PASS ✅ | 0.03s    |                                   |
| TC-AU006-08  | User with no role is blocked from admin → 403               | Security | PASS ✅ | 0.03s    |                                   |
| TC-AU006-09  | Both `user` and `admin` roles exist in DB                   | Edge     | PASS ✅ | 0.03s    | RoleSeeder verified               |
| TC-AU006-10  | `role` middleware alias registered in Kernel                | Security | PASS ✅ | 0.05s    | `RoleMiddleware::class` confirmed |
| TC-AU006-11  | `admin.dashboard` route name resolves to `/admin/dashboard` | Edge     | PASS ✅ | 0.04s    |                                   |
| TC-AU006-12  | Admin dashboard responds within 2s                          | Perf     | PASS ✅ | 0.03s    |                                   |

**Summary:** 12 Passed · 0 Failed · 0 Skipped · 16 Assertions  
**Test Duration:** 1.19s (AU-006 alone) · 4.85s (full 74-test suite)  
**Regression:** AU-001–005 all 62/62 PASS ✅ · No regression.

---

### Quality Scores

| Dimension     | Score | Comment                                                                    |
| ------------- | ----- | -------------------------------------------------------------------------- |
| Simplicity    | 5/5   | 3 middleware aliases in Kernel + route group + controller — minimal code   |
| Security      | 5/5   | `auth` + `role:admin` double guard, 403 for wrong role, redirect for guest |
| Performance   | 5/5   | 0.03s response, well under 2s                                              |
| Test Coverage | 5/5   | 12 cases — 3× happy, 4× security, 3× edge, 1× performance                  |

---

### Bugs / Side Effects Found

| Bug ID | Description                                | Severity | Status |
| ------ | ------------------------------------------ | -------- | ------ |
| —      | No bugs — all 12 tests passed on first run | —        | —      |

---

### Technical Notes

- **Middleware registration** — Spatie v6 uses `Spatie\Permission\Middleware\RoleMiddleware` (path is `src/Middleware/`, not `src/Middlewares/`). Added `role`, `permission`, `role_or_permission` all to `Kernel::$middlewareAliases`.
- **Route group** — `/admin/*` routes use `['auth', 'role:admin']` middleware stack. `auth` resolves first, so a guest gets a 302 redirect to login rather than a 403 (correct UX).
- **Admin seeder** — `RoleSeeder` was already created in a prior task. `DatabaseSeeder` now calls it, so `php artisan db:seed` will create both roles.
- **No privilege escalation risk** — a `role:user` user cannot elevate to `admin` without explicit `assignRole('admin')` — Spatie enforces this at the DB and middleware level.
- **Architectural Impact** — AU-006 adds the `role:admin` middleware guard. This must be applied to all future admin routes (AD-001–004, PM-001–006, UM-001–004, RM-001–003). All existing user routes (AU-001–005) are unaffected.
- **Mocked Dependencies:** None.

---

### Improvement Proposals

| Proposal ID | Description                                                                             | Benefit                                               | Complexity                                                           |
| ----------- | --------------------------------------------------------------------------------------- | ----------------------------------------------------- | -------------------------------------------------------------------- |
| AU-006.1    | Add granular permissions (e.g., `edit-products`, `view-orders`) beyond role-only checks | Fine-grained access control for future admin features | Medium — define permissions in seeder + use `permission:` middleware |
| AU-006.2    | Create a dedicated 403 error view (`errors/403.blade.php`) with a friendly message      | Better UX than default Laravel 403 page               | Low — one Blade file                                                 |
| AU-006.3    | Log unauthorized access attempts to `audit_logs` (user_id, route, timestamp)            | Security monitoring                                   | Medium — requires `audit_logs` table                                 |

> ⚠️ Proposals are listed only. No code changes until explicit instruction.

<!-- EVAL-AU-006 END -->
<!-- EVAL-AU-005 will appear here after AU-005 is completed -->
<!-- EVAL-AU-006 will appear here after AU-006 is completed -->

<!-- ============================================================
     SPRINT 3 — User Profile
     ============================================================ -->

## EVAL-UP-001 · User Profile View/Edit with Avatar

- **Task:** UP-001
- **Sprint:** 3
- **Date:** 2026-04-15
- **Tag:** `v1.0-UP-001-stable`
- **Branch:** `feature/UP-001` → merged to `master`

### STEP 1 — Architecture Review

- Added `avatar` column (nullable string) via migration `add_avatar_to_users_table`
- `User::$fillable` extended with `'avatar'`
- New `ProfileController` (show/update) in `app/Http/Controllers/`
- New `UpdateProfileRequest` with `image|mimes:jpg,jpeg,png|max:2048` rule + `Rule::unique` ignore self
- Two routes (`GET /profile`, `PUT /profile`) added to `auth` middleware group
- New Blade view `resources/views/profile/show.blade.php`
- Avatar files stored in `storage/app/public/avatars/` via `Storage::disk('public')`

### STEP 2 — Security Checklist

- [x] CSRF: `@csrf` + `@method('PUT')` in form, middleware active on route
- [x] XSS: Blade `{{ }}` auto-escapes all output
- [x] Auth: both routes inside `auth` middleware group; guest → redirect to login
- [x] File Upload: `mimes:jpg,jpeg,png`, `max:2048`, `image` rule — no arbitrary file upload
- [x] SQLi: Eloquent ORM + FormRequest validation — no raw queries
- [x] Email unique: `Rule::unique()->ignore($user->id)` prevents false conflict on own email

### STEP 3 — Test Results

| TC    | Description                                        | Type        | Result |
| ----- | -------------------------------------------------- | ----------- | ------ |
| TC-01 | GET /profile returns 200 + pre-filled data         | Happy       | PASS   |
| TC-02 | PUT /profile valid name/email → DB updated + flash | Happy       | PASS   |
| TC-03 | Avatar jpg upload stores file + DB updated         | Happy       | PASS   |
| TC-04 | Same email → no unique conflict                    | Edge        | PASS   |
| TC-05 | Guest GET /profile → redirect login                | Security    | PASS   |
| TC-06 | Guest PUT /profile → redirect login                | Security    | PASS   |
| TC-07 | CSRF middleware registered on route                | Security    | PASS   |
| TC-08 | Avatar >2MB → validation error                     | Negative    | PASS   |
| TC-09 | Non-image (pdf) avatar → validation error          | Negative    | PASS   |
| TC-10 | Empty name → validation error                      | Negative    | PASS   |
| TC-11 | Duplicate email (other user) → error               | Negative    | PASS   |
| TC-12 | Profile update within 2s                           | Performance | PASS   |

**Score: 12/12 — All acceptance criteria met**

### STEP 4 — Proposals for Next Task

- Consider adding a `change_password` form to the profile page (UP-002 or separate task)
- Avatar deletion feature (remove current avatar) could be a quick win
- Image resizing/thumbnail generation before storage (reduce disk usage)

<!-- EVAL-UP-001 END -->

<!-- ============================================================
     SPRINT 2 — Product Catalog & Cart
     ============================================================ -->

<!-- EVAL-PC-001 through PC-005, SC-001 through SC-004 -->

<!-- ============================================================
     More sprints follow the same pattern...
     ============================================================ -->

---

## Regression Test History

> Each time a new sprint or upgrade is deployed, the full regression result is recorded here.

| Run Date   | Trigger (Task/Sprint) | Total Tests | Passed | Failed | Regressions  | Run By |
| ---------- | --------------------- | ----------- | ------ | ------ | ------------ | ------ |
| 2026-04-09 | AU-001 (Sprint 1)     | 12          | 12     | 0      | 0 (baseline) | Agent  |
| 2026-04-09 | AU-002 (Sprint 1)     | 24          | 24     | 0      | 0            | Agent  |
| 2026-04-09 | AU-003 (Sprint 1)     | 36          | 36     | 0      | 0            | Agent  |
| 2026-04-15 | AU-004 (Sprint 1)     | 50          | 50     | 0      | 0            | Agent  |
| 2026-04-15 | AU-005 (Sprint 1)     | 62          | 62     | 0      | 0            | Agent  |
| 2026-04-15 | AU-006 (Sprint 1)     | 74          | 74     | 0      | 0            | Agent  |
| 2026-04-15 | UP-001 (Sprint 3)     | 86          | 86     | 0      | 0            | Agent  |

---

## Upgrade Version Log

> Tracks approved improvement proposals and their outcomes.

| Upgrade ID | Base Task | Proposal Source (EVAL link) | Approval Date | New Metrics vs Old | Outcome |
| ---------- | --------- | --------------------------- | ------------- | ------------------ | ------- |
| —          | —         | —                           | —             | —                  | —       |

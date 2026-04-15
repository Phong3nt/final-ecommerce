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

## EVAL-PC-001 · Product Listing Page with Pagination

- **Task:** PC-001
- **Sprint:** 2
- **Date:** 2026-04-15
- **Tag:** `v1.0-PC-001-stable`
- **Branch:** `feature/PC-001` → merged to `master`

### STEP 1 — Architecture Review

- New `products` table migration: `id`, `name`, `description`, `price` (decimal 10,2), `stock` (uint), `image` (nullable), timestamps
- `Product` model with `$fillable`, `$casts` (`price` → decimal:2, `stock` → integer)
- `ProductFactory` with `outOfStock()` state helper
- `ProductController::index()` — `Product::latest()->paginate(12)` → `products.index` view
- `GET /products` route added as public (no auth middleware)
- Blade view: grid loop with name, price, stock status badge; `{{ $products->links() }}` pagination

### STEP 2 — Security Checklist

- [x] XSS: Blade `{{ }}` escapes product name and all output
- [x] No auth required — public route is intentional per AC
- [x] No mass assignment risk — `$fillable` defined
- [x] No raw SQL — Eloquent paginate()

### STEP 3 — Test Results

| TC    | Description                                   | Type        | Result |
| ----- | --------------------------------------------- | ----------- | ------ |
| TC-01 | GET /products returns 200 without login       | Happy       | PASS   |
| TC-02 | Name, price, stock status visible in listing  | Happy       | PASS   |
| TC-03 | Out-of-stock product shows "Out of Stock"     | Happy       | PASS   |
| TC-04 | Page 1 returns exactly 12 items               | Happy       | PASS   |
| TC-05 | Page 2 returns remaining items                | Happy       | PASS   |
| TC-06 | Empty catalog shows "No products available"   | Edge        | PASS   |
| TC-07 | Products ordered newest first                 | Happy       | PASS   |
| TC-08 | Pagination links present with >12 products    | Happy       | PASS   |
| TC-09 | XSS in product name is escaped                | Security    | PASS   |
| TC-10 | Product with null image renders without error | Edge        | PASS   |
| TC-11 | Out-of-range page returns 200                 | Edge        | PASS   |
| TC-12 | Listing responds within 2s                    | Performance | PASS   |

**Score: 12/12 — All acceptance criteria met**

### STEP 4 — Proposals for Next Task

- PC-002 (search) can reuse `ProductController` with a `search()` method or a query scope on `Product`
- Consider adding a `Category` model (belongsToMany) before PC-003 filters
- Image thumbnail generation on upload would improve page-load performance

<!-- EVAL-PC-001 END -->

## EVAL-PC-002 · Product Search by Name and Description

- **Task:** PC-002
- **Sprint:** 2
- **Date:** 2026-04-15
- **Tag:** `v1.0-PC-002-stable`
- **Branch:** `feature/PC-002` → merged to `master`

### STEP 1 — Architecture Review

- `scopeSearch($query, string $term)` added to `Product` model — `LIKE %term%` on `name` OR `description`
- `ProductController::search()` — validates blank query (redirects to index), paginates 12/page with `withQueryString()`
- Route `GET /products/search` added as public (no auth)
- New view `products/search.blade.php` — search form, result count, product grid, "No products found" state, pagination

### STEP 2 — Security Checklist

- [x] XSS: `{{ $q }}` auto-escaped; product fields rendered with `{{ }}`
- [x] SQL injection: Eloquent `LIKE` binding — never raw interpolation
- [x] No auth required — public route per AC
- [x] Query string preserved via `withQueryString()` — no sensitive data leak

### STEP 3 — Test Results

| TC    | Description                                     | Type        | Result |
| ----- | ----------------------------------------------- | ----------- | ------ |
| TC-01 | Search by name keyword returns matching product | Happy       | PASS   |
| TC-02 | Search by description keyword returns match     | Happy       | PASS   |
| TC-03 | Search results paginated at 12/page             | Happy       | PASS   |
| TC-04 | Search is case-insensitive                      | Happy       | PASS   |
| TC-05 | No match shows "No products found" message      | Edge        | PASS   |
| TC-06 | Empty query redirects to /products              | Edge        | PASS   |
| TC-07 | Search accessible without login                 | Security    | PASS   |
| TC-08 | XSS in query param is escaped                   | Security    | PASS   |
| TC-09 | Search term preserved in input field            | Happy       | PASS   |
| TC-10 | Non-matching products not returned              | Negative    | PASS   |
| TC-11 | Partial keyword match works                     | Edge        | PASS   |
| TC-12 | Search completes within 1 second                | Performance | PASS   |

**Score: 12/12 — All acceptance criteria met**

### STEP 4 — Proposals for Next Task

- PC-003 (filter by category/price/rating) can add `scopeFilter()` on Product; category requires its own model
- Consider debounced JS search-as-you-type for UX improvement (post-MVP)
- Search index (MySQL FULLTEXT) recommended before production for scale

<!-- EVAL-PC-002 END -->

## EVAL-PC-003 · Product Filters by Category, Price Range, Rating

- **Task:** PC-003
- **Sprint:** 2
- **Date:** 2026-04-15
- **Tag:** `v1.0-PC-003-stable`
- **Branch:** `feature/PC-003` → merged to `master`

### STEP 1 — Architecture Review

- `Category` model + migration (`id`, `name` unique, timestamps) introduced; `CategoryFactory` added
- `products` table migrated: `category_id` (nullable FK → nullOnDelete) + `rating` (decimal 3,2 nullable)
- `scopeFilter($query, array $filters)` added to `Product` model — handles `category`, `min_price`, `max_price`, `min_rating`; each condition only applied when value is non-empty
- `ProductController::index()` accepts filter params via `$request->only([...])`, loads `$categories`, applies `Product::filter($filters)->latest()->paginate(12)->withQueryString()`
- Filter form in `products/index.blade.php` — `GET` to `products.index`, category `<select>`, min/max price and min_rating inputs, filter state persisted via `value=` attributes, "Clear Filters" link

### STEP 2 — Security Checklist

- [x] XSS: all filter outputs rendered with `{{ }}` auto-escaping; `<select>` option values from DB integer IDs
- [x] SQL injection: Eloquent parameterised bindings — no raw interpolation in `scopeFilter`
- [x] No auth required — public route per AC
- [x] `withQueryString()` on paginator — filter params persist across pages
- [x] Nullable FK with `nullOnDelete` — no orphan constraint violations when category deleted

### STEP 3 — Test Results

| TC    | Description                                              | Type        | Result |
| ----- | -------------------------------------------------------- | ----------- | ------ |
| TC-01 | Filter by category returns only matching products        | Happy       | PASS   |
| TC-02 | Filter by min_price excludes cheaper products            | Happy       | PASS   |
| TC-03 | Filter by max_price excludes expensive products          | Happy       | PASS   |
| TC-04 | Filter by min_rating excludes lower-rated products       | Happy       | PASS   |
| TC-05 | Combined category + price filter works correctly         | Happy       | PASS   |
| TC-06 | All three filters combined work together                 | Happy       | PASS   |
| TC-07 | No filters returns all products                          | Edge        | PASS   |
| TC-08 | Filter state persists in pagination links (query string) | Happy       | PASS   |
| TC-09 | Category dropdown rendered in filter form                | Happy       | PASS   |
| TC-10 | Filter by non-existent category shows empty state        | Edge        | PASS   |
| TC-11 | Filter accessible without login                          | Security    | PASS   |
| TC-12 | Filtered listing responds within 2 seconds               | Performance | PASS   |

**Score: 12/12 — All acceptance criteria met**

### STEP 4 — Proposals for Next Task

- PC-004 (product detail page) should display `category.name` and `rating` now that both fields are available
- Consider adding a rating widget (stars) on the detail and listing pages for visual clarity
- Price range slider (JS) would improve UX over plain number inputs post-MVP

<!-- EVAL-PC-003 END -->

## EVAL-PC-004 · Product Sort by Newest, Oldest, Price, Rating

- **Task:** PC-004
- **Sprint:** 2
- **Date:** 2026-04-15
- **Tag:** `v1.0-PC-004-stable`
- **Branch:** `feature/PC-004` → merged to `master`

### STEP 1 — Architecture Review

- `scopeSort($query, string $sort)` added to `Product` model — uses `match` expression; options: `newest` (default), `oldest`, `price_asc`, `price_desc`, `rating`; unknown values fall back to `newest`
- `ProductController::index()` updated: `sort` added to `$request->only([...])`, `$sort` extracted with `?? 'newest'` default; `->latest()` replaced with `->sort($sort)`
- Sort `<select>` dropdown added to filter form in `products/index.blade.php`; selected state persisted from `$filters['sort']`; 5 options rendered

### STEP 2 — Security Checklist

- [x] Unknown sort values fall back to `newest` via `match` default — no raw SQL injection possible
- [x] Sort param rendered in `<select>` via server-side comparison only — no unescaped output
- [x] `withQueryString()` preserves sort across pagination pages
- [x] No auth required — public route per AC

### STEP 3 — Test Results

| TC    | Description                                               | Type        | Result |
| ----- | --------------------------------------------------------- | ----------- | ------ |
| TC-01 | Default sort (no param) returns products newest first     | Happy       | PASS   |
| TC-02 | sort=newest returns products newest first                 | Happy       | PASS   |
| TC-03 | sort=oldest returns products oldest first                 | Happy       | PASS   |
| TC-04 | sort=price_asc returns cheapest product first             | Happy       | PASS   |
| TC-05 | sort=price_desc returns most expensive product first      | Happy       | PASS   |
| TC-06 | sort=rating returns highest-rated product first           | Happy       | PASS   |
| TC-07 | Sort dropdown has at least 5 options rendered             | Happy       | PASS   |
| TC-08 | Sort selection persisted in dropdown after applying       | Happy       | PASS   |
| TC-09 | Sort works combined with category filter                  | Happy       | PASS   |
| TC-10 | Unknown sort value falls back to newest                   | Edge        | PASS   |
| TC-11 | Sort param persists in pagination links (withQueryString) | Happy       | PASS   |
| TC-12 | Sorted listing responds within 2 seconds                  | Performance | PASS   |

**Score: 12/12 — All acceptance criteria met**

### STEP 4 — Proposals for Next Task

- PC-005 (product detail page) should display category, rating, and allow adding to cart
- Consider persisting default sort preference in session/cookie for returning users (post-MVP)
- A relevance/sales-based sort could be added once order data exists (post-MVP)

<!-- EVAL-PC-004 END -->

## EVAL-PC-005 · Product Detail Page with Slug, SKU, Related Products

- **Task:** PC-005
- **Sprint:** 2
- **Date:** 2026-04-15
- **Tag:** `v1.0-PC-005-stable`
- **Branch:** `feature/PC-005` → merged to `master`

### STEP 1 — Architecture Review

- Migration `add_slug_sku_to_products_table`: adds `slug` (unique, nullable) and `sku` (unique, nullable) to `products`
- `Product` model: `slug` + `sku` added to `$fillable`; `getRouteKeyName()` returns `'slug'` for SEO-friendly URLs; `relatedProducts(int $limit)` method queries same category, excludes self, limits to 4
- `ProductFactory`: `name` made unique with `fake()->unique()->words(3, true)`; `slug = Str::slug($name)`, `sku = strtoupper(fake()->bothify('???-####'))`
- Route `GET /products/{product:slug}` → `products.show` (public, no auth)
- `ProductController::show(Product $product)` — route model binding by slug, loads related products
- `products/show.blade.php` — image, name, SKU, price, category, rating, stock status, description, related products grid, "Add to Cart" placeholder (disabled), `<meta description>` for SEO
- `products/index.blade.php` — product name in listing now links to `products.show`

### STEP 2 — Security Checklist

- [x] Route model binding — invalid slug auto-returns 404 (no information disclosure)
- [x] All product fields rendered with `{{ }}` auto-escaping (XSS safe)
- [x] `<meta description>` uses `Str::limit()` — description truncated, never raw user input in meta length
- [x] No auth required per AC (visitor feature)
- [x] SKU/slug uniqueness enforced at DB level

### STEP 3 — Test Results

| TC    | Description                                                | Type          | Result |
| ----- | ---------------------------------------------------------- | ------------- | ------ |
| TC-01 | Detail page returns 200 for valid slug                     | Happy         | PASS   |
| TC-02 | Product name shown on detail page                          | Happy         | PASS   |
| TC-03 | Product description shown on detail page                   | Happy         | PASS   |
| TC-04 | Product price shown on detail page                         | Happy         | PASS   |
| TC-05 | In Stock status shown when stock > 0                       | Happy         | PASS   |
| TC-06 | Out of Stock status shown when stock = 0                   | Edge          | PASS   |
| TC-07 | SKU shown on detail page                                   | Happy         | PASS   |
| TC-08 | Category name shown on detail page                         | Happy         | PASS   |
| TC-09 | Rating shown on detail page                                | Happy         | PASS   |
| TC-10 | Related products section shown with same-category products | Happy         | PASS   |
| TC-11 | Non-existent slug returns 404                              | Edge          | PASS   |
| TC-12 | Detail page accessible without login and responds < 2s     | Security/Perf | PASS   |

**Score: 12/12 — All acceptance criteria met**

### STEP 4 — Proposals for Next Task

- SC-001 (Add to Cart) — "Add to Cart" button is already present on detail page (disabled placeholder); next sprint can wire it up
- Consider auto-generating slug from name on product creation (Observer or `boot()` hook) before admin CRUD is built
- Image gallery (multiple images) is an upgrade path; current implementation supports single image

<!-- EVAL-PC-005 END -->

## EVAL-SC-001 · Add to Cart (Session-Based, Guest+Auth, AJAX)

**Version:** A  
**Date:** 2026-04-15  
**Status in Backlog:** Done  
**Linked Task:** [SC-001](backlog.md)  
**Git Tag:** `v1.0-SC-001-stable`

### Test Results

| TC    | Scenario                                    | Type       | Result  |
| ----- | ------------------------------------------- | ---------- | ------- |
| TC-01 | Guest adds product to session cart          | Happy Path | PASS ✅ |
| TC-02 | Authenticated user adds product to cart     | Happy Path | PASS ✅ |
| TC-03 | Cart stores the correct quantity            | Happy Path | PASS ✅ |
| TC-04 | Adding same product twice merges quantities | Edge       | PASS ✅ |
| TC-05 | Out-of-stock product returns 422 JSON error | Negative   | PASS ✅ |
| TC-06 | Nonexistent product_id fails validation     | Negative   | PASS ✅ |
| TC-07 | Zero quantity fails validation              | Negative   | PASS ✅ |
| TC-08 | Negative quantity fails validation          | Negative   | PASS ✅ |
| TC-09 | Quantity exceeding stock is capped at stock | Edge       | PASS ✅ |
| TC-10 | AJAX request returns JSON with cart_count   | Happy Path | PASS ✅ |
| TC-11 | Session item contains correct data keys     | Happy Path | PASS ✅ |
| TC-12 | Add to cart completes within 1 second       | Perf       | PASS ✅ |

**Summary:** 12 Passed · 0 Failed · 0 Skipped  
**Regression:** All 146 previous tests still PASS ✅ · Total suite: 158/158 · 323 assertions

### Quality Scores (1–5)

| Dimension        | Score | Notes                                                              |
| ---------------- | ----- | ------------------------------------------------------------------ |
| Correctness      | 5     | All AC satisfied; qty cap, merge, out-of-stock guard               |
| Test Coverage    | 5     | 12 tests cover happy, negative, edge, perf                         |
| Security         | 5     | CSRF active, `exists:products,id` validation, no mass-assignment   |
| Code Clarity     | 5     | Controller single-responsibility, clear intent                     |
| Architecture Fit | 4     | Session-based fits SC-001 scope; DB persistence deferred to SC-005 |

**Score: 12/12 — All acceptance criteria met**

### Architecture Notes

- Cart stored as `session('cart')` keyed by `product_id` (integer): `['product_id', 'name', 'price', 'quantity', 'slug']`
- Session survives login: Laravel's `session()->regenerate()` preserves data, so guest cart is automatically available after auth (AC satisfied)
- `CartController::store()` returns `JsonResponse` when `$request->expectsJson()`, `RedirectResponse` otherwise — dual-mode, no duplication
- Quantity merges on re-add; total capped at current stock

### STEP 4 — Proposals for Next Task

- **SC-002 (View Cart)** — `GET /cart` → `CartController::index()` returning `cart.index` view; list items with image, name, qty, unit price, subtotal, order total; empty-cart state
- Consider extracting a `CartService` when SC-002/SC-003 are built (currently thin enough to stay in controller)
- Cart badge in a shared layout/nav (currently local to product detail page) should be addressed in SC-002 sprint

<!-- EVAL-SC-001 END -->

## EVAL-SC-002 · View Cart — Items, Subtotals, Order Total

**Version:** A  
**Date:** 2026-04-15  
**Status in Backlog:** Done  
**Linked Task:** [SC-002](backlog.md)  
**Git Tag:** `v1.0-SC-002-stable`

### Test Results

| TC    | Scenario                                     | Type       | Result  |
| ----- | -------------------------------------------- | ---------- | ------- |
| TC-01 | Cart page returns 200 for guest              | Happy Path | PASS ✅ |
| TC-02 | Cart page returns 200 for authenticated user | Happy Path | PASS ✅ |
| TC-03 | Empty cart shows empty-cart message          | Edge       | PASS ✅ |
| TC-04 | Cart shows product name                      | Happy Path | PASS ✅ |
| TC-05 | Cart shows unit price                        | Happy Path | PASS ✅ |
| TC-06 | Cart shows quantity                          | Happy Path | PASS ✅ |
| TC-07 | Cart shows correct line subtotal             | Happy Path | PASS ✅ |
| TC-08 | Cart shows correct order total               | Happy Path | PASS ✅ |
| TC-09 | Multiple products all appear in the cart     | Happy Path | PASS ✅ |
| TC-10 | Empty cart does not show order total         | Edge       | PASS ✅ |
| TC-11 | Cart page has a Continue Shopping link       | Happy Path | PASS ✅ |
| TC-12 | Cart page responds within 1 second           | Perf       | PASS ✅ |

**Summary:** 12 Passed · 0 Failed · 0 Skipped  
**Regression:** All 158 previous tests still PASS ✅ · Total suite: 170/170 · 337 assertions

### Quality Scores (1–5)

| Dimension        | Score | Notes                                                    |
| ---------------- | ----- | -------------------------------------------------------- |
| Correctness      | 5     | All AC satisfied; name, unit price, qty, subtotal, total |
| Test Coverage    | 5     | 12 tests cover happy, edge, perf                         |
| Security         | 5     | Read-only view; no user input processed                  |
| Code Clarity     | 5     | `index()` is 7 lines, total computed via `array_map`     |
| Architecture Fit | 5     | Consistent with SC-001; same session cart structure      |

**Score: 12/12 — All acceptance criteria met**

### STEP 4 — Proposals for Next Task

- **SC-003 (Update Cart Qty)** — `PATCH /cart/{productId}` → `CartController::update()`; qty bounded 1–stock; redirect to cart view
- **SC-004 (Remove from Cart)** — `DELETE /cart/{productId}` → `CartController::destroy()`; can be batched with SC-003 (same sprint)
- Consider moving total calculation into a helper/service once SC-003 lands (total will be reused across update/view)

<!-- EVAL-SC-002 END -->

<!-- EVAL-SC-003 START -->

## EVAL-SC-003 — Update Cart Quantity (Stock Cap, AJAX Subtotal/Total Recalc)

**Date:** 2026-04-15  
**Branch:** `feature/SC-003` → merged `master` @ `v1.0-SC-003-stable`  
**Baseline:** 170 tests · 337 assertions · 0 failures  
**Result:** 182 tests · 363 assertions · 0 failures

### STEP 1 — Code

| File                   | Change                                                                                                           |
| ---------------------- | ---------------------------------------------------------------------------------------------------------------- |
| `CartController.php`   | Added `update(Request $request, int $productId)` — validates qty ≥ 1, caps at stock, dual JSON/redirect response |
| `routes/web.php`       | Added `Route::patch('/cart/{productId}', ...)` → `cart.update`                                                   |
| `cart/index.blade.php` | Added CSRF meta, qty-update `<form class="qty-update-form">` per row, subtotal/total IDs, AJAX JS                |
| `CartUpdateTest.php`   | NEW — 12 tests                                                                                                   |

### STEP 2 — Tests (CartUpdateTest.php — 12/12 PASS)

| #     | Test                                                    | Result  |
| ----- | ------------------------------------------------------- | ------- |
| TC-01 | `sc003 update redirects to cart`                        | ✅ PASS |
| TC-02 | `sc003 update saves new quantity in session`            | ✅ PASS |
| TC-03 | `sc003 quantity exceeding stock is capped`              | ✅ PASS |
| TC-04 | `sc003 ajax returns json with subtotal and order total` | ✅ PASS |
| TC-05 | `sc003 ajax order total recalculates across items`      | ✅ PASS |
| TC-06 | `sc003 updating nonexistent cart item returns 404`      | ✅ PASS |
| TC-07 | `sc003 zero quantity fails validation`                  | ✅ PASS |
| TC-08 | `sc003 negative quantity fails validation`              | ✅ PASS |
| TC-09 | `sc003 missing quantity fails validation`               | ✅ PASS |
| TC-10 | `sc003 minimum quantity one is accepted`                | ✅ PASS |
| TC-11 | `sc003 successful update flashes success message`       | ✅ PASS |
| TC-12 | `sc003 update completes within one second`              | ✅ PASS |

**Regression:** All 170 previous tests still PASS ✅ · Total suite: 182/182 · 363 assertions

### STEP 3 — Evaluation

| Criterion        | Score | Notes                                                               |
| ---------------- | ----- | ------------------------------------------------------------------- |
| Correctness      | 5     | Qty bounded 1–stock, session updated, subtotal/order_total correct  |
| Test Coverage    | 5     | 12 tests: happy path, edge (min/stock cap), validation, AJAX, perf  |
| Security         | 5     | CSRF protected, input validated, no direct object injection         |
| Code Clarity     | 5     | `update()` is 22 lines; dual-mode pattern mirrors `store()`         |
| Architecture Fit | 5     | Consistent session-cart pattern; AJAX response shape extends SC-001 |

**Score: 12/12 — All acceptance criteria met**

### STEP 4 — Proposals for Next Task

- **SC-004 (Remove from Cart)** — `DELETE /cart/{productId}` → `CartController::destroy()`; remove item from session; JSON response returns updated `cart_count` and `order_total`; add Remove button to `cart/index.blade.php`
- Consider extracting a `CartService` once SC-004 lands — `update()`, `destroy()`, and `store()` all share the same session-cart read/write pattern

<!-- EVAL-SC-003 END -->

<!-- EVAL-SC-004 START -->

## EVAL-SC-004 — Remove Cart Item (AJAX cart_count + order_total Recalc)

**Date:** 2026-04-15  
**Branch:** `feature/SC-004` → merged `master` @ `v1.0-SC-004-stable`  
**Baseline:** 182 tests · 363 assertions · 0 failures  
**Result:** 194 tests · 395 assertions · 0 failures

### STEP 1 — Code

| File                   | Change                                                                                                     |
| ---------------------- | ---------------------------------------------------------------------------------------------------------- |
| `CartController.php`   | Added `destroy(Request $request, int $productId)` — removes item from session, dual JSON/redirect response |
| `routes/web.php`       | Added `Route::delete('/cart/{productId}', ...)` → `cart.destroy`                                           |
| `cart/index.blade.php` | Added Actions column header, Remove form per row with `@method('DELETE')`, AJAX JS for remove              |
| `CartRemoveTest.php`   | NEW — 12 tests                                                                                             |

### STEP 2 — Tests (CartRemoveTest.php — 12/12 PASS)

| #     | Test                                                      | Result  |
| ----- | --------------------------------------------------------- | ------- |
| TC-01 | `sc004 remove redirects to cart`                          | ✅ PASS |
| TC-02 | `sc004 removes item from session`                         | ✅ PASS |
| TC-03 | `sc004 order total recalculates after remove`             | ✅ PASS |
| TC-04 | `sc004 ajax returns json with cart count and order total` | ✅ PASS |
| TC-05 | `sc004 removing last item returns empty cart`             | ✅ PASS |
| TC-06 | `sc004 removing nonexistent item returns 404 json`        | ✅ PASS |
| TC-07 | `sc004 removing nonexistent item redirects with error`    | ✅ PASS |
| TC-08 | `sc004 guest can remove item from cart`                   | ✅ PASS |
| TC-09 | `sc004 authenticated user can remove item from cart`      | ✅ PASS |
| TC-10 | `sc004 other items remain after remove`                   | ✅ PASS |
| TC-11 | `sc004 successful remove flashes success message`         | ✅ PASS |
| TC-12 | `sc004 remove completes within one second`                | ✅ PASS |

**Regression:** All 182 previous tests still PASS ✅ · Total suite: 194/194 · 395 assertions

### STEP 3 — Evaluation

| Criterion        | Score | Notes                                                                                          |
| ---------------- | ----- | ---------------------------------------------------------------------------------------------- |
| Correctness      | 5     | Item removed from session; `cart_count` and `order_total` accurate                             |
| Test Coverage    | 5     | 12 tests: redirect, session, recalc, AJAX shape, empty cart, 404, guest/auth, isolation, flash |
| Security         | 5     | CSRF protected via form spoofing and `X-CSRF-TOKEN` header                                     |
| Code Clarity     | 5     | `destroy()` is 20 lines; mirrors `update()` dual-mode pattern                                  |
| Architecture Fit | 5     | Consistent session-cart pattern; response shape follows SC-001/SC-003 conventions              |

**Score: 12/12 — All acceptance criteria met**

### STEP 4 — Proposals for Next Task

- **SC-005 (Coupon Codes, Sprint 7)** — `POST /cart/coupon` → apply discount code to session cart; validate code against DB; return updated `order_total` and `discount_amount`
- Consider extracting a `CartService` now that `store()`, `update()`, and `destroy()` all share identical session-read/write patterns

<!-- EVAL-SC-004 END -->

<!-- EVAL-SC-005 -->

<!-- EVAL-CP-001 START -->

## EVAL-CP-001 — Checkout Address Step (Saved Addresses, New Form, Validation)

**Date:** 2026-04-15  
**Branch:** `feature/CP-001` → merged `master` @ `v1.0-CP-001-stable`  
**Baseline:** 194 tests · 395 assertions · 0 failures  
**Result:** 206 tests · 422 assertions · 0 failures

### STEP 1 — Code

| File                                                | Change                                                                                                                             |
| --------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------- |
| `2026_04_15_200000_create_user_addresses_table.php` | NEW migration — `user_addresses` (user_id, name, address_line1/2, city, state, postal_code, country, is_default)                   |
| `UserAddress.php`                                   | NEW model — `belongsTo User`, fillable, `is_default` cast                                                                          |
| `User.php`                                          | Added `addresses()` hasMany relationship                                                                                           |
| `UserAddressFactory.php`                            | NEW factory                                                                                                                        |
| `CheckoutController.php`                            | NEW — `showAddress()` + `storeAddress()` — saved address selection or new address validation, stores to `checkout.address` session |
| `routes/web.php`                                    | Added `GET/POST /checkout/address` (auth), `GET /checkout/shipping` placeholder                                                    |
| `checkout/address.blade.php`                        | NEW view — saved addresses list (radio), new address form, validation errors                                                       |
| `CheckoutAddressTest.php`                           | NEW — 12 tests                                                                                                                     |

### STEP 2 — Tests (CheckoutAddressTest.php — 12/12 PASS)

| #     | Test                                                 | Result  |
| ----- | ---------------------------------------------------- | ------- |
| TC-01 | `cp001 address page returns 200 for auth user`       | ✅ PASS |
| TC-02 | `cp001 guest is redirected to login`                 | ✅ PASS |
| TC-03 | `cp001 auth user sees saved addresses`               | ✅ PASS |
| TC-04 | `cp001 user with no addresses sees new address form` | ✅ PASS |
| TC-05 | `cp001 valid address stored in session`              | ✅ PASS |
| TC-06 | `cp001 new address saved to database`                | ✅ PASS |
| TC-07 | `cp001 selecting saved address stores it in session` | ✅ PASS |
| TC-08 | `cp001 name is required`                             | ✅ PASS |
| TC-09 | `cp001 address line1 is required`                    | ✅ PASS |
| TC-10 | `cp001 city is required`                             | ✅ PASS |
| TC-11 | `cp001 postal code is required`                      | ✅ PASS |
| TC-12 | `cp001 country is required`                          | ✅ PASS |

**Regression:** All 194 previous tests still PASS ✅ · Total suite: 206/206 · 422 assertions

### STEP 3 — Evaluation

| Criterion        | Score | Notes                                                                                               |
| ---------------- | ----- | --------------------------------------------------------------------------------------------------- |
| Correctness      | 5     | Saved addresses listed, new form available, address saved to DB and session                         |
| Test Coverage    | 5     | 12 tests: auth/guest, saved address display, new address DB persist, session, all 5 required fields |
| Security         | 5     | Auth guard enforced, address ownership checked (user_id scope) before use, CSRF on all forms        |
| Code Clarity     | 5     | `showAddress()` is 4 lines; `storeAddress()` is 25 lines; dual path clearly branched                |
| Architecture Fit | 5     | `checkout.address` session key scoped under `checkout.*` for CP-002+ steps                          |

**Score: 12/12 — All acceptance criteria met**

### STEP 4 — Proposals for Next Task

- **CP-002 (Shipping Method)** — `GET/POST /checkout/shipping` → `CheckoutController::showShipping()/storeShipping()`; show Standard / Express options; add cost to order total; store in `checkout.shipping` session
- Replace the `checkout.shipping` placeholder route with the full CP-002 implementation

<!-- EVAL-CP-001 END -->

<!-- EVAL-CP-002 -->

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
| 2026-04-15 | PC-001 (Sprint 2)     | 98          | 98     | 0      | 0            | Agent  |
| 2026-04-15 | PC-002 (Sprint 2)     | 110         | 110    | 0      | 0            | Agent  |
| 2026-04-15 | PC-003 (Sprint 2)     | 122         | 122    | 0      | 0            | Agent  |
| 2026-04-15 | PC-004 (Sprint 2)     | 134         | 134    | 0      | 0            | Agent  |
| 2026-04-15 | PC-005 (Sprint 2)     | 146         | 146    | 0      | 0            | Agent  |
| 2026-04-15 | SC-001 (Sprint 2)     | 158         | 158    | 0      | 0            | Agent  |
| 2026-04-15 | SC-002 (Sprint 2)     | 170         | 170    | 0      | 0            | Agent  |
| 2026-04-15 | SC-003 (Sprint 2)     | 182         | 182    | 0      | 0            | Agent  |
| 2026-04-15 | SC-004 (Sprint 2)     | 194         | 194    | 0      | 0            | Agent  |
| 2026-04-15 | CP-001 (Sprint 3)     | 206         | 206    | 0      | 0            | Agent  |

---

## Upgrade Version Log

> Tracks approved improvement proposals and their outcomes.

| Upgrade ID | Base Task | Proposal Source (EVAL link) | Approval Date | New Metrics vs Old | Outcome |
| ---------- | --------- | --------------------------- | ------------- | ------------------ | ------- |
| —          | —         | —                           | —             | —                  | —       |

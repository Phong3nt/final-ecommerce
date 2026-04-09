# Security Checks — Laravel E-Commerce

> Referenced by [instruction.md](instruction.md) STEP 1 (code) and STEP 3 (evaluation).  
> Apply to ALL tasks that handle user input, authentication, data mutations, or payments.

---

## Mandatory Checklist — Before Marking Any Task Done

### 1. CSRF Protection

- [ ] All POST / PUT / PATCH / DELETE routes are inside the `web` middleware group
- [ ] All Blade forms include `@csrf`
- [ ] No mutating route bypasses CSRF middleware (check `VerifyCsrfToken` exceptions list)

### 2. XSS Prevention

- [ ] All user-controlled output uses `{{ }}` (auto-escaped) — **never** `{!! !!}` unless explicitly sanitised
- [ ] Search results, flash messages, error messages all use `{{ }}`
- [ ] No raw HTML rendered from user-supplied data without explicit purification

### 3. SQL Injection

- [ ] No raw SQL string concatenation anywhere
- [ ] If raw query is unavoidable: `DB::select('SELECT * FROM x WHERE id = ?', [$id])` — parameterised only
- [ ] All Eloquent `where()` calls use parameter binding (default) — no string interpolation inside queries

### 4. Authentication & Session

- [ ] Protected routes are behind `auth` middleware
- [ ] Admin routes behind `role:admin` middleware (implemented in AU-006)
- [ ] `Auth::attempt()` used for credential verification — no manual password comparison
- [ ] Session regenerated after login: `$request->session()->regenerate()`
- [ ] Session invalidated on logout: `$request->session()->invalidate()` + `regenerateToken()`
- [ ] Google OAuth (AU-003): session regenerated after `Auth::login()`

### 5. Sensitive Data

- [ ] Passwords stored only as bcrypt hash — `Hash::make()` or `bcrypt()`
- [ ] No `dd()`, `dump()`, `Log::info($user)` that expose password, token, or card data
- [ ] API keys, credentials in `.env` only — never hardcoded, never in Blade views
- [ ] `.env` is in `.gitignore` and never committed

### 6. File Upload (Tasks: UP-001)

- [ ] MIME type validated server-side (`mimes:jpg,png` in FormRequest)
- [ ] Max file size enforced (`max:2048` in FormRequest)
- [ ] Files stored outside `public/` or served through authenticated download routes

### 7. Payment Security (Task: CP-003 only)

- [ ] Card numbers, CVV **never** stored in database
- [ ] Tokenisation handled client-side (Stripe.js / Midtrans.js)
- [ ] Webhook endpoint validates provider signature before processing
- [ ] Idempotency key used to prevent duplicate order creation on retry

---

## Security Test Requirements Per Risk

| Risk                 | Required Test                               | Pass Condition                      |
| -------------------- | ------------------------------------------- | ----------------------------------- |
| XSS                  | Submit `<script>alert(1)</script>` as input | Output is escaped: `&lt;script&gt;` |
| CSRF                 | POST without `_token`                       | Response is 419                     |
| SQLi                 | Submit `' OR 1=1 --` as input               | No SQL error, no data leak          |
| Auth bypass          | Request protected route without session     | Redirect to `/login` or 401         |
| Privilege escalation | Request `/admin/*` as `role:user`           | 403 Forbidden                       |
| Session fixation     | Login — compare session ID before/after     | Session ID must change              |

---

## Architectural Conflict Triggers (→ invoke Rule 10 in instruction.md)

If a new task does ANY of the following, immediately run Rule 10 check:

- Adds a new way to authenticate (bypassing `Auth::attempt`)
- Removes or loosens middleware from an existing route
- Gives users elevated access without role check
- Stores or transmits data that existing tasks explicitly protect

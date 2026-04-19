# Improvement Execution Template

> Use this template whenever you want to propose or apply a **UI/UX, logic, or infrastructure improvement** that is NOT triggered by a runtime error (use `fix_template.md` for errors) and NOT a brand-new feature (use `task_template.md` + `backlog.md` for those).
>
> An improvement is a deliberate enhancement to existing, working code — better UI, cleaner logic, better config — initiated by the user.

---

## HOW TO INVOKE (Command Scopes)

Prefix your request with one of the following scope keywords so the Agent focuses **only** on the relevant layer and does not touch anything else:

| Scope Keyword       | What the Agent Touches                                               | What the Agent Must NOT Touch               |
| ------------------- | -------------------------------------------------------------------- | ------------------------------------------- |
| `[UIUX_MODE]`       | Blade views, Bootstrap classes, CSS, Alpine.js, JS interactions      | Controllers, Services, Models, DB, Routes   |
| `[LOGIC_MODE]`      | Controllers, Services, Repositories, FormRequests, Jobs, PHP logic   | Blade views, CSS, DB schema, config files   |
| `[INFRA_MODE]`      | `.env`, `config/*.php`, migrations, queue/mail drivers, Firebase cfg | Application code, Blade views, test files   |
| `[FULL_STACK_MODE]` | All layers — only use for complex cross-cutting improvements         | Core vendor libraries (never touch vendor/) |

**Examples of valid invocations:**

```
[UIUX_MODE] Cải thiện giao diện trang checkout để mobile-friendly hơn — IMP-003
[LOGIC_MODE] Tối ưu query trong OrderController để giảm N+1 — IMP-007
[INFRA_MODE] Chuyển queue driver từ sync sang database — IMP-012
[FULL_STACK_MODE] Thêm Skeleton Screen cho trang danh sách sản phẩm — IMP-015
```

> **Rule:** If no scope keyword is provided, the Agent must ask the user to specify one before doing anything.

---

## IMPROVEMENT HEADER

Fill this in before writing any code:

| Field            | Value                                                                 |
| ---------------- | --------------------------------------------------------------------- |
| Improvement ID   | `IMP-<NNN>` (next sequential number in improvement backlog)           |
| Improvement Name | _(short descriptive title)_                                           |
| Scope            | `[UIUX_MODE]` / `[LOGIC_MODE]` / `[INFRA_MODE]` / `[FULL_STACK_MODE]` |
| Target Task IDs  | _(which backlog Task IDs are affected, e.g. PC-001, CP-003)_          |
| Epic             | _(which Epic this improvement belongs to)_                            |
| Sprint           | _(assigned sprint, or "Backlog" if unscheduled)_                      |
| Story Points     | `1 · 2 · 3 · 5 · 8`                                                   |
| Priority         | `1-Critical / 2-High / 3-Medium / 4-Low`                              |
| Date             | `YYYY-MM-DD`                                                          |

---

## PRE-TASK GIT HEALTH CHECK

> Run **before** writing any code. Same discipline as `task_template.md`. Full rules → [git_workflow.md](git_workflow.md) Rule G6.

```powershell
$env:Path = "C:\xampp\php;" + $env:Path
cd C:\Users\DELL\Desktop\final
git status        # must be: "nothing to commit, working tree clean"
git branch        # must be on master
git tag --list    # confirm latest stable task tag exists
```

| Check               | Result       | Action if Fail                    |
| ------------------- | ------------ | --------------------------------- |
| Working tree clean? | ☐ Yes / ☐ No | Commit or stash before continuing |
| On `master` branch? | ☐ Yes / ☐ No | `git checkout master`             |
| Latest tag correct? | ☐ Yes / ☐ No | Re-tag or investigate             |

Then create the improvement branch:

```bash
git checkout -b improve/[IMP-NNN]   # e.g. improve/IMP-006
```

---

## STEP 0 — TECH STACK COMPATIBILITY CHECK

> **Mandatory before any code.** This prevents scope creep and tech stack conflicts.

Answer all questions. If any answer is ❌, the Agent must stop and propose a stack-compatible alternative.

### A. Library / Framework Conflict

| Question                                                                             | Answer       | Action if ❌                                                      |
| ------------------------------------------------------------------------------------ | ------------ | ----------------------------------------------------------------- |
| Does this improvement introduce a NEW library not in `composer.json`/`package.json`? | ☐ Yes / ☐ No | Must ask user approval before `composer require` or `npm install` |
| Does the suggested approach assume Tailwind CSS, but project uses Bootstrap?         | ☐ Yes / ☐ No | Propose Bootstrap equivalent. Do NOT add Tailwind.                |
| Does it assume Vue/React, but project uses Blade + Alpine.js?                        | ☐ Yes / ☐ No | Rewrite using Blade components + Alpine.js.                       |
| Does it assume Livewire, but project has not installed it?                           | ☐ Yes / ☐ No | Use standard Blade + AJAX/Alpine instead.                         |
| Does it require a DB schema change (new column/table)?                               | ☐ Yes / ☐ No | Ask user first (Rule 7 in `instruction.md`).                      |

**Current confirmed stack** (do not deviate without user approval):

| Layer    | Technology                          |
| -------- | ----------------------------------- |
| Backend  | Laravel 10 · PHP 8.1                |
| Database | MySQL (XAMPP)                       |
| Frontend | Blade · Bootstrap 5 · Alpine.js     |
| Auth     | Laravel Sanctum / Spatie Permission |
| Cloud    | Firebase (Push Notifications only)  |
| Queue    | Laravel Queue (driver per .env)     |
| Testing  | PHPUnit · Laravel Test helpers      |

### B. Scope Creep Check

| Question                                                            | Answer       |
| ------------------------------------------------------------------- | ------------ |
| Does this improvement change any **Done** task's public API?        | ☐ Yes / ☐ No |
| Does this improvement add logic outside the declared scope keyword? | ☐ Yes / ☐ No |
| Does this improvement duplicate functionality already in backlog?   | ☐ Yes / ☐ No |

> If any **Yes** → Document the overlap and ask user whether to merge with the existing task or proceed as a separate improvement.

---

## STEP 1 — BACKLOG & EVALUATION SCAN

Before touching any file, map the improvement to the existing backlog.

```
1. Open backlog.md
   → Find every Task ID whose code will be touched
   → List them in "Affected Task IDs" table below
2. Open evaluation_history.md
   → Read the EVAL block for each affected task
   → Note current quality scores and any existing proposals
3. Check if a similar improvement already exists:
   → Search evaluation_history.md proposals for duplicate intent
   → Search improvement_backlog section in backlog.md for duplicate IMP-IDs
4. Check for active proposals (STALE/INVALID markers)
   → If any proposal for the affected task is ⚠️ STALE, report to user before proceeding
```

### Affected Task IDs

| Task ID | Epic | Current Status | Why Affected |
| ------- | ---- | -------------- | ------------ |
|         |      |                |              |

### Existing Proposals Check

| Proposal ID | Status (Valid / ⚠️ STALE / ❌ INVALID) | Conflict with This Improvement? |
| ----------- | -------------------------------------- | ------------------------------- |
|             |                                        |                                 |

---

## STEP 2 — IMPACT ANALYSIS

### Risk Assessment

| Risk                                                   | Likelihood   | Severity     | Mitigation |
| ------------------------------------------------------ | ------------ | ------------ | ---------- |
| Breaks existing UI layout for affected Blade views     | Low/Med/High | Low/Med/High |            |
| Changes controller response format (JSON/redirect)     | Low/Med/High | Low/Med/High |            |
| Affects shared helper / base class used by other tasks | Low/Med/High | Low/Med/High |            |
| Requires cache/config clear after deployment           | Low/Med/High | Low/Med/High |            |

### Architectural Conflict Check (Rule 10)

- Does this improvement add a new auth path? ☐ Yes / ☐ No
- Does this improvement remove or weaken middleware? ☐ Yes / ☐ No
- Does this improvement change session/token/role behavior? ☐ Yes / ☐ No

> **Any Yes** → Run full Rule 10 procedure from `instruction.md` before proceeding.

---

## STEP 3 — IMPROVEMENT PLAN

State the **minimal** change needed. No over-engineering. No unrequested additions.

| Layer    | Change Description | Files to Modify |
| -------- | ------------------ | --------------- |
| View     |                    |                 |
| Logic    |                    |                 |
| Config   |                    |                 |
| Database |                    |                 |

### Isolation Check

- [ ] Change is isolated to the declared scope keyword?
- [ ] No shared base class / service modified without user approval?
- [ ] No schema changes without user approval?
- [ ] No new library added without user approval?

---

## STEP 4 — APPLY IMPROVEMENT

Follow the active scope keyword strictly:

**[UIUX_MODE] checklist:**

- [ ] Only Blade, CSS, JS, Alpine.js files modified
- [ ] Bootstrap utility classes used first (no custom CSS if Bootstrap has equivalent)
- [ ] Mobile-first: tested at 375px viewport before 1280px
- [ ] No raw JS that conflicts with Alpine.js or Bootstrap's own JS
- [ ] **Security (scope-limited):** All user-controlled Blade output uses `{{ }}` — never `{!! !!}`. Ref: [security_checks.md §2 XSS](security_checks.md)
- [ ] **Tests:** ⛔ Not required for pure UI changes. No PHPUnit tests needed.

**[LOGIC_MODE] checklist:**

- [ ] No UI/View files touched
- [ ] Fat Model / Skinny Controller pattern maintained
- [ ] No raw SQL — Eloquent or Query Builder only
- [ ] CSRF protection intact on all mutating routes
- [ ] **Security:** Run all applicable sections of [security_checks.md](security_checks.md) — §1 CSRF, §2 XSS, §3 SQLi, §4 Auth/Session, §5 Sensitive Data. Skip §6/§7 unless touching file upload or payments.
- [ ] **Tests:** ✅ Required — minimum 3 cases (happy + negative + edge) per [testing_standards.md §2](testing_standards.md). Follow naming convention §3. Update existing TCs if behavior changes (Rule 8 in instruction.md).

**[INFRA_MODE] checklist:**

- [ ] Only `config/`, `.env.example`, migrations, or queue/mail config files touched
- [ ] No application logic changed
- [ ] Artisan commands documented (e.g., `php artisan config:clear`)
- [ ] **Security (scope-limited):** [security_checks.md §5](security_checks.md) only — no credentials hardcoded in config files or migrations; all secrets in `.env`.
- [ ] **Tests:** ⛔ Not required. Config/driver changes are environment-level — document artisan commands only.

**[FULL_STACK_MODE] checklist:**

- [ ] All checklists above applied to their respective layers
- [ ] Each layer's change documented separately in the plan table above
- [ ] **Security:** All applicable sections of [security_checks.md](security_checks.md). Security tests mandatory if logic touches auth or payment (see [testing_standards.md §5](testing_standards.md)).
- [ ] **Tests:** ✅ Required for the logic layer — minimum 3 cases per [testing_standards.md §2](testing_standards.md). UI layer: no PHPUnit tests.

### Code / Config Changes Applied

```bash
# Artisan or setup commands (if any)
```

```php
// Summary of logic changes
```

---

## STEP 5 — VERIFY

### Scope-Conditional Test Requirement

| Scope               | Tests Required?           | Standard                                                                                   |
| ------------------- | ------------------------- | ------------------------------------------------------------------------------------------ |
| `[UIUX_MODE]`       | ⛔ No                     | Pure Blade/CSS/Alpine.js — no PHPUnit-testable logic                                       |
| `[LOGIC_MODE]`      | ✅ Yes                    | Min 3 cases: happy + negative + edge. Ref: [testing_standards.md §2](testing_standards.md) |
| `[INFRA_MODE]`      | ⛔ No                     | Environment-level — document artisan commands only                                         |
| `[FULL_STACK_MODE]` | ✅ Yes (logic layer only) | Logic layer: min 3 cases per [testing_standards.md §2](testing_standards.md)               |

### Baseline

> Skip baseline if `[UIUX_MODE]` or `[INFRA_MODE]` (no tests required).

```powershell
$env:Path = "C:\xampp\php;" + $env:Path
cd C:\Users\DELL\Desktop\final\ecommerce
php artisan test --filter <AffectedTask1>Test
php artisan test --filter <AffectedTask2>Test
```

Baseline: `__ tests · __ assertions · 0 failures`

### After Improvement

> Skip if `[UIUX_MODE]` or `[INFRA_MODE]`.

```powershell
# Targeted regression: affected tasks + same Epic Done tasks
php artisan test --filter <AffectedTask1>Test
php artisan test --filter <AffectedTask2>Test

# Full suite for final gate
php artisan test
```

| Test Run             | Before     | After          |
| -------------------- | ---------- | -------------- |
| Affected task tests  | `X/Y PASS` | `X/Y PASS`     |
| Full suite           | `N PASS`   | `N PASS`       |
| Regression detected? | —          | Yes ❌ / No ✅ |

> Regression detected → **STOP**. Do not commit. Fix regression before continuing.

---

## STEP 6 — EVALUATE & DOCUMENT

### Improvement Quality Scores

| Dimension     | Before (1–5) | After (1–5) | Note |
| ------------- | ------------ | ----------- | ---- |
| Simplicity    |              |             |      |
| Security      |              |             |      |
| Performance   |              |             |      |
| UX (if UI)    |              |             |      |
| Test Coverage |              |             |      |

### Documentation Updates

- [ ] Append `EVAL-IMP-<NNN>` block to `evaluation_history.md` (same format as task EVAL blocks)
- [ ] Update affected Task ID rows in `backlog.md` — change `Evaluation Record` column to link to new EVAL block
- [ ] If improvement adds new behavior → propose new TC in EVAL block (do NOT add test yet)
- [ ] If improvement changes an existing TC expectation → update the test and report: _"Updated TC-X: ..."_
- [ ] Update `improvement_backlog` section in `backlog.md` — change IMP status to `Done`

---

## STEP 7 — COMMIT & TAG

> Commit format aligns with [git_workflow.md](git_workflow.md) Rule G2. Commit only after all applicable tests pass.

**Commit:**

```
improve: [IMP-NNN] — <short description> — N/N tests pass [SCOPE]

- Scope: [UIUX_MODE / LOGIC_MODE / INFRA_MODE / FULL_STACK_MODE]
- Changes: <list of files>
- Stack check: <libraries added or "None">
- Security: <sections checked or "UIUX-only: XSS checked">
- Regression: <N tests · M assertions · 0 failures> (or "N/A — UIUX/INFRA scope")
- Ref: IMP-<NNN> in docs/backlog.md improvement backlog
```

**Tag** (after commit, per [git_workflow.md](git_workflow.md) Rule G3):

```bash
git tag -a v1.0-IMP-NNN-stable -m "IMP-NNN complete — N/N tests pass, evaluation done"
```

> Record the tag in the `Git Tag` column of the Improvement Backlog in `backlog.md`.

**Merge to master** requires explicit user approval — same as Rule G5 in [git_workflow.md](git_workflow.md).

---

## Quick Decision Tree

```
User sends improvement request
         │
         ▼
Scope keyword present? ──No──▶ Ask user to specify [UIUX/LOGIC/INFRA/FULL_STACK]_MODE
         │
         Yes
         ▼
STEP 0: Tech Stack Compatibility Check
  Bootstrap? → no Tailwind
  Blade?     → no Vue/React
  New lib?   → ask user first
         │
         ▼
STEP 1: Backlog & Evaluation Scan
  Find affected Task IDs
  Check existing proposals (STALE?)
         │
         ▼
STEP 2: Impact Analysis
  Risk table filled?
  Rule 10 triggered? → run Rule 10 first
         │
         ▼
STEP 3: Improvement Plan
  Minimal change only
  Isolation check passed?
         │
         ▼
STEP 4: Apply (scope-specific checklist)
         │
         ▼
STEP 5: Verify
  Regression? ──Yes──▶ STOP — fix regression
         │
         No
         ▼
STEP 6: Evaluate & Document
STEP 7: Commit
```

---

## Improvement Type Reference

| Type           | Scope Keyword       | Tests?                             | Security Check          | Git Branch        | Commit Now?           | EVAL Entry? |
| -------------- | ------------------- | ---------------------------------- | ----------------------- | ----------------- | --------------------- | ----------- |
| UI/UX polish   | `[UIUX_MODE]`       | ⛔ No PHPUnit                      | §2 XSS only             | `improve/IMP-NNN` | Yes, after verify     | Yes         |
| Logic refactor | `[LOGIC_MODE]`      | ✅ Min 3 (testing_standards.md §2) | Full security_checks.md | `improve/IMP-NNN` | Yes, after tests pass | Yes         |
| Config/Infra   | `[INFRA_MODE]`      | ⛔ No PHPUnit                      | §5 Sensitive Data only  | `improve/IMP-NNN` | Yes, immediately      | Yes         |
| Cross-cutting  | `[FULL_STACK_MODE]` | ✅ Logic layer: min 3              | All applicable sections | `improve/IMP-NNN` | Yes, after all verify | Yes         |

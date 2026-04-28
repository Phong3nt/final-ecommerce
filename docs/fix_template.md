# Fix Execution Template

> Copy this block every time you apply a fix outside the normal task workflow.  
> A fix is any code/data/config change triggered by an observed runtime error, NOT by a backlog task.

---

## FIX HEADER

| Field          | Value                                                                                     |
| -------------- | ----------------------------------------------------------------------------------------- |
| Fix ID         | `FIX-<NNN>` (next sequential number in fixlog.md)                                         |
| Fix Date       | `YYYY-MM-DD`                                                                              |
| Error Message  | _(paste exact error text)_                                                                |
| Error Location | _(file:line or URL where it appeared)_                                                    |
| Trigger        | _(what action triggered the error: "clicked Register", "visited /admin/dashboard", etc.)_ |
| Fix Type       | `Code` / `Environment` / `Data` / `Config`                                                |
| Batch?         | `Yes — batch with FIX-NNN` / `No — commit immediately`                                    |

---

## STEP 1 — IDENTIFY PARENT TASK(S)

Before writing a single line of code, map the error to the backlog.

```
1. Read the error message and stack trace
2. Identify the controller / service / model where it originated
3. Open backlog.md → find which Task ID(s) produced that code
4. Open evaluation_history.md → read the EVAL block for those tasks
5. Check the Test Results table: which TC should have caught this?
```

| Parent Task ID  | Why related?                                                          |
| --------------- | --------------------------------------------------------------------- |
| `[e.g. AU-001]` | _(e.g. "assignRole() called in RegisterController built for AU-001")_ |
| `[e.g. AU-006]` | _(e.g. "RoleSeeder is part of AU-006 setup")_                         |

---

## STEP 2 — ROOT CAUSE ANALYSIS

Answer these questions before touching any file:

| Question                                                          | Answer   |
| ----------------------------------------------------------------- | -------- |
| What is the exact line that throws?                               |          |
| What value is null/missing/wrong at that line?                    |          |
| Is this a **Code bug** (logic error in a Done task)?              | Yes / No |
| Is this an **Environment issue** (missing .env key, unseeded DB)? | Yes / No |
| Is this a **Data issue** (wrong value in production DB)?          | Yes / No |
| Is this a **Config issue** (wrong setting in config/ or routes/)? | Yes / No |

> **Rule:** Fix only the identified layer. Do NOT change code to work around an environment issue.

---

## STEP 3 — TEST CASE GAP ANALYSIS

For each parent task, check its EVAL block:

| TC ID     | Description | Did it catch this bug? | Why not?                                                                 |
| --------- | ----------- | ---------------------- | ------------------------------------------------------------------------ |
| TC-XXX-01 |             | ✅ Yes / ❌ No         | _(e.g. "Event::fake() suppressed the listener that would have crashed")_ |
| TC-XXX-02 |             | ✅ Yes / ❌ No         |                                                                          |

**Gap conclusion:**

- `Environment-only` — tests cannot catch this class of issue (acceptable)
- `Test gap` — a test SHOULD have caught this → propose new TC below
- `Mock gap` — mock hid a real integration issue → propose upgrade from mock to real test

**Proposed new test cases (if any):**

| Proposed TC ID  | Type | Description | Covers Fix |
| --------------- | ---- | ----------- | ---------- |
| `TC-XXX-NEW-01` |      |             |            |

---

## STEP 4 — APPLY FIX

### Fix Plan

State the minimal change needed (Rule 7 — minimal impact):

| Layer       | Change Description | Files Affected |
| ----------- | ------------------ | -------------- |
| Code        |                    |                |
| Config      |                    |                |
| Data        |                    |                |
| Environment |                    |                |

### Isolation Check

- [ ] Does this fix change a shared service/base class used by multiple tasks? → **Ask user first**
- [ ] Does this fix require a DB schema change (new column / migration)? → **Ask user first**
- [ ] Does this fix alter route structure or middleware stack? → **Ask user first**
- [ ] Does this fix affect only the originating controller/model/service? → Proceed freely

### Fix Applied

_(Document exactly what was changed, include tinker commands or config changes)_

```bash
# Commands run (if any)
```

```php
// Code changes summary
```

---

## STEP 5 — VERIFY

After applying the fix:

```powershell
# Run tests for all affected parent tasks
php artisan test --filter <ParentTask1>Test
php artisan test --filter <ParentTask2>Test

# Run full suite to catch regressions
php artisan test
```

| Test                 | Before Fix | After Fix      |
| -------------------- | ---------- | -------------- |
| Parent task tests    | `X/Y PASS` | `X/Y PASS`     |
| Full suite           | `N PASS`   | `N PASS`       |
| Regression detected? | —          | Yes ❌ / No ✅ |

> If regression detected → **STOP**. Do not commit. Investigate regression first.

---

## STEP 6 — COMMIT DECISION

| Condition                                             | Action                                                 |
| ----------------------------------------------------- | ------------------------------------------------------ |
| Fix is self-contained, all tests pass                 | Commit now (use format below)                          |
| Fix is one of 2–4 small related fixes in same session | Mark "Batch" in header; commit together at session end |
| Fix caused a regression                               | DO NOT commit — fix regression first                   |
| Same bug failed to fix 3 times                        | STOP — report to user (Rule 6)                         |

**Commit message:**

```
fix([TASK-IDs]): <short description>

- Root cause: <what caused it>
- Files changed: <list>
- Test gap: <TC that should have caught this, or "Environment-only">
- Ref: FIX-<NNN> in docs/fixlog.md
```

---

## STEP 7 — RECORD IN FIXLOG

Append a new `### FIX-<NNN>` block to [fixlog.md](fixlog.md) with:

- [ ] Error message (exact)
- [ ] Root cause (one sentence)
- [ ] Parent tasks table
- [ ] Fix applied (commands + files)
- [ ] Test gap analysis table
- [ ] Proposed new test cases (if any)
- [ ] Commit message used

---

## Quick Decision Tree

```
Error reported
     │
     ▼
Is it in backlog.md code? ──No──▶ Environment/Data/Config fix
     │                              → Fix .env / DB / config
     Yes                            → Document in fixlog.md
     │                              → No test change needed
     ▼
Which Task ID produced this code?
     │
     ▼
Check EVAL block → did any TC catch it?
     │
     ├── YES → Why did it pass? Was it a mock gap?
     │           → If mock gap: propose integration upgrade
     │
     └── NO  → New bug in Done code
                → Fix the CODE (not the test)
                → Propose new TC
                → Append to fixlog.md
                → Run full test suite
                → Commit with fix() prefix
```

---

## Fix Type Reference

| Fix Type        | Description                                            | Commit Now?            | Test Change?          | fixlog Entry? |
| --------------- | ------------------------------------------------------ | ---------------------- | --------------------- | ------------- |
| **Code**        | Logic error in a Done task's controller/service/model  | Yes, after tests pass  | Propose new TC        | Yes           |
| **Environment** | Missing/wrong `.env` variable                          | Yes, immediately       | No (environment-only) | Yes           |
| **Data**        | Wrong value in production DB (tinker fix)              | Yes                    | No (data-only)        | Yes           |
| **Config**      | Wrong `config/*.php` value or route structure          | Yes, after tests pass  | Possibly              | Yes           |
| **Deployment**  | Missing `php artisan migrate / db:seed / config:clear` | Run command + document | No                    | Yes           |

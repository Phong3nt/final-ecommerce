# Task Execution Template

> Copy this block at the start of every task. Fill in all fields before writing any code.

---

## TASK HEADER

| Field        | Value                        |
| ------------ | ---------------------------- |
| Task ID      | `[e.g. AU-004]`              |
| Task Name    | `[e.g. Logout]`              |
| Epic         | `[e.g. AU — Authentication]` |
| Sprint       | `[e.g. 1]`                   |
| Story Points | `[e.g. 2]`                   |
| Branch       | `feature/[TASK-ID]`          |
| Tag          | `v1.0-[TASK-ID]-stable`      |

### Acceptance Criteria

- [ ] AC1:
- [ ] AC2:
- [ ] AC3:

---

## PRE-TASK CHECKLIST (Rule G6)

Run these commands before anything else:

```powershell
$env:Path = "C:\xampp\php;" + $env:Path
cd C:\Users\DELL\Desktop\final
git status        # must be clean
git branch        # must be on master
git tag --list    # confirm latest tag exists
```

| Check                      | Result       | Action if Fail                    |
| -------------------------- | ------------ | --------------------------------- |
| Working tree clean?        | ☐ Yes / ☐ No | Commit or stash before continuing |
| On `master` branch?        | ☐ Yes / ☐ No | `git checkout master`             |
| Latest tag correct?        | ☐ Yes / ☐ No | Re-tag or investigate             |
| Targeted regression green? | ☐ Yes / ☐ No | **STOP — fix before starting**    |

---

## ARCHITECTURAL CONFLICT CHECK (Rule 10)

Before writing code, answer:

1. Does this task add a new authentication path? ☐ Yes / ☐ No
2. Does this task remove or weaken a middleware? ☐ Yes / ☐ No
3. Does this task change session/token behavior? ☐ Yes / ☐ No
4. Does this task affect data that a previous task secured? ☐ Yes / ☐ No

If **any Yes** → Read all previous EVAL blocks in `evaluation_history.md` → run Rule 10 and document impact before STEP 1.

---

## STEP 1 — CODE

Files to create/modify:

| Action | File Path                            |
| ------ | ------------------------------------ |
| CREATE | `ecommerce/app/Http/Controllers/...` |
| UPDATE | `ecommerce/routes/web.php`           |
| UPDATE | `ecommerce/...`                      |

Security checklist (→ see [security_checks.md](security_checks.md)):

- [ ] CSRF: all mutating routes in `web` middleware + `@csrf` in forms
- [ ] XSS: all user output uses `{{ }}`
- [ ] Auth: routes behind `auth` middleware
- [ ] Session: regenerated after login / invalidated after logout

---

## STEP 2 — TESTS

Test file: `ecommerce/tests/Feature/[Epic]/[TaskID]Test.php`

Planned test cases:

| TC    | Type     | Description | Expected |
| ----- | -------- | ----------- | -------- |
| TC-01 | Happy    |             |          |
| TC-02 | Happy    |             |          |
| TC-03 | Edge     |             |          |
| TC-04 | Edge     |             |          |
| TC-05 | Security |             |          |
| TC-06 | Negative |             |          |

Run tests:

```powershell
# 1. Isolated — current task only
php artisan test tests/Feature/[TaskID]Test.php

# 2. Targeted regression
#    = current task  +  Done tasks in same Sprint  +  Done tasks in same Epic
#    (replace with actual test file paths for each done task)
php artisan test tests/Feature/[TaskID]Test.php \
                tests/Feature/[SameSprint_DoneTask1]Test.php \
                tests/Feature/[SameSprint_DoneTask2]Test.php \
                ...
```

> **Regression scope rule:**  
> Run the current task's tests **plus** the test files of every task that is already `Done` and shares the same **Sprint** or the same **Epic** as the current task.  
> This replaces the old "full suite" regression. A full suite run is still optional for extra confidence but is not required.

Baseline before task: `__ tests · __ assertions · 0 failures`  
Expected after task: `__ tests · __ assertions · 0 failures`  
Targeted regression files: `[list test file paths here]`

---

## STEP 3 — EVALUATE

```
## EVAL-[TASK-ID] — [Task Name]
**Date:** YYYY-MM-DD
**Tests:** X/X pass | X assertions
**Regression:** XX tests · XXX assertions · 0 failures
**Security:** [CSRF ✅ | XSS ✅ | SQLi ✅ | Auth ✅]

### Summary
[2–3 sentence summary of what was implemented]

### Bugs Found & Fixed
| Bug | Root Cause | Fix |
| --- | ---------- | --- |
| | | |

### What Passed First Try
- TC-XX: ...

### Architectural Impact
[None / or describe conflict + resolution if Rule 10 triggered]
```

---

## STEP 4 — PROPOSALS

```
### Proposals for [TASK-ID]
| ID | Title | Priority | Type |
| -- | ----- | -------- | ---- |
| [TASK-ID].1 | ... | Low/Med/High | Security/UX/Feature |
```

Backlog rule: Add to `backlog.md` only if:

- [ ] Priority ≥ Medium
- [ ] Not invalidated by a future planned task
- [ ] Does not re-implement behavior already handled by an existing Done task

---

## GIT CHECKLIST

```powershell
git checkout -b feature/[TASK-ID]
# ... make changes ...
git add .
git commit -m "feat([TASK-ID]): [description]"
git checkout master
git merge feature/[TASK-ID] --no-ff -m "merge: [TASK-ID] [description]"
git tag -a v1.0-[TASK-ID]-stable -m "[Task Name] complete — X/X tests pass"
git push origin master --tags
git push origin feature/[TASK-ID]
```

| Step                             | Done |
| -------------------------------- | ---- |
| Branch created from master       | ☐    |
| Code committed on feature branch | ☐    |
| Merged to master (--no-ff)       | ☐    |
| Tagged                           | ☐    |
| Pushed master + tags             | ☐    |
| Pushed feature branch            | ☐    |
| backlog.md updated to Done       | ☐    |
| evaluation_history.md updated    | ☐    |

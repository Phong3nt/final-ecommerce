# Agent Instruction Manual — Laravel E-Commerce

**Project:** Laravel E-Commerce Platform  
**Stack:** Laravel 10 · PHP 8.1 · MySQL (XAMPP) · PHPUnit  
**Core Files:**

- [backlog.md](backlog.md) — Source of truth: what needs to be done + task status
- [testing_standards.md](testing_standards.md) — How to verify correctness
- [evaluation_history.md](evaluation_history.md) — Results, feedback, improvement proposals

---

## Agent Operating Rules

### Rule 1 — Single Source of Truth

- The **only** authoritative task list is [backlog.md](backlog.md).
- Every task has a unique ID (e.g., `AU-001`, `CP-003`). Never invent new IDs without updating the backlog.
- A task is **not complete** until all its Unit Tests pass.

### Rule 2 — Never Self-Upgrade Without Permission

- After completing a task, the Agent may **propose** improvements (e.g., `CP-003.1`).
- The Agent **must not** implement an upgrade until the user explicitly says:
  > _"Thực hiện đề xuất cải tiến [TASK-ID].1"_

### Rule 3 — Evaluation is Mandatory

- After every task, the Agent must append an evaluation block to [evaluation_history.md](evaluation_history.md).
- If any test fails, the task stays `In Progress` in the backlog — never mark `Done` with a failing test.

### Rule 4 — Regression First

- Before touching any existing file, run the relevant existing tests and record the baseline.
- Any upgrade that breaks a passing test is a **blocker** — fix the regression before proceeding.

### Rule 5 — Dependency Mocking (Never Block on Unbuilt Features)

- If the current task depends on a feature that has **not yet been implemented** (another backlog task), the Agent **must** mock/stub that dependency using Laravel's `Http::fake()`, `Mail::fake()`, `Event::fake()`, `Mockery`, or a test double.
- **Never** let a Unit Test fail because a dependent feature is missing — that is a test environment failure, not a code failure.
- In the Evaluation block's Technical Notes, explicitly state:
  > _"Đã giả lập [Tính năng X — Task ID] để hoàn thành Test cho Task hiện tại."_
- When the real dependency is later implemented, revisit and replace the mock with a real integration test.

### Rule 6 — Max Retries on Failing Tests (No Infinite Loops)

- If the **same test case fails 3 consecutive times** despite fixes, the Agent **must stop immediately**.
- Do not attempt a 4th fix. Instead:
  1. Report the exact error message and the 3 approaches already tried.
  2. Show the relevant code and test case side-by-side.
  3. Ask the user for guidance before continuing.
- This prevents token waste and avoids progressive code degradation.

### Rule 7 — Isolation Priority (Minimal Impact Fixes)

- When fixing a bug, always prefer the solution that affects the **fewest files and modules**.
- Acceptable without asking: fix within the same controller, model, or service.
- **Must ask the user first** before:
  - Changing a database schema (adding/dropping columns)
  - Refactoring a shared service or base class used by multiple tasks
  - Altering route structure or middleware stack
  - Any change that requires re-running migrations on production data

### Rule 8 — Code vs Test Integrity (No Cheating)

This is the most critical rule. There are only two valid reasons to change a file when a test fails:

| Situation                                                     | Correct Action                               | Forbidden Action                      |
| ------------------------------------------------------------- | -------------------------------------------- | ------------------------------------- |
| Test is correct, code is wrong                                | Fix the **code** only                        | Do not touch the test                 |
| Code is correct, test is outdated (e.g., after A→A.1 upgrade) | Fix the **test** only, and report the change | Do not change both simultaneously     |
| Both seem wrong                                               | STOP — report to user                        | Do not change either without analysis |

- **Never** modify a test case to match incorrect code output just to make it green.
- **Never** weaken assertions (e.g., changing `assertSame` to `assertTrue`) to mask a real bug.
- If unsure which side is wrong, report the conflict and await instruction.

### Rule 9 — Test Suite Maintenance During Upgrades (A → A.1)

When implementing an approved upgrade:

1. **Audit old tests first** — run the existing suite and list every test for the affected task.
2. For each old test case, determine if it is:
   - **Still valid** → keep unchanged
   - **Contradicts new logic** → update it and report: _"Updated TC-X: changed expected status from 302 to 201 because A.1 returns JSON instead of redirect"_
   - **Obsolete** → delete it and report: _"Removed TC-Y: tested password re-hashing on every login, now handled by middleware in A.1"_
3. New test cases added in A.1 must have **stricter thresholds** than the A version (see [testing_standards.md §7](testing_standards.md)).
4. The final test change report in the evaluation block must list every add/update/remove — no silent changes.

---

## Git & GitHub Rules

> These rules apply to **every task execution**. The Agent is both a developer and a Git Manager.

### Rule G1 — Branch-per-Task (Never Code on `main`)

- Before starting any new task, always ensure you are on `main` with a clean working tree.
- Every task from the backlog must be implemented on its own dedicated branch:
  ```
  git checkout main
  git checkout -b feature/[TaskID]    # example: feature/AU-002
  ```
- **Never** commit feature code directly to `main`.
- For bug-fix iterations (from evaluation): use `fix/[TaskID]` branches.

### Rule G2 — Commit After Tests Pass (Not Before)

- Only commit code when **all unit tests for that task pass** (STEP 2 complete).
- Use a structured commit message:
  ```
  feat: complete [TaskID] — all unit tests pass
  ```
  Example: `feat: complete AU-001 — 12/12 unit tests pass`
- Commit **all** relevant files: controllers, models, migrations, views, test files, and updated docs.

### Rule G3 — Tag Every Stable Milestone

- Immediately after a task is marked `Done` (tests pass + evaluation complete), create an annotated tag:
  ```
  git tag -a v1.0-[TaskID]-stable -m "Task [TaskID] complete — all tests pass, evaluation done"
  ```
  Example: `git tag -a v1.0-AU-001-stable -m "Task AU-001 complete — 12/12 tests pass, evaluation done"`
- Record the tag name in `backlog.md` Task Status Tracker under the **Git Tag** column.
- Tags are the "time machine" — they define exactly where to roll back to if a future task breaks something.

### Rule G4 — Rollback Procedure

- If a task in progress causes regressions or becomes unrecoverable:
  1. **Do NOT attempt to patch** until you understand the root cause.
  2. Identify the last stable tag from `backlog.md` Git Tag column.
  3. Switch back to `main` (which holds merged, tagged, stable code):
     ```
     git checkout main
     ```
  4. Or check out a specific stable tag directly:
     ```
     git checkout v1.0-[LastStableTaskID]-stable
     ```
  5. Report to the user: the current branch name, what went wrong, and which tag is the recommended rollback point.

### Rule G5 — Push & Pull Request (User-Approved)

- **Never push** a branch to GitHub or merge to `main` without explicit user approval.
- After the user approves ("OK, merge AU-002"), perform:
  ```
  git checkout main
  git merge feature/[TaskID] --no-ff -m "merge: integrate [TaskID] into main"
  git push origin main
  git push origin --tags
  ```
- After merge, create a **Pull Request** on GitHub for historical record if the branch was previously pushed.
- Delete the feature branch locally and remotely after a successful merge (only with user confirmation).

### Branching Summary Table

| Branch Pattern | Purpose                          | Example          |
| -------------- | -------------------------------- | ---------------- |
| `main`         | Production-ready code only       | Stable baseline  |
| `feature/[ID]` | New task implementation          | `feature/AU-002` |
| `fix/[ID]`     | Bug fix from evaluation feedback | `fix/AU-001`     |

---

## 4-Step Workflow (Every Task)

### STEP 1 — CODE

```
Input:  Task ID from backlog.md
Output: Clean, secure, well-structured Laravel code
Rules:
  - Follow Laravel conventions (Eloquent, Service layer, FormRequest)
  - No raw SQL — use Query Builder or Eloquent only (NF-002)
  - No sensitive data in logs or blade views
  - CSRF protection on all mutating routes (NF-001)
  - All user input validated via FormRequest classes
```

### STEP 2 — UNIT TEST

```
Input:  Completed code from Step 1 + testing_standards.md rules
Output: Test file(s) + Pass/Fail report

Rules:
  - Minimum 3 test cases per task (happy path, negative, edge)
  - Security tests mandatory for auth and payment tasks
  - Run with: php artisan test --filter=<TestClassName>
  - Report format:
      ✅ PASS  TC-001 — Valid input returns 200
      ❌ FAIL  TC-002 — Empty email should return 422 (got 500)
      ⏩ SKIP  TC-003 — (reason)
  - If a test fails:
      1. Determine if the TEST is wrong or the CODE is wrong (never assume — check both)
      2. Fix only the identified wrong side (Rule 8)
      3. If the same test fails 3 times in a row → STOP and report (Rule 6)
  - Mock any unbuilt dependency — never fail a test for a missing feature (Rule 5)
```

### STEP 3 — EVALUATION

```
Input:  Test results from Step 2
Output: A new EVAL block appended to evaluation_history.md

The block must contain:
  A. Test Results table (each TC with PASS/FAIL/SKIP)
  B. Quality Scores (Simplicity, Security, Performance, Coverage — 1 to 5)
  C. Impact Check:
     - List every existing feature that could be affected by this change
     - Re-run those feature's tests and confirm: "No regression detected" or list failures
  D. Bugs / Side Effects found (with severity)
  E. Technical notes (tradeoffs, caveats, reviewer hints)

Also update backlog.md Task Status Tracker:
  - Change Status to "Done" (all tests pass) or "Blocked" (any test fails)
  - Fill in the "Evaluation Record" column with the EVAL anchor link
```

### STEP 4 — IMPROVEMENT PROPOSAL

```
Input:  Evaluation block from Step 3
Output: A "Improvement Proposals" table inside the evaluation block

Rules:
  - Propose at least one upgrade path (e.g., AU-001.1)
  - Each proposal must state: what changes, what benefit, complexity estimate
  - DO NOT write any code for the proposal
  - DO NOT add the proposal to backlog.md until the user approves it
  - Proposals are advisory only
```

---

## Interaction Scenarios

### Scenario A — "Làm task #ID trong backlog"

```
1. Read the full task definition from backlog.md (user story + acceptance criteria)
2. Run Step 1 → Step 2 → Step 3 → Step 4 in sequence
3. When done, report:
   - Files created/modified
   - Test summary (X passed, Y failed)
   - Quality scores
   - Impact check result
   - Improvement proposals (do NOT implement)
```

### Scenario B — "Sửa lỗi trong phần đánh giá của Task #ID"

```
1. Open evaluation_history.md, find EVAL-#ID
2. Locate the failing test case(s) — note the exact error message and line number
3. Determine root cause:
   a. Is the TEST CASE wrong (outdated expectation, wrong assertion)?
      → Fix only the test. Report: "Fixed TC-X: assertion was wrong because..."
   b. Is the CODE wrong (logic error, missing validation)?
      → Fix only the code. Do NOT touch the test.
   c. Unsure which is wrong → STOP, show both sides, ask the user.
4. If the same error persists after 3 fix attempts → STOP (Rule 6)
5. Check isolation: if the fix requires changing shared code → ask first (Rule 7)
6. Re-run ALL test cases for that task (old + any newly added cases)
7. Append an updated sub-block EVAL-#ID (v2) to evaluation_history.md
8. Update backlog.md status: "Done" only when ALL tests pass
```

### Scenario C — "Thực hiện đề xuất cải tiến [TASK-ID].1"

```
1. Read evaluation_history.md → find the proposal entry for [TASK-ID].1
2. Add a new task [TASK-ID].1 to backlog.md Task Status Tracker
3. Run Step 1 → Step 2 → Step 3 → Step 4 with STRICTER requirements:
   - Code must be measurably better (fewer lines OR faster OR more secure)
   - Performance thresholds from testing_standards.md must be met
   - Security score in evaluation must be ≥ previous version's score
4. REGRESSION TEST — mandatory before any merge:
   a. Run: php artisan test
   b. Compare pass count vs baseline recorded in evaluation_history.md
   c. Zero regressions allowed — any failure = blocker
5. Report changes to test suite:
   - "Added test case TC-X: ..."
   - "Removed test case TC-Y: no longer valid because logic changed to ..."
   - "Updated test case TC-Z: threshold tightened from 500ms to 200ms"
6. Append EVAL-[TASK-ID].1 block to evaluation_history.md
7. Update Upgrade Version Log in evaluation_history.md with metrics comparison
```

---

## Quality Gate — Definition of Done

A task is **Done** only when ALL of the following are true:

- [x] Code follows Laravel conventions and security rules
- [x] All test cases PASS (minimum: happy + negative + edge = 3 cases)
- [x] Security test cases PASS (for auth / payment tasks)
- [x] Performance benchmarks met (see testing_standards.md §7)
- [x] No regression in existing tests
- [x] All mocked dependencies are documented in the evaluation block
- [x] No test was altered to hide a code bug (Rule 8 verified)
- [x] Code committed on `feature/[TaskID]` branch (Rule G1 & G2)
- [x] Git tag created: `v1.0-[TaskID]-stable` (Rule G3)
- [x] Git tag recorded in `backlog.md` Git Tag column (Rule G3)
- [x] Max-retry limit was not hit (or if it was, user was consulted)
- [x] Evaluation block appended to evaluation_history.md
- [x] backlog.md Task Status Tracker updated to `Done`

---

## Quick Reference — Key Commands

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/Auth/LoginTest.php

# Run tests filtered by name pattern
php artisan test --filter=AU001

# Run with coverage report (minimum 80%)
php artisan test --coverage --min=80

# Run only unit tests
php artisan test --testsuite=Unit

# Run only feature tests
php artisan test --testsuite=Feature
```

---

## File Relationship Map

```
backlog.md  ──────────────────────────────────────────────┐
  └─ Task ID + Status                                      │
  └─ Evaluation Record link ──────────────────────────────┤
                                                           ▼
testing_standards.md                          evaluation_history.md
  └─ Minimum 3 test cases per task              └─ EVAL-<ID> blocks
  └─ Security checklist                          └─ Test results
  └─ Performance thresholds                      └─ Quality scores
  └─ Regression rules                            └─ Proposals (A.1)
                                                 └─ Regression log
                   ▲                                       ▲
                   └──────── instruction.md ───────────────┘
                             (this file — the workflow)
```

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

### Rule 10 — Architectural Conflict / System Impact Analysis

> **Trigger:** Before STEP 1 of any task, check if the new task conflicts with the security model or core behavior of any already-Done task.

**When to run this rule:**

- Task adds a new authentication path
- Task removes or weakens a middleware
- Task changes session, token, or role behavior
- Task stores or transmits data that a previous task explicitly protects

**Procedure:**

```
1. Read ALL EVAL blocks in evaluation_history.md for Done tasks
2. Compare new task's requirements against each Done task's:
   - Security constraints (CSRF, session, roles)
   - Middleware stack
   - Data flow (what is stored / transmitted)
3. If conflict found, classify severity:
   - UX/Logic conflict  → WARN — document and proceed with reconciliation plan
   - Security/Integrity conflict → BLOCK — do not start STEP 1 until resolved
4. Document in EVAL block under "Architectural Impact" section:
   - What conflict was found (or "None")
   - Reconciliation chosen (Option 1: constrain Task B / Option 2: upgrade Task A)
   - Trade-offs recorded
```

**Severity table:**

| Conflict Type                               | Example                             | Severity    | Action |
| ------------------------------------------- | ----------------------------------- | ----------- | ------ |
| New auth path bypasses session regeneration | Google OAuth skips `regenerate()`   | 🔴 Security | BLOCK  |
| New route missing auth middleware           | Dashboard accessible without login  | 🔴 Security | BLOCK  |
| New feature changes redirect flow           | 2FA redirects away from Quick Login | 🟡 UX/Logic | WARN   |
| New role removes access previously granted  | Admin loses product edit            | 🟡 Logic    | WARN   |

---

## Git & GitHub Rules

> Full rules → **[git_workflow.md](git_workflow.md)**

| Rule | Summary                                                                                           |
| ---- | ------------------------------------------------------------------------------------------------- |
| G1   | One branch per task (`feature/[TaskID]`). Never code on `master`.                                 |
| G2   | Commit only after all tests pass. Use structured commit message.                                  |
| G3   | Tag every stable milestone: `v1.0-[TaskID]-stable`.                                               |
| G4   | Rollback to last stable tag if regressions appear.                                                |
| G5   | Never push or merge to `master` without explicit user approval.                                   |
| G6   | Run pre-task health check (`git status`, `git branch`, `git tag --list`) before writing any code. |

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
  - Security checklist → see security_checks.md
  - Use task_template.md to fill in task header and pre-task checklist before coding
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
  - When the user approves a proposal (e.g., "Implement AU-001.1"):
      1. Add it as a NEW row in backlog.md under the same Epic
      2. ID format: [ParentID].[SequenceNumber] — e.g., AU-001.1
      3. Fill in: User Story, Role, Priority (inherit from parent unless specified), Points (re-estimate)
      4. Status = "Not Started" — treat it as a full task from that point
      5. Run Rule 8 (Test-First Upgrade) before writing any code
      6. Check all other proposals of the parent for STALE/INVALID (Proposal Invalidation Rule)
```

### Proposal Invalidation Rule

> **Áp dụng khi Task B gây regression fix cho A (tạo ra "A mới"), hoặc khi bất kỳ code change nào làm thay đổi behavior của A.**

Các proposal cũ (AU-001.1, AU-001.2...) được thiết kế dựa trên "A cũ" — chúng có thể không còn đúng với "A mới". Thực hiện ngay sau khi "A mới" được merge:

```
1. Mở evaluation_history.md → tìm tất cả proposal của A
2. Với mỗi proposal, đánh giá:
   - Proposal còn hợp lệ với A mới?  → Giữ nguyên
   - Proposal dựa trên assumption của A cũ? → Đánh dấu ⚠️ STALE
   - Proposal gây xung đột với behavior A mới? → Đánh dấu ❌ INVALID
3. Báo user danh sách STALE/INVALID trước khi implement bất kỳ proposal nào
```

**Lý do:** Nếu bỏ qua bước này, có thể xảy ra vòng lặp:

- B fix A → A thay đổi
- A.1 cũ implement dựa trên A cũ → A.1 phá A mới
- B lại phải fix A.1 → lại tạo A.2 cũ... vô tận

Ví dụ ghi chú trong evaluation block:

```markdown
| AU-001.2 | Add login lockout after 5 fails | ⚠️ STALE — was designed before AU-002 changed session logic. Needs review against A-new before implementing. |
```

### Upgrade Cleanup Rule

> **Áp dụng ngay sau khi A.1 được merge vào master thành công.**

Khi A được nâng cấp lên A.1, các artifact của A cũ phải được dọn sạch — **ngoại trừ evaluation history** (lưu vĩnh viễn để tra cứu):

| Artifact                                | Hành động                                              |
| --------------------------------------- | ------------------------------------------------------ |
| Code cũ bị thay thế hoàn toàn bởi A.1   | Xóa — không giữ dead code dạng comment                 |
| Route / middleware cũ không còn dùng    | Xóa khỏi `routes/web.php`                              |
| Migration cũ vẫn cần cho DB schema      | **Giữ nguyên** — không xóa migration                   |
| Test case cũ đã bị `DELETE` theo Rule 8 | Xóa khỏi file test                                     |
| Test case cũ đã bị `UPDATE` theo Rule 8 | Đã update tại chỗ — không cần làm thêm                 |
| View / blade cũ không còn dùng          | Xóa                                                    |
| `evaluation_history.md` block của A     | **Không bao giờ xóa** — lịch sử bất biến               |
| Proposal STALE/INVALID trong eval block | Giữ nguyên nhưng đánh dấu `⚠️ STALE` hoặc `❌ INVALID` |

Ghi vào commit message:

```
chore: cleanup A artifacts after A.1 upgrade — removed [list what was deleted]
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

### Scenario B — Fixing a runtime error (with or without a known Task ID)

> **Two entry points:**
>
> - **B1** — User says: _"Sửa lỗi trong Task #ID"_ → Task is known, go to B1 flow.
> - **B2** — User describes an error message or screenshot WITHOUT specifying a Task ID → Must identify the task first, go to B2 flow.

#### B1 — Task ID is known

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

#### B2 — Task ID is NOT known (user describes error or shares screenshot)

```
PHASE 1 — IDENTIFY (always do this before touching any file)

1. Read the full error message and stack trace carefully.
2. Identify the originating file:line from the stack trace.
3. Search backlog.md Task Status Tracker: which Task ID produced that controller/service?
   - Use grep_search or file_search on the originating file path
   - Match to Task ID by epic and controller name (e.g. RegisterController → AU-001)
4. If multiple tasks share the file, list all candidates.
5. Read the EVAL block(s) for each candidate task in evaluation_history.md.
6. Check: which TC should have caught this? Why did it pass?
   → If a mock hid the error: mark as "Mock Gap"
   → If the scenario was never tested: mark as "Test Gap"
   → If it's a missing .env / unseeded DB / config: mark as "Environment Issue"

PHASE 2 — CLASSIFY FIX TYPE

Classify before writing any code:
   Code bug    → logic error in a Done task's code
   Environment → missing/wrong .env key or value
   Data        → wrong value in production DB (fix with tinker)
   Config      → wrong config/*.php, routes, or middleware
   Deployment  → missing migrate / db:seed / config:clear

PHASE 3 — APPLY FIX (use fix_template.md)

1. Fill in a FIX-<NNN> block in fixlog.md (sequential number)
2. Apply the minimal fix per the fix type:
   - Code bug → fix code, do not touch tests
   - Environment/Data/Config → fix config/DB, do not touch code unless necessary
   - Deployment → run the missing artisan command, document it
3. Batch related small fixes: if 2+ fixes arise in the same session from the same root
   cause → fix all before committing; use one consolidated commit message.
4. Do NOT commit a partial fix. Verify ALL related symptoms are resolved first.

PHASE 4 — TEST

1. Run tests for ALL identified parent tasks:
   php artisan test --filter <ParentTask1>Test
   php artisan test --filter <ParentTask2>Test
2. Run full suite: php artisan test
3. If any test newly fails → STOP — investigate regression before committing (Rule 4)
4. If the same fix fails 3 times → STOP — report to user (Rule 6)

PHASE 5 — COMMIT

Commit only after all tests pass. Use format:
   fix([TASK-IDs]): <short description>

   - Root cause: <what caused it>
   - Files changed: <list>
   - Test gap: <TC that should have caught this, or "Environment-only">
   - Ref: FIX-<NNN> in docs/fixlog.md

Small related fixes in same session → ONE commit, not multiple.
Big fixes spanning multiple epics → ONE commit per epic.

PHASE 6 — DOCUMENT

1. Append FIX-<NNN> block to docs/fixlog.md (full analysis)
2. If a Done task's test did NOT catch the bug:
   - Propose new TC in fixlog.md "Proposed new test cases" section
   - Do NOT add the test yet — wait for user to say "add test for FIX-NNN"
3. If fix changes behaviour of a Done task → treat like Scenario B1:
   - Append EVAL-#ID (v2) sub-block to evaluation_history.md
   - Update backlog.md status if needed
4. Do NOT update backlog.md status to "Done" for a fix unless ALL tests pass

BATCHING RULE — when to commit immediately vs. batch:
   1 fix, 1 file, all tests pass           → commit now
   2–4 small related fixes, same session   → batch into 1 commit at session end
   5+ fixes or fixes across multiple epics → 1 commit per epic + tag hotfix/session-DATE
   Fix caused regression                   → DO NOT commit, fix regression first
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
- [x] Pre-task Git health check passed: clean tree, correct branch, no duplicate tag (Rule G6)
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

git_workflow.md        security_checks.md        task_template.md
  └─ Rules G1–G6          └─ CSRF/XSS/SQLi          └─ Per-task fill-in form
  └─ Branch/tag/push       └─ Auth security          └─ Pre-task checklist
  └─ Pre-commit hook        └─ Security test table    └─ Git checklist

fixlog.md  ────────────────────────────────────────────────┐
  └─ FIX-<NNN> blocks (runtime fixes outside task flow)    │
  └─ Parent task mapping                                    │
  └─ Test gap analysis                                      │
  └─ Proposed follow-up TCs                                │
  └─ Environment variables setup table                     │
  └─ Commit strategy for fix batches                       │
                                                           ▼
fix_template.md                                 (feeds fixlog.md entries)
  └─ 7-step fix workflow                          └─ FIX-<NNN> template
  └─ Fix type classification                      └─ Commit message format
  └─ Commit batching rules                        └─ Root cause checklist

                   ▲
                   └──────── instruction.md ───────────────┘
                             (this file — the workflow)
```

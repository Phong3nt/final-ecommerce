# Git Workflow — Laravel E-Commerce

> Reference file for [instruction.md](instruction.md) — Git & GitHub Rules section.  
> These rules apply to **every task execution**. The Agent is both developer and Git Manager.

---

## Rule G1 — Branch-per-Task (Never Code on `main`)

- Always ensure you are on `main` with a clean working tree before starting any task.
- Every task must be implemented on its own dedicated branch:
  ```
  git checkout main
  git checkout -b feature/[TaskID]    # example: feature/AU-002
  ```
- **Never** commit feature code directly to `main`.
- For bug-fix iterations (from evaluation): use `fix/[TaskID]` branches.
- For approved upgrade proposals: use `upgrade/[TaskID].1` branches.

---

## Rule G2 — Commit After Tests Pass (Not Before)

- Only commit when **all unit tests for that task pass** (STEP 2 complete).
- Structured commit message:
  ```
  feat: [TaskID] — [short description] — N/N tests pass
  ```
  Example: `feat: AU-001 — user registration — 12/12 tests pass`
- Commit **all** relevant files: controllers, models, migrations, views, test files, updated docs.

---

## Rule G3 — Tag Every Stable Milestone

- After every `Done` task (tests pass + evaluation complete):
  ```
  git tag -a v1.0-[TaskID]-stable -m "Task [TaskID] complete — N/N tests pass, evaluation done"
  ```
  Example: `git tag -a v1.0-AU-001-stable -m "Task AU-001 complete — 12/12 tests pass, evaluation done"`
- Record the tag in `backlog.md` **Git Tag** column.
- Tags are the rollback "time machine".

---

## Rule G4 — Rollback Procedure

If a task causes regressions or becomes unrecoverable:

1. **Stop** — do not patch blindly.
2. Find the last stable tag from `backlog.md` Git Tag column.
3. Rollback options:
   ```
   git checkout main                               # Back to last merged stable
   git checkout v1.0-[LastStableTaskID]-stable     # Specific rollback point
   ```
4. Report to user: current branch, what broke, recommended rollback tag.

---

## Rule G5 — Push & Merge (User-Approved Only)

- **Never push or merge to `main`** without explicit user approval.
- After approval:
  ```
  git checkout main
  git merge feature/[TaskID] --no-ff -m "merge: integrate [TaskID] into main"
  git push origin main
  git push origin --tags
  git push origin feature/[TaskID]
  ```
- Create a Pull Request on GitHub after push (for history).
- Delete feature branch only with user confirmation.

---

## Rule G6 — Pre-Task Git Health Check (Always Run First)

> Run before starting ANY task — including resuming a paused/interrupted task.

### Required checks:

```powershell
git status                              # Must be: "nothing to commit, working tree clean"
git branch                              # Confirm you are on the correct branch
git tag --list "v1.0-[TaskID]-*"        # Must NOT already exist
```

### Decision table — `git status`:

| Result                   | Action                                         |
| ------------------------ | ---------------------------------------------- |
| Clean                    | ✅ Proceed                                     |
| Modified/untracked files | Stage + commit those files first, then proceed |
| In the middle of a merge | `git merge --abort`, investigate, then proceed |

### Resuming an interrupted task — additional checks:

```powershell
git log --oneline -5                            # What was already committed?
git tag --list "v1.0-[TaskID]-*"                # Tag already created?
git branch -a | Select-String "[TaskID]"        # Feature branch already exists?
```

| Scenario                             | Action                                                 |
| ------------------------------------ | ------------------------------------------------------ |
| Feature branch exists, no commit yet | `git checkout feature/[TaskID]` and continue from code |
| Feature branch exists, commit exists | Skip commit step, continue from merge                  |
| Tag already exists                   | Skip `git tag` step, proceed to push only              |
| Dirty files unrelated to this task   | `git stash`, complete task, `git stash pop` after      |

---

## Branching Summary Table

| Pattern            | Purpose                          | Example            |
| ------------------ | -------------------------------- | ------------------ |
| `main`             | Production-ready only            | Stable baseline    |
| `feature/[ID]`     | New task implementation          | `feature/AU-002`   |
| `fix/[ID]`         | Bug fix from evaluation          | `fix/AU-001`       |
| `upgrade/[ID].1`   | Approved proposal upgrade        | `upgrade/AU-001.1` |
| `improve/[IMP-ID]` | Deliberate improvement (IMP-NNN) | `improve/IMP-006`  |

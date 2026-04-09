# Final Ecommerce — Laravel E-Commerce Platform

A full-featured e-commerce platform built with **Laravel 10**, **PHP 8.1**, and **MySQL**, following a structured backlog-driven development process with automated testing.

## Tech Stack

| Layer       | Technology                    |
| ----------- | ----------------------------- |
| Framework   | Laravel 10 (PHP 8.1)          |
| Database    | MySQL 8 (XAMPP)               |
| Auth / RBAC | Spatie Laravel Permission v6  |
| OAuth       | Laravel Socialite v5 (Google) |
| Testing     | PHPUnit 10 (SQLite in-memory) |
| Export      | Maatwebsite Excel v3.1        |

## Project Structure

```
final/
├── docs/
│   ├── backlog.md              # 14 Epics · 64 Tasks · 231 Story Points
│   ├── instruction.md          # Agent workflow rules & Git Flow guide
│   ├── testing_standards.md    # PHPUnit standards & anti-cheat rules
│   └── evaluation_history.md   # Per-task evaluation records
└── ecommerce/                  # Laravel 10 application
    ├── app/
    ├── database/
    ├── resources/
    ├── routes/
    └── tests/
```

## Roles

| Role  | Permissions                                                   |
| ----- | ------------------------------------------------------------- |
| Admin | Full CRUD on products, orders, users; access admin dashboard  |
| User  | Browse catalog, manage cart, place orders, manage own profile |

## Development Workflow

All work follows a **4-step cycle** tracked in `docs/instruction.md`:

1. **CODE** — Implement the feature per backlog task definition
2. **TEST** — Write + run PHPUnit tests (minimum 3 test cases per task)
3. **EVALUATE** — Score quality, detect regressions, document in `evaluation_history.md`
4. **PROPOSE** — Write improvement proposals (never implement without approval)

Every task lives on its own branch (`feature/TASK-ID`) and is tagged on completion (`v[version]-[TASK-ID]-stable`).

## Progress

See [docs/backlog.md](docs/backlog.md) for the full task list and current status.

| Sprint | Scope                        | Status      |
| ------ | ---------------------------- | ----------- |
| 1      | Authentication & Auth Guards | In Progress |
| 2–7    | Catalog, Cart, Orders, Admin | Not Started |

## License

This project is licensed under the [MIT License](LICENSE).

# Testing Standards — Laravel E-Commerce

**Project:** Laravel E-Commerce Platform  
**Applies to:** All tasks defined in [backlog.md](backlog.md)  
**Test Framework:** PHPUnit (built-in with Laravel 10)  
**Related:** [evaluation_history.md](evaluation_history.md) · [instruction.md](instruction.md)

---

## 1. Core Principle

> **Unit Test is the only evidence an Agent uses to self-correct code.**  
> If a test fails → the corresponding task in the backlog is **not complete**.  
> No exceptions.

### Integrity Rules (Anti-Cheat)

| Situation                   | Correct Action                         | Forbidden                         |
| --------------------------- | -------------------------------------- | --------------------------------- |
| Test correct, code wrong    | Fix **code** only                      | Modify test to match wrong output |
| Code correct, test outdated | Fix **test** only, document the change | Change both simultaneously        |
| Ambiguous                   | STOP — report to user                  | Guess and change either side      |
| Same test fails 3× in a row | STOP — report error + 3 attempts tried | Attempt a 4th fix silently        |

---

## 2. Mandatory Test Coverage Per Task

Every backlog task that produces code **must** ship with at minimum:

| Test Category     | Required Cases                                       | Minimum Count |
| ----------------- | ---------------------------------------------------- | ------------- |
| **Happy Path**    | Valid input → expected output                        | 1             |
| **Negative Path** | Invalid / wrong input → expected error/rejection     | 1             |
| **Edge / Border** | Empty, null, max-length, boundary values             | 1             |
| **Security**      | SQL injection, XSS payload, CSRF for mutating routes | 1 per risk    |

> Total minimum: **3 test cases per task** (happy + negative + edge).  
> Security tests are mandatory for tasks involving user input or payment (CP-003, AU-001, AU-002).

---

## 3. Test Naming Convention

```
test_<taskId>_<scenario>_<expectedOutcome>()

Examples:
test_AU001_validCredentials_redirectsToDashboard()
test_AU001_wrongPassword_returns422WithError()
test_AU001_emptyEmail_failsValidation()
test_CP003_validCardToken_createsOrderAndSendsEmail()
test_CP003_expiredCard_returnsPaymentFailedPage()
```

---

## 4. Test File Location

```
tests/
├── Unit/                          # Pure logic, no DB / HTTP
│   ├── Services/
│   │   ├── CartServiceTest.php    # SC-001–SC-005
│   │   ├── PaymentServiceTest.php # CP-003
│   │   └── OrderServiceTest.php   # OH-001–OH-004
│   └── Models/
│       └── CouponTest.php         # SC-005, RM-003
└── Feature/                       # Full HTTP request → DB → response
    ├── Auth/
    │   ├── RegisterTest.php       # AU-001
    │   ├── LoginTest.php          # AU-002
    │   ├── GoogleLoginTest.php    # AU-003
    │   └── PasswordResetTest.php  # AU-005
    ├── User/
    │   ├── ProductCatalogTest.php # PC-001–PC-005
    │   ├── CartTest.php           # SC-001–SC-005
    │   ├── CheckoutTest.php       # CP-001–CP-005
    │   └── OrderHistoryTest.php   # OH-001–OH-004
    └── Admin/
        ├── DashboardTest.php      # AD-001–AD-004
        ├── ProductManagementTest.php # PM-001–PM-006
        ├── OrderManagementTest.php   # OM-001–OM-005
        ├── UserManagementTest.php    # UM-001–UM-004
        └── RevenueTest.php           # RM-001–RM-003
```

---

## 5. Test Standards Per Epic

### EPIC 1 — Authentication (AU-001–AU-006)

| Rule              | Description                                                              |
| ----------------- | ------------------------------------------------------------------------ |
| Password hashing  | Assert `bcrypt` is used; raw password must never exist in DB             |
| Google OAuth mock | Mock `Socialite::driver('google')` — never call real Google API in tests |
| Session security  | Assert session is destroyed on logout (AU-004)                           |
| Throttle          | Assert login returns 429 after 5 failed attempts (NF-006)                |
| Role guard        | Assert non-admin gets 403 on all `/admin/*` routes (AU-006)              |

### EPIC 3 — Product Catalog (PC-001–PC-005)

| Rule               | Description                                                          |
| ------------------ | -------------------------------------------------------------------- |
| Search performance | Assert search query runs in < 500ms with seeded 1,000-row dataset    |
| Pagination         | Assert response contains `links` and `meta.total` keys               |
| Guest access       | Assert catalog pages return 200 without authentication               |
| XSS in search      | Assert search input `<script>alert(1)</script>` is escaped in output |

### EPIC 4 — Shopping Cart (SC-001–SC-005)

| Rule              | Description                                                    |
| ----------------- | -------------------------------------------------------------- |
| Stock enforcement | Assert adding qty > stock returns a 422 with `errors.quantity` |
| Guest cart merge  | Assert cart items created before login are merged after login  |
| Total calculation | Assert subtotal = unit_price × quantity with decimal precision |
| Coupon expiry     | Assert expired coupon returns 422 with `errors.coupon_code`    |

### EPIC 5 — Checkout & Payment (CP-001–CP-005)

| Rule                 | Description                                                               |
| -------------------- | ------------------------------------------------------------------------- |
| Payment tokenization | Assert no card number / CVV is ever stored in `payments` table            |
| Webhook idempotency  | Assert processing the same webhook twice does not create duplicate orders |
| Email dispatch       | Assert `OrderPlaced` Mailable is queued (use `Mail::fake()`)              |
| Failure page         | Assert failed payment status shows failure reason from gateway            |

### EPIC 8–12 — Admin Areas

| Rule                 | Description                                               |
| -------------------- | --------------------------------------------------------- |
| Auth guard           | Every admin test must execute as a user with `role:admin` |
| Regular user blocked | Same request as `role:user` must return 403               |
| CSV export headers   | Assert exported CSV contains all required column headers  |
| Revenue accuracy     | Assert revenue totals exclude `Refunded` orders           |

---

## 6. Regression Test Rule

When a task is upgraded (e.g., `CP-003` → `CP-003.1`):

1. **Run the full existing test suite** before writing any new code.
2. Record the baseline pass count in [evaluation_history.md](evaluation_history.md).
3. After changes, run the suite again.
4. **Zero regressions are acceptable** — any newly failing test = blocker.
5. Document removed/updated test cases with explicit justification.

### Test Suite Audit During Upgrade

For every existing test case of the upgraded task, apply this decision tree:

```
Does the old test case still reflect the new logic?
├── YES → Keep unchanged
├── PARTIALLY → Update assertion/threshold — report: "Updated TC-X because..."
└── NO → Delete — report: "Removed TC-Y: [reason]"
    └── Was it a security/edge test? → Must replace with an equivalent stricter test
```

The evaluation block must include a **Test Change Report**:

```
Added:   TC-X (new scenario: ...)
Updated: TC-Y (changed assertion from ... to ... because ...)
Removed: TC-Z (no longer valid: old logic assumed X, new logic does Y)
```

```bash
# Run full suite
php artisan test

# Run a specific epic
php artisan test --filter=Auth
php artisan test --filter=CheckoutTest

# Run with coverage
php artisan test --coverage --min=80
```

---

## 8. Rule: Test-First Upgrade

> **Áp dụng cho mọi nâng cấp A → A.1, bất kể lý do (proposal, regression fix, hay hotfix).**

**Nguyên tắc:** Test mới phải được viết và chạy _trước khi viết bất kỳ dòng code nào_ của phiên bản nâng cấp. Test là "thước đo mới" — không phải là thứ viết sau để xác nhận code đúng.

### Trình tự bắt buộc:

```
1. Đọc lại tất cả test case hiện có của A
2. Với mỗi test case hiện có, quyết định:
   - KEEP   → behavior không đổi, giữ nguyên
   - UPDATE → behavior thay đổi có chủ ý, sửa assertion trước
   - DELETE → không còn relevant, xóa + ghi lý do
   - ADD    → scenario mới của A.1, viết test mới (lúc này sẽ FAIL — đó là đúng)
3. Chạy toàn bộ suite → phải thấy đúng số test FAIL như kỳ vọng
4. Chỉ sau bước 3 mới được viết code A.1
5. Code cho tới khi tất cả test PASS
```

### Ký hiệu trong commit:

```
test: upgrade AU-001 test suite to A.1 spec (before code)
feat: AU-001.1 — implement rate limiting — 14/14 tests pass
```

> **Cấm:** Viết code A.1 trước, rồi mới sửa test cho khớp. Đó là "test chạy theo code" — không phải "test làm thước đo".

### Liên quan đến Proposal cũ:

Trước khi bắt đầu viết test cho A.1, **kiểm tra tất cả proposal cũ** (AU-001.1, AU-001.2...) trong `evaluation_history.md`:

| Trạng thái proposal                                        | Hành động                                                                                      |
| ---------------------------------------------------------- | ---------------------------------------------------------------------------------------------- |
| Proposal được thiết kế dựa trên "A cũ" nhưng A đã thay đổi | **Invalidate** — đánh dấu `⚠️ Cần review` trong evaluation block, báo user trước khi implement |
| Proposal vẫn hợp lệ với "A mới"                            | Giữ nguyên, tiến hành bình thường                                                              |
| Không chắc chắn                                            | STOP — báo user, liệt kê điểm xung đột cụ thể                                                  |

| Area                          | Threshold            | Measured By                            |
| ----------------------------- | -------------------- | -------------------------------------- |
| Product search (PC-002)       | < 500ms              | `$this->assertLessThan(0.5, $elapsed)` |
| Cart add/update (SC-001)      | < 200ms              | `$this->assertLessThan(0.2, $elapsed)` |
| Checkout page load (CP-001)   | < 1000ms             | `$this->assertLessThan(1.0, $elapsed)` |
| Admin dashboard load (AD-001) | < 1500ms             | `$this->assertLessThan(1.5, $elapsed)` |
| Order export CSV (OM-004)     | < 3000ms for 1k rows | `$this->assertLessThan(3.0, $elapsed)` |

---

## 8. Security Test Checklist (run on every PR)

- [ ] All `POST/PUT/PATCH/DELETE` routes include `@csrf` / `X-CSRF-TOKEN`
- [ ] No raw `DB::select` with user input (use bindings)
- [ ] File uploads reject non-image MIME types (PM-006)
- [ ] Payment webhook validates signature before processing (CP-003)
- [ ] Admin routes return 403 for unauthenticated and non-admin users
- [ ] Password reset tokens expire after 60 minutes (AU-005)

---

## 9. Test Database Setup

Use an **in-memory SQLite database** for speed in CI, or a dedicated `ecommerce_test` MySQL database locally:

```php
// phpunit.xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
<env name="QUEUE_CONNECTION" value="sync"/>
<env name="MAIL_MAILER" value="array"/>
```

Always use `RefreshDatabase` or `DatabaseTransactions` trait in Feature Tests to ensure isolation.

---

## 10. Pass/Fail Definition

| Result          | Condition                                                                                                            |
| --------------- | -------------------------------------------------------------------------------------------------------------------- |
| **PASS**        | All test cases green, zero warnings, performance within threshold                                                    |
| **PARTIAL**     | Core happy-path passes but edge/security cases fail — task status = `Blocked`                                        |
| **FAIL**        | Any critical path test fails — task status reverts to `In Progress`                                                  |
| **REGRESSION**  | Any previously passing test now fails after a change — must fix before merge                                         |
| **MAX-RETRY**   | Same test failed 3 consecutive times — Agent must stop and report to user                                            |
| **BLOCKED-DEP** | Test cannot run because a dependency (another task) is not yet built — Agent must mock/stub the dependency and retry |

---

## 11. Dependency Mocking Standards

When a task requires functionality from a not-yet-implemented backlog task, use the following Laravel test helpers:

| Dependency Type              | Laravel Test Helper    | Example                          |
| ---------------------------- | ---------------------- | -------------------------------- |
| Email sending                | `Mail::fake()`         | CP-004 mocked for AU-001         |
| HTTP calls (payment gateway) | `Http::fake([...])`    | CP-003 mocked for checkout tests |
| Events / Listeners           | `Event::fake()`        | Order placed event               |
| Queue jobs                   | `Queue::fake()`        | Background job dispatch          |
| External services            | `Mockery::mock()`      | Socialite for AU-003             |
| Notifications                | `Notification::fake()` | NT-001, NT-002                   |

Each mocked dependency **must be documented** in the evaluation block:

```
Mocked Dependencies:
  - Mail::fake() — CP-004 (Order Confirmation Email) not yet built
  - Queue::fake() — NF-008 (Queue jobs) not yet configured
```

When the real dependency is implemented, the mock must be removed and replaced with a real integration assertion.

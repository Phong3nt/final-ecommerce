# Proposal Ver1 — Laravel E-Commerce Platform

**Version:** 1.0  
**Date:** April 28, 2026  
**Status:** Active Development  
**Course:** COMP1682 — Web Development Capstone  
**Project Code:** CO1106_1682

---

## 1. Tóm Tắt Dự Án (Project Summary)

### 1.1 Mô Tả Tổng Quan

Dự án xây dựng một **Nền Tảng E-Commerce Đầy Đủ** bằng Laravel 10, cho phép người dùng duyệt, tìm kiếm, và mua sắm các sản phẩm trực tuyến. Nền tảng hỗ trợ hai vai trò chính: **User (Khách hàng)** và **Admin (Quản trị viên)**.

### 1.2 Lợi Ích Chính

- **Cho Khách Hàng:** Trải nghiệm mua sắm trực tuyến hiện đại, thanh toán an toàn, theo dõi đơn hàng
- **Cho Quản Trị Viên:** Quản lý sản phẩm, đơn hàng, người dùng, và xem báo cáo doanh thu
- **Cho Tổ Chức:** Tăng doanh thu, mở rộng phạm vi khách hàng, tự động hóa quy trình bán hàng

### 1.3 Vấn Đề Cần Giải Quyết

- Nhu cầu bán hàng trực tuyến không phục vụ được bởi các giải pháp hiện tại
- Tính năng quản lý hàng hóa thủ công chiếm nhiều thời gian
- Khách hàng không có kênh thanh toán trực tuyến an toàn

---

## 2. Mục Tiêu Dự Án (Project Objectives)

| Mục Tiêu                   | Mô Tả                                                                    | Đo Lường                                              |
| -------------------------- | ------------------------------------------------------------------------ | ----------------------------------------------------- |
| **Chức Năng Core**         | Xây dựng các tính năng xác thực, danh mục sản phẩm, giỏ hàng, thanh toán | Tất cả 12 Epic hoàn thành ≥ 80% test pass             |
| **Trải Nghiệm Người Dùng** | Giao diện trực quan, responsive trên mobile                              | Dashboard user score ≥ 4/5                            |
| **Bảo Mật**                | Xác thực OAuth, RBAC, bảo vệ thanh toán                                  | Không có lỗ hổng OWASP Top 10                         |
| **Hiệu Năng**              | Xử lý ≥ 10k sản phẩm, tìm kiếm < 1s                                      | Response time < 200ms, Load test 100 concurrent users |
| **Bảo Trì**                | Code sạch, test coverage > 70%, tài liệu đầy đủ                          | PHPUnit ≥ 100 test cases, Code review pass            |

---

## 3. Phạm Vi Dự Án (Project Scope)

### 3.1 Bao Gồm (In Scope)

#### EPIC 1–6: Người Dùng Cuối (User-Facing)

- **AU (Authentication):** Đăng ký, đăng nhập, OAuth Google, quên mật khẩu
- **UP (User Profile):** Cập nhật thông tin, quản lý địa chỉ
- **PC (Product Catalog):** Duyệt, tìm kiếm, lọc, sắp xếp sản phẩm
- **SC (Shopping Cart):** Thêm/xóa/cập nhật giỏ hàng
- **CP (Checkout & Payment):** Chọn địa chỉ, phương thức vận chuyển, thanh toán Stripe/Midtrans
- **OH (Order History):** Xem lịch sử đơn hàng, theo dõi trạng thái

#### EPIC 7–12: Quản Trị Viên (Admin-Facing)

- **RV (Reviews):** Đánh giá sản phẩm từ người dùng
- **AD (Admin Dashboard):** KPI cards, biểu đồ doanh thu
- **PM (Product Management):** CRUD sản phẩm, import CSV, quản lý ảnh
- **OM (Order Management):** Xem đơn hàng, cập nhật trạng thái, xuất CSV
- **UM (User Management):** Quản lý tài khoản, gán vai trò
- **RM (Revenue Management):** Báo cáo doanh thu, hoàn lại tiền

### 3.2 Không Bao Gồm (Out of Scope)

| Tính Năng                      | Lý Do                            | Đề Xuất          |
| ------------------------------ | -------------------------------- | ---------------- |
| Inventory Forecasting ML       | Phức tạp, vượt quá thời gian     | Sprint 8+        |
| Multi-Tenant (Marketplace)     | Yêu cầu kiến trúc khác           | Giai đoạn 2      |
| Social Login (Facebook, Apple) | Scope giới hạn, chỉ Google OAuth | Phase 2          |
| Advanced Analytics (GA4)       | Không phải lõi kinh doanh        | Backlog tùy chọn |
| SMS Notifications              | Chi phí, scope hạn chế           | Ver 2            |

### 3.3 Kỳ Vọng Người Dùng

- **Khách hàng:** Mua hàng được ≤ 3 bước click, thanh toán < 2 phút
- **Admin:** Nhập 100 sản phẩm < 5 phút qua CSV
- **Tất cả:** Không gặp lỗi, thời gian tải < 3 giây

---

## 4. Phương Pháp Thực Hiện (Methodology)

### 4.1 Quy Trình Phát Triển

```
BACKLOG DRIVEN WORKFLOW:
┌─────────────────────────────────────────────────────────────┐
│ 1. CODE - Implement per task definition                     │
│ 2. TEST - Write + run PHPUnit (≥3 test cases per task)      │
│ 3. EVALUATE - Score quality, detect regressions             │
│ 4. PROPOSE - Write improvement proposals (if needed)        │
└─────────────────────────────────────────────────────────────┘
```

**Mỗi task:**

- Tạo branch: `feature/[TASK-ID]`
- Viết test TRƯỚC code (TDD)
- Tag khi hoàn thành: `v[version]-[TASK-ID]-stable`
- Ghi nhận trong `evaluation_history.md`

### 4.2 Tech Stack Chi Tiết

| Layer         | Công Nghệ                       | Lý Do                              |
| ------------- | ------------------------------- | ---------------------------------- |
| **Backend**   | Laravel 10 (PHP 8.1)            | Mature, RAD, excellent ecosystem   |
| **Database**  | MySQL 8 (XAMPP)                 | Relational, ACID, SQL queries      |
| **Auth/RBAC** | Spatie Laravel Permission v6    | Flexible role/permission system    |
| **OAuth**     | Laravel Socialite v5            | Standardized OAuth flow            |
| **Testing**   | PHPUnit 10 + SQLite in-memory   | Fast, isolated, no external DB     |
| **Export**    | Maatwebsite Excel v3.1          | Industry-standard CSV/XLSX export  |
| **Frontend**  | Blade + Bootstrap 5.3 CDN       | Server-side templating, responsive |
| **Payment**   | Stripe/Midtrans API             | PCI-DSS compliant, webhooks        |
| **Queue**     | Laravel Queue (database driver) | Reliable background job processing |
| **Email**     | Laravel Mail (queue-backed)     | Async email, no block main request |

### 4.3 Cấu Trúc Dự Án

```
final/
├── docs/
│   ├── backlog.md              ← Source of truth: 14 Epics, 64 Tasks, 231 SP
│   ├── instruction.md          ← Rules & Git Flow
│   ├── testing_standards.md    ← Test strategies
│   ├── evaluation_history.md   ← Per-task evaluation records
│   └── ...                     ← Other specs & checklists
├── ecommerce/                  ← Laravel 10 app root
│   ├── app/
│   │   ├── Http/Controllers/
│   │   ├── Models/
│   │   ├── Services/
│   │   └── Jobs/
│   ├── database/
│   │   ├── migrations/
│   │   └── seeders/
│   ├── resources/views/        ← Blade templates (Layouts + Pages)
│   ├── routes/
│   ├── tests/                  ← PHPUnit tests
│   │   ├── Feature/
│   │   └── Unit/
│   └── config/
└── PROPOSAL_VER1.md            ← This file
```

### 4.4 Tiêu Chuẩn Chất Lượng

| Tiêu Chuẩn        | Yêu Cầu                     | Kiểm Tra                       |
| ----------------- | --------------------------- | ------------------------------ |
| **Test Coverage** | ≥ 70%                       | `phpunit --coverage-html`      |
| **Code Style**    | PSR-12                      | `php-cs-fixer`                 |
| **Security**      | No SQL Injection, XSS, CSRF | `php artisan security:check`   |
| **Performance**   | Response < 200ms (avg)      | Laravel Debugbar, Load testing |
| **Regression**    | Zero breaking tests         | Run full suite before merge    |

---

## 5. Lịch Trình Dự Án (Timeline)

### 5.1 Milestone Timeline

| Sprint          | Epic(s)           | Tên                                 | Khoảng Thời Gian          | Độ Dài |
| --------------- | ----------------- | ----------------------------------- | ------------------------- | ------ |
| **Sprint 0**    | Setup             | Cấu hình dự án, DB, repo            | 2–6 tháng Năm             | 1 tuần |
| **Sprint 1**    | AU, UP            | Authentication, User Profile        | 6–13 tháng Năm            | 2 tuần |
| **Sprint 2**    | PC                | Product Catalog                     | 13–27 tháng Năm           | 2 tuần |
| **Sprint 3**    | SC, CP            | Shopping Cart, Checkout             | 27 tháng Năm–10 tháng Sáu | 2 tuần |
| **Sprint 4**    | OH, RV            | Order History, Reviews              | 10–24 tháng Sáu           | 2 tuần |
| **Sprint 5**    | AD, PM            | Admin Dashboard, Product Management | 24 tháng Sáu–8 tháng Bảy  | 2 tuần |
| **Sprint 6**    | OM, UM            | Order Management, User Management   | 8–22 tháng Bảy            | 2 tuần |
| **Sprint 7**    | RM                | Revenue Management                  | 22–29 tháng Bảy           | 1 tuần |
| **UAT/Release** | Kiểm tra, Sửa lỗi | Final QA, Deployment                | 29 tháng Bảy–5 tháng Tám  | 1 tuần |

**Tổng cộng:** ~4 tháng (16 tuần)

### 5.2 Dependencies & Critical Path

```
Sprint 0: Setup
    ↓
Sprint 1: AU (required by all other features)
    ↓
    ├─→ Sprint 2: PC (independent)
    ├─→ Sprint 3: SC, CP (depends on PC)
    │       ↓
    │   Sprint 4: OH (depends on CP)
    │
    └─→ Sprint 5–7: Admin features (depend on initial data)
```

**Critical Path:** AU → PC → SC → CP → OH → Release

---

## 6. Tài Nguyên Dự Án (Resource Requirements)

### 6.1 Nhân Lực

| Vai Trò                | Số Lượng | Trách Nhiệm                                           | Tuyệt Đối Cần      |
| ---------------------- | -------- | ----------------------------------------------------- | ------------------ |
| **Backend Developer**  | 1–2      | Implement Laravel controllers, models, services, jobs | ✅ Yes             |
| **Frontend Developer** | 1        | Blade views, CSS (Bootstrap), Alpine.js, UX           | ✅ Yes             |
| **QA/Tester**          | 0.5      | Test case design, regression testing, UAT             | ⚠️ Recommended     |
| **DevOps**             | 0.25     | Database setup, deployment pipeline, monitoring       | ✅ Yes (part-time) |
| **Product Manager**    | 0.5      | Backlog prioritization, stakeholder communication     | ⚠️ Recommended     |

**Tổng:** ~3–3.5 FTE

### 6.2 Phần Mềm & Công Cụ

| Công Cụ                  | Mục Đích                      | Chi Phí                            |
| ------------------------ | ----------------------------- | ---------------------------------- |
| **Laravel 10**           | Framework                     | Free (OSS)                         |
| **MySQL 8**              | Database                      | Free (OSS)                         |
| **PHPUnit 10**           | Testing framework             | Free (OSS)                         |
| **Stripe API**           | Payment gateway               | Pay-per-transaction (2.9% + $0.30) |
| **Midtrans API**         | Payment gateway (alternative) | Pay-per-transaction (2.9%)         |
| **Laravel Forge**        | Deployment, SSL               | $12–50/month                       |
| **GitHub**               | Version control, CI/CD        | Free (for public)                  |
| **Postman/Insomnia**     | API testing                   | Free                               |
| **VS Code + Extensions** | Development IDE               | Free                               |

**Tổng Chi Phí Software:** ~$14–50/month (hosting) + transaction fees

### 6.3 Phần Cứng

| Loại                   | Thông Số                          | Lý Do                                      |
| ---------------------- | --------------------------------- | ------------------------------------------ |
| **Development Laptop** | CPU ≥ 8 core, RAM ≥ 16GB          | Run Laravel + MySQL + tests simultaneously |
| **Staging Server**     | Ubuntu 22.04, 2GB RAM, 20GB disk  | Pre-production environment                 |
| **Production Server**  | Ubuntu 22.04, 4GB RAM, 100GB disk | Live e-commerce platform                   |
| **Database Backup**    | External HDD ≥ 1TB                | Critical data protection                   |

---

## 7. Ngân Sách Dự Án (Budget Estimation)

### 7.1 Chi Phí Lao Động

```
Backend Developer:
  - 2 developers × 16 weeks × 40 hours/week × $25/hour
  = 2 × 640 × $25 = $32,000

Frontend Developer:
  - 1 developer × 16 weeks × 40 hours/week × $22/hour
  = 1 × 640 × $22 = $14,080

QA/Tester:
  - 0.5 resource × 16 weeks × 40 hours/week × $18/hour
  = 0.5 × 640 × $18 = $5,760

DevOps (part-time):
  - 0.25 resource × 16 weeks × 40 hours/week × $20/hour
  = 0.25 × 640 × $20 = $3,200

PM (part-time):
  - 0.5 resource × 16 weeks × 40 hours/week × $20/hour
  = 0.5 × 640 × $20 = $6,400

Subtotal Labor: $61,440
```

### 7.2 Chi Phí Hạ Tầng & Công Cụ

```
Hosting (Laravel Forge + Server):
  - Development: $0 (XAMPP local)
  - Staging: $25/month × 4 months = $100
  - Production: $50/month × 4 months = $200

Domain & SSL:
  - Domain registration: $12/year = $1/month × 4 = $4
  - SSL certificate: Free (Let's Encrypt via Forge)

Payment Gateway (Stripe/Midtrans):
  - Setup fee: $0
  - Transaction fees: ~2.9% + $0.30 per transaction
  - Estimated: $500 (from test transactions)

Development Tools:
  - GitHub Pro: $4/month × 4 = $16
  - Postman Cloud: $0 (free tier)

Subtotal Infrastructure: ~$820
```

### 7.3 Tóm Tắt Ngân Sách Tổng Thể

| Hạng Mục      | Chi Phí     | % Tổng   |
| ------------- | ----------- | -------- |
| Nhân lực      | $61,440     | 98.7%    |
| Hạ tầng       | $820        | 1.3%     |
| **TỔNG CỘNG** | **$62,260** | **100%** |

**Contingency (10%):** $6,226  
**Grand Total:** ~$68,486

---

## 8. Đánh Giá Rủi Ro (Risk Assessment)

### 8.1 Bảng Rủi Ro Chi Tiết

| ID     | Rủi Ro                                  | Xác Suất      | Tác Động | Độ Ưu Tiên  | Chiến Lược Giảm Thiểu                                                                           |
| ------ | --------------------------------------- | ------------- | -------- | ----------- | ----------------------------------------------------------------------------------------------- |
| **R1** | Thiếu nhân viên / burn-out              | Cao (70%)     | Cao      | 🔴 Critical | - Thuê thêm freelancer<br>- Giảm phạm vi tính năng<br>- Mở rộng timeline                        |
| **R2** | Lỗi bảo mật (SQL Injection, XSS)        | Trung (40%)   | Rất Cao  | 🔴 Critical | - Code review mandatory<br>- OWASP security checks<br>- Penetration testing (tháng 8)           |
| **R3** | Stripe/Payment gateway downtime         | Thấp (10%)    | Cao      | 🟡 High     | - Fallback to Midtrans<br>- Queue retry logic<br>- Webhook logging                              |
| **R4** | Database migration failure (production) | Trung (35%)   | Rất Cao  | 🔴 Critical | - Test migrations in staging<br>- Backup before migration<br>- Rollback plan prepared           |
| **R5** | Scope creep (feature requests)          | Rất Cao (80%) | Trung    | 🟡 High     | - Strict backlog process<br>- Feature vote system<br>- Move to Ver 2 backlog                    |
| **R6** | Performance issues (slow queries)       | Trung (45%)   | Trung    | 🟡 High     | - Query optimization review<br>- Load testing Sprint 6<br>- Index strategy documented           |
| **R7** | Third-party dependency bug              | Thấp (15%)    | Trung    | 🟢 Medium   | - Monitor Laravel security updates<br>- Vendor lock check<br>- Fallback plans for critical libs |
| **R8** | Test flakiness (intermittent failures)  | Trung (50%)   | Trung    | 🟡 High     | - Isolate unit tests<br>- Mock external dependencies<br>- CI/CD retry logic                     |

### 8.2 Kế Hoạch Ứng Phó (Contingency Plans)

```
IF delayed past midpoint:
  → Reduce RV (Reviews), RM (Revenue Management) to MVP only
  → Extend UAT phase to 2 weeks

IF critical security flaw found:
  → Pause feature work, emergency fix sprint
  → Full security audit post-fix

IF payment gateway fails:
  → Implement "offline checkout" mode
  → Queue manual payment verification
```

---

## 9. Kế Hoạch Đánh Giá (Evaluation Plan)

### 9.1 Tiêu Chí Thành Công

| Tiêu Chí                    | Định Nghĩa                         | Đo Lường                                       |
| --------------------------- | ---------------------------------- | ---------------------------------------------- |
| **Functional Completeness** | 100% backlog tasks completed       | Backlog status = "Done" for all 64 tasks       |
| **Test Coverage**           | ≥70% code coverage                 | `phpunit --coverage` report                    |
| **Security**                | Zero critical vulnerabilities      | OWASP scan + code review sign-off              |
| **Performance**             | Page load ≤ 3 seconds, API ≤ 200ms | Lighthouse audit score ≥ 80, Load test results |
| **UX Quality**              | User satisfaction ≥ 4/5 stars      | User feedback survey                           |
| **Team Satisfaction**       | Developer + QA score ≥ 4/5         | Post-project retrospective                     |

### 9.2 Evaluation Timeline

| Phase           | Timing               | Deliverable                                 | Owner       |
| --------------- | -------------------- | ------------------------------------------- | ----------- |
| **Daily**       | EOD                  | Test results + code review comments         | Dev team    |
| **Sprint**      | End of each sprint   | Evaluation block in `evaluation_history.md` | QA + PM     |
| **Release**     | 1 week before launch | Final security audit + UAT sign-off         | DevOps + QA |
| **Post-Launch** | 2 weeks after        | Bug report summary + lessons learned        | PM          |

### 9.3 Metrics Dashboard

```
TRACKED METRICS:
┌─────────────────────────────────────────┐
│ Sprint Velocity: Story points completed │
│ Test Pass Rate: % of tests passing      │
│ Bug Escape Rate: Production bugs/sprint │
│ Code Churn: Lines changed/sprint        │
│ Deployment Frequency: Releases/week     │
│ Mean Time to Recovery: Minutes          │
└─────────────────────────────────────────┘
```

---

## 10. Tài Liệu Tham Khảo (References & Appendices)

### 10.1 Tài Liệu Nội Bộ

1. **[backlog.md](docs/backlog.md)** — 14 Epics, 64 Tasks, 231 Story Points
2. **[instruction.md](docs/instruction.md)** — 10 Agent Operating Rules
3. **[testing_standards.md](docs/testing_standards.md)** — PHPUnit best practices
4. **[evaluation_history.md](docs/evaluation_history.md)** — Per-task evaluation records
5. **[uiux_design_spec.md](docs/uiux_design_spec.md)** — Bootstrap 5.3 + Alpine.js guidelines
6. **[security_checks.md](docs/security_checks.md)** — OWASP Top 10 audit checklist
7. **[task_template.md](docs/task_template.md)** — Per-task execution template
8. **[improvement_template.md](docs/improvement_template.md)** — Improvement execution rules

### 10.2 Công Nghệ Chính

- **Laravel:** https://laravel.com/docs/10.x
- **Spatie Permission:** https://spatie.be/docs/laravel-permission/v6/introduction
- **Stripe API:** https://stripe.com/docs/payments
- **Midtrans:** https://docs.midtrans.com
- **PHPUnit:** https://phpunit.de/documentation.html
- **Bootstrap 5.3:** https://getbootstrap.com/docs/5.3
- **Alpine.js:** https://alpinejs.dev/

### 10.3 Công Cụ & Repositories

| Công Cụ     | URL                             | Mục Đích                    |
| ----------- | ------------------------------- | --------------------------- |
| GitHub Repo | https://github.com/[org]/[repo] | Version control, CI/CD      |
| Staging DB  | `staging.db.example.com`        | Pre-production validation   |
| Analytics   | Google Analytics / Metabase     | Business metrics            |
| Monitoring  | Laravel Telescope / Sentry      | Error tracking, performance |

### 10.4 Thống Kê Dự Án

```
BACKLOG SUMMARY:
├─ 14 Epics
├─ 64 Tasks (User Stories)
├─ 231 Story Points Total
├─ ~32,000 lines of code (estimated)
├─ ≥100 PHPUnit test cases
└─ 4-month timeline

TEAM:
├─ 2 Backend Developers
├─ 1 Frontend Developer
├─ 0.5 QA Engineer
├─ 0.25 DevOps
└─ 0.5 Product Manager (Total 3–3.5 FTE)

BUDGET: ~$68,486 (including 10% contingency)
```

---

## 11. Phê Duyệt & Ký Kết (Sign-Off)

| Vai Trò             | Tên    | Ngày   | Chữ Ký       |
| ------------------- | ------ | ------ | ------------ |
| **Product Owner**   | [Name] | [Date] | ****\_\_**** |
| **Tech Lead**       | [Name] | [Date] | ****\_\_**** |
| **Project Manager** | [Name] | [Date] | ****\_\_**** |
| **Sponsor/Client**  | [Name] | [Date] | ****\_\_**** |

---

## 12. Lịch Sử Thay Đổi (Version History)

| Version | Ngày       | Thay Đổi                   | Người Thực Hiện |
| ------- | ---------- | -------------------------- | --------------- |
| 1.0     | 28/04/2026 | Initial proposal draft     | Agent           |
| 1.1     | [TBD]      | Feedback from stakeholders | [TBD]           |
| 2.0     | [TBD]      | Post-launch retrospective  | [TBD]           |

---

**END OF PROPOSAL VER1**

_Last Updated: April 28, 2026_  
_Next Review: May 28, 2026 (EOD Sprint 2)_

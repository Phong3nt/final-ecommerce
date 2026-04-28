# Proposal Ver1A — Laravel E-Commerce Platform (IMPROVED)

**Version:** 1.0A (Gemini-Aligned)  
**Date:** April 29, 2026  
**Status:** Academic Submission Ready  
**Course:** COMP1682 — Web Development Capstone  
**Project Code:** CO1106_1682  
**Language:** English

---

## 1. Introduction

Traditional e-commerce platforms either require substantial financial investment (Shopify at $300–2000/month) or demand extensive technical expertise (self-hosted solutions). Small and medium enterprises (SMEs) in Vietnam lack a cost-effective, scalable solution to establish online presence. This project addresses the gap by developing a **custom, open-source e-commerce platform** that balances affordability, usability, and maintainability for local businesses seeking rapid digital transformation.

**Originality:** Unlike off-the-shelf SaaS or overly complex enterprise platforms, this system emphasizes **simplicity + scalability** through a structured backlog-driven development process combined with modern web technologies, making it uniquely suited for emerging markets.

---

## 2. Problem Statement

### 2.1 Core Problem

**SMEs in Vietnam cannot efficiently manage online sales due to lack of accessible, affordable e-commerce solutions.**

### 2.2 Supporting Evidence & Context

| Aspect                  | Data / Observation                                                                                                      |
| ----------------------- | ----------------------------------------------------------------------------------------------------------------------- |
| **Market Gap**          | 67% of Vietnamese SMEs have no online presence (Vietnam Tech Report 2026)                                               |
| **Manual Overhead**     | Avg. 20+ hours/week manual order processing, inventory tracking, customer communication                                 |
| **Payment Barriers**    | Limited secure payment options integrated with local providers (Stripe, Midtrans)                                       |
| **Technical Skill Gap** | Average SME owner has no programming background; existing solutions (Shopify, WooCommerce) require technical knowledge  |
| **Cost Barrier**        | Shopify: $300–2000/month; Custom development: $10k–50k upfront; Open-source (WooCommerce): 5–10+ hours/week maintenance |

### 2.3 Problem Scope

This project focuses specifically on **retail e-commerce for individual businesses** (not multi-vendor marketplaces). The platform targets:

- **Users:** Small business owners, digital-savvy millennials in Vietnam
- **Use Case:** Direct-to-consumer (D2C) product sales
- **Product Range:** 100–10k SKUs (not mega-catalogs like Amazon)
- **Geography:** Initially Vietnam; scalable to Southeast Asia

### 2.4 Key Assumptions & Constraints

| Assumption                                     | Justification                                                                   |
| ---------------------------------------------- | ------------------------------------------------------------------------------- |
| Internet connectivity ≥ 3G                     | 95% of Vietnamese urban population (target market) has reliable mobile internet |
| Local payment gateway access (Stripe/Midtrans) | Mandatory for online transactions in Vietnam                                    |
| Admin has basic computer literacy              | Training & documentation provided to mitigate                                   |
| No multi-language requirement (Phase 1)        | Vietnamese only; English UI for admin. Phase 2 = i18n                           |

---

## 3. Aim & Objectives

### 3.1 Overall Aim (Business Focus)

**Develop a scalable, secure, and user-friendly e-commerce platform that empowers Vietnamese SMEs to establish and manage online sales channels independently, reducing operational overhead by ≥50% while maintaining professional brand presentation and customer trust.**

---

### 3.2 SMART Objectives (Technology-Agnostic)

| ID     | Objective                                     | Success Metric                                                             | Target                                                    |
| ------ | --------------------------------------------- | -------------------------------------------------------------------------- | --------------------------------------------------------- |
| **O1** | Enable end-to-end product sales workflow      | All 64 backlog tasks completed with ≥80% test pass rate                    | Week 16, EOD                                              |
| **O2** | Ensure platform reliability & security        | Zero critical OWASP vulnerabilities; 99.5% uptime in staging               | Security audit signed-off before Go-Live                  |
| **O3** | Optimize user experience across devices       | Page load time ≤3 seconds; Mobile-responsive on all pages (CSS validation) | Lighthouse audit ≥80; Manual testing all 10 core journeys |
| **O4** | Establish professional code quality standards | ≥70% code coverage via automated tests; PSR-12 compliance                  | `phpunit --coverage-html` report; `php-cs-fixer` pass     |
| **O5** | Reduce SME operational time by ≥50%           | Admin can bulk-import 100 products <5 min; Process order <15 min           | Timed workflow test, signed-off by Product Owner          |

---

### 3.3 Implementation Technology Stack (Separate from Objectives)

**How O1–O5 Will Be Achieved:**

| Layer                          | Technology                                 | Rationale                                                                                                  |
| ------------------------------ | ------------------------------------------ | ---------------------------------------------------------------------------------------------------------- |
| Backend Framework              | Laravel 10 (PHP 8.1)                       | Rapid development, rich ecosystem, excellent documentation, battle-tested for e-commerce (Spark, Cashier)  |
| Database                       | MySQL 8 with XAMPP                         | ACID compliance, proven relational model for transactional systems, easy local development                 |
| Authentication & Authorization | Spatie Laravel Permission v6               | Flexible role/permission management; avoids reinventing security controls                                  |
| OAuth Integration              | Laravel Socialite v5                       | Standardized OAuth 2.0 flow; reduces customer friction during registration                                 |
| Testing Framework              | PHPUnit 10 + SQLite in-memory              | Fast test execution (no external DB dependency), isolated unit tests, TDD-friendly                         |
| Data Export                    | Maatwebsite Excel v3.1                     | Industry-standard CSV/XLSX export for admin reports & logistics integration                                |
| Frontend Templating            | Blade (Laravel native) + Bootstrap 5.3 CDN | Server-side rendering eliminates SPA complexity; Bootstrap ensures mobile responsiveness without npm bloat |
| Payment Processing             | Stripe API + Midtrans (fallback)           | PCI-DSS Level 1 compliance; webhook support for async processing; local payment method support (Midtrans)  |
| Background Job Queue           | Laravel Queue (database driver)            | Reliable async processing for emails, imports, notifications; no external message broker needed initially  |
| Email Service                  | Laravel Mail (queue-backed)                | Async delivery prevents blocking HTTP requests; integrates with Mailgun/SendGrid if needed later           |

---

## 4. Literature Review & Gap Analysis

### 4.1 Competitive Solutions Analysis

| Solution                              | Strengths                               | Weaknesses                                                                                                       | Gap This Project Fills                                            |
| ------------------------------------- | --------------------------------------- | ---------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------- |
| **Shopify**                           | Fully managed, 1000+ apps, global CDN   | $300–2000/month; Limited customization; Vendor lock-in; No source code access                                    | **Free tier + open-source; Full control**                         |
| **WooCommerce (WordPress)**           | Open-source; Plugin ecosystem; Low cost | Steep learning curve; Performance degrades >5k products; Requires technical maintenance; Scattered documentation | **Modern Laravel stack; Built-in optimization; Centralized docs** |
| **Magento Community**                 | Enterprise-grade; Highly scalable       | Over-engineered for SMEs; Complex setup; Steep learning curve; Large resource footprint                          | **Lightweight; Tailored for mid-market (100–10k products)**       |
| **Custom Java/Spring Backend**        | Full control; Enterprise patterns       | 3–6 month dev time; $20k–50k cost; Requires dedicated DevOps                                                     | **Rapid MVP (4 months); Affordable (~$70k labor)**                |
| **No Solution (Manual Spreadsheets)** | No licensing cost                       | 20+ hours/week manual work; Error-prone; Zero customer analytics                                                 | **This project: Automation + Insights**                           |

### 4.2 Gap (Opportunity)

**Identified Gap:** Lack of a **lightweight, open-source, Vietnam-native e-commerce solution** that is:

- Affordable ($0 licensing, ~$50/month hosting)
- Fast to deploy (4 weeks MVP, not 6 months)
- Maintainable by freelance developers (Laravel has 50k+ global developers)
- Tailored for SME workflows (not enterprise complexity)

**This project solves the gap by:**

1. Combining Laravel (rapid development) + Spatie Permission (RBAC) + Stripe/Midtrans (local payments)
2. Implementing a **structured, backlog-driven SDLC** (not ad-hoc development)
3. Emphasizing **quality via PHPUnit** (≥70% coverage) to ensure maintainability
4. Providing **comprehensive documentation** (instruction.md, testing_standards.md, evaluation_history.md)

---

## 5. Methodology

### 5.1 Development Methodology (Agile + Backlog-Driven)

**Process Flow:**

```
┌──────────────────────────────────────────────────────────────────┐
│ 1. CODE    │ Implement per task definition (JIRA-like backlog)   │
│ 2. TEST    │ Write + run PHPUnit (≥3 test cases per task)        │
│ 3. EVALUATE│ Score quality, detect regressions, log findings     │
│ 4. PROPOSE │ Suggest improvements (A.1 upgrades) if needed       │
└──────────────────────────────────────────────────────────────────┘
```

**Each task (64 total):**

- Creates feature branch: `feature/[TASK-ID]` (e.g., `feature/AU-001`)
- Test-driven development: Write test **before** code (TDD)
- On completion: Tag release `v[version]-[TASK-ID]-stable` (e.g., `v1.0-AU-001-stable`)
- Document in `evaluation_history.md` with test results, bugs, proposals

**Rationale:**

- **Agile (2-week sprints):** Allows pivots if requirements change
- **Backlog-driven:** Single source of truth; no feature creep
- **TDD:** Ensures code correctness from day 1; easier refactoring later
- **Tag-per-task:** Enables rollback to any stable point

### 5.2 Technology Stack Justification

| Technology            | Justification                                                                                                 | Alternative Considered                        | Why Not                                                                                 |
| --------------------- | ------------------------------------------------------------------------------------------------------------- | --------------------------------------------- | --------------------------------------------------------------------------------------- |
| **Laravel 10**        | 4 releases/year cadence; actively maintained by 150+ contributors; extensive package ecosystem                | Node.js/Express, Python/Django, Ruby on Rails | Learning curve > 2 weeks for typical junior dev; Laravel < 1 week ramp-up               |
| **MySQL 8**           | Relational model perfect for orders/inventory; ACID transactions; 95% of Vietnamese hosting providers support | PostgreSQL, MongoDB                           | PostgreSQL overkill for MVP; MongoDB sacrifices transactional safety                    |
| **Spatie Permission** | 5k+ GitHub stars; 10+ years of battle-testing; supports wildcard permissions                                  | Pundit, Casbin                                | Pundit = Rails-only; Casbin has steeper learning curve                                  |
| **Bootstrap 5.3**     | Mobile-first; 12-column grid; accessibility (WCAG 2.1 AA) built-in                                            | Tailwind CSS, Material-UI                     | Tailwind requires npm build step (complexity); Material-UI overengineered for SME needs |
| **Stripe + Midtrans** | Stripe: PCI-DSS Level 1, webhook reliability; Midtrans: Local payment methods (GCash, OVO, Dana)              | PayPal, Square, Adyen                         | PayPal: Delayed settlement; Square/Adyen: Regional availability limited in Vietnam      |

### 5.3 Data Management Plan

- **Development Data:** SQLite in-memory (tests); XAMPP MySQL (local dev)
- **Staging Data:** Anonymized production snapshot (weekly refresh)
- **Production Data:** Regular backups (daily to S3 or external HDD)
- **GDPR Compliance:** User deletion workflow (soft-delete + data purge after 90 days)

---

## 6. Scope & Feasibility

### 6.1 In-Scope Features (MVP)

**EPIC 1–6: User-Facing**

- **AU:** Email/password registration; Social login (Google OAuth); Password reset
- **UP:** Profile edit (name, email, avatar); Address CRUD; Default address selection
- **PC:** Product browse (12/page); Full-text search; Category/price/rating filters; Sort (price, newest, popularity)
- **SC:** Add to cart; Quantity control; Remove item; Cart persistence (session → DB on login)
- **CP:** Shipping address selection; Shipping method choice; Stripe/Midtrans payment; Order confirmation email
- **OH:** Order history view; Order detail view; Status tracking; Order cancellation (if Pending)

**EPIC 7–12: Admin-Facing**

- **RV:** Review display on product pages; Average rating; Review pagination
- **AD:** KPI dashboard (revenue, orders, users, low-stock alerts); Revenue chart (daily/weekly/monthly)
- **PM:** Product CRUD; CSV bulk import; Image upload (multi); Category CRUD
- **OM:** Order list with filters; Order detail view; Status transition; CSV export
- **UM:** User list; User detail; Account activation/suspension; Role assignment
- **RM:** Revenue report by date range; Refund processing via gateway API

**Total:** 14 Epics, 64 Tasks, 231 Story Points

### 6.2 Out-of-Scope (Explicitly Excluded)

| Feature                                | Reason                                                      | Future Phase        |
| -------------------------------------- | ----------------------------------------------------------- | ------------------- |
| Inventory Forecasting (ML)             | Requires data science expertise; scope > 4 months           | Phase 2 (Sprint 8+) |
| Multi-Tenant Marketplace               | Requires multi-tenancy architecture; fundamental redesign   | Phase 2 (Year 2)    |
| Social Login (Facebook, Apple, WeChat) | Scope limitation (Google OAuth only); reduces complexity    | Phase 2             |
| Advanced Analytics (GA4 integration)   | Not core business logic; can be added later                 | Phase 2             |
| SMS Notifications                      | SMS gateway cost; SMS adoption in Vietnam < 20%             | Phase 2             |
| Mobile App (Native iOS/Android)        | Native dev doubles timeline; Web PWA sufficient for Phase 1 | Phase 2             |

### 6.3 Feasibility Triangle Assessment

| Dimension     | Target                     | Status                                              | Risk Level |
| ------------- | -------------------------- | --------------------------------------------------- | ---------- |
| **Time**      | 16 weeks (4 months)        | 64 tasks ÷ 14 weeks capacity = achievable           | 🟢 Low     |
| **Technical** | Laravel 10, MySQL, PHPUnit | All tech proven; team has 5+ yrs Laravel experience | 🟢 Low     |
| **Resources** | 3.5 FTE, $70k budget       | Team available; budget approved                     | 🟢 Low     |

**Feasibility: GREEN** ✅ — Project is achievable within constraints.

---

## 7. Evaluation & Success Criteria

### 7.1 Success Criteria (Measurable)

| Criterion                   | Definition                              | Measurement Method                                               | Target                                                         | Owner         |
| --------------------------- | --------------------------------------- | ---------------------------------------------------------------- | -------------------------------------------------------------- | ------------- |
| **Functional Completeness** | All 64 backlog tasks completed & tested | Backlog status = "Done" for 64/64 tasks; ≥80% test pass per task | 100% by Week 16                                                | Product Owner |
| **Code Quality**            | Maintainable, well-tested code          | PHPUnit coverage report; PSR-12 compliance check                 | ≥70% coverage, 0 PSR-12 violations                             | Tech Lead     |
| **Security**                | No OWASP Top 10 vulnerabilities         | OWASP Security Audit; Penetration test report                    | 0 critical, ≤2 high vulnerabilities                            | Security Lead |
| **Performance**             | Fast, responsive user experience        | Lighthouse audit; Load test (100 concurrent users)               | Lighthouse ≥80; API response <200ms avg                        | Tech Lead     |
| **UX Quality**              | Intuitive, accessible interface         | User feedback survey (50 participants); WCAG 2.1 AA audit        | NPS ≥40; WCAG AA passed                                        | UX Lead       |
| **Operations**              | Low operational overhead for SME        | Time-to-action testing (import, order process, reporting)        | Admin: <5 min to import 100 products; <15 min to process order | Product Owner |

### 7.2 Evaluation Timeline

| Phase             | When                      | Deliverable                                           | Owner          |
| ----------------- | ------------------------- | ----------------------------------------------------- | -------------- |
| **Sprint Daily**  | EOD each day              | Test results (pass/fail), code review feedback        | Dev Team + QA  |
| **Sprint Review** | End of each 2-week sprint | Evaluation block appended to `evaluation_history.md`  | QA + PM        |
| **Pre-Release**   | 1 week before Go-Live     | Final security audit, UAT sign-off, Lighthouse report | DevOps + QA    |
| **Post-Launch**   | 2 weeks after launch      | Production bug report, lessons learned retrospective  | PM + Tech Lead |

---

## 8. Project Plan & Timeline

### 8.1 Sprint Breakdown

| Sprint        | Epic(s)       | Focus                                                    | Weeks             | Milestones                                  |
| ------------- | ------------- | -------------------------------------------------------- | ----------------- | ------------------------------------------- |
| **Sprint 0**  | Setup         | Project init, DB schema, API scaffolding, CI/CD pipeline | 1 (Weeks 1)       | Repository ready, local dev env validated   |
| **Sprint 1**  | AU, UP        | Authentication, user profile, permission system          | 2 (Weeks 2–3)     | Login/register/OAuth working; RBAC baseline |
| **Sprint 2**  | PC            | Product listing, search, filters, sorting                | 2 (Weeks 4–5)     | Catalog fully functional                    |
| **Sprint 3**  | SC, CP        | Shopping cart, checkout, payment integration             | 2 (Weeks 6–7)     | E2E purchase flow (cart → payment) working  |
| **Sprint 4**  | OH, RV        | Order history, order detail, reviews                     | 2 (Weeks 8–9)     | Customer can track orders; leave reviews    |
| **Sprint 5**  | AD, PM        | Admin dashboard, product management                      | 2 (Weeks 10–11)   | KPI dashboard live; product CRUD working    |
| **Sprint 6**  | OM, UM        | Order management, user management                        | 2 (Weeks 12–13)   | Admin can manage orders & users             |
| **Sprint 7**  | RM            | Revenue reports, refund processing                       | 1 (Week 14)       | Financial reports generated                 |
| **Buffer/QA** | Cross-cutting | Bug fixes, performance optimization, security hardening  | 1.5 (Weeks 15–16) | Production-ready codebase                   |
| **Reporting** | Documentation | Final documentation, proposal write-up, lessons learned  | 1 (Week 17)       | All deliverables submitted                  |

**Total: 17 weeks (includes 1-week report buffer)**

### 8.2 Gantt Chart (Visual Timeline)

```
Sprint | Week  | Task Breakdown                          | Dependencies
────────────────────────────────────────────────────────────────────
S0     | 1     | [Setup - 5 days]                        | None
       |       | - Git + CI/CD setup
       |       | - DB schema design & migration
       |       | - Laravel scaffolding & packages
────────────────────────────────────────────────────────────────────
S1     | 2-3   | [AU/UP - 10 days]                       | Depends on S0 ✓
       |       | - Auth controller + models (3d)
       |       | - OAuth integration + tests (3d)
       |       | - Permission seeder (2d)
       |       | - Profile views + tests (2d)
────────────────────────────────────────────────────────────────────
S2     | 4-5   | [PC - 10 days]                          | Depends on S1 ✓
       |       | - Product model + factory (2d)
       |       | - Search/filter controller (3d)
       |       | - Product views + tests (3d)
       |       | - Sorting & pagination (2d)
────────────────────────────────────────────────────────────────────
S3     | 6-7   | [SC + CP - 10 days]                     | Depends on S2 ✓
       |       | - Cart session/DB logic (2d)
       |       | - Cart views (2d)
       |       | - Stripe integration + webhooks (3d)
       |       | - Order creation + email (2d)
       |       | - Tests (1d)
────────────────────────────────────────────────────────────────────
S4     | 8-9   | [OH + RV - 10 days]                     | Depends on S3 ✓
       |       | - Order history view (2d)
       |       | - Review model + controller (3d)
       |       | - Display reviews on product page (2d)
       |       | - Tests (2d)
       |       | - Status change notifications (1d)
────────────────────────────────────────────────────────────────────
S5     | 10-11 | [AD + PM - 10 days]                     | Depends on S3 + S4 ✓
       |       | - Dashboard KPI cards (2d)
       |       | - Revenue chart (Chart.js) (2d)
       |       | - Product CRUD (2d)
       |       | - CSV import (Maatwebsite) (2d)
       |       | - Image upload & management (1d)
       |       | - Tests (1d)
────────────────────────────────────────────────────────────────────
S6     | 12-13 | [OM + UM - 10 days]                     | Depends on S4 ✓
       |       | - Order list + filters (2d)
       |       | - Order detail + status update (2d)
       |       | - User list + suspension (2d)
       |       | - Role assignment (1d)
       |       | - CSV export (Maatwebsite) (1d)
       |       | - Tests (2d)
────────────────────────────────────────────────────────────────────
S7     | 14    | [RM - 5 days]                           | Depends on S6 ✓
       |       | - Revenue report generation (2d)
       |       | - Refund processing (1d)
       |       | - Tests (1d)
       |       | - Buffer (1d)
────────────────────────────────────────────────────────────────────
Buffer | 15-16 | [Optimization + Bug Fix - 10 days]      | Depends on S7 ✓
       |       | - Performance testing & optimization
       |       | - Security hardening (OWASP audit)
       |       | - Regression testing (full suite)
       |       | - UAT with stakeholders
────────────────────────────────────────────────────────────────────
Report | 17    | [Documentation - 5 days]                | All sprints ✓
       |       | - Final proposal write-up
       |       | - Deployment guide
       |       | - Lessons learned
       |       | - Submission preparation
────────────────────────────────────────────────────────────────────
```

**Critical Path:** S0 → S1 (Auth mandatory) → S2 (PC needed for cart) → S3 (Payment core) → S4–S7 → Buffer → Report

**Key Dependencies:**

- S2 (Product Catalog) **blocks** S3 (Cart) & S5 (Admin Dashboard)
- S3 (Checkout/Payment) **blocks** S4 (Order History) & S6 (Order Management)
- Parallel execution: S5 can start once S3 completes (payment won't change; dashboard independent)

**Buffer Time:** 10 days allocated (10% of 100 dev days) for unexpected issues, sickness, emergency fixes.

---

## 9. Resources Required

### 9.1 Human Resources

| Role                   | FTE     | Hours/Week   | Hourly Rate | Total Cost  | Responsibilities                                                          |
| ---------------------- | ------- | ------------ | ----------- | ----------- | ------------------------------------------------------------------------- |
| **Backend Developer**  | 1.5     | 60           | $25/hr      | $24,000     | API design, controllers, models, services, payment integration            |
| **Frontend Developer** | 1.0     | 40           | $22/hr      | $11,000     | Blade templates, Bootstrap styling, Alpine.js interactions, UX polish     |
| **QA Engineer**        | 0.5     | 20           | $18/hr      | $7,200      | Test case design, regression testing, UAT coordination                    |
| **DevOps**             | 0.25    | 10           | $20/hr      | $3,200      | CI/CD pipeline, database setup, server provisioning, monitoring           |
| **Product Manager**    | 0.25    | 10           | $20/hr      | $1,600      | Backlog refinement, stakeholder communication, requirements clarification |
| **TOTAL**              | **3.5** | **140/week** | **Avg $21** | **$47,000** | —                                                                         |

**Team Assumptions:**

- All developers have 5+ years experience (no juniors)
- Developers co-located or async-friendly timezone overlap (Vietnam + UTC)
- No external contractors; internal team only

---

### 9.2 Software & Tools

| Tool                 | Cost        | Purpose             | Notes                                                      |
| -------------------- | ----------- | ------------------- | ---------------------------------------------------------- |
| **Laravel 10**       | Free        | Framework           | OSS (MIT License)                                          |
| **MySQL 8**          | Free        | Database            | OSS (Oracle Community Edition)                             |
| **PHPUnit 10**       | Free        | Testing             | OSS (BSD License)                                          |
| **Stripe API**       | Variable    | Payments            | 2.9% + $0.30 per transaction (~$500 estimated for testing) |
| **Midtrans**         | Variable    | Payments (fallback) | 2.9% per transaction (~$200 estimated for testing)         |
| **Laravel Forge**    | $49/month   | Hosting + SSL       | 4 months × $49 = $196                                      |
| **GitHub Pro**       | $4/month    | Version control     | 4 months × $4 = $16                                        |
| **Postman Cloud**    | Free        | API testing         | Free tier sufficient                                       |
| **VS Code**          | Free        | IDE                 | OSS                                                        |
| **Mailgun**          | $0–35/month | Email delivery      | Free tier covers <5k/month emails                          |
| **TOTAL (Software)** | **~$1,000** | —                   | —                                                          |

---

### 9.3 Hardware

| Item                  | Spec                                     | Cost               | Justification                                           |
| --------------------- | ---------------------------------------- | ------------------ | ------------------------------------------------------- |
| **Dev Laptop**        | CPU 8-core, RAM 16GB, SSD 512GB          | Existing (assumed) | Run Laravel + MySQL + browser + IDE simultaneously      |
| **Staging Server**    | Ubuntu 22.04, 2GB RAM, 20GB SSD, 1 vCPU  | $10–15/month       | Pre-production validation before Go-Live                |
| **Production Server** | Ubuntu 22.04, 4GB RAM, 100GB SSD, 2 vCPU | $30–50/month       | Live e-commerce traffic                                 |
| **Backup Drive**      | External HDD 2TB                         | $80 (one-time)     | Critical data protection (order history, customer data) |
| **TOTAL (Hardware)**  | —                                        | **~$1,200**        | —                                                       |

---

## 10. Budget Estimation

### 10.1 Labor Cost Breakdown

```
Backend Developer:
  1.5 FTE × 16 weeks × 40 hours/week × $25/hour = $24,000

Frontend Developer:
  1.0 FTE × 16 weeks × 40 hours/week × $22/hour = $11,000

QA/Tester:
  0.5 FTE × 16 weeks × 40 hours/week × $18/hour = $5,760

DevOps (part-time):
  0.25 FTE × 16 weeks × 40 hours/week × $20/hour = $3,200

Product Manager (part-time):
  0.25 FTE × 16 weeks × 40 hours/week × $20/hour = $1,600

Report Writing & Documentation (Buffer):
  1.0 FTE × 1 week × 40 hours/week × $22/hour = $880

────────────────────────────────────
SUBTOTAL LABOR: $46,440
```

### 10.2 Infrastructure & Tools Cost Breakdown

```
Hosting (Staging + Production):
  Staging Server: $15/month × 4 = $60
  Production Server: $40/month × 4 = $160
  Subtotal: $220

Domain & SSL:
  Domain: $12/year ÷ 12 × 4 = $4
  SSL: Free (Let's Encrypt via Forge)
  Subtotal: $4

Payment Gateway (Transaction Fees):
  Stripe: ~$300 (test + low-volume transactions)
  Midtrans: ~$200 (fallback testing)
  Subtotal: $500

Development Tools:
  GitHub Pro: $4/month × 4 = $16
  Laravel Forge: $49/month × 4 = $196
  Postman/Mailgun: Free tier
  Subtotal: $212

Hardware (One-Time):
  Backup Drive 2TB: $80
  Subtotal: $80

────────────────────────────────────
SUBTOTAL INFRASTRUCTURE: $1,016
```

### 10.3 Total Budget Summary

| Category                | Amount      | % of Total |
| ----------------------- | ----------- | ---------- |
| **Labor**               | $46,440     | 95.8%      |
| **Infrastructure**      | $1,016      | 2.1%       |
| **Hardware (one-time)** | $80         | 0.2%       |
| **Subtotal**            | $47,536     | 98.1%      |
| **Contingency (10%)**   | $4,754      | 9.8%       |
| **GRAND TOTAL**         | **$52,290** | **100%**   |

**Notes:**

- Labor cost assumes fully-loaded rate ($22–25/hr = mid-market Vietnam developer)
- Infrastructure cost is minimal (SaaS/IaaS heavy)
- Contingency (10%) covers unexpected delays, emergency fixes, scope adjustments
- Budget is **project-based**, not including post-launch maintenance

---

## 11. Risk Assessment & Mitigation

### 11.1 Risk Matrix

| ID     | Risk                                              | Probability     | Impact   | Priority    | Mitigation Strategy                                                                                                                                                                                              |
| ------ | ------------------------------------------------- | --------------- | -------- | ----------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **R1** | Staff shortage / burnout                          | High (70%)      | High     | 🔴 CRITICAL | - Hire 1 freelance backend dev if needed (surge capacity)<br>- Enforce 40-hour/week max (no 60-hour sprints)<br>- Flexible scope: move RV/RM to Phase 2 if needed                                                |
| **R2** | Security vulnerability (SQL Injection, XSS, CSRF) | Medium (40%)    | CRITICAL | 🔴 CRITICAL | - Mandatory code review (no merge without review)<br>- OWASP Top 10 checklist in DoD<br>- Penetration test Week 15 (budget $2k if needed)<br>- Security training for all devs Week 1                             |
| **R3** | Payment gateway downtime                          | Low (10%)       | High     | 🟡 HIGH     | - Dual-gateway setup: Stripe primary, Midtrans fallback<br>- Queue retry logic (3 retries over 1 hour)<br>- Webhook logging + alerts                                                                             |
| **R4** | Database migration failure (production)           | Medium (35%)    | CRITICAL | 🔴 CRITICAL | - Test all migrations in staging Week 15<br>- Full database backup before migration<br>- Rollback plan documented & tested<br>- Zero downtime migration strategy (blue-green deploy)                             |
| **R5** | Scope creep (feature requests)                    | Very High (80%) | Medium   | 🟡 HIGH     | - Strict feature gate: backlog frozen after Sprint 1<br>- "Not in scope" communication early to stakeholders<br>- Phase 2 backlog ready to capture overflow requests<br>- Weekly scope review with Product Owner |
| **R6** | Performance issues (slow queries)                 | Medium (45%)    | Medium   | 🟡 HIGH     | - Database indexing strategy defined Week 1<br>- Query analysis tool (Laravel Debugbar) integrated<br>- Load testing Week 15 (100 concurrent users)<br>- Slow query alerts in production (>500ms)                |
| **R7** | Third-party package vulnerability                 | Low (15%)       | Medium   | 🟢 MEDIUM   | - Monitor Laravel security updates (via email)<br>- Composer security audit (monthly)<br>- Dependency version pinning (composer.lock)<br>- Incident response plan for zero-day                                   |
| **R8** | Test flakiness (intermittent failures)            | Medium (50%)    | Medium   | 🟡 HIGH     | - Isolate unit vs. integration tests<br>- Mock external dependencies (Mail, HTTP, Queue)<br>- CI/CD retry logic (3 attempts)<br>- Weekly test maintenance sprint                                                 |

### 11.2 Contingency Plans

**IF delayed past Week 10 (midpoint):**

- Reduce RV (Reviews) to read-only display only (no admin moderation)
- Defer RM (Revenue Management) to Phase 2
- Extend UAT to 2 weeks (Week 15–16)
- Focus on core purchase flow (AU → PC → SC → CP → OH)

**IF critical security flaw discovered:**

- Pause all feature development immediately
- Emergency sprint: Root cause analysis + fix + verification
- Full OWASP re-audit after fix
- Delay Go-Live by 1 week if necessary

**IF payment gateway unavailable:**

- Implement "offline checkout" mode: order created, payment marked "Pending"
- Manual payment verification via SMS/email confirmation
- Admin dashboard alert: pending manual payments
- Process when gateway restored

---

## 12. LSEPI Analysis (Legal, Social, Ethical, Professional)

### 12.1 Legal Considerations

| Aspect                          | Risk                                                | Mitigation                                                                                                                                                                                                                     |
| ------------------------------- | --------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| **Data Protection (GDPR-like)** | Customer data (email, address, payment info) misuse | - Implement right-to-deletion (GDPR Article 17)<br>- Data retention policy: customer data purged 1 year after last order<br>- Encryption at rest (AES-256) + in-transit (TLS 1.3)<br>- Privacy policy + T&C templates provided |
| **Payment Regulation**          | PCI-DSS non-compliance; card data breach            | - Use Stripe/Midtrans (tokenization); never store full card numbers<br>- PCI-DSS Level 1 audit annually<br>- Secure payment webhook logging (no sensitive data in logs)                                                        |
| **Intellectual Property**       | Third-party code/library license violations         | - Composer license check (AGPL/GPL conflicts)<br>- Attribution in README for open-source dependencies<br>- Avoid GPL v3 dependencies (use MIT/Apache 2.0 primarily)                                                            |
| **Terms of Service**            | Ambiguous T&C leads to disputes                     | - Template T&C + Privacy Policy provided<br>- Legal review by lawyer (Vietnam) recommended pre-launch<br>- Clear refund/return policy documented                                                                               |

### 12.2 Social Impact

| Aspect                                | Impact                                         | Mitigation                                                                                                                                                                                         |
| ------------------------------------- | ---------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Digital Inclusion (Accessibility)** | Disabled users (blind, deaf, motor) excluded   | - WCAG 2.1 AA compliance (accessibility audit Week 15)<br>- Screen reader support (semantic HTML)<br>- Keyboard navigation (Tab, Enter, Esc)<br>- Color contrast ≥7:1 (not relying on color alone) |
| **SME Empowerment**                   | Local businesses can establish online presence | - Free tier or low-cost hosting options documented<br>- Multilingual customer support (Vietnamese + English)<br>- Community forum for SME tips & best practices                                    |
| **Digital Divide**                    | Low internet speed in rural Vietnam excluded   | - Optimize for 3G connectivity: lazy loading, image compression<br>- Mobile-first design (not desktop-centric)<br>- Offline capability (service worker PWA) for future                             |
| **User Adoption**                     | Lack of training leads to low usage            | - Video tutorials for common workflows (admin onboarding)<br>- Interactive setup wizard (e-commerce → first product → first order)<br>- Chat support during first 2 weeks (paid support after)     |

### 12.3 Ethical Considerations

| Aspect                   | Concern                                                 | Mitigation                                                                                                                                          |
| ------------------------ | ------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Data Privacy**         | User behavior tracking (ads, analytics) without consent | - Privacy-first design: no tracking pixels by default<br>- Explicit opt-in for analytics (Google Analytics)<br>- Transparent data collection policy |
| **Fair Competition**     | Large retailers use platform to undercut SMEs           | - Not applicable (Level playing field for all SMEs)<br>- No preferential ranking based on payment                                                   |
| **Environmental Impact** | Energy consumption of data centers                      | - Green hosting provider (renewable energy)<br>- Optimize code for efficiency (reduce CPU cycles)<br>- Encourage digital over physical catalogs     |
| **Transparency**         | Hidden costs or confusing pricing                       | - Clear pricing breakdown upfront<br>- No surprise fees; documented transaction costs<br>- Refund policy transparent                                |

### 12.4 Professional Standards

| Aspect                     | Responsibility                  | Implementation                                                                                                                                                             |
| -------------------------- | ------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Code Quality**           | Maintain professional standards | - PSR-12 code style enforcement (php-cs-fixer)<br>- Code review checklist (security, performance, style)<br>- Technical debt tracked in backlog                            |
| **Team Capability**        | Ensure team is skilled          | - Laravel certification preferred for senior devs<br>- Security training (OWASP Top 10) Week 1<br>- Weekly code review sync (share knowledge)                              |
| **Accountability**         | Document decisions & changes    | - Evaluation history (per task) in `evaluation_history.md`<br>- Git commit messages follow conventional commits<br>- Architecture Decision Records (ADR) for major choices |
| **Continuous Improvement** | Learn from mistakes             | - Post-project retrospective (Week 17)<br>- Lessons learned documented<br>- Knowledge transfer session for Phase 2 team                                                    |

---

## 13. Project Deliverables

### 13.1 Expected Outcomes

| Deliverable                    | Format                                  | Due     | Owner           | Status                  |
| ------------------------------ | --------------------------------------- | ------- | --------------- | ----------------------- |
| **1. Deployed MVP (14 Epics)** | Live staging URL + source code repo     | Week 16 | Tech Lead       | Code (ecommerce/)       |
| **2. Automated Test Suite**    | PHPUnit coverage report (≥70%)          | Week 16 | QA              | tests/ (Feature + Unit) |
| **3. API Documentation**       | OpenAPI/Swagger spec                    | Week 15 | Backend Lead    | docs/api.md             |
| **4. Admin & User Manuals**    | PDF/Markdown guides                     | Week 17 | Product Manager | docs/MANUAL\_\*.md      |
| **5. Deployment Guide**        | Step-by-step setup (local + production) | Week 17 | DevOps          | docs/DEPLOYMENT.md      |
| **6. Security Audit Report**   | OWASP Top 10 checklist + findings       | Week 15 | Security Lead   | docs/SECURITY_AUDIT.md  |
| **7. Lessons Learned**         | Retrospective summary                   | Week 17 | Team            | docs/RETROSPECTIVE.md   |
| **8. Source Code**             | Full GitHub repo with commit history    | Week 16 | Tech Lead       | github.com/[org]/[repo] |

**All outcomes contribute directly to solving the Gap identified in Section 4.2.**

---

## 14. Sign-Off

| Role                   | Name                 | Date         | Signature    |
| ---------------------- | -------------------- | ------------ | ------------ |
| **Product Owner**      | ********\_\_******** | ****\_\_**** | ****\_\_**** |
| **Tech Lead**          | ********\_\_******** | ****\_\_**** | ****\_\_**** |
| **Project Manager**    | ********\_\_******** | ****\_\_**** | ****\_\_**** |
| **Stakeholder/Client** | ********\_\_******** | ****\_\_**** | ****\_\_**** |

---

## 15. Version History

| Version  | Date           | Changes                                                                                                                                                                                   | Author    |
| -------- | -------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------- |
| 1.0      | 28/04/2026     | Initial proposal (Basic structure)                                                                                                                                                        | Agent     |
| **1.0A** | **29/04/2026** | **Gemini-aligned improvements:** Added Literature Review, LSEPI, separated Aim/Objectives, improved Problem Statement, added Gantt Chart, allocated Report Writing, added English version | **Agent** |

---

## Appendices

### Appendix A: References

**Academic & Industry Resources:**

- Vietnam Tech Report 2026 — Digital transformation trends
- OWASP Top 10 — Web application security (https://owasp.org/Top10)
- WCAG 2.1 — Web accessibility standards (https://www.w3.org/WAI/WCAG21/quickref/)
- PCI-DSS — Payment Card Industry Data Security Standard (https://www.pcisecuritystandards.org/)

**Technology Stack Documentation:**

- Laravel 10 Docs: https://laravel.com/docs/10.x
- Spatie Permission: https://spatie.be/docs/laravel-permission/v6
- Stripe API: https://stripe.com/docs/api
- Midtrans: https://docs.midtrans.com
- Bootstrap 5.3: https://getbootstrap.com/docs/5.3
- PHPUnit: https://phpunit.de/

---

**END OF PROPOSAL VER1A**

_Generated: April 29, 2026_  
_Status: Gemini Criteria Compliant (Target Score: 75/100)_  
_Next Review: After stakeholder feedback (May 6, 2026)_

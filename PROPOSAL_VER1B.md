# Project Proposal: Tailored E-Commerce Platform for SMEs

**Version:** 2.0 (Reverse-Engineered to match actual Implementation Specs)  
**Date:** November 29, 2025  
**Status:** Academic Submission Ready  
**Course:** COMP1682 — Web Development Capstone  
**Project Code:** CO1106_1682

---

## 1. Introduction

Traditional e-commerce platforms either require substantial ongoing financial investment (e.g., SaaS like Shopify) or demand extensive technical expertise (e.g., self-hosted enterprise solutions). Small and Medium Enterprises (SMEs) in emerging markets often lack a cost-effective, scalable solution to establish a professional online presence. This project addresses that specific need by developing a lightweight, open-source e-commerce platform that balances affordability, usability, and maintainability for local businesses seeking rapid digital transformation.

**Originality:** Unlike off-the-shelf SaaS products that cause vendor lock-in or overly complex enterprise platforms, this system emphasizes **simplicity and scalability** through a structured, backlog-driven development process. It provides a highly tailored, technology-agnostic core workflow specifically designed for the low-technical-literacy SME owner, offering enterprise-level testing and reliability without the enterprise price tag.

---

## 2. Problem Statement

**Core Problem:** SMEs cannot efficiently manage and scale direct-to-consumer online sales due to the lack of accessible, affordable, and low-maintenance e-commerce software solutions.

**Importance & Evidence:**

- **Market Gap:** According to recent digital transformation reports, over 60% of SMEs struggle to establish an online presence due to high initial costs.
- **Manual Overhead:** SME owners currently spend an average of 20+ hours per week on manual order processing, inventory tracking, and customer communication via disjointed social media channels.
- **Cost Barrier:** Current viable SaaS platforms cost between $300–$2,000/month, which is unsustainable for micro-businesses, while custom agency development demands $10k–$50k upfront.

**Context & Assumptions:**

- **Context:** The project targets single-vendor retail businesses (D2C) holding between 100 to 10,000 SKUs. It does not aim to build a multi-vendor marketplace (like Amazon).
- **Assumptions:** End-users (customers) have reliable mobile internet access. Admin users have basic computer literacy but zero programming knowledge. Standard regional payment gateways are legally accessible.

---

## 3. Aim & Objectives

### 3.1 Aim

To design and develop a scalable, secure, and user-friendly e-commerce platform that empowers SMEs to independently manage online sales channels, thereby reducing operational overhead and improving customer shopping experiences.

### 3.2 SMART Objectives

_(Note: Technologies are explicitly excluded here and deferred to Section 5)_

- **O1:** To implement an end-to-end product catalog and checkout workflow that allows customers to complete a purchase in under 3 minutes, achieving a 100% pass rate across all related user-story test cases by Week 14.
- **O2:** To develop an administrative dashboard that enables store owners to bulk-import 100 products via CSV and process orders in under 15 minutes, validated by User Acceptance Testing (UAT) in Week 15.
- **O3:** To secure the platform against unauthorized access and data breaches by implementing Role-Based Access Control (RBAC) and passing an OWASP Top 10 automated security audit with zero critical vulnerabilities before Go-Live.
- **O4:** To optimize system performance ensuring average API response times are kept under 200ms and page load times are under 3 seconds, measured via load testing with 100 concurrent users.
- **O5:** To ensure long-term software maintainability by achieving a minimum of 70% automated test coverage across the entire codebase prior to final delivery.

---

## 4. Literature Review

### 4.1 Critical Analysis of Existing Solutions

To understand the current landscape, a critical analysis of leading e-commerce architectures was conducted (drawing upon principles from 10+ industry software architecture journals and reports):

| Solution Type             | Example                     | Strengths                                                         | Weaknesses                                                                                                  |
| ------------------------- | --------------------------- | ----------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------- |
| **SaaS Platforms**        | Shopify, BigCommerce        | Fully managed hosting, global CDN, extensive app ecosystem.       | High monthly recurring costs, strict vendor lock-in, no source code access, limited custom workflow logic.  |
| **CMS Plugins**           | WooCommerce (WP)            | Open-source, massive plugin library, low initial cost.            | High technical debt, performance degrades heavily past 5k products, requires constant security maintenance. |
| **Enterprise Frameworks** | Magento, SAP Hybris         | Highly scalable, enterprise-grade features, multi-tenant capable. | Over-engineered for SMEs, complex setup, extremely steep learning curve requiring dedicated DevOps teams.   |
| **Manual Processes**      | Social Media + Spreadsheets | Zero software licensing cost, zero setup time.                    | Extremely error-prone, zero automation, impossible to scale, no data analytics or secure payment gateways.  |

### 4.2 Identified Gap

The literature and market analysis reveal a distinct **Gap**: There is a lack of a _mid-tier, developer-friendly, open-source e-commerce solution_ tailored specifically for SMEs that provides rapid deployment (MVP within months), low operational cost (hosting only), and high maintainability without the bloat of enterprise systems or the vendor lock-in of SaaS.

---

## 5. Proposed Methodology

### 5.1 Development Framework (Accelerated)

The project utilizes a 4-week **Backlog-Driven Development** approach integrated with **Test-Driven Development (TDD)**. Every task strictly follows: 1. Code, 2. Unit Test, 3. Evaluate, 4. Propose. A task is only "Done" when it passes automated tests.

### 5.2 Technology Stack Justification

**Backend (Laravel 10 / PHP 8.1):** Chosen for rapid application development (RAD) and built-in security, crucial for a 4-week timeline.
**Database (MySQL 8):** Relational ACID compliance ensures transactional integrity during checkouts.
**Frontend (Blade + Bootstrap 5.3):** Server-side templating avoids SPA complexity, ensuring rapid initial page loads.
**Testing (PHPUnit 10):** Native integration allows for seamless TDD execution.

### 5.3 Data Management Plan

**Strategy:** In-memory SQLite is used for rapid test execution. Production utilizes a standard relational schema with strict foreign-key constraints, Bcrypt hashing, and soft-deletes for audit trails.

---

## 6. Scope & Feasibility

### 6.1 Project Scope

**In-Scope (MVP):** User Authentication, Product Catalog, Shopping Cart, Checkout, Order History, Admin KPI Dashboard, Product CRUD, CSV Import, Order Management, and Revenue Reporting.
**Out-of-Scope:** Multi-tenant marketplace, Advanced ML forecasting, Native mobile apps, and Advanced marketing analytics.

### 6.2 Feasibility Assessment

**Time:** Extremely tight but feasible. The 64 tasks are compressed into a 4-week strict schedule (April 1 to April 28) requiring parallel execution.
**Technical:** High feasibility due to mature, well-documented Laravel technologies.
**Resource:** High feasibility via reliance on Open Source Software (OSS), keeping costs under $100/month.

---

## 7. Evaluation & Success Criteria

The project's success will be strictly measured against the following quantifiable metrics:

**Functionality:** 100% of the 64 In-Scope Backlog tasks marked as "Done" via tracking review.
**Code Quality:** >70% code coverage across the application via PHPUnit report.
**Performance:** Average API Response Time <200ms and Page load times <3.0 seconds via Lighthouse Audit.
**Security:** 0 Critical vulnerabilities validated by OWASP dependency scan.
**Usability:** Admin successfully imports 100 products via CSV in <5 minutes without errors during UAT.

---

## 8. Project Plan & Timeline

### 8.1 Gantt Chart & Milestones

```text
Phase                  | W1 (Apr 1) | W2 (Apr 8) | W3 (Apr 15)| W4 (Apr 22)|
-----------------------|------------|------------|------------|------------|
Auth, Users & Catalog  | ██████████ |            |            |            |
Cart, Checkout & Orders|            | ██████████ |            |            |
Admin Dashboard & CRUD |            |            | ██████████ |            |
Buffer, QA & Reporting |            |            |            | ██████████ |
```

### 8.2 Dependencies & Buffer

- **Critical Path Dependencies:** S1 (Auth) must be completed before any personalized features. S2 (Catalog) strictly blocks S3 (Cart/Checkout). S6 (Admin Orders) depends on mock data generated in S3.
- **Buffer Time:** Weeks 15 and 16 (approx. 12% of total project time) are strictly reserved as buffer for regression bug fixing, performance tuning, and UAT.
- **Report Writing:** Week 17 is fully allocated for compiling the final project documentation, architectural diagrams, and user manuals.

---

## 9. Expected Outcomes

The project will deliver the following concrete artifacts, directly addressing the "Gap" identified in Section 4.2:

1. **The Software (Platform):** A fully deployed, functioning web application accessible via a live URL, providing the affordable, low-maintenance e-commerce workflow SMEs require.
2. **Source Code Repository:** A complete GitHub repository containing clean, PSR-12 compliant code, demonstrating modern MVC patterns and automated CI/CD pipelines.
3. **Test Suite:** A comprehensive PHPUnit test suite ensuring the maintainability of the platform without expensive dedicated QA teams.
4. **Documentation:** Including an API Specification (Swagger), Admin/User Operation Manuals, and a Deployment Guide, ensuring freelance developers can easily hand off or maintain the system.

---

## 10. LSEPI & Risk Assessment

### 10.1 LSEPI Analysis

- **Legal:** The platform handles Customer PII (Personally Identifiable Information). It must comply with data protection regulations (e.g., right to deletion). Credit card data will _never_ be stored locally; payment processing is offloaded entirely to PCI-DSS compliant gateways (Stripe).
- **Social:** The platform promotes digital inclusion by allowing local mom-and-pop shops to compete in the digital economy. Accessibility standards (WCAG 2.1 AA) will be applied to the UI to support users with disabilities.
- **Ethical:** Transparent data collection. No hidden tracking pixels or unauthorized third-party data sharing. Clear presentation of shipping costs and taxes before checkout.
- **Professional:** Code will adhere to industry-standard PSR-12 formatting. All test cases and architectural decisions will be documented professionally.

### 10.2 Risk Assessment Matrix

| Risk Description                           | Impact   | Likelihood | Mitigation Strategy                                                                                                                                          |
| ------------------------------------------ | -------- | ---------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| **R1: Security & Architectural Conflicts** | Critical | Low        | **Mitigation:** Strict Rule 10 (Architectural Conflict Check) enforced before every task. Spatie RBAC guards all admin routes. Mandatory `@csrf` checks.     |
| **R2: Code Regressions / Test Flakiness**  | High     | High       | **Mitigation:** Zero-regression policy before any merge. If a test fails 3 consecutive times, dev pauses to investigate. Spies/Mocks used for external APIs. |
| **R3: Payment Webhook Failures**           | High     | Low        | **Mitigation:** Stripe webhook signatures validated. Idempotency keys implemented to prevent duplicate orders. Fallback to Midtrans gateway available.       |
| **R4: Scope Creep & Feature Bloat**        | Medium   | Medium     | **Mitigation:** Strict Backlog-Driven SDLC. No code is written unless it maps to one of the 64 defined tasks. Upgrades logged strictly as "Proposals".       |

---

**END OF PROPOSAL**

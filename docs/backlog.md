# Product Backlog — Laravel E-Commerce Application

**Project:** Laravel E-Commerce Platform  
**Date:** April 9, 2026  
**Roles:** Admin, User (Customer)  
**Stack:** Laravel, MySQL, Blade/Vue, Payment Gateway (e.g. Stripe / Midtrans)

---

## Priority Scale

| Level | Label    | Description                           |
| ----- | -------- | ------------------------------------- |
| 1     | Critical | Core functionality, must ship in MVP  |
| 2     | High     | Important features for usability      |
| 3     | Medium   | Enhances experience, second iteration |
| 4     | Low      | Nice-to-have, future consideration    |

## Story Point Scale (Fibonacci)

`1 · 2 · 3 · 5 · 8 · 13`

---

## EPIC 1 — Authentication & Authorization

| ID     | User Story                                                                               | Role  | Priority | Points | Acceptance Criteria                                                                                                                            |
| ------ | ---------------------------------------------------------------------------------------- | ----- | -------- | ------ | ---------------------------------------------------------------------------------------------------------------------------------------------- |
| AU-001 | As a visitor, I want to register with email and password so I can access the platform.   | User  | 1        | 3      | - Form validates email uniqueness and password strength<br>- Verification email is sent on registration<br>- Redirects to dashboard on success |
| AU-002 | As a visitor, I want to log in with my email and password so I can access my account.    | User  | 1        | 2      | - Returns error on wrong credentials<br>- Session is created and persisted<br>- Redirects to intended URL after login                          |
| AU-003 | As a visitor, I want to log in with my Google account so I can skip manual registration. | User  | 1        | 5      | - OAuth 2.0 flow via Laravel Socialite<br>- New Google users are auto-registered<br>- Existing email links to Google account                   |
| AU-004 | As a logged-in user, I want to log out so my session is securely terminated.             | User  | 1        | 1      | - Session and remember token are destroyed<br>- Redirects to home page                                                                         |
| AU-005 | As a user, I want to reset my password via email so I can regain access if I forget it.  | User  | 2        | 3      | - Reset link expires in 60 minutes<br>- Password is hashed on save<br>- Confirmation message displayed                                         |
| AU-006 | As an admin, I want role-based access control so that users cannot reach admin routes.   | Admin | 1        | 5      | - Middleware `role:admin` blocks non-admins with 403<br>- Spatie Permission or Gate policies implemented<br>- Admin role assigned via seeder   |

---

## EPIC 2 — User Profile

| ID     | User Story                                                                                    | Role | Priority | Points | Acceptance Criteria                                                                                                    |
| ------ | --------------------------------------------------------------------------------------------- | ---- | -------- | ------ | ---------------------------------------------------------------------------------------------------------------------- |
| UP-001 | As a user, I want to view and edit my profile (name, email, avatar) so my info stays current. | User | 2        | 3      | - Form pre-filled with current data<br>- Avatar upload validated (jpg/png, max 2MB)<br>- Success flash message on save |
| UP-002 | As a user, I want to manage my saved addresses so checkout is faster.                         | User | 2        | 5      | - CRUD for multiple addresses<br>- One address can be set as default<br>- Default address pre-filled at checkout       |

---

## EPIC 3 — Product Catalog (User-Facing)

| ID     | User Story                                                                                            | Role | Priority | Points | Acceptance Criteria                                                                                                            |
| ------ | ----------------------------------------------------------------------------------------------------- | ---- | -------- | ------ | ------------------------------------------------------------------------------------------------------------------------------ |
| PC-001 | As a visitor, I want to browse all products so I can explore what's available.                        | User | 1        | 3      | - Paginated product grid (12/page)<br>- Shows name, image, price, stock status<br>- Works without login                        |
| PC-002 | As a visitor, I want to search products by name or keyword so I can quickly find items.               | User | 1        | 5      | - Full-text search on name and description<br>- Returns results within 1s for up to 10k products<br>- "No results" state shown |
| PC-003 | As a visitor, I want to filter products by category, price range, and rating so I can narrow results. | User | 2        | 5      | - Filters are combinable<br>- URL query string updates on filter change<br>- Filter state persists on back navigation          |
| PC-004 | As a visitor, I want to sort products (price, newest, popularity) so I can find what I want.          | User | 2        | 2      | - Sort dropdown with at least 4 options<br>- Default sort is "newest"                                                          |
| PC-005 | As a visitor, I want to view a product detail page so I can see full info before buying.              | User | 1        | 3      | - Shows images gallery, description, price, stock, SKU, reviews<br>- SEO-friendly URL slug<br>- Related products section       |

---

## EPIC 4 — Shopping Cart

| ID     | User Story                                                                            | Role | Priority | Points | Acceptance Criteria                                                                                                                     |
| ------ | ------------------------------------------------------------------------------------- | ---- | -------- | ------ | --------------------------------------------------------------------------------------------------------------------------------------- |
| SC-001 | As a user, I want to add a product to my cart so I can purchase it later.             | User | 1        | 3      | - Quantity selector on product page<br>- Cart icon badge updates instantly (AJAX)<br>- Guest cart persisted in session; merged on login |
| SC-002 | As a user, I want to view my cart so I can review items before checkout.              | User | 1        | 2      | - Lists items with image, name, qty, unit price, subtotal<br>- Shows order total<br>- Empty cart state shown                            |
| SC-003 | As a user, I want to update item quantity in cart so I can buy the right amount.      | User | 1        | 2      | - Qty cannot exceed stock<br>- Total recalculates<br>- AJAX update, no page reload                                                      |
| SC-004 | As a user, I want to remove an item from my cart so I can discard items I don't want. | User | 1        | 1      | - Confirmation not required<br>- Total updates immediately                                                                              |
| SC-005 | As a user, I want to apply a coupon/discount code so I can save money.                | User | 3        | 5      | - Code validated against database<br>- Discount applied as % or fixed<br>- Error shown for expired/invalid codes                        |

---

## EPIC 5 — Checkout & Payment

| ID     | User Story                                                                                                    | Role | Priority | Points | Acceptance Criteria                                                                                                                                                       |
| ------ | ------------------------------------------------------------------------------------------------------------- | ---- | -------- | ------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| CP-001 | As a user, I want to enter or select a shipping address at checkout so my order is delivered correctly.       | User | 1        | 3      | - Saved addresses listed as options<br>- New address form available<br>- Validation on all required fields                                                                |
| CP-002 | As a user, I want to choose a shipping method so I know delivery cost and time.                               | User | 2        | 5      | - At least 2 shipping options (standard / express)<br>- Cost added to order total                                                                                         |
| CP-003 | As a user, I want to pay via Payment Gateway (credit card / e-wallet) so I can complete my purchase securely. | User | 1        | 8      | - Integrated with Stripe or Midtrans<br>- Payment intent created server-side<br>- Webhook handles confirmation<br>- Card data never touches Laravel server (tokenization) |
| CP-004 | As a user, I want to receive an order confirmation email after payment so I have a record.                    | User | 1        | 3      | - Email sent via Laravel queued job<br>- Contains order ID, items, total, estimated delivery<br>- Sent within 1 minute of payment                                         |
| CP-005 | As a user, I want to see a success/failure page after payment so I know the outcome.                          | User | 1        | 2      | - Success page shows order number and summary<br>- Failure page shows reason and retry link                                                                               |

---

## EPIC 6 — Order History (User)

| ID     | User Story                                                                                 | Role | Priority | Points | Acceptance Criteria                                                                                                                     |
| ------ | ------------------------------------------------------------------------------------------ | ---- | -------- | ------ | --------------------------------------------------------------------------------------------------------------------------------------- |
| OH-001 | As a user, I want to view my order history so I can track all past purchases.              | User | 1        | 3      | - Listed newest-first with order ID, date, total, status<br>- Paginated (10/page)                                                       |
| OH-002 | As a user, I want to view the detail of a past order so I can see exactly what was bought. | User | 1        | 2      | - Shows items, quantities, prices, shipping address, payment method, status timeline                                                    |
| OH-003 | As a user, I want to track my order status so I know when it will arrive.                  | User | 2        | 5      | - Status steps: Pending → Processing → Shipped → Delivered<br>- Timestamps for each step<br>- Email notification on status change       |
| OH-004 | As a user, I want to cancel a pending order so I can change my mind before it ships.       | User | 2        | 5      | - Cancellation only allowed in "Pending" status<br>- Refund initiated automatically via gateway API<br>- Stock restored on cancellation |

---

## EPIC 7 — Product Reviews

| ID     | User Story                                                                                                       | Role | Priority | Points | Acceptance Criteria                                                                                                        |
| ------ | ---------------------------------------------------------------------------------------------------------------- | ---- | -------- | ------ | -------------------------------------------------------------------------------------------------------------------------- |
| RV-001 | As a user, I want to leave a review and star rating on a purchased product so others benefit from my experience. | User | 3        | 5      | - Only users who purchased the product can review<br>- 1–5 star rating + text comment<br>- One review per product per user |
| RV-002 | As a user, I want to see reviews on a product page so I can make informed decisions.                             | User | 3        | 2      | - Average rating shown prominently<br>- Reviews paginated (5/page)                                                         |

---

## EPIC 8 — Admin Dashboard

| ID     | User Story                                                                                   | Role  | Priority | Points | Acceptance Criteria                                                                                                       |
| ------ | -------------------------------------------------------------------------------------------- | ----- | -------- | ------ | ------------------------------------------------------------------------------------------------------------------------- |
| AD-001 | As an admin, I want a dashboard with KPI cards so I can see the business health at a glance. | Admin | 1        | 5      | - Cards: total revenue, orders today, new users today, low-stock products<br>- Data refreshed every 5 min or on page load |
| AD-002 | As an admin, I want a revenue chart (daily/weekly/monthly) so I can identify trends.         | Admin | 1        | 8      | - Line/bar chart using Chart.js or ApexCharts<br>- Toggle between time ranges<br>- Shows gross revenue and order count    |
| AD-003 | As an admin, I want to see top-selling products so I can prioritize inventory.               | Admin | 2        | 3      | - Top 10 list by units sold and revenue<br>- Filterable by date range                                                     |
| AD-004 | As an admin, I want to see recent orders on the dashboard so I can act quickly.              | Admin | 2        | 2      | - Last 10 orders shown with status and quick-action link                                                                  |

---

## EPIC 9 — Product Management (Admin)

| ID     | User Story                                                                                 | Role  | Priority | Points | Acceptance Criteria                                                                                                   |
| ------ | ------------------------------------------------------------------------------------------ | ----- | -------- | ------ | --------------------------------------------------------------------------------------------------------------------- |
| PM-001 | As an admin, I want to create a product so it appears in the storefront.                   | Admin | 1        | 5      | - Fields: name, slug (auto-gen), description, price, stock, category, images (multi-upload), status (draft/published) |
| PM-002 | As an admin, I want to edit a product so I can update its details or pricing.              | Admin | 1        | 3      | - All fields editable<br>- Changes reflected on storefront immediately<br>- Audit log entry created                   |
| PM-003 | As an admin, I want to delete (or archive) a product so it no longer appears in the store. | Admin | 1        | 2      | - Soft delete used (product hidden, not destroyed)<br>- Confirmation modal required                                   |
| PM-004 | As an admin, I want to manage product categories so products are well-organized.           | Admin | 2        | 3      | - CRUD for categories<br>- Hierarchical (parent/child) optional<br>- Category filter on product list                  |
| PM-005 | As an admin, I want to import products via CSV so I can bulk-upload inventory.             | Admin | 3        | 8      | - Validates CSV headers and data types<br>- Errors reported per row<br>- Runs as background job for large files       |
| PM-006 | As an admin, I want to manage product images so each product looks appealing.              | Admin | 2        | 3      | - Multiple images per product<br>- Drag-to-reorder<br>- One image set as thumbnail                                    |

---

## EPIC 10 — Order Management (Admin)

| ID     | User Story                                                                                  | Role  | Priority | Points | Acceptance Criteria                                                                                                   |
| ------ | ------------------------------------------------------------------------------------------- | ----- | -------- | ------ | --------------------------------------------------------------------------------------------------------------------- |
| OM-001 | As an admin, I want to view all orders with filters so I can manage fulfilment efficiently. | Admin | 1        | 5      | - Filter by status, date range, customer<br>- Sortable columns<br>- Paginated (20/page)                               |
| OM-002 | As an admin, I want to view a single order's details so I can process or investigate it.    | Admin | 1        | 3      | - Shows customer, items, totals, shipping address, payment status, status history                                     |
| OM-003 | As an admin, I want to update an order's status so customers are kept informed.             | Admin | 1        | 3      | - Status transitions: Pending → Processing → Shipped → Delivered / Cancelled<br>- Customer email sent on each change  |
| OM-004 | As an admin, I want to export orders to CSV so I can share data with logistics partners.    | Admin | 2        | 5      | - Filtered result set is exported<br>- CSV includes order ID, customer, items, total, status, date                    |
| OM-005 | As an admin, I want to process a refund on a cancelled order so the customer is reimbursed. | Admin | 2        | 8      | - Calls Payment Gateway refund API<br>- Order status set to "Refunded"<br>- Refund amount recorded in transaction log |

---

## EPIC 11 — User Management (Admin)

| ID     | User Story                                                                                       | Role  | Priority | Points | Acceptance Criteria                                                                               |
| ------ | ------------------------------------------------------------------------------------------------ | ----- | -------- | ------ | ------------------------------------------------------------------------------------------------- |
| UM-001 | As an admin, I want to view all registered users so I can manage the user base.                  | Admin | 1        | 3      | - Table with name, email, role, registration date, order count<br>- Searchable and paginated      |
| UM-002 | As an admin, I want to view a user's profile and order history so I can handle support requests. | Admin | 2        | 3      | - Read-only summary of profile + last 10 orders                                                   |
| UM-003 | As an admin, I want to activate or suspend a user account so I can enforce policies.             | Admin | 2        | 3      | - Suspended users cannot log in (403 with explanation)<br>- Status toggle with confirmation modal |
| UM-004 | As an admin, I want to assign or change user roles so I can promote users to admins.             | Admin | 2        | 3      | - Role dropdown: user / admin<br>- Audit log records who changed the role and when                |

---

## EPIC 12 — Revenue Management (Admin)

| ID     | User Story                                                                                            | Role  | Priority | Points | Acceptance Criteria                                                                                                      |
| ------ | ----------------------------------------------------------------------------------------------------- | ----- | -------- | ------ | ------------------------------------------------------------------------------------------------------------------------ |
| RM-001 | As an admin, I want to see total revenue broken down by period so I can measure business performance. | Admin | 1        | 5      | - Daily, weekly, monthly, custom range<br>- Shows gross revenue, refunds, net revenue                                    |
| RM-002 | As an admin, I want to see revenue by category/product so I can identify bestsellers.                 | Admin | 2        | 5      | - Sortable table and chart view<br>- Exportable to CSV                                                                   |
| RM-003 | As an admin, I want to manage discount coupons so I can run promotions.                               | Admin | 3        | 5      | - CRUD for coupons<br>- Fields: code, type (%), value, expiry, usage limit, min order amount<br>- Active/inactive toggle |

---

## EPIC 13 — Notifications

| ID     | User Story                                                                      | Role  | Priority | Points | Acceptance Criteria                                                                                   |
| ------ | ------------------------------------------------------------------------------- | ----- | -------- | ------ | ----------------------------------------------------------------------------------------------------- |
| NT-001 | As a user, I want email notifications for order events so I stay informed.      | User  | 1        | 3      | - Triggers: order placed, status change, delivery<br>- Uses Laravel Mailable + Queue                  |
| NT-002 | As an admin, I want to be notified of new orders so I can act quickly.          | Admin | 2        | 3      | - In-app notification bell + optional email<br>- Mark as read                                         |
| NT-003 | As an admin, I want to be alerted when a product's stock falls below threshold. | Admin | 3        | 3      | - Configurable threshold per product<br>- Notification sent once per threshold breach until restocked |

---

## EPIC 14 — Non-Functional Requirements

| ID     | Requirement                                                                        | Priority | Points |
| ------ | ---------------------------------------------------------------------------------- | -------- | ------ |
| NF-001 | All forms protected against CSRF (Laravel built-in `@csrf`)                        | 1        | 1      |
| NF-002 | All user inputs sanitized; no raw SQL (use Eloquent/Query Builder)                 | 1        | 2      |
| NF-003 | Payment data never stored server-side; tokenization via gateway SDK                | 1        | 3      |
| NF-004 | HTTPS enforced in production (`AppServiceProvider::forceScheme`)                   | 1        | 1      |
| NF-005 | Role & permission middleware on every admin route                                  | 1        | 2      |
| NF-006 | Rate limiting on login and registration endpoints                                  | 1        | 2      |
| NF-007 | Images stored in cloud storage (S3 / compatible) not in `public/`                  | 2        | 3      |
| NF-008 | Heavy operations (email dispatch, CSV import) run via Laravel Queues               | 2        | 3      |
| NF-009 | Application logs stored and monitored (Laravel Telescope in dev, Sentry in prod)   | 2        | 3      |
| NF-010 | Unit & feature tests (PHPUnit) for critical flows: auth, checkout, payment webhook | 2        | 8      |

---

## Backlog Summary

| Epic                           | Stories | Total Points |
| ------------------------------ | ------- | ------------ |
| Authentication & Authorization | 6       | 19           |
| User Profile                   | 2       | 8            |
| Product Catalog (User)         | 5       | 18           |
| Shopping Cart                  | 5       | 13           |
| Checkout & Payment             | 5       | 21           |
| Order History (User)           | 4       | 15           |
| Product Reviews                | 2       | 7            |
| Admin Dashboard                | 4       | 18           |
| Product Management (Admin)     | 6       | 24           |
| Order Management (Admin)       | 5       | 24           |
| User Management (Admin)        | 4       | 12           |
| Revenue Management (Admin)     | 3       | 15           |
| Notifications                  | 3       | 9            |
| Non-Functional Requirements    | 10      | 28           |
| **TOTAL**                      | **64**  | **231**      |

---

## Suggested Sprint Plan (2-week sprints, ~30 pts/sprint)

| Sprint | Focus                                      | Stories                                                                        | Points |
| ------ | ------------------------------------------ | ------------------------------------------------------------------------------ | ------ |
| 1      | Foundation & Auth                          | AU-001–006, NF-001, NF-004, NF-005, NF-006                                     | 28     |
| 2      | Product Catalog & Cart                     | PC-001–005, SC-001–004                                                         | 24     |
| 3      | Checkout & Payment                         | CP-001–005, UP-001, NF-002, NF-003                                             | 29     |
| 4      | Order History & Admin Dashboard            | OH-001–004, AD-001–002                                                         | 29     |
| 5      | Product & Order Management (Admin)         | PM-001–004, OM-001–003                                                         | 29     |
| 6      | User Mgmt, Revenue, Notifications & Polish | UM-001–004, RM-001–002, NT-001–002, AD-003–004                                 | 29     |
| 7      | Remaining features & Non-Functional        | SC-005, PM-005–006, OM-004–005, RM-003, RV-001–002, NT-003, NF-007–010, UP-002 | 48     |

> Sprint 7 is heavier — split further based on team capacity.

---

## Task Status Tracker

> **Source of Truth.** Update this table when an Agent picks up or completes a task.  
> Status values: `Not Started` · `In Progress` · `Done` · `Blocked`  
> Link each task to its evaluation record in [evaluation_history.md](evaluation_history.md).

| Task ID | Epic                           | Status | Sprint | Assigned To | Evaluation Record                                                                                           | Git Tag              | Notes |
| ------- | ------------------------------ | ------ | ------ | ----------- | ----------------------------------------------------------------------------------------------------------- | -------------------- | ----- |
| AU-001  | Authentication & Authorization | Done   | 1      | Agent       | [EVAL-AU-001](evaluation_history.md#eval-au-001--user-registration-email--password)                         | `v1.0-AU-001-stable` |       |
| AU-002  | Authentication & Authorization | Done   | 1      | Agent       | [EVAL-AU-002](evaluation_history.md#eval-au-002--user-login-email--password)                                | `v1.0-AU-002-stable` |       |
| AU-003  | Authentication & Authorization | Done   | 1      | Agent       | [EVAL-AU-003](evaluation_history.md#eval-au-003--google-oauth-login)                                        | v1.0-AU-003-stable   |       |
| AU-004  | Authentication & Authorization | Done   | 1      | Agent       | [EVAL-AU-004](evaluation_history.md#eval-au-004--user-logout)                                               | `v1.0-AU-004-stable` |       |
| AU-005  | Authentication & Authorization | Done   | 1      | Agent       | [EVAL-AU-005](evaluation_history.md#eval-au-005--password-reset-via-email)                                  | `v1.0-AU-005-stable` |       |
| AU-006  | Authentication & Authorization | Done   | 1      | Agent       | [EVAL-AU-006](evaluation_history.md#eval-au-006--role-based-access-control)                                 | `v1.0-AU-006-stable` |       |
| UP-001  | User Profile                   | Done   | 3      | Agent       | [EVAL-UP-001](evaluation_history.md#eval-up-001--user-profile-viewedit-with-avatar)                         | `v1.0-UP-001-stable` |       |
| UP-002  | User Profile                   | Done   | 7      | Agent       | [EVAL-UP-002](evaluation_history.md#eval-up-002--user-saved-addresses-crud)                                 | `v1.0-UP-002-stable` |       |
| PC-001  | Product Catalog                | Done   | 2      | Agent       | [EVAL-PC-001](evaluation_history.md#eval-pc-001--product-listing-page-with-pagination)                      | `v1.0-PC-001-stable` |       |
| PC-002  | Product Catalog                | Done   | 2      | Agent       | [EVAL-PC-002](evaluation_history.md#eval-pc-002--product-search-by-name-and-description)                    | `v1.0-PC-002-stable` |       |
| PC-003  | Product Catalog                | Done   | 2      | Agent       | [EVAL-PC-003](evaluation_history.md#eval-pc-003--product-filters-by-category-price-range-rating)            | `v1.0-PC-003-stable` |       |
| PC-004  | Product Catalog                | Done   | 2      | Agent       | [EVAL-PC-004](evaluation_history.md#eval-pc-004--product-sort-by-newest-oldest-price-rating)                | `v1.0-PC-004-stable` |       |
| PC-005  | Product Catalog                | Done   | 2      | Agent       | [EVAL-PC-005](evaluation_history.md#eval-pc-005--product-detail-page-with-slug-sku-related-products)        | `v1.0-PC-005-stable` |       |
| SC-001  | Shopping Cart                  | Done   | 2      | Agent       | [EVAL-SC-001](evaluation_history.md#eval-sc-001--add-to-cart-session-based-guestauth-ajax)                  | `v1.0-SC-001-stable` |       |
| SC-002  | Shopping Cart                  | Done   | 2      | Agent       | [EVAL-SC-002](evaluation_history.md#eval-sc-002--view-cart-items-subtotals-order-total)                     | `v1.0-SC-002-stable` |       |
| SC-003  | Shopping Cart                  | Done   | 2      | Agent       | [EVAL-SC-003](evaluation_history.md#eval-sc-003--update-cart-quantity-stock-cap-ajax-subtotaltotal-recalc)  | `v1.0-SC-003-stable` |       |
| SC-004  | Shopping Cart                  | Done   | 2      | Agent       | [EVAL-SC-004](evaluation_history.md#eval-sc-004--remove-cart-item-ajax-cart_count--order_total-recalc)      | `v1.0-SC-004-stable` |       |
| SC-005  | Shopping Cart                  | Done   | 7      | Agent       | [EVAL-SC-005](evaluation_history.md#eval-sc-005--coupon--discount-code)                                     | `v1.0-SC-005-stable` |       |
| CP-001  | Checkout & Payment             | Done   | 3      | Agent       | [EVAL-CP-001](evaluation_history.md#eval-cp-001--checkout-address-step-saved-addresses-new-form-validation) | `v1.0-CP-001-stable` |       |
| CP-002  | Checkout & Payment             | Done   | 3      | Agent       | [EVAL-CP-002](evaluation_history.md#eval-cp-002--checkout-shipping-method-standardexpress-cost-in-session)  | `v1.0-CP-002-stable` |       |
| CP-003  | Checkout & Payment             | Done   | 3      | Agent       | [EVAL-CP-003](evaluation_history.md#eval-cp-003--payment-gateway-stripe-paymentintent-webhook-tokenization) | `v1.0-CP-003-stable` |       |
| CP-004  | Checkout & Payment             | Done   | 3      | Agent       | [EVAL-CP-004](evaluation_history.md#eval-cp-004--order-confirmation-email-queued-job-mailable)              | `v1.0-CP-004-stable` |       |
| CP-005  | Checkout & Payment             | Done   | 3      | Agent       | [EVAL-CP-005](evaluation_history.md#eval-cp-005--successfailure-page-stripe-redirect-order-summary-retry)   | `v1.0-CP-005-stable` |       |
| OH-001  | Order History                  | Done   | 4      | Agent       | [EVAL-OH-001](evaluation_history.md#eval-oh-001--order-history-page)                                        | `v1.0-OH-001-stable` |       |
| OH-002  | Order History                  | Done   | 4      | Agent       | [EVAL-OH-002](evaluation_history.md#eval-oh-002--order-detail-page)                                         | `v1.0-OH-002-stable` |       |
| OH-003  | Order History                  | Done   | 4      | Agent       | [EVAL-OH-003](evaluation_history.md#eval-oh-003--order-status-tracking)                                     | `v1.0-OH-003-stable` |       |
| OH-004  | Order History                  | Done   | 4      | Agent       | [EVAL-OH-004](evaluation_history.md#eval-oh-004--order-cancellation)                                        | `v1.0-OH-004-stable` |       |
| RV-001  | Product Reviews                | Done   | 7      | Agent       | [EVAL-RV-001](evaluation_history.md#eval-rv-001--product-reviews)                                           | `v1.0-RV-001-stable` |       |
| RV-002  | Product Reviews                | Done   | 7      | Agent       | [EVAL-RV-002](evaluation_history.md#eval-rv-002--review-listing)                                            | `v1.0-RV-002-stable` |       |
| AD-001  | Admin Dashboard                | Done   | 4      | Agent       | [EVAL-AD-001](evaluation_history.md#eval-ad-001--admin-dashboard-kpi-cards)                                 | `v1.0-AD-001-stable` |       |
| AD-002  | Admin Dashboard                | Done   | 4      | Agent       | [EVAL-AD-002](evaluation_history.md#eval-ad-002--revenue-chart)                                             | `v1.0-AD-002-stable` |       |
| AD-003  | Admin Dashboard                | Done   | 6      | Agent       | [EVAL-AD-003](evaluation_history.md#eval-ad-003--top-selling-products)                                      | `v1.0-AD-003-stable` |       |
| AD-004  | Admin Dashboard                | Done   | 6      | Agent       | [EVAL-AD-004](evaluation_history.md#eval-ad-004--recent-orders-on-dashboard)                                | `v1.0-AD-004-stable` |       |
| PM-001  | Product Management             | Done   | 5      | Agent       | [EVAL-PM-001](evaluation_history.md#eval-pm-001--admin-product-create)                                      | `v1.0-PM-001-stable` |       |
| PM-002  | Product Management             | Done   | 5      | Agent       | [EVAL-PM-002](evaluation_history.md#eval-pm-002--admin-product-edit)                                        | `v1.0-PM-002-stable` |       |
| PM-003  | Product Management             | Done   | 5      | Agent       | [EVAL-PM-003](evaluation_history.md#eval-pm-003--admin-product-archive)                                     | `v1.0-PM-003-stable` |       |
| PM-004  | Product Management             | Done   | 5      | Agent       | [EVAL-PM-004](evaluation_history.md#eval-pm-004--admin-category-crud)                                       | `v1.0-PM-004-stable` |       |
| PM-005  | Product Management             | Done   | 7      | Agent       | [EVAL-PM-005](evaluation_history.md#eval-pm-005--admin-product-csv-import)                                  | `v1.0-PM-005-stable` |       |
| PM-006  | Product Management             | Done   | 7      | Agent       | [EVAL-PM-006](evaluation_history.md#eval-pm-006--admin-product-image-management)                            | `v1.0-PM-006-stable` |       |
| OM-001  | Order Management               | Done   | 5      | Agent       | [EVAL-OM-001](evaluation_history.md#eval-om-001--admin-order-list-with-filters)                             | `v1.0-OM-001-stable` |       |
| OM-002  | Order Management               | Done   | 5      | Agent       | [EVAL-OM-002](evaluation_history.md#eval-om-002--admin-order-detail)                                        | `v1.0-OM-002-stable` |       |
| OM-003  | Order Management               | Done   | 5      | Agent       | [EVAL-OM-003](evaluation_history.md#eval-om-003--admin-order-status-update)                                 | `v1.0-OM-003-stable` |       |
| OM-004  | Order Management               | Done   | 7      | Agent       | [EVAL-OM-004](evaluation_history.md#eval-om-004--export-orders-to-csv)                                      | `v1.0-OM-004-stable` |       |
| OM-005  | Order Management               | Done   | 7      | Agent       | [EVAL-OM-005](evaluation_history.md#eval-om-005--process-refund-on-cancelled-order)                         | `v1.0-OM-005-stable` |       |
| UM-001  | User Management                | Done   | 6      | Agent       | [EVAL-UM-001](evaluation_history.md#eval-um-001--admin-user-list)                                           | `v1.0-UM-001-stable` |       |

---

## IMP — UI/UX Improvement Tasks [UIUX_MODE]

> Bootstrap 5.3 CDN + Alpine.js 3 redesign. Tracked separately from main backlog.

| ID      | Description                                          | Status | Phase | Type          | Tag                                                                                         | Date                 |
| ------- | ---------------------------------------------------- | ------ | ----- | ------------- | ------------------------------------------------------------------------------------------- | -------------------- | --- |
| IMP-017 | (Foundation setup)                                   | Done   | 1     | —             | `v1.0-IMP-017-stable`                                                                       | 2026-04-23           |
| IMP-018 | Create `layouts/app.blade.php` + migrate BS5 pages   | Done   | 1     | Migration     | `v1.0-IMP-018-stable`                                                                       | 2026-04-23           |
| IMP-031 | Create `partials/navbar.blade.php`                   | Done   | 1     | New component | `v1.0-IMP-031-stable`                                                                       | 2026-04-23           |
| IMP-019 | Login page — Full Redesign                           | Done   | 2     | Full Redesign | `v1.0-IMP-019-stable`                                                                       | 2026-04-23           |
| IMP-020 | Register page — Full Redesign                        | Done   | 2     | Full Redesign | `v1.0-IMP-020-stable`                                                                       | 2026-04-23           |
| IMP-021 | User Dashboard — Full Redesign                       | Done   | 3     | Full Redesign | `v1.0-IMP-021-stable`                                                                       | 2026-04-23           |
| IMP-024 | Forgot Password + Reset Password — Full Redesign     | Done   | 2     | Full Redesign | `v1.0-IMP-024-stable`                                                                       | 2026-04-23           |
| IMP-030 | Email Verify + Google OAuth callback — Full Redesign | To Do  | 2     | Full Redesign | —                                                                                           | —                    |
| IMP-022 | Profile page — Full Redesign                         | Done   | 3     | Full Redesign | `v1.0-IMP-022-stable`                                                                       | 2026-04-23           |
| IMP-023 | Order History + Order Detail — Full Redesign         | Done   | 3     | Full Redesign | `v1.0-IMP-023-stable`                                                                       | 2026-04-23           |
| IMP-025 | Addresses page — Full Redesign                       | Done   | 3     | Full Redesign | `v1.0-IMP-025-stable`                                                                       | 2026-04-23           |
| IMP-026 | Create `layouts/admin.blade.php` + migrate admin BS5 | Done   | 1     | Migration     | `v1.0-IMP-026-stable`                                                                       | 2026-04-23           |
| IMP-027 | Admin Dashboard — Full Redesign                      | Done   | 4     | Full Redesign | `v1.0-IMP-027-stable`                                                                       | 2026-04-23           |
| IMP-028 | Welcome / Homepage — Full Redesign                   | Done   | 5     | Full Redesign | `v1.0-IMP-028-stable`                                                                       | 2026-04-23           |
| IMP-029 | Cart page — Full Redesign                            | Done   | 5     | Full Redesign | `v1.0-IMP-029-stable`                                                                       | 2026-04-24           |
| UM-002  | User Management                                      | Done   | 6     | Agent         | [EVAL-UM-002](evaluation_history.md#eval-um-002--admin-view-user-profile-and-order-history) | `v1.0-UM-002-stable` |     |
| UM-003  | User Management                                      | Done   | 6     | Agent         | [EVAL-UM-003](evaluation_history.md#eval-um-003--admin-activatesuspend-user-account)        | `v1.0-UM-003-stable` |     |
| UM-004  | User Management                                      | Done   | 6     | Agent         | [EVAL-UM-004](evaluation_history.md#eval-um-004--admin-assignchange-user-roles)             | `v1.0-UM-004-stable` |     |
| RM-001  | Revenue Management                                   | Done   | 6     | Agent         | [EVAL-RM-001](evaluation_history.md#eval-rm-001--admin-revenue-report-by-period)            | `v1.0-RM-001-stable` |     |
| RM-002  | Revenue Management                                   | Done   | 6     | Agent         | [EVAL-RM-002](evaluation_history.md#eval-rm-002--admin-revenue-by-productcategory)          | `v1.0-RM-002-stable` |     |
| RM-003  | Revenue Management                                   | Done   | 7     | Agent         | [EVAL-RM-003](evaluation_history.md#eval-rm-003--admin-coupon-management)                   | `v1.0-RM-003-stable` |     |
| NT-001  | Notifications                                        | Done   | 6     | Agent         | [EVAL-NT-001](evaluation_history.md#eval-nt-001--queued-order-email-notifications)          | `v1.0-NT-001-stable` |     |
| NT-002  | Notifications                                        | Done   | 6     | Agent         | [EVAL-NT-002](evaluation_history.md#eval-nt-002--admin-new-order-notification-bell)         | `v1.0-NT-002-stable` |     |
| NT-003  | Notifications                                        | Done   | 7     | Agent         | [EVAL-NT-003](evaluation_history.md#eval-nt-003--admin-low-stock-threshold-notification)    | `v1.0-NT-003-stable` |     |
| NF-001  | Non-Functional                                       | Done   | 1     | Agent         | [EVAL-NF-001](evaluation_history.md#eval-nf-001--csrf-protection-audit)                     | `v1.0-NF-001-stable` |     |
| NF-002  | Non-Functional                                       | Done   | 3     | Agent         | [EVAL-NF-002](evaluation_history.md#eval-nf-002--input-sanitization-audit)                  | `v1.0-NF-002-stable` |     |
| NF-003  | Non-Functional                                       | Done   | 3     | Agent         | [EVAL-NF-003](evaluation_history.md#eval-nf-003--payment-tokenization-audit)                | `v1.0-NF-003-stable` |     |
| NF-004  | Non-Functional                                       | Done   | 1     | Agent         | [EVAL-NF-004](evaluation_history.md#eval-nf-004--https-enforcement)                         | `v1.0-NF-004-stable` |     |
| NF-005  | Non-Functional                                       | Done   | 1     | Agent         | [EVAL-NF-005](evaluation_history.md#eval-nf-005--admin-route-middleware-audit)              | `v1.0-NF-005-stable` |     |
| NF-006  | Non-Functional                                       | Done   | 1     | Agent         | [EVAL-NF-006](evaluation_history.md#eval-nf-006--rate-limiting)                             | `v1.0-NF-006-stable` |     |
| NF-007  | Non-Functional                                       | Done   | 7     | Agent         | [EVAL-NF-007](evaluation_history.md#eval-nf-007--cloud-image-storage)                       | `v1.0-NF-007-stable` |     |
| NF-008  | Non-Functional                                       | Done   | 7     | Agent         | [EVAL-NF-008](evaluation_history.md#eval-nf-008--queued-heavy-operations)                   | `v1.0-NF-008-stable` |     |
| NF-009  | Non-Functional                                       | Done   | 7     | Agent         | [EVAL-NF-009](evaluation_history.md#eval-nf-009--application-logging--monitoring)           | `v1.0-NF-009-stable` |     |
| NF-010  | Non-Functional                                       | Done   | 7     | Agent         | [EVAL-NF-010](evaluation_history.md#eval-nf-010--critical-flow-test-coverage-audit)         | `v1.0-NF-010-stable` |     |

---

## Improvement Backlog

> Improvements are deliberate enhancements to existing, working features — NOT new features and NOT bug fixes.
> **Workflow:** [improvement_template.md](improvement_template.md)
> **Invoke:** prefix your request with `[UIUX_MODE]`, `[LOGIC_MODE]`, `[INFRA_MODE]`, or `[FULL_STACK_MODE]`.
> **Status values:** `Not Started` · `In Progress` · `Done` · `Blocked` · `Rejected`

| IMP ID  | Scope               | Title                                                                                                                                                                                                                                                                                         | Target Task IDs                                | Epic(s)                                                           | Priority | Points | Status      | Git Tag             | Evaluation Record |
| ------- | ------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------- | ----------------------------------------------------------------- | -------- | ------ | ----------- | ------------------- | ----------------- |
| IMP-001 | `[UIUX_MODE]`       | Bento Grid layout for catalog & category page                                                                                                                                                                                                                                                 | PC-001                                         | Product Catalog                                                   | 3        | 3      | Done        | v1.0-IMP-001-stable | 2026-04-19        |
| IMP-002 | `[UIUX_MODE]`       | Skeleton Screen for all async-load areas                                                                                                                                                                                                                                                      | PC-001, AD-001, AD-002                         | Product Catalog · Admin                                           | 3        | 3      | Done        | v1.0-IMP-002-stable | 2026-04-19        |
| IMP-003 | `[FULL_STACK_MODE]` | One-Page Checkout (collapse multi-step to single view)                                                                                                                                                                                                                                        | CP-001, CP-002, CP-003                         | Checkout & Payment                                                | 2        | 5      | Done        | v1.0-IMP-003-stable | 2026-04-19        |
| IMP-004 | `[FULL_STACK_MODE]` | Guest Checkout (complete order without login)                                                                                                                                                                                                                                                 | CP-001, SC-001                                 | Checkout & Payment                                                | 2        | 5      | Done        | v1.0-IMP-004-stable | 2026-04-19        |
| IMP-005 | `[UIUX_MODE]`       | Off-canvas cart drawer (mobile-first slide-in)                                                                                                                                                                                                                                                | SC-001, SC-002                                 | Shopping Cart                                                     | 3        | 2      | Done        | v1.0-IMP-005-stable | 2026-04-19        |
| IMP-006 | `[LOGIC_MODE]`      | Eliminate N+1 queries via eager-loading                                                                                                                                                                                                                                                       | PC-001, OH-001, OH-002, OM-001, OM-002         | Product Catalog · Order History · Order Mgmt                      | 2        | 3      | Done        | v1.0-IMP-006-stable | 2026-04-19        |
| IMP-007 | `[UIUX_MODE]`       | Alpine.js micro-interactions on all cart actions                                                                                                                                                                                                                                              | SC-001, SC-002, SC-003, SC-004                 | Shopping Cart                                                     | 3        | 2      | Done        | v1.0-IMP-007-stable | 2026-04-19        |
| IMP-008 | `[INFRA_MODE]`      | Switch queue driver from sync to database                                                                                                                                                                                                                                                     | CP-004, NT-001, NT-002                         | Notifications · Checkout                                          | 2        | 2      | Done        | v1.0-IMP-008-stable | 2026-04-19        |
| IMP-009 | `[UIUX_MODE]`       | Global toast notification system (replace bare flash)                                                                                                                                                                                                                                         | AU-001–004, SC-001–004, CP-005                 | Cross-cutting                                                     | 2        | 3      | Done        | v1.0-IMP-009-stable | 2026-04-19        |
| IMP-010 | `[UIUX_MODE]`       | Product image lightbox + zoom on detail page                                                                                                                                                                                                                                                  | PC-005                                         | Product Catalog                                                   | 3        | 2      | Done        | v1.0-IMP-010-stable | 2026-04-21        |
| IMP-011 | `[UIUX_MODE]`       | Order status visual progress stepper                                                                                                                                                                                                                                                          | OH-003                                         | Order History                                                     | 3        | 2      | Done        | v1.0-IMP-011-stable | 2026-04-21        |
| IMP-012 | `[UIUX_MODE]`       | Interactive star rating input (Alpine.js, no reload)                                                                                                                                                                                                                                          | RV-001, RV-002                                 | Product Reviews                                                   | 3        | 2      | Done        | v1.0-IMP-012-stable | 2026-04-21        |
| IMP-013 | `[UIUX_MODE]`       | Admin tables: sortable columns + responsive layout                                                                                                                                                                                                                                            | OM-001, PM-001, UM-001                         | Admin (Order/Product/User Mgmt)                                   | 2        | 3      | Done        | v1.0-IMP-013-stable | 2026-04-21        |
| IMP-014 | `[LOGIC_MODE]`      | Product catalog response caching (Laravel Cache)                                                                                                                                                                                                                                              | PC-001, PC-002, PC-003, PC-004                 | Product Catalog                                                   | 2        | 3      | Done        | v1.0-IMP-014-stable | 2026-04-21        |
| IMP-015 | `[LOGIC_MODE]`      | DB-backed cart persistence for authenticated users                                                                                                                                                                                                                                            | SC-001, SC-002, SC-003, SC-004                 | Shopping Cart                                                     | 2        | 5      | Done        | v1.0-IMP-015-stable | 2026-04-21        |
| IMP-016 | `[FULL_STACK_MODE]` | Consolidated audit log (auth events + admin actions)                                                                                                                                                                                                                                          | AU-002, AU-003, AU-004, PM-002, UM-004         | Auth · Admin                                                      | 2        | 5      | Done        | v1.0-IMP-016-stable | 2026-04-21        |
| IMP-017 | `[FULL_STACK_MODE]` | Real-time admin notifications via Firebase                                                                                                                                                                                                                                                    | NT-002, AD-001, AD-004                         | Notifications · Admin                                             | 3        | 5      | Done        | v1.0-IMP-017-stable | 2026-04-21        |
| IMP-018 | `[FULL_STACK_MODE]` | Create `layouts/app.blade.php` — Bootstrap 5 CDN entry point (user stylesheet) + navbar + footer. Then MIGRATE already-Bootstrap pages to extend it: `products/index`, `products/show`, `checkout/index`, `checkout/guest` (remove duplicate CDN, add `@extends`, keep all Bootstrap classes) | AU-001, AU-002, UP-001, UP-002, OH-001, SC-001 | Cross-cutting (all user-facing pages)                             | 1        | 5      | Done        | v1.0-IMP-018-stable | 2026-04-23        |
| IMP-019 | `[UIUX_MODE]`       | Redesign Login page — card layout, branding, Google OAuth button, forgot-password link                                                                                                                                                                                                        | AU-002                                         | Authentication                                                    | 1        | 2      | Done        | v1.0-IMP-019-stable | 2026-04-23        |
| IMP-020 | `[UIUX_MODE]`       | Redesign Register page — card style (matches IMP-019), password strength hint                                                                                                                                                                                                                 | AU-001                                         | Authentication                                                    | 1        | 2      | Not Started | —                   | —                 |
| IMP-021 | `[UIUX_MODE]`       | Redesign User Dashboard — quick-link cards (Orders, Profile, Addresses), last order badge                                                                                                                                                                                                     | dashboard                                      | User Dashboard                                                    | 1        | 3      | Not Started | —                   | —                 |
| IMP-022 | `[UIUX_MODE]`       | Redesign Profile page — card layout, avatar upload preview, form sections, save button                                                                                                                                                                                                        | UP-001                                         | User Profile                                                      | 2        | 2      | Not Started | —                   | —                 |
| IMP-023 | `[UIUX_MODE]`       | Redesign Order History + Order Detail — Bootstrap table, status badges, order timeline                                                                                                                                                                                                        | OH-001, OH-002                                 | Order History                                                     | 2        | 3      | Not Started | —                   | —                 |
| IMP-024 | `[UIUX_MODE]`       | Redesign Forgot Password + Reset Password — card layout matching IMP-019/020                                                                                                                                                                                                                  | AU-005                                         | Authentication                                                    | 2        | 2      | Done        | v1.0-IMP-024-stable | 2026-04-23        |
| IMP-025 | `[UIUX_MODE]`       | Redesign Addresses page — card-per-address, default badge, inline edit/delete                                                                                                                                                                                                                 | UP-002                                         | User Profile                                                      | 2        | 2      | Done        | v1.0-IMP-025-stable | 2026-04-23        |
| IMP-026 | `[FULL_STACK_MODE]` | Create `layouts/admin.blade.php` — Bootstrap 5 CDN entry point (admin stylesheet) + sidebar nav + topbar. Then MIGRATE already-Bootstrap admin pages: `admin/audit-log` (remove duplicate CDN, add `@extends`)                                                                                | AD-001–004, PM-001–006, OM-001–005, UM-001–004 | Cross-cutting (all admin pages)                                   | 1        | 5      | Not Started | —                   | —                 |
| IMP-027 | `[UIUX_MODE]`       | Redesign Admin Dashboard — modern KPI cards, trend indicators, full-width revenue chart                                                                                                                                                                                                       | AD-001, AD-002                                 | Admin Dashboard                                                   | 2        | 3      | Done        | v1.0-IMP-027-stable | 2026-04-23        |
| IMP-028 | `[UIUX_MODE]`       | Replace welcome page with real e-commerce homepage — hero banner, featured products, CTA                                                                                                                                                                                                      | PC-001                                         | Product Catalog                                                   | 3        | 3      | Done        | v1.0-IMP-028-stable | 2026-04-23        |
| IMP-029 | `[UIUX_MODE]`       | Cart: FULL REDESIGN (cart has NO Bootstrap — only IMP-007 Alpine.js interactions). Migrate to `layouts/app.blade.php`, add Bootstrap 5 markup, preserve all IMP-007 Alpine.js logic, add sticky order summary panel, product thumbnails, styled qty stepper                                   | SC-001, SC-002                                 | Shopping Cart                                                     | 3        | 3      | Done        | v1.0-IMP-029-stable | 2026-04-24        |
| IMP-030 | `[UIUX_MODE]`       | Email Verify + Google OAuth callback pages — card layouts matching auth redesign                                                                                                                                                                                                              | AU-002, AU-003                                 | Authentication                                                    | 3        | 2      | Not Started | —                   | —                 |
| IMP-031 | `[FULL_STACK_MODE]` | Global navigation: persistent top navbar + mobile hamburger menu for all user pages                                                                                                                                                                                                           | IMP-018 (depends on shared layout)             | Cross-cutting (navigation — prerequisite for IMP-021/022/023/025) | 1        | 5      | Done        | v1.0-IMP-031-stable | 2026-04-23        |

> IMP IDs are sequential and never reused. Add new rows at the bottom as improvements are identified.

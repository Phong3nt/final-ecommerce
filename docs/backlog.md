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

| Task ID | Epic                           | Status      | Sprint | Assigned To | Evaluation Record                                                                                           | Git Tag              | Notes |
| ------- | ------------------------------ | ----------- | ------ | ----------- | ----------------------------------------------------------------------------------------------------------- | -------------------- | ----- |
| AU-001  | Authentication & Authorization | Done        | 1      | Agent       | [EVAL-AU-001](evaluation_history.md#eval-au-001--user-registration-email--password)                         | `v1.0-AU-001-stable` |       |
| AU-002  | Authentication & Authorization | Done        | 1      | Agent       | [EVAL-AU-002](evaluation_history.md#eval-au-002--user-login-email--password)                                | `v1.0-AU-002-stable` |       |
| AU-003  | Authentication & Authorization | Done        | 1      | Agent       | [EVAL-AU-003](evaluation_history.md#eval-au-003--google-oauth-login)                                        | v1.0-AU-003-stable   |       |
| AU-004  | Authentication & Authorization | Done        | 1      | Agent       | [EVAL-AU-004](evaluation_history.md#eval-au-004--user-logout)                                               | `v1.0-AU-004-stable` |       |
| AU-005  | Authentication & Authorization | Done        | 1      | Agent       | [EVAL-AU-005](evaluation_history.md#eval-au-005--password-reset-via-email)                                  | `v1.0-AU-005-stable` |       |
| AU-006  | Authentication & Authorization | Done        | 1      | Agent       | [EVAL-AU-006](evaluation_history.md#eval-au-006--role-based-access-control)                                 | `v1.0-AU-006-stable` |       |
| UP-001  | User Profile                   | Done        | 3      | Agent       | [EVAL-UP-001](evaluation_history.md#eval-up-001--user-profile-viewedit-with-avatar)                         | `v1.0-UP-001-stable` |       |
| UP-002  | User Profile                   | Not Started | 7      | —           | —                                                                                                           | —                    |       |
| PC-001  | Product Catalog                | Done        | 2      | Agent       | [EVAL-PC-001](evaluation_history.md#eval-pc-001--product-listing-page-with-pagination)                      | `v1.0-PC-001-stable` |       |
| PC-002  | Product Catalog                | Done        | 2      | Agent       | [EVAL-PC-002](evaluation_history.md#eval-pc-002--product-search-by-name-and-description)                    | `v1.0-PC-002-stable` |       |
| PC-003  | Product Catalog                | Done        | 2      | Agent       | [EVAL-PC-003](evaluation_history.md#eval-pc-003--product-filters-by-category-price-range-rating)            | `v1.0-PC-003-stable` |       |
| PC-004  | Product Catalog                | Done        | 2      | Agent       | [EVAL-PC-004](evaluation_history.md#eval-pc-004--product-sort-by-newest-oldest-price-rating)                | `v1.0-PC-004-stable` |       |
| PC-005  | Product Catalog                | Done        | 2      | Agent       | [EVAL-PC-005](evaluation_history.md#eval-pc-005--product-detail-page-with-slug-sku-related-products)        | `v1.0-PC-005-stable` |       |
| SC-001  | Shopping Cart                  | Done        | 2      | Agent       | [EVAL-SC-001](evaluation_history.md#eval-sc-001--add-to-cart-session-based-guestauth-ajax)                  | `v1.0-SC-001-stable` |       |
| SC-002  | Shopping Cart                  | Done        | 2      | Agent       | [EVAL-SC-002](evaluation_history.md#eval-sc-002--view-cart-items-subtotals-order-total)                     | `v1.0-SC-002-stable` |       |
| SC-003  | Shopping Cart                  | Done        | 2      | Agent       | [EVAL-SC-003](evaluation_history.md#eval-sc-003--update-cart-quantity-stock-cap-ajax-subtotaltotal-recalc)  | `v1.0-SC-003-stable` |       |
| SC-004  | Shopping Cart                  | Done        | 2      | Agent       | [EVAL-SC-004](evaluation_history.md#eval-sc-004--remove-cart-item-ajax-cart_count--order_total-recalc)      | `v1.0-SC-004-stable` |       |
| SC-005  | Shopping Cart                  | Not Started | 7      | —           | —                                                                                                           | —                    |       |
| CP-001  | Checkout & Payment             | Done        | 3      | Agent       | [EVAL-CP-001](evaluation_history.md#eval-cp-001--checkout-address-step-saved-addresses-new-form-validation) | `v1.0-CP-001-stable` |       |
| CP-002  | Checkout & Payment             | Done        | 3      | Agent       | [EVAL-CP-002](evaluation_history.md#eval-cp-002--checkout-shipping-method-standardexpress-cost-in-session)  | `v1.0-CP-002-stable` |       |
| CP-003  | Checkout & Payment             | Done        | 3      | Agent       | [EVAL-CP-003](evaluation_history.md#eval-cp-003--payment-gateway-stripe-paymentintent-webhook-tokenization) | `v1.0-CP-003-stable` |       |
| CP-004  | Checkout & Payment             | Done        | 3      | Agent       | [EVAL-CP-004](evaluation_history.md#eval-cp-004--order-confirmation-email-queued-job-mailable)              | `v1.0-CP-004-stable` |       |
| CP-005  | Checkout & Payment             | Done        | 3      | Agent       | [EVAL-CP-005](evaluation_history.md#eval-cp-005--successfailure-page-stripe-redirect-order-summary-retry)   | `v1.0-CP-005-stable` |       |
| OH-001  | Order History                  | Not Started | 4      | —           | —                                                                                                           | —                    |       |
| OH-002  | Order History                  | Not Started | 4      | —           | —                                                                                                           | —                    |       |
| OH-003  | Order History                  | Not Started | 4      | —           | —                                                                                                           | —                    |       |
| OH-004  | Order History                  | Not Started | 4      | —           | —                                                                                                           | —                    |       |
| RV-001  | Product Reviews                | Not Started | 7      | —           | —                                                                                                           | —                    |       |
| RV-002  | Product Reviews                | Not Started | 7      | —           | —                                                                                                           | —                    |       |
| AD-001  | Admin Dashboard                | Not Started | 4      | —           | —                                                                                                           | —                    |       |
| AD-002  | Admin Dashboard                | Not Started | 4      | —           | —                                                                                                           | —                    |       |
| AD-003  | Admin Dashboard                | Not Started | 6      | —           | —                                                                                                           | —                    |       |
| AD-004  | Admin Dashboard                | Not Started | 6      | —           | —                                                                                                           | —                    |       |
| PM-001  | Product Management             | Not Started | 5      | —           | —                                                                                                           | —                    |       |
| PM-002  | Product Management             | Not Started | 5      | —           | —                                                                                                           | —                    |       |
| PM-003  | Product Management             | Not Started | 5      | —           | —                                                                                                           | —                    |       |
| PM-004  | Product Management             | Not Started | 5      | —           | —                                                                                                           | —                    |       |
| PM-005  | Product Management             | Not Started | 7      | —           | —                                                                                                           | —                    |       |
| PM-006  | Product Management             | Not Started | 7      | —           | —                                                                                                           | —                    |       |
| OM-001  | Order Management               | Not Started | 5      | —           | —                                                                                                           | —                    |       |
| OM-002  | Order Management               | Not Started | 5      | —           | —                                                                                                           | —                    |       |
| OM-003  | Order Management               | Not Started | 5      | —           | —                                                                                                           | —                    |       |
| OM-004  | Order Management               | Not Started | 7      | —           | —                                                                                                           | —                    |       |
| OM-005  | Order Management               | Not Started | 7      | —           | —                                                                                                           | —                    |       |
| UM-001  | User Management                | Not Started | 6      | —           | —                                                                                                           | —                    |       |
| UM-002  | User Management                | Not Started | 6      | —           | —                                                                                                           | —                    |       |
| UM-003  | User Management                | Not Started | 6      | —           | —                                                                                                           | —                    |       |
| UM-004  | User Management                | Not Started | 6      | —           | —                                                                                                           | —                    |       |
| RM-001  | Revenue Management             | Not Started | 6      | —           | —                                                                                                           | —                    |       |
| RM-002  | Revenue Management             | Not Started | 6      | —           | —                                                                                                           | —                    |       |
| RM-003  | Revenue Management             | Not Started | 7      | —           | —                                                                                                           | —                    |       |
| NT-001  | Notifications                  | Not Started | 6      | —           | —                                                                                                           | —                    |       |
| NT-002  | Notifications                  | Not Started | 6      | —           | —                                                                                                           | —                    |       |
| NT-003  | Notifications                  | Not Started | 7      | —           | —                                                                                                           | —                    |       |
| NF-001  | Non-Functional                 | Done        | 1      | Agent       | [EVAL-NF-001](evaluation_history.md#eval-nf-001--csrf-protection-audit)                                     | `v1.0-NF-001-stable` |       |
| NF-002  | Non-Functional                 | Done        | 3      | Agent       | [EVAL-NF-002](evaluation_history.md#eval-nf-002--input-sanitization-audit)                                  | `v1.0-NF-002-stable` |       |
| NF-003  | Non-Functional                 | Done        | 3      | Agent       | [EVAL-NF-003](evaluation_history.md#eval-nf-003--payment-tokenization-audit)                                | `v1.0-NF-003-stable` |       |
| NF-004  | Non-Functional                 | Done        | 1      | Agent       | [EVAL-NF-004](evaluation_history.md#eval-nf-004--https-enforcement)                                         | `v1.0-NF-004-stable` |       |
| NF-005  | Non-Functional                 | Done        | 1      | Agent       | [EVAL-NF-005](evaluation_history.md#eval-nf-005--admin-route-middleware-audit)                              | `v1.0-NF-005-stable` |       |
| NF-006  | Non-Functional                 | Done        | 1      | Agent       | [EVAL-NF-006](evaluation_history.md#eval-nf-006--rate-limiting)                                             | `v1.0-NF-006-stable` |       |
| NF-007  | Non-Functional                 | Not Started | 7      | —           | —                                                                                                           | —                    |       |
| NF-008  | Non-Functional                 | Not Started | 7      | —           | —                                                                                                           | —                    |       |
| NF-009  | Non-Functional                 | Not Started | 7      | —           | —                                                                                                           | —                    |       |
| NF-010  | Non-Functional                 | Not Started | 7      | —           | —                                                                                                           | —                    |       |

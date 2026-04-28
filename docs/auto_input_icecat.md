# Icecat API Auto-Import — Agent Specification

> **IMP Task:** IMP-038  
> **Purpose:** Automatically populate the e-commerce product catalogue with real, production-quality data sourced from the Icecat product content database. This is the preferred alternative to manual seeding (IMP-037) for accurate specs and professional product descriptions.  
> **Audience:** Agent implementing IMP-038. Read this file in full before writing any code.

---

## Overview

The Icecat Import Pipeline fetches product data from the [Icecat Open Catalogue API](https://icecat.us/), maps it to the local `products` / `product_variants` / `categories` schema, and inserts records in **Draft** status for admin review. The pipeline runs as a queued Laravel job triggered by an artisan command or admin UI button.

**Target categories (7):**  
`Electronics` · `Laptops` · `Smartphones` · `Smartwatches` · `Tablets` · `Headphones & Headsets` · `Battery Chargers`

**Volume:** 20 EANs per category → ~140 products per full run.

**Entry points:**

- CLI: `php artisan icecat:import --category=all --limit=20php artisan icecat:import --category=all --limit=20`
- Admin UI: `admin/products` page → "Import from Icecat" button (opens category picker + EAN count selector)

---

## Prerequisites

Add to `.env`:

```
ICECAT_USERNAME=your_username
ICECAT_PASSWORD=your_password
```

Icecat API base URL: `https://live.icecat.us/api`  
Authentication: HTTP Basic Auth (`ICECAT_USERNAME:ICECAT_PASSWORD`)

---

## Workflow — 5 Steps

### Step 0 — EAN Targeting

**Goal:** Collect a list of EAN codes for the target category.

**Method:** Call the Icecat Search / Index API to retrieve products by category keyword.

```
GET https://live.icecat.us/api?UserName={user}&Language=EN&Content=Product&Brand=&CategoryId={icecat_category_id}&Offset=0&Limit=20
```

**Mapping — local category → Icecat CategoryId:**

| Local Category        | Icecat CategoryId (example) |
| --------------------- | --------------------------- |
| Laptops               | 151                         |
| Smartphones           | 160                         |
| Smartwatches          | 8394                        |
| Tablets               | 32                          |
| Headphones & Headsets | 168                         |
| Battery Chargers      | 2072                        |
| Electronics (general) | 1 (root/fallback)           |

> If a CategoryId returns no results, fall back to keyword search:  
> `GET .../api?UserName={user}&Language=EN&Content=Product&FullSearch={keyword}&Limit=20`

**Output of Step 0:** A list of `[{ ean, icecat_product_id, name }]` objects.

---

### Step 1 — Data Extraction (per EAN)

**Goal:** Fetch full product details for each EAN collected in Step 0.

```
GET https://live.icecat.us/api?UserName={user}&Language=EN&GTIN={ean}
```

**Error handling (non-negotiable):**

- If the API returns `404`, `EAN not found`, or missing critical fields (`title`, `price`, `category`) → log to `storage/logs/icecat_import.log` with `[SKIP] EAN={ean} reason=...` and continue to next EAN.
- Never throw an exception that halts the entire batch.
- Track: `{ attempted, succeeded, skipped }` counts for the final summary.

**Fields to extract from response:**

| Icecat Field                        | Local Field                | Notes                                             |
| ----------------------------------- | -------------------------- | ------------------------------------------------- |
| `Title`                             | `products.name`            | Required                                          |
| `LongDesc` or `ShortDesc`           | `products.description`     | Prefer LongDesc; fallback to ShortDesc            |
| `HighImg` or `LowImg`               | `products.image`           | Store URL; download to storage if needed          |
| `Category.Name`                     | `categories.name`          | Create category if not exists                     |
| `Brand.Name`                        | `products.brand`           | Optional field                                    |
| `ProductFamily` or `Series`         | (used in Step 2)           | Group key for parent-child                        |
| `EAN`                               | `product_variants.sku`     | SKU = EAN                                         |
| `Specs[Color]`                      | `product_variants.color`   | Map to dropdown option                            |
| `Specs[Memory]` or `Specs[Storage]` | `product_variants.storage` | Map to dropdown option                            |
| `Specs[Processor]`                  | `products.spec_processor`  | Hard-locked field (see Step 3)                    |
| `Specs[DisplaySize]`                | `products.spec_display`    | Hard-locked field                                 |
| `Specs[Weight]`                     | `products.spec_weight`     | Hard-locked field                                 |
| `MSRP` or estimated price           | `product_variants.price`   | Use MSRP; if absent, estimate from category range |

---

### Step 2 — Parent-Child Grouping

**Goal:** Group EAN variants that belong to the same physical product into one parent `Product` with multiple `ProductVariant` children.

**Logic:**

1. Read `ProductFamily` (or `Series`) from each extracted record.
2. Group all records sharing the same `ProductFamily` value under a single parent product.
3. If `ProductFamily` is empty or unique per EAN → treat each EAN as its own standalone product (1 parent + 1 variant).

**Result structure:**

```
Product (parent)
  ├── name        = shared product name (stripped of color/storage suffix)
  ├── description = shared description
  ├── category_id = resolved from category name
  └── ProductVariants[]
        ├── sku   = EAN
        ├── color = extracted color value
        ├── storage = extracted storage value
        └── price = extracted price
```

---

### Step 3 — Smart CRUD (Field Mapping + Hard-Lock)

**Goal:** Insert or update products in the database without duplicating.

**Duplicate check:** Before inserting, query `WHERE products.name = ? AND category_id = ?`. If exists, update instead of insert.

**Hard-locked fields** (set `is_icecat_locked = true` on these columns in the DB; admin UI must show them as read-only):

- `spec_processor` (Chip / CPU)
- `spec_display` (Screen size and resolution)
- `spec_weight` (Physical dimensions / weight)

**Attribute mapping to dropdowns:**

- `color` values → match or create entries in `product_attribute_options` table (type=`color`)
- `storage` values → match or create entries in `product_attribute_options` (type=`storage`)
- These dropdown values are used by the filter sidebar on `products/index`

---

### Step 4 — Operation Presets

**Goal:** Make imported products ready for review without manual admin data-entry.

| Preset            | Value                                  |
| ----------------- | -------------------------------------- |
| Stock per variant | Random integer between 50 and 100      |
| Product status    | `draft` (not visible to users yet)     |
| Featured          | `false` (admin enables manually)       |
| Low-stock alert   | Threshold = 10 (same as existing rule) |
| Import source     | `icecat` (for audit/filtering)         |

**After insert:** Dispatch `AdminNotification` with type `icecat_import_complete` and summary counts `{ attempted, succeeded, skipped }`.

---

## Implementation Files

| File                                                      | Purpose                                                                                          |
| --------------------------------------------------------- | ------------------------------------------------------------------------------------------------ |
| `app/Services/IcecatImportService.php`                    | Steps 0–4 orchestration logic                                                                    |
| `app/Jobs/ImportProductsIcecatJob.php`                    | Queued wrapper for `IcecatImportService`                                                         |
| `app/Console/Commands/IcecatImportCommand.php`            | `artisan icecat:import` CLI entry point                                                          |
| `app/Http/Controllers/Admin/IcecatController.php`         | Admin UI trigger + progress response                                                             |
| `storage/logs/icecat_import.log`                          | Per-run import log (appended, not overwritten)                                                   |
| `database/migrations/*_add_icecat_fields_to_products.php` | Add `spec_processor`, `spec_display`, `spec_weight`, `is_icecat_locked`, `import_source` columns |

---

## Admin UI (admin/products page)

Add a button in the `admin/products/index.blade.php` toolbar:

```html
<button
  type="button"
  class="btn btn-outline-primary btn-sm"
  data-bs-toggle="modal"
  data-bs-target="#icecatImportModal"
>
  <i class="bi bi-cloud-download me-1"></i> Import from Icecat
</button>
```

Modal contains:

- Category multi-select (checkboxes for all 7 categories, default: all checked)
- EAN count per category (number input, default: 20, max: 50)
- "Start Import" button → POST `/admin/icecat/import` → dispatches `ImportProductsIcecatJob` → returns JSON `{ message: "Import queued. Check Notifications for results." }`

---

## Security Notes

- Icecat credentials stored only in `.env` — never hard-coded or logged.
- Admin route protected by `middleware(['auth', 'role:admin'])`.
- Imported image URLs are validated before storage (allow only `https://` from `icecat.us` / `icecat.biz` domains).
- All inserted text fields are sanitized through Laravel's standard `htmlspecialchars` / Eloquent binding.

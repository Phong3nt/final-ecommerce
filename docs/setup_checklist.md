# Setup Checklist — Laravel E-Commerce

> Reference file for [instruction.md](instruction.md) — Scenario E.
> Dùng khi: bắt đầu phiên làm việc mới, sau khi clone/chuyển dự án, hoặc khi gặp lỗi môi trường không rõ nguyên nhân.
>
> **Nguyên tắc cốt lõi:** Mỗi bước đều phải **kiểm tra trước — hành động sau**. Nếu lệnh trả về lỗi, Agent **phải xử lý lỗi đó ngay tại chỗ** trước khi chuyển sang bước tiếp theo. Tuyệt đối không bỏ qua lỗi.

---

## STEP 0 — Xác định thư mục làm việc

```bash
# Đảm bảo đang đứng đúng thư mục Laravel (có file artisan)
ls artisan         # Windows: Test-Path artisan
```

| Kết quả                | Hành động                                                  |
| ---------------------- | ---------------------------------------------------------- |
| File `artisan` tồn tại | Tiếp tục STEP 1                                            |
| Không thấy `artisan`   | `cd ecommerce` (hoặc đúng tên thư mục Laravel) rồi thử lại |
| Vẫn không thấy         | STOP — hỏi user đường dẫn thư mục gốc dự án                |

---

## STEP 1 — System Check (Kiểm tra công cụ hệ thống)

Chạy từng lệnh và xử lý theo kết quả:

### 1.1 PHP

```bash
php -v
```

| Kết quả                    | Hành động                                                                                                                    |
| -------------------------- | ---------------------------------------------------------------------------------------------------------------------------- |
| Phiên bản `>= 8.1` hiện ra | ✅ OK — tiếp tục                                                                                                             |
| Phiên bản `< 8.1`          | ⛔ STOP — báo user: "PHP version không đủ. Cần >= 8.1, hiện tại là X.Y.Z. Vui lòng nâng cấp PHP rồi thử lại."                |
| `'php' is not recognized`  | ⛔ STOP — báo user: "PHP không có trong PATH. Trên XAMPP: thêm `C:\xampp\php` vào PATH. Sau đó mở terminal mới và chạy lại." |

### 1.2 Composer

```bash
composer -V
```

| Kết quả                        | Hành động                                                                                            |
| ------------------------------ | ---------------------------------------------------------------------------------------------------- |
| Phiên bản Composer hiện ra     | ✅ OK — tiếp tục                                                                                     |
| `'composer' is not recognized` | ⛔ STOP — báo user: "Composer chưa được cài. Tải tại https://getcomposer.org/download/ rồi thử lại." |

### 1.3 Node.js & npm

```bash
node -v
npm -v
```

| Kết quả                         | Hành động                                                                                                     |
| ------------------------------- | ------------------------------------------------------------------------------------------------------------- |
| Cả hai đều có phiên bản         | ✅ OK — tiếp tục                                                                                              |
| Lỗi `not recognized`            | ⛔ STOP — báo user: "Node.js chưa cài. Tải tại https://nodejs.org (LTS). Sau đó mở terminal mới và chạy lại." |
| `node -v` OK nhưng `npm -v` lỗi | Chạy `npm install -g npm` để sửa                                                                              |

### 1.4 MySQL

```bash
mysql --version
```

| Kết quả                     | Hành động                                                                                           |
| --------------------------- | --------------------------------------------------------------------------------------------------- |
| Phiên bản MySQL hiện ra     | ✅ OK — kiểm tra thêm xem service đang chạy không (xem STEP 4.1)                                    |
| `'mysql' is not recognized` | Kiểm tra xem XAMPP MySQL có đang chạy không (module MySQL phải `Running` trong XAMPP Control Panel) |
| XAMPP MySQL không chạy      | ⛔ STOP — báo user: "MySQL chưa được khởi động. Mở XAMPP Control Panel → Start MySQL."              |

---

## STEP 2 — Backend Check (Thư viện PHP)

### 2.1 Kiểm tra thư mục vendor

```bash
ls vendor/autoload.php    # Windows: Test-Path vendor/autoload.php
```

| Kết quả            | Hành động                 |
| ------------------ | ------------------------- |
| File tồn tại       | ✅ OK — tiếp tục STEP 2.2 |
| File không tồn tại | Chạy `composer install`   |

**Xử lý lỗi khi chạy `composer install`:**

| Lỗi                                                            | Hành động                                                                                          |
| -------------------------------------------------------------- | -------------------------------------------------------------------------------------------------- |
| `Your lock file does not contain a compatible set of packages` | Chạy `composer update` thay vì `install`                                                           |
| `PHP extension ... is missing`                                 | ⛔ STOP — báo user extension PHP nào đang thiếu (ví dụ `ext-gd`, `ext-zip`) để bật trong `php.ini` |
| `Memory limit`                                                 | Chạy `php -d memory_limit=-1 $(which composer) install`                                            |
| Lỗi khác                                                       | Ghi lại lỗi đầy đủ, phân loại theo Scenario B2, xử lý trước khi tiếp tục                           |

### 2.2 Kiểm tra tổng thể dự án

```bash
php artisan about
```

| Kết quả                             | Hành động                                                     |
| ----------------------------------- | ------------------------------------------------------------- |
| Bảng thông tin hiện đầy đủ          | ✅ OK — đọc để xác nhận `Environment`, `Cache`, `Drivers`     |
| `Application Key` trống hoặc lỗi    | → Xử lý tại STEP 3.2                                          |
| `Database` hiện `Could not connect` | → Xử lý tại STEP 4                                            |
| `Class ... not found`               | Chạy `composer dump-autoload` rồi thử lại                     |
| Lỗi khác                            | Ghi lại toàn bộ output, phân loại và xử lý trước khi tiếp tục |

---

## STEP 3 — .env Check (Cấu hình môi trường)

### 3.1 Kiểm tra file .env

```bash
ls .env    # Windows: Test-Path .env
```

| Kết quả                                 | Hành động                                                                                          |
| --------------------------------------- | -------------------------------------------------------------------------------------------------- |
| File `.env` tồn tại                     | ✅ OK — tiếp tục STEP 3.2                                                                          |
| Không có `.env` nhưng có `.env.example` | Chạy `cp .env.example .env` (Linux/Mac) hoặc `copy .env.example .env` (Windows)                    |
| Không có cả `.env` lẫn `.env.example`   | ⛔ STOP — báo user: "Không tìm thấy file .env hoặc .env.example. Dự án thiếu cấu hình môi trường." |

### 3.2 Kiểm tra APP_KEY

```bash
php artisan key:generate --show
```

| Kết quả                       | Hành động                                                                 |
| ----------------------------- | ------------------------------------------------------------------------- |
| Key dạng `base64:...` hiện ra | Kiểm tra `APP_KEY` trong `.env` — nếu đã có giá trị khớp thì ✅ OK        |
| `APP_KEY` trong `.env` trống  | Chạy `php artisan key:generate` (không có `--show`) để ghi key vào `.env` |
| Lỗi `Encryption key missing`  | Chạy `php artisan key:generate`                                           |

### 3.3 Kiểm tra cấu hình DB trong .env

Đọc các dòng sau trong `.env` và xác nhận chúng hợp lệ:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=<tên_db>
DB_USERNAME=root
DB_PASSWORD=
```

| Vấn đề                            | Hành động                                                 |
| --------------------------------- | --------------------------------------------------------- |
| `DB_DATABASE` trống               | ⛔ STOP — báo user điền tên database                      |
| `DB_USERNAME` / `DB_PASSWORD` sai | ⛔ STOP — báo user kiểm tra lại thông tin đăng nhập MySQL |

---

## STEP 4 — Database Check

### 4.1 Kiểm tra kết nối DB

```bash
php artisan db:show 2>&1 || php artisan tinker --execute="DB::connection()->getPdo(); echo 'DB OK';"
```

| Kết quả                                         | Hành động                                                                     |
| ----------------------------------------------- | ----------------------------------------------------------------------------- |
| Thông tin DB hiện ra hoặc `DB OK`               | ✅ OK — tiếp tục STEP 4.2                                                     |
| `Unknown database`                              | Tạo database: mở phpMyAdmin → New → nhập `DB_DATABASE` → Create               |
| `Access denied for user`                        | ⛔ STOP — báo user: "Sai username/password MySQL trong .env"                  |
| `Connection refused` / `SQLSTATE[HY000] [2002]` | ⛔ STOP — báo user: "MySQL chưa chạy. Vào XAMPP Control Panel → Start MySQL." |

### 4.2 Kiểm tra trạng thái migration

```bash
php artisan migrate:status
```

| Kết quả                     | Hành động                                         |
| --------------------------- | ------------------------------------------------- |
| Tất cả hàng đều `Ran`       | ✅ OK — tiếp tục STEP 5                           |
| Có hàng `Pending`           | Chạy `php artisan migrate`                        |
| Không có bảng nào (DB rỗng) | Chạy `php artisan migrate` (sẽ tạo tất cả từ đầu) |

**Xử lý lỗi khi chạy `php artisan migrate`:**

| Lỗi                                     | Hành động                                                                            |
| --------------------------------------- | ------------------------------------------------------------------------------------ |
| `SQLSTATE[42S01]: Table already exists` | Chạy `php artisan migrate:status` để xem bảng nào bị trùng, rồi báo user             |
| `Class ... not found`                   | Chạy `composer dump-autoload` rồi thử migrate lại                                    |
| Lỗi foreign key / constraint            | Ghi lại migration file gây lỗi, phân tích thứ tự migration, báo user                 |
| Lỗi khác                                | STOP — không tự ý `migrate:fresh` hay `migrate:rollback` trên dữ liệu thật. Báo user |

> ⚠️ **CẤMM**: Không được chạy `php artisan migrate:fresh` hay `php artisan migrate:rollback` mà không có sự cho phép rõ ràng từ user — sẽ xóa toàn bộ dữ liệu.

### 4.3 Kiểm tra seeder (nếu cần)

```bash
php artisan db:seed --class=DatabaseSeeder 2>&1 | head -20
```

Chỉ chạy bước này nếu user yêu cầu hoặc khi database hoàn toàn rỗng và cần dữ liệu mẫu.

---

## STEP 5 — Frontend Check (Thư viện JS)

### 5.1 Kiểm tra thư mục node_modules

```bash
ls node_modules/.bin/vite 2>&1    # Windows: Test-Path node_modules/.bin/vite
```

| Kết quả       | Hành động                 |
| ------------- | ------------------------- |
| File tồn tại  | ✅ OK — tiếp tục STEP 5.2 |
| Không tồn tại | Chạy `npm install`        |

**Xử lý lỗi khi chạy `npm install`:**

| Lỗi                                          | Hành động                                                         |
| -------------------------------------------- | ----------------------------------------------------------------- |
| `ERESOLVE unable to resolve dependency tree` | Thử `npm install --legacy-peer-deps`                              |
| `EACCES permission denied`                   | Chạy terminal với quyền Admin (Windows)                           |
| `npm ERR! code ENOTFOUND`                    | Kiểm tra mạng, thử `npm install --prefer-offline`                 |
| Lỗi khác                                     | Xóa `node_modules` và `package-lock.json`, chạy lại `npm install` |

### 5.2 Quyết định build frontend

| Tình huống                         | Lệnh            | Ghi chú              |
| ---------------------------------- | --------------- | -------------------- |
| Đang phát triển, cần sửa giao diện | `npm run dev`   | Hot-reload, chạy nền |
| Chỉ cần xem web hoạt động ổn định  | `npm run build` | Build tĩnh một lần   |
| CI/CD hoặc kiểm thử tự động        | `npm run build` | Không cần `dev`      |

**Xử lý lỗi build:**

| Lỗi                               | Hành động                                                          |
| --------------------------------- | ------------------------------------------------------------------ |
| `Cannot find module 'vite'`       | Chạy `npm install` lại                                             |
| `[vite] Internal server error`    | Đọc chi tiết lỗi — thường do import sai đường dẫn trong JS/CSS     |
| Build thành công nhưng assets 404 | Chạy `php artisan storage:link` và kiểm tra `APP_URL` trong `.env` |

---

## STEP 6 — Stripe Check (Thanh toán)

### 6.1 Kiểm tra Stripe CLI

```bash
stripe --version
```

| Kết quả                      | Hành động                                                                             |
| ---------------------------- | ------------------------------------------------------------------------------------- |
| Phiên bản hiện ra            | ✅ OK — tiếp tục 6.2                                                                  |
| `'stripe' is not recognized` | ⛔ STOP — báo user: "Stripe CLI chưa cài. Tải tại https://stripe.com/docs/stripe-cli" |

### 6.2 Kiểm tra trạng thái đăng nhập

```bash
stripe whoami
```

| Kết quả                      | Hành động                                                   |
| ---------------------------- | ----------------------------------------------------------- |
| Tên tài khoản Stripe hiện ra | ✅ OK — tiếp tục 6.3                                        |
| `You are not logged in`      | Chạy `stripe login` và làm theo hướng dẫn (yêu cầu browser) |
| Lỗi mạng                     | Kiểm tra kết nối internet                                   |

### 6.3 Kiểm tra webhook key trong .env

Đọc `.env` và xác nhận:

```
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

| Vấn đề                                  | Hành động                                                            |
| --------------------------------------- | -------------------------------------------------------------------- |
| Thiếu `STRIPE_KEY` hoặc `STRIPE_SECRET` | ⛔ STOP — báo user điền Stripe API keys từ dashboard.stripe.com      |
| `STRIPE_WEBHOOK_SECRET` trống           | Chạy `stripe listen --print-secret` để lấy secret và điền vào `.env` |

### 6.4 Khởi động webhook listener (chỉ khi cần test thanh toán)

```bash
stripe listen --forward-to localhost:8000/stripe/webhook
```

> ⚠️ Lệnh này cần giữ terminal riêng (chạy song song với `php artisan serve`).

---

## STEP 7 — Khởi động Server

Chỉ thực hiện bước này sau khi **tất cả STEP 1–6 đều pass**.

```bash
php artisan serve
```

| Kết quả                                   | Hành động                                       |
| ----------------------------------------- | ----------------------------------------------- |
| `Server running on http://127.0.0.1:8000` | ✅ OK — mở browser kiểm tra                     |
| `Address already in use`                  | Dùng port khác: `php artisan serve --port=8001` |
| `Composer autoload error`                 | Chạy `composer dump-autoload` rồi thử lại       |

---

## Tóm tắt Quick-Check (Chạy lần đầu mỗi phiên)

```
STEP 0: Đúng thư mục? (có file artisan)
STEP 1: php -v ≥ 8.1, composer, node, npm, MySQL đang chạy
STEP 2: vendor/ tồn tại, php artisan about không báo lỗi
STEP 3: .env tồn tại, APP_KEY có giá trị, DB_DATABASE đúng
STEP 4: php artisan migrate:status → không có Pending
STEP 5: node_modules/ tồn tại, build đã chạy
STEP 6: stripe --version OK, STRIPE_* keys có trong .env
STEP 7: php artisan serve → server lên
```

---

## Bảng lỗi phổ biến & cách xử lý nhanh

| Triệu chứng                         | Nguyên nhân thường gặp        | Lệnh sửa nhanh                                        |
| ----------------------------------- | ----------------------------- | ----------------------------------------------------- |
| `Class not found`                   | Autoload chưa cập nhật        | `composer dump-autoload`                              |
| `SQLSTATE[HY000] [2002]`            | MySQL chưa chạy               | Bật MySQL trong XAMPP                                 |
| `No application encryption key`     | APP_KEY trống                 | `php artisan key:generate`                            |
| `The Mix manifest does not exist`   | Frontend chưa build           | `npm run build`                                       |
| `Target class [...] does not exist` | Service Provider chưa đăng ký | `php artisan config:clear && php artisan cache:clear` |
| `419 Page Expired`                  | CSRF token hết hạn            | Clear cache: `php artisan cache:clear`                |
| `Route not found`                   | Route cache cũ                | `php artisan route:clear`                             |
| Assets 404 (CSS/JS không tải)       | Storage link chưa tạo         | `php artisan storage:link`                            |
| `Vite manifest not found`           | Frontend chưa build           | `npm run build` hoặc `npm run dev`                    |

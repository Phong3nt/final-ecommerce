<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * IMP-004: Guest Checkout
 * - Makes user_id nullable so guest orders (no account) can be stored.
 * - Adds guest_email for order confirmation and tracking when user_id is null.
 *
 * Uses raw SQL to avoid doctrine/dbal dependency.
 * SQLite (test DB) requires a table rebuild to change column nullability.
 */
return new class extends Migration {
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            $this->rebuildForSqlite();
        } else {
            // MySQL / PostgreSQL: raw ALTER TABLE — no DBAL needed
            DB::statement('ALTER TABLE orders MODIFY COLUMN user_id BIGINT UNSIGNED NULL');
            DB::statement('ALTER TABLE orders ADD COLUMN guest_email VARCHAR(255) NULL AFTER user_id');
        }
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('ALTER TABLE orders DROP COLUMN guest_email');
        } else {
            DB::statement('ALTER TABLE orders DROP COLUMN guest_email');
            DB::statement('ALTER TABLE orders MODIFY COLUMN user_id BIGINT UNSIGNED NOT NULL');
        }
    }

    /**
     * SQLite does not support ALTER TABLE MODIFY COLUMN.
     * We reconstruct the orders table with user_id nullable + guest_email added.
     */
    private function rebuildForSqlite(): void
    {
        DB::unprepared('PRAGMA foreign_keys = OFF');

        DB::unprepared('
            CREATE TABLE "orders_new" (
                "id"                        integer        NOT NULL PRIMARY KEY AUTOINCREMENT,
                "user_id"                   integer        NULL,
                "guest_email"               varchar(255)   NULL,
                "status"                    varchar(255)   NOT NULL DEFAULT \'pending\',
                "subtotal"                  decimal(10,2)  NOT NULL,
                "shipping_cost"             decimal(10,2)  NOT NULL DEFAULT \'0\',
                "total"                     decimal(10,2)  NOT NULL,
                "shipping_method"           varchar(255)   NOT NULL,
                "shipping_label"            varchar(255)   NOT NULL,
                "address"                   text           NOT NULL,
                "stripe_payment_intent_id"  varchar(255)   NULL,
                "stripe_client_secret"      varchar(255)   NULL,
                "created_at"                datetime       NULL,
                "updated_at"                datetime       NULL,
                "processing_at"             datetime       NULL,
                "shipped_at"                datetime       NULL,
                "delivered_at"              datetime       NULL,
                "cancelled_at"              datetime       NULL,
                "coupon_code"               varchar(255)   NULL,
                "discount_amount"           decimal(8,2)   NULL,
                "refunded_at"               datetime       NULL
            )
        ');

        DB::unprepared('
            INSERT INTO "orders_new" (
                id, user_id, guest_email, status, subtotal, shipping_cost, total,
                shipping_method, shipping_label, address, stripe_payment_intent_id,
                stripe_client_secret, created_at, updated_at, processing_at,
                shipped_at, delivered_at, cancelled_at, coupon_code, discount_amount,
                refunded_at
            )
            SELECT
                id, user_id, NULL, status, subtotal, shipping_cost, total,
                shipping_method, shipping_label, address, stripe_payment_intent_id,
                stripe_client_secret, created_at, updated_at, processing_at,
                shipped_at, delivered_at, cancelled_at, coupon_code, discount_amount,
                refunded_at
            FROM "orders"
        ');

        DB::unprepared('DROP TABLE "orders"');
        DB::unprepared('ALTER TABLE "orders_new" RENAME TO "orders"');

        DB::unprepared('PRAGMA foreign_keys = ON');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('refunded_at')->nullable()->after('cancelled_at');
        });

        // Extend the status enum to include 'refunded' on MySQL/MariaDB.
        // SQLite does not support ALTER TABLE MODIFY COLUMN; the column is
        // already defined with 'refunded' in the create migration.
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement(
                "ALTER TABLE orders MODIFY COLUMN status ENUM('pending','paid','failed','cancelled','processing','shipped','delivered','refunded') NOT NULL DEFAULT 'pending'"
            );
        }
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('refunded_at');
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement(
                "ALTER TABLE orders MODIFY COLUMN status ENUM('pending','paid','failed','cancelled','processing','shipped','delivered') NOT NULL DEFAULT 'pending'"
            );
        }
    }
};

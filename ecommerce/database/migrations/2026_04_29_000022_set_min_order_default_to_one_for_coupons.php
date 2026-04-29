<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('coupons')->whereNull('min_order_amount')->update(['min_order_amount' => 1]);
        DB::table('coupon_templates')->whereNull('min_order_amount')->update(['min_order_amount' => 1]);

        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE coupons MODIFY min_order_amount DECIMAL(10,2) NOT NULL DEFAULT 1.00');
            DB::statement('ALTER TABLE coupon_templates MODIFY min_order_amount DECIMAL(10,2) NOT NULL DEFAULT 1.00');
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE coupons MODIFY min_order_amount DECIMAL(10,2) NULL DEFAULT NULL');
            DB::statement('ALTER TABLE coupon_templates MODIFY min_order_amount DECIMAL(10,2) NULL DEFAULT NULL');
        }
    }
};

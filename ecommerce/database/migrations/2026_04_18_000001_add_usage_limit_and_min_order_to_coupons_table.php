<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->unsignedInteger('usage_limit')->nullable()->after('is_active');
            $table->decimal('min_order_amount', 10, 2)->nullable()->after('usage_limit');
        });
    }

    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->dropColumn(['usage_limit', 'min_order_amount']);
        });
    }
};

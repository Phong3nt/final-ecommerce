<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->string('name')->nullable()->after('code');
            $table->text('description')->nullable()->after('name');
            $table->foreignId('coupon_template_id')->nullable()->after('id')->constrained('coupon_templates')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->after('coupon_template_id')->constrained()->cascadeOnDelete();
            $table->timestamp('assigned_at')->nullable()->after('times_used');

            $table->index('user_id');
            $table->index('coupon_template_id');
            $table->unique(['coupon_template_id', 'user_id'], 'coupons_template_user_unique');
        });
    }

    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->dropUnique('coupons_template_user_unique');
            $table->dropIndex(['user_id']);
            $table->dropIndex(['coupon_template_id']);
            $table->dropConstrainedForeignId('user_id');
            $table->dropConstrainedForeignId('coupon_template_id');
            $table->dropColumn(['name', 'description', 'assigned_at']);
        });
    }
};

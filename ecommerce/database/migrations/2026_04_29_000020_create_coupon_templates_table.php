<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('coupon_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name_template');
            $table->text('description_template')->nullable();
            $table->enum('scope', ['new_user', 'seasonal', 'category']);
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('season')->nullable();
            $table->unsignedSmallInteger('season_year')->nullable();
            $table->enum('type', ['percent', 'fixed']);
            $table->decimal('value', 10, 2);
            $table->unsignedInteger('uses_per_user')->nullable();
            $table->enum('expiry_mode', ['none', 'duration_days', 'fixed_date'])->default('none');
            $table->unsignedSmallInteger('expiry_days')->nullable();
            $table->timestamp('fixed_expires_at')->nullable();
            $table->unsignedInteger('quantity_limit')->nullable();
            $table->unsignedInteger('quantity_issued')->default(0);
            $table->decimal('min_order_amount', 10, 2)->nullable();
            $table->boolean('is_active')->default(false);
            $table->string('code_prefix', 40)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_templates');
    }
};

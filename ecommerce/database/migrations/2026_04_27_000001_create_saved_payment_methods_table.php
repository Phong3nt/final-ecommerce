<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('stripe_payment_method_id', 50);
            $table->string('last4', 4);
            $table->string('brand', 20);
            $table->unsignedSmallInteger('exp_month');
            $table->unsignedSmallInteger('exp_year');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->unique(['user_id', 'stripe_payment_method_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_payment_methods');
    }
};

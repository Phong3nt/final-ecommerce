<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('is_demo')->default(false)->index()->after('refunded_at');
            $table->string('ship_sim_status', 32)->nullable()->after('is_demo');
            $table->timestamp('ship_sim_started_at')->nullable()->after('ship_sim_status');
            $table->timestamp('ship_sim_updated_at')->nullable()->after('ship_sim_started_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['is_demo', 'ship_sim_status', 'ship_sim_started_at', 'ship_sim_updated_at']);
        });
    }
};

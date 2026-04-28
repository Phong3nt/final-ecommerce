<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('spec_processor')->nullable()->after('images');
            $table->string('spec_display')->nullable()->after('spec_processor');
            $table->string('spec_weight')->nullable()->after('spec_display');
            $table->boolean('is_icecat_locked')->default(false)->after('spec_weight');
            $table->string('import_source', 32)->nullable()->after('is_icecat_locked');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['spec_processor', 'spec_display', 'spec_weight', 'is_icecat_locked', 'import_source']);
        });
    }
};

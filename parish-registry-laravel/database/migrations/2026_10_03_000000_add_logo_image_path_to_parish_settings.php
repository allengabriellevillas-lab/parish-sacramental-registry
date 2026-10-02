<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('parish_settings') && !Schema::hasColumn('parish_settings', 'logo_image_path')) {
            Schema::table('parish_settings', function (Blueprint $table) {
                $table->string('logo_image_path', 255)->nullable()->after('address');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('parish_settings') && Schema::hasColumn('parish_settings', 'logo_image_path')) {
            Schema::table('parish_settings', function (Blueprint $table) {
                $table->dropColumn('logo_image_path');
            });
        }
    }
};

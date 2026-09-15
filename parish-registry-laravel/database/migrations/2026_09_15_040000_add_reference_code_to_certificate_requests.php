<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('certificate_requests') && ! Schema::hasColumn('certificate_requests', 'reference_code')) {
            Schema::table('certificate_requests', function (Blueprint $table) {
                $table->string('reference_code', 24)->nullable()->unique()->after('tracking_code');
            });
        }
    }

    public function down(): void
    {
        // Compatibility migration: preserve identifier data on rollback.
    }
};

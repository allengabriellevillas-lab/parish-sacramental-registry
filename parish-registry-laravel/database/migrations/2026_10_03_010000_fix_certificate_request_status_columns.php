<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('certificate_requests') && Schema::hasColumn('certificate_requests', 'status')) {
            Schema::table('certificate_requests', function (Blueprint $table) {
                $table->string('status', 32)->default('submitted')->change();
            });
        }

        if (Schema::hasTable('certificate_request_status_logs') && Schema::hasColumn('certificate_request_status_logs', 'status')) {
            Schema::table('certificate_request_status_logs', function (Blueprint $table) {
                $table->string('status', 32)->change();
            });
        }
    }

    public function down(): void
    {
        // Keep the widened columns to avoid losing status values written after this migration.
    }
};

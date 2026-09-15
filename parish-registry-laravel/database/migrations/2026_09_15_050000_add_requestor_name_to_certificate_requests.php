<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('certificate_requests') && ! Schema::hasColumn('certificate_requests', 'requestor_name')) {
            Schema::table('certificate_requests', function (Blueprint $table) {
                $table->string('requestor_name', 200)->nullable()->after('requester_name');
            });
        }
    }

    public function down(): void
    {
        // Compatibility migration: preserve requester details on rollback.
    }
};

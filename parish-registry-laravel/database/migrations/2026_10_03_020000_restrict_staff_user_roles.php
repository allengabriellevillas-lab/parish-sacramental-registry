<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('staff_users') || ! Schema::hasColumn('staff_users', 'role')) {
            return;
        }

        DB::table('staff_users')->update(['role' => 'staff']);

        Schema::table('staff_users', function (Blueprint $table) {
            $table->enum('role', ['staff'])->default('staff')->change();
        });
    }

    public function down(): void
    {
        // Roles stay staff-only after rollback to avoid restoring privileged role values.
    }
};

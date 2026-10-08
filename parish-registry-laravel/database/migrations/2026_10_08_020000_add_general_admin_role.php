<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('staff_users') && Schema::hasColumn('staff_users', 'role')) {
            Schema::table('staff_users', function (Blueprint $table) {
                $table->enum('role', ['staff', 'admin'])->default('staff')->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('staff_users') && Schema::hasColumn('staff_users', 'role')) {
            \Illuminate\Support\Facades\DB::table('staff_users')->where('role', 'admin')->update(['role' => 'staff']);
            Schema::table('staff_users', function (Blueprint $table) {
                $table->enum('role', ['staff'])->default('staff')->change();
            });
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('admin_activity_logs')) {
            Schema::create('admin_activity_logs', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('admin_user_id')->nullable();
                $table->string('action', 80);
                $table->string('target_type', 80)->nullable();
                $table->unsignedInteger('target_id')->nullable();
                $table->string('summary', 255);
                $table->timestamp('created_at')->useCurrent();
                $table->foreign('admin_user_id')->references('id')->on('staff_users')->nullOnDelete();
                $table->index('created_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_activity_logs');
    }
};

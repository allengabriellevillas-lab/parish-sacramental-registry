<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('parishes')) {
            Schema::create('parishes', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 200)->unique();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        }

        $currentParish = Schema::hasTable('parish_settings')
            ? DB::table('parish_settings')->where('id', 1)->value('parish_name')
            : null;
        $currentParish = trim((string) $currentParish) ?: 'Parish of Our Lady of the Assumption';

        if (! DB::table('parishes')->where('name', $currentParish)->exists()) {
            DB::table('parishes')->insert([
                'name' => $currentParish,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (Schema::hasTable('certificate_requests')) {
            if (! Schema::hasColumn('certificate_requests', 'record_parish_id')) {
                Schema::table('certificate_requests', function (Blueprint $table) {
                    $table->unsignedInteger('record_parish_id')->nullable()->after('sacrament_type');
                });

                Schema::table('certificate_requests', function (Blueprint $table) {
                    $table->foreign('record_parish_id')->references('id')->on('parishes')->nullOnDelete();
                });
            }

            if (! Schema::hasColumn('certificate_requests', 'record_parish_not_listed')) {
                Schema::table('certificate_requests', function (Blueprint $table) {
                    $table->boolean('record_parish_not_listed')->default(false)->after('record_parish_id');
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('certificate_requests') && Schema::hasColumn('certificate_requests', 'record_parish_id')) {
            Schema::table('certificate_requests', function (Blueprint $table) {
                $table->dropForeign(['record_parish_id']);
                $table->dropColumn('record_parish_id');
            });
        }

        if (Schema::hasTable('certificate_requests') && Schema::hasColumn('certificate_requests', 'record_parish_not_listed')) {
            Schema::table('certificate_requests', fn (Blueprint $table) => $table->dropColumn('record_parish_not_listed'));
        }

        Schema::dropIfExists('parishes');
    }
};

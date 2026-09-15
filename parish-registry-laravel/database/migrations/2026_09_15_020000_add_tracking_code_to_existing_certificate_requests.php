<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('certificate_requests') || Schema::hasColumn('certificate_requests', 'tracking_code')) {
            return;
        }

        Schema::table('certificate_requests', function (Blueprint $table) {
            $table->string('tracking_code', 24)->nullable()->unique()->after('id');
        });

        DB::table('certificate_requests')->orderBy('id')->eachById(function ($request) {
            DB::table('certificate_requests')->where('id', $request->id)->update([
                'tracking_code' => 'PCR-LEGACY-' . $request->id,
            ]);
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('certificate_requests') && Schema::hasColumn('certificate_requests', 'tracking_code')) {
            Schema::table('certificate_requests', fn (Blueprint $table) => $table->dropUnique(['tracking_code']));
            Schema::table('certificate_requests', fn (Blueprint $table) => $table->dropColumn('tracking_code'));
        }
    }
};

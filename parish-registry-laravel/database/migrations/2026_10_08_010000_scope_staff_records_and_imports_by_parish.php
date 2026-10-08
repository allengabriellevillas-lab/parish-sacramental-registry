<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $defaultParishId = DB::table('parishes')->orderBy('id')->value('id');

        if (Schema::hasTable('staff_users')) {
            if (! Schema::hasColumn('staff_users', 'parish_id')) {
                Schema::table('staff_users', function (Blueprint $table) {
                    $table->unsignedInteger('parish_id')->nullable()->after('id')->index();
                });
                DB::table('staff_users')->whereNull('parish_id')->update(['parish_id' => $defaultParishId]);
                Schema::table('staff_users', function (Blueprint $table) {
                    $table->foreign('parish_id')->references('id')->on('parishes')->nullOnDelete();
                });
            }
            if (! Schema::hasColumn('staff_users', 'can_manage_parishes')) {
                Schema::table('staff_users', fn (Blueprint $table) => $table->boolean('can_manage_parishes')->default(false)->after('parish_id'));
            }
            $firstStaffId = DB::table('staff_users')->orderBy('id')->value('id');
            if ($firstStaffId) {
                DB::table('staff_users')->where('id', $firstStaffId)->update(['can_manage_parishes' => true]);
            }
        }

        if (Schema::hasTable('sacramental_records') && ! Schema::hasColumn('sacramental_records', 'parish_id')) {
            Schema::table('sacramental_records', function (Blueprint $table) {
                $table->unsignedInteger('parish_id')->nullable()->after('person_id')->index();
            });
            DB::table('sacramental_records')->whereNull('parish_id')->update(['parish_id' => $defaultParishId]);
            Schema::table('sacramental_records', function (Blueprint $table) {
                $table->foreign('parish_id')->references('id')->on('parishes')->nullOnDelete();
            });
        }

        if (Schema::hasTable('sacramental_records')) {
            $locationColumns = ['sacrament_type', 'book_number', 'page_number', 'line_number'];
            foreach (Schema::getIndexes('sacramental_records') as $index) {
                if (($index['unique'] ?? false) && ($index['columns'] ?? []) === $locationColumns) {
                    Schema::table('sacramental_records', fn (Blueprint $table) => $table->dropUnique($index['name']));
                }
            }
            if (! Schema::hasIndex('sacramental_records', ['parish_id', ...$locationColumns], 'unique')) {
                Schema::table('sacramental_records', function (Blueprint $table) use ($locationColumns) {
                    $table->unique(['parish_id', ...$locationColumns], 'sr_parish_record_location_unique');
                });
            }
        }

        if (Schema::hasTable('import_batches') && ! Schema::hasColumn('import_batches', 'parish_id')) {
            Schema::table('import_batches', function (Blueprint $table) {
                $table->unsignedInteger('parish_id')->nullable()->after('id')->index();
            });
            DB::table('import_batches')->whereNull('parish_id')->update(['parish_id' => $defaultParishId]);
            Schema::table('import_batches', function (Blueprint $table) {
                $table->foreign('parish_id')->references('id')->on('parishes')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sacramental_records') && Schema::hasIndex('sacramental_records', ['parish_id', 'sacrament_type', 'book_number', 'page_number', 'line_number'], 'unique')) {
            Schema::table('sacramental_records', function (Blueprint $table) {
                $table->dropUnique('sr_parish_record_location_unique');
                $table->unique(['sacrament_type', 'book_number', 'page_number', 'line_number'], 'sr_record_location_unique');
            });
        }
        foreach (['staff_users', 'sacramental_records', 'import_batches'] as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'parish_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropForeign(['parish_id']);
                    $table->dropColumn('parish_id');
                });
            }
        }
        if (Schema::hasTable('staff_users') && Schema::hasColumn('staff_users', 'can_manage_parishes')) {
            Schema::table('staff_users', fn (Blueprint $table) => $table->dropColumn('can_manage_parishes'));
        }
    }
};

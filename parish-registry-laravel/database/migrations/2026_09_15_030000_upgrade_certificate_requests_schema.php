<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bring installations that already had the original request table up to
     * date.  The create migration is deliberately non-destructive, so it
     * cannot add these fields after it has previously run.
     */
    public function up(): void
    {
        if (! Schema::hasTable('certificate_requests')) {
            return;
        }

        $columns = [
            'status' => fn (Blueprint $table) => $table->string('status', 32)->default('submitted')->index(),
            'requester_name' => fn (Blueprint $table) => $table->string('requester_name', 200)->nullable(),
            'requester_email' => fn (Blueprint $table) => $table->string('requester_email', 150)->nullable(),
            'requester_phone' => fn (Blueprint $table) => $table->string('requester_phone', 50)->nullable(),
            'relationship_to_person' => fn (Blueprint $table) => $table->string('relationship_to_person', 100)->nullable(),
            'purpose' => fn (Blueprint $table) => $table->string('purpose', 150)->nullable(),
            'delivery_method' => fn (Blueprint $table) => $table->string('delivery_method', 32)->default('pickup'),
            'person_first_name' => fn (Blueprint $table) => $table->string('person_first_name', 100)->nullable(),
            'person_middle_name' => fn (Blueprint $table) => $table->string('person_middle_name', 100)->nullable(),
            'person_last_name' => fn (Blueprint $table) => $table->string('person_last_name', 100)->nullable(),
            'person_gender' => fn (Blueprint $table) => $table->string('person_gender', 16)->default('Unknown'),
            'person_date_of_birth' => fn (Blueprint $table) => $table->date('person_date_of_birth')->nullable(),
            'father_name' => fn (Blueprint $table) => $table->string('father_name', 200)->nullable(),
            'mother_maiden_name' => fn (Blueprint $table) => $table->string('mother_maiden_name', 200)->nullable(),
            'spouse_name' => fn (Blueprint $table) => $table->string('spouse_name', 200)->nullable(),
            'event_date' => fn (Blueprint $table) => $table->date('event_date')->nullable(),
            'event_year' => fn (Blueprint $table) => $table->string('event_year', 4)->nullable(),
            'notes' => fn (Blueprint $table) => $table->text('notes')->nullable(),
            'public_note' => fn (Blueprint $table) => $table->text('public_note')->nullable(),
            'staff_notes' => fn (Blueprint $table) => $table->text('staff_notes')->nullable(),
            'attachment_path' => fn (Blueprint $table) => $table->string('attachment_path', 255)->nullable(),
            'attachment_original_name' => fn (Blueprint $table) => $table->string('attachment_original_name', 255)->nullable(),
            'sacramental_record_id' => fn (Blueprint $table) => $table->unsignedInteger('sacramental_record_id')->nullable(),
            'status_updated_by' => fn (Blueprint $table) => $table->unsignedInteger('status_updated_by')->nullable(),
            'submitted_at' => fn (Blueprint $table) => $table->timestamp('submitted_at')->nullable()->useCurrent(),
        ];

        foreach ($columns as $column => $addColumn) {
            if (! Schema::hasColumn('certificate_requests', $column)) {
                Schema::table('certificate_requests', $addColumn);
            }
        }
    }

    public function down(): void
    {
        // This is an additive compatibility migration. Do not remove data on rollback.
    }
};

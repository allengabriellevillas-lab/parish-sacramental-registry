<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('certificate_requests')) {
            Schema::create('certificate_requests', function (Blueprint $table) {
                $table->increments('id');
                $table->string('tracking_code', 24)->unique();
                $table->enum('sacrament_type', ['Baptism', 'Communion', 'Confirmation', 'Marriage', 'Death'])->index();
                $table->enum('status', ['submitted', 'under_review', 'needs_more_info', 'approved', 'ready_for_pickup', 'released', 'rejected', 'not_found'])->default('submitted')->index();
                $table->string('requester_name', 200);
                $table->string('requester_email', 150)->nullable()->index();
                $table->string('requester_phone', 50);
                $table->string('relationship_to_person', 100)->nullable();
                $table->string('purpose', 150);
                $table->enum('delivery_method', ['pickup', 'email_copy', 'courier'])->default('pickup');
                $table->string('person_first_name', 100);
                $table->string('person_middle_name', 100)->nullable();
                $table->string('person_last_name', 100);
                $table->enum('person_gender', ['Male', 'Female', 'Unknown'])->default('Unknown');
                $table->date('person_date_of_birth')->nullable();
                $table->string('father_name', 200)->nullable();
                $table->string('mother_maiden_name', 200)->nullable();
                $table->string('spouse_name', 200)->nullable();
                $table->date('event_date')->nullable();
                $table->string('event_year', 4)->nullable();
                $table->text('notes')->nullable();
                $table->text('public_note')->nullable();
                $table->text('staff_notes')->nullable();
                $table->string('attachment_path', 255)->nullable();
                $table->string('attachment_original_name', 255)->nullable();
                $table->unsignedInteger('sacramental_record_id')->nullable();
                $table->unsignedInteger('status_updated_by')->nullable();
                $table->timestamp('submitted_at')->useCurrent();
                $table->timestamps();
                $table->foreign('sacramental_record_id')->references('id')->on('sacramental_records')->nullOnDelete();
                $table->foreign('status_updated_by')->references('id')->on('staff_users')->nullOnDelete();
                $table->index(['person_last_name', 'person_first_name']);
            });
        }

        if (! Schema::hasTable('certificate_request_status_logs')) {
            Schema::create('certificate_request_status_logs', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('certificate_request_id');
                $table->enum('status', ['submitted', 'under_review', 'needs_more_info', 'approved', 'ready_for_pickup', 'released', 'rejected', 'not_found']);
                $table->text('note')->nullable();
                $table->unsignedInteger('changed_by')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->foreign('certificate_request_id')->references('id')->on('certificate_requests')->cascadeOnDelete();
                $table->foreign('changed_by')->references('id')->on('staff_users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('certificate_request_status_logs');
        Schema::dropIfExists('certificate_requests');
    }
};

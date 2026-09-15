<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('certificate_requests')) {
            Schema::create('certificate_requests', function (Blueprint $table) {
                $table->increments('id');
                $table->string('tracking_code', 24)->unique();
                $table->string('reference_code', 24)->nullable()->unique();
                $table->enum('sacrament_type', ['Baptism', 'Communion', 'Confirmation', 'Marriage', 'Death'])->index();
                $table->enum('status', ['submitted', 'under_review', 'needs_more_info', 'approved', 'ready_for_pickup', 'released', 'rejected', 'not_found'])->default('submitted')->index();
                $table->string('requester_name', 200);
                $table->string('requestor_name', 200)->nullable();
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
                // This table may be added to an existing registry whose ID columns use
                // a different integer size, so keep these relationship columns portable.
            });
        }

        if (Schema::hasTable('parish_settings') && ! DB::table('parish_settings')->where('id', 1)->exists()) {
            DB::table('parish_settings')->insert([
                'id' => 1,
                'parish_name' => 'Parish of Our Lady of the Assumption',
                'diocese_name' => 'Diocese of San Ildefonso',
                'address' => 'Cebu City, Philippines',
                'default_priest_name' => 'Rev. Parish Priest',
                'updated_at' => now(),
            ]);
        }

        if (Schema::hasTable('certificate_templates')) {
            $templates = [
                'Baptism' => ['Certificate of Baptism', 'This is to certify that {name}, born on {dob} to {fatherName} and {motherName}, received the Sacrament of Baptism in this Parish on {eventDate}, according to the rites of the Roman Catholic Church.'],
                'Communion' => ['Certificate of First Holy Communion', 'This is to certify that {name}, born on {dob} to {fatherName} and {motherName}, received First Holy Communion in this Parish on {eventDate}.'],
                'Confirmation' => ['Certificate of Confirmation', 'This is to certify that {name}, born on {dob} to {fatherName} and {motherName}, received the Sacrament of Confirmation in this Parish on {eventDate}.'],
                'Marriage' => ['Certificate of Marriage', 'This is to certify that {name} and {spouse} were joined in Holy Matrimony in this Parish on {eventDate}, according to the rites of the Roman Catholic Church.'],
                'Death' => ['Certificate of Death', 'This is to certify that {name}, born on {dob}, departed this life and was given ecclesiastical rites in this Parish on {eventDate}.'],
            ];

            foreach ($templates as $sacrament => [$title, $body]) {
                if (! DB::table('certificate_templates')->where('sacrament_type', $sacrament)->exists()) {
                    DB::table('certificate_templates')->insert([
                        'sacrament_type' => $sacrament,
                        'title_text' => $title,
                        'body_template' => $body,
                        'footer_note' => 'Not valid without the parish dry seal.',
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('certificate_request_status_logs');
        Schema::dropIfExists('certificate_requests');
    }
};

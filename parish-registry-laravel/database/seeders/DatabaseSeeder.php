<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate([
            'username' => 'admin',
        ], [
            'full_name' => 'System Administrator',
            'email' => 'admin@example.com',
            'password_hash' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        DB::table('parish_settings')->updateOrInsert(['id' => 1], [
            'parish_name' => 'Parish of Our Lady of the Assumption',
            'diocese_name' => 'Diocese of San Ildefonso',
            'address' => 'Cebu City, Philippines',
            'default_priest_name' => 'Rev. Parish Priest',
            'updated_at' => now(),
        ]);

        $templates = [
            'Baptism' => ['Certificate of Baptism', 'This is to certify that {name}, born on {dob} to {fatherName} and {motherName}, received the Sacrament of Baptism in this Parish on {eventDate}, according to the rites of the Roman Catholic Church.'],
            'Communion' => ['Certificate of First Holy Communion', 'This is to certify that {name}, born on {dob} to {fatherName} and {motherName}, received First Holy Communion in this Parish on {eventDate}.'],
            'Confirmation' => ['Certificate of Confirmation', 'This is to certify that {name}, born on {dob} to {fatherName} and {motherName}, received the Sacrament of Confirmation in this Parish on {eventDate}.'],
            'Marriage' => ['Certificate of Marriage', 'This is to certify that {name} and {spouse} were joined in Holy Matrimony in this Parish on {eventDate}, according to the rites of the Roman Catholic Church.'],
            'Death' => ['Certificate of Death', 'This is to certify that {name}, born on {dob}, departed this life and was given ecclesiastical rites in this Parish on {eventDate}.'],
        ];

        foreach ($templates as $sacrament => [$title, $body]) {
            DB::table('certificate_templates')->updateOrInsert(['sacrament_type' => $sacrament], [
                'title_text' => $title,
                'body_template' => $body,
                'footer_note' => 'Not valid without the parish dry seal.',
                'updated_at' => now(),
            ]);
        }
    }
}

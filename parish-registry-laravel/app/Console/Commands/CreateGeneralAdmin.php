<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateGeneralAdmin extends Command
{
    protected $signature = 'admin:create';
    protected $description = 'Create a general administrator account';

    public function handle(): int
    {
        $fullName = trim((string) $this->ask('Administrator name'));
        $username = trim((string) $this->ask('Username'));
        $email = trim((string) $this->ask('Email address (optional)')) ?: null;
        $validator = Validator::make(compact('fullName', 'username', 'email'), [
            'fullName' => 'required|string|max:150',
            'username' => 'required|string|max:80|alpha_dash|unique:staff_users,username',
            'email' => 'nullable|email|max:150|unique:staff_users,email',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return self::FAILURE;
        }

        $password = $this->secret('Password (at least 8 characters)');
        if (strlen((string) $password) < 8) {
            $this->error('The admin password must be at least 8 characters.');
            return self::FAILURE;
        }
        if (! hash_equals((string) $password, (string) $this->secret('Confirm password'))) {
            $this->error('The passwords do not match.');
            return self::FAILURE;
        }

        User::create([
            'full_name' => $fullName,
            'username' => $username,
            'email' => $email,
            'parish_id' => null,
            'can_manage_parishes' => false,
            'password_hash' => Hash::make($password),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->info("Administrator account '{$username}' created. Sign in at /admin.");
        return self::SUCCESS;
    }
}

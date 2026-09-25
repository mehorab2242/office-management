<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class ProductionAdminUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! app()->environment('production')) {
            return;
        }

        foreach (['ridwan@crm.com', 'ahmed@crm.com', 'khalid@crm.com'] as $email) {
            User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => 'Office Admin',
                    'password' => 'OfficeDemo123!',
                    'role' => User::ROLE_SUPER_ADMIN,
                    'is_active' => true,
                ],
            );
        }
    }
}

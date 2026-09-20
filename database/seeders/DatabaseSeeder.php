<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the single admin account from ADMIN_EMAIL / ADMIN_PASSWORD in .env.
     */
    public function run(): void
    {
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if (! $email || ! $password) {
            $this->command->warn('ADMIN_EMAIL / ADMIN_PASSWORD not set in .env — skipping admin user creation.');

            return;
        }

        User::updateOrCreate(
            ['email' => $email],
            ['name' => 'Admin', 'password' => $password]
        );

        $this->command->info("Admin user ready: {$email}");

        $this->call(FeedSeeder::class);
    }
}

<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the single admin account from ADMIN_USERNAME / ADMIN_PASSWORD in .env.
     */
    public function run(): void
    {
        $username = env('ADMIN_USERNAME');
        $password = env('ADMIN_PASSWORD');

        if (! $username || ! $password) {
            $this->command->warn('ADMIN_USERNAME / ADMIN_PASSWORD not set in .env — skipping admin user creation.');

            return;
        }

        User::updateOrCreate(
            ['username' => $username],
            ['name' => 'Admin', 'email' => $username.'@local', 'password' => $password]
        );

        $this->command->info("Admin user ready: {$username}");

        $this->call(FeedSeeder::class);
    }
}

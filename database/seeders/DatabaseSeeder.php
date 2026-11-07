<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // No demo accounts or tokens. Create your administrator with app:install.
        $this->call(RolesSeeder::class);
    }
}

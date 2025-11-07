<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Institusi;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SlugTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create test institutions
        $institusi1 = Institusi::create([
            'nama' => 'Universitas Test ABC',
            'slug' => 'univ-test-abc',
            'feeder_url' => 'https://feeder-test.example.com',
            'feeder_username' => 'test_user',
            'feeder_password' => 'test_password',
        ]);

        $institusi2 = Institusi::create([
            'nama' => 'Institut Test XYZ',
            'slug' => 'inst-test-xyz',
            'feeder_url' => 'https://feeder-xyz.example.com',
            'feeder_username' => 'xyz_user',
            'feeder_password' => 'xyz_password',
        ]);

        // Create test users
        User::create([
            'name' => 'User Test ABC',
            'email' => 'user@univ-test-abc.edu',
            'password' => Hash::make('password123'),
            'institusi_id' => $institusi1->id,
        ]);

        User::create([
            'name' => 'User Test XYZ',
            'email' => 'user@inst-test-xyz.edu',
            'password' => Hash::make('password123'),
            'institusi_id' => $institusi2->id,
        ]);

        User::create([
            'name' => 'Admin Test ABC',
            'email' => 'admin@univ-test-abc.edu',
            'password' => Hash::make('admin123'),
            'institusi_id' => $institusi1->id,
        ]);

        $this->command->info('Test data created successfully!');
        $this->command->info('Test credentials:');
        $this->command->info('1. user@univ-test-abc.edu / password123 (slug: univ-test-abc)');
        $this->command->info('2. user@inst-test-xyz.edu / password123 (slug: inst-test-xyz)');
        $this->command->info('3. admin@univ-test-abc.edu / admin123 (slug: univ-test-abc)');
    }
}

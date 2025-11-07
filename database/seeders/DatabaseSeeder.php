<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        Role::create(['name' => 'sadmin']);
        Role::create(['name' => 'admin']);

        $this->call([
            DataSeeder::class,
        ]);

        $admin = User::factory()->create([
            'name' => 'Admin Super',
            'email' => 'sadmin@example.com',
            'institusi_id' => 1,
        ]);
        $admin->assignRole('sadmin');

        $admin = User::factory()->create([
            'name' => 'Admin User UIT',
            'email' => 'admin@example.com',
            'institusi_id' => 1,
        ]);
        $admin->assignRole('admin');

        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // buat data untuk 5 data pada table institusis:

        $data = [
            [
                'nama' => 'Universitas Indonesia Timur',
                'slug' => 'uit',
                'feeder_url' => 'http://100.97.191.56:8501/ws/live2.php',
                'feeder_username' => 'ibrahim4min@gmail.com',
                'feeder_password' => 'Pdpt@0000',
            ],
            [
                'nama' => 'Universitas Hasanuddin',
                'slug' => 'unhas',
                'feeder_url' => 'https://unhas.ac.id:8080',
                'feeder_username' => 'admin_unhas',
                'feeder_password' => 'password123',
            ],
            [
                'nama' => 'Universitas Muslim Indonesia',
                'slug' => 'umi',
                'feeder_url' => 'https://umi.ac.id:8080',
                'feeder_username' => 'admin_umi',
                'feeder_password' => 'password789',
            ],
        ];

        \App\Models\Institusi::insert($data);
    }
}

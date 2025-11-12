<?php

namespace Database\Seeders;

use App\Models\Institusi;
use Illuminate\Database\Seeder;

class InstitusiThemeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Contoh institusi dengan berbagai theme
        $institusis = [
            [
                'nama' => 'Universitas Indonesia Timur',
                'slug' => 'uit',
                'theme_primary_color' => 'blue',
                'theme_secondary_color' => 'indigo',
                'theme_accent_color' => 'sky',
                'theme_mode' => 'auto',
            ],
            [
                'nama' => 'Universitas Negeri Makassar',
                'slug' => 'unm',
                'theme_primary_color' => 'emerald',
                'theme_secondary_color' => 'teal',
                'theme_accent_color' => 'green',
                'theme_mode' => 'light',
            ],
            [
                'nama' => 'Universitas Hasanuddin',
                'slug' => 'unhas',
                'theme_primary_color' => 'red',
                'theme_secondary_color' => 'orange',
                'theme_accent_color' => 'rose',
                'theme_mode' => 'auto',
            ],
            [
                'nama' => 'Institut Teknologi Sepuluh Nopember',
                'slug' => 'its',
                'theme_primary_color' => 'purple',
                'theme_secondary_color' => 'violet',
                'theme_accent_color' => 'fuchsia',
                'theme_mode' => 'dark',
            ],
            [
                'nama' => 'Universitas Gadjah Mada',
                'slug' => 'ugm',
                'theme_primary_color' => 'amber',
                'theme_secondary_color' => 'yellow',
                'theme_accent_color' => 'orange',
                'theme_mode' => 'light',
            ],
        ];

        foreach ($institusis as $data) {
            Institusi::updateOrCreate(
                ['slug' => $data['slug']],
                $data
            );
        }

        $this->command->info('Institusi dengan theme berhasil di-seed!');
    }
}

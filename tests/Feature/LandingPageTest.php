<?php

declare(strict_types=1);

use App\Models\Institusi;

test('landing page menampilkan informasi institusi dengan slug yang valid', function () {
    $institusi = Institusi::factory()->create([
        'nama' => 'Universitas Indonesia Timur',
        'slug' => 'uit',
    ]);

    $response = $this->get('/uit');

    $response->assertStatus(200)
        ->assertSee('Universitas Indonesia Timur')
        ->assertSee('Transformasi Data PDDikti')
        ->assertSee('Menjadi Keputusan Strategis')
        ->assertSee('Pantau Kinerja Prodi')
        ->assertSee('Analisis Mahasiswa Mendalam')
        ->assertSee('Percepat Akreditasi', false); // false = don't escape HTML
});

test('landing page menampilkan 404 ketika slug institusi tidak ditemukan', function () {
    $response = $this->get('/institusi-tidak-ada');

    $response->assertNotFound()
        ->assertSee('404')
        ->assertSee('Halaman Tidak Ditemukan')
        ->assertSee('institusi atau halaman yang Anda cari tidak ditemukan');
});

test('landing page memiliki link ke dashboard', function () {
    $institusi = Institusi::factory()->create(['slug' => 'unhas']);

    $response = $this->get('/unhas');

    $response->assertStatus(200)
        ->assertSee(route('admin.dashboard', $institusi));
});

test('landing page responsive dan support dark mode', function () {
    $institusi = Institusi::factory()->create(['slug' => 'test']);

    $response = $this->get('/test');

    $response->assertStatus(200)
        ->assertSee('darkMode')
        ->assertSee('dark:');
});

test('landing page menampilkan semua section utama', function () {
    $institusi = Institusi::factory()->create([
        'nama' => 'Test University',
        'slug' => 'test-uni',
    ]);

    $response = $this->get('/test-uni');

    $response->assertStatus(200)
        // Hero section
        ->assertSee('Test University')
        // Features section
        ->assertSee('Fitur Unggulan')
        // Benefits section
        ->assertSee('Kekuatan Analisis Akademik')
        // CTA section
        ->assertSee('Siap Membaca Masa Depan Kampus Anda');
});

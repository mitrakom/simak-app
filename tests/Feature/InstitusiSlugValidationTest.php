<?php

declare(strict_types=1);

use App\Models\Institusi;

test('institusi dengan slug valid dapat diakses', function () {
    $institusi = Institusi::factory()->create([
        'nama' => 'Universitas Indonesia Timur',
        'slug' => 'uit',
    ]);

    $response = $this->get('/'.$institusi->slug);

    $response->assertStatus(200);
});

test('institusi dengan slug tidak terdaftar return 404', function () {
    $response = $this->get('/institusi-tidak-ada');

    $response->assertNotFound();
});

test('institusi tidak bisa menggunakan slug terlarang default', function () {
    expect(fn () => Institusi::factory()->create([
        'nama' => 'Default University',
        'slug' => 'default',
    ]))->toThrow(\InvalidArgumentException::class);
});

test('institusi tidak bisa menggunakan slug terlarang admin', function () {
    expect(fn () => Institusi::factory()->create([
        'nama' => 'Admin University',
        'slug' => 'admin',
    ]))->toThrow(\InvalidArgumentException::class);
});

test('institusi tidak bisa menggunakan slug terlarang api', function () {
    expect(fn () => Institusi::factory()->create([
        'nama' => 'API University',
        'slug' => 'api',
    ]))->toThrow(\InvalidArgumentException::class);
});

test('institusi tidak bisa menggunakan slug terlarang auth', function () {
    expect(fn () => Institusi::factory()->create([
        'nama' => 'Auth University',
        'slug' => 'auth',
    ]))->toThrow(\InvalidArgumentException::class);
});

test('institusi tidak bisa menggunakan slug dengan format tidak valid', function () {
    expect(fn () => Institusi::factory()->create([
        'nama' => 'Invalid Slug University',
        'slug' => 'INVALID_SLUG',
    ]))->toThrow(\InvalidArgumentException::class);
});

test('institusi tidak bisa menggunakan slug dengan spasi', function () {
    expect(fn () => Institusi::factory()->create([
        'nama' => 'Space Slug University',
        'slug' => 'with space',
    ]))->toThrow(\InvalidArgumentException::class);
});

test('institusi dapat menggunakan slug dengan dash', function () {
    $institusi = Institusi::factory()->create([
        'nama' => 'Universitas Negeri Makassar',
        'slug' => 'unm-makassar',
    ]);

    expect($institusi->slug)->toBe('unm-makassar');
});

test('mengakses slug terlarang default redirect ke 404', function () {
    // Buat institusi dengan slug terlarang secara manual (bypass validation untuk test)
    $institusi = new Institusi;
    $institusi->nama = 'Default Test';
    $institusi->slug = 'default';
    $institusi->saveQuietly(); // Save tanpa trigger event

    $response = $this->get('/default');

    $response->assertNotFound();

    // Cleanup
    $institusi->delete();
});

test('path root tidak memerlukan slug institusi', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});

test('middleware validate institusi exists diterapkan pada semua route institusi', function () {
    $institusi = Institusi::factory()->create([
        'slug' => 'test-institusi',
    ]);

    // Test landing page
    $response = $this->get('/'.$institusi->slug);
    $response->assertStatus(200);

    // Test login page
    $response = $this->get('/'.$institusi->slug.'/auth/login');
    $response->assertStatus(200);
});

<?php

use App\Jobs\SyncProdiJob;
use App\Livewire\Admin\Synchronize;
use App\Models\Institusi;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    Queue::fake();

    $this->institusi = Institusi::factory()->create();
    $this->user = User::factory()->create(['institusi_id' => $this->institusi->id]);
});

it('renders successfully', function () {
    actingAs($this->user);

    Livewire::test(Synchronize::class)
        ->assertStatus(200)
        ->assertSee('Data Synchronization')
        ->assertSee('Sinkronisasi Semua');
});

it('displays all sync jobs', function () {
    actingAs($this->user);

    Livewire::test(Synchronize::class)
        ->assertSee('Program Studi')
        ->assertSee('Dosen')
        ->assertSee('Mahasiswa')
        ->assertSee('Akademik Mahasiswa')
        ->assertSee('Aktivitas Mahasiswa');
});

it('can toggle expandable row', function () {
    actingAs($this->user);

    Livewire::test(Synchronize::class)
        ->call('toggleRow', 'sync_mahasiswa')
        ->assertSet('expandedRows', ['sync_mahasiswa'])
        ->call('toggleRow', 'sync_mahasiswa')
        ->assertSet('expandedRows', []);
});

it('can sync individual job', function () {
    actingAs($this->user);

    Livewire::test(Synchronize::class)
        ->call('syncJob', 'sync_prodi')
        ->assertHasNoErrors();

    Queue::assertPushed(SyncProdiJob::class);
});

it('can sync all jobs', function () {
    actingAs($this->user);

    Livewire::test(Synchronize::class)
        ->call('syncAll')
        ->assertHasNoErrors();

    Queue::assertPushed(SyncProdiJob::class);
});

it('shows synchronize page from route', function () {
    actingAs($this->user);

    $this->get(route('admin.synchronize', ['institusi' => $this->institusi->slug]))
        ->assertSeeLivewire(Synchronize::class)
        ->assertSee('Data Synchronization');
});

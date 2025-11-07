<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncStatus extends Model
{
    protected $fillable = [
        'institusi_id',
        'sync_type',
        'status',
        'total_records',
        'current_progress',
        'progress_message',
        'error_message',
        'last_sync_time',
        'sync_process_id'
    ];

    protected $casts = [
        'institusi_id' => 'integer',
        'total_records' => 'integer',
        'current_progress' => 'integer',
        'last_sync_time' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Relasi ke Institusi
     */
    public function institusi(): BelongsTo
    {
        return $this->belongsTo(Institusi::class);
    }

    /**
     * Scope: Get last sync status untuk institusi
     */
    public static function getLastStatusForInstitusi(int $institusiId): array
    {
        $syncTypes = [
            'dosen' => 'Data Dosen',
            'mahasiswa' => 'Data Mahasiswa',
            'prodi' => 'Program Studi',
            'nilai_mahasiswa' => 'Nilai Mahasiswa',
            'akademik_mahasiswa' => 'Akademik Mahasiswa',
            'prestasi_mahasiswa' => 'Prestasi Mahasiswa',
            'bimbingan_ta' => 'Bimbingan TA',
            'lulusan' => 'Data Lulusan',
            'aktivitas_mahasiswa' => 'Aktivitas Mahasiswa',
            'dosen_akreditasi' => 'Dosen Akreditasi'
        ];

        $statuses = [];
        foreach ($syncTypes as $syncType => $label) {
            $status = self::where('institusi_id', $institusiId)
                ->where('sync_type', $syncType)
                ->orderByDesc('created_at')
                ->first();

            // Icon mapping
            $icons = [
                'dosen' => '👨‍🏫',
                'mahasiswa' => '👨‍🎓',
                'prodi' => '🎓',
                'nilai_mahasiswa' => '📊',
                'akademik_mahasiswa' => '📖',
                'prestasi_mahasiswa' => '🏆',
                'bimbingan_ta' => '📚',
                'lulusan' => '👔',
                'aktivitas_mahasiswa' => '📋',
                'dosen_akreditasi' => '🔍'
            ];

            $statuses[] = [
                'sync_type' => $syncType,
                'label' => $label,
                'icon' => $icons[$syncType] ?? '📌',
                'status' => $status?->status ?? 'pending',
                'last_sync_time' => $status?->last_sync_time,
                'total_records' => $status?->total_records ?? 0,
                'current_progress' => $status?->current_progress ?? 0,
                'progress_message' => $status?->progress_message,
                'error_message' => $status?->error_message,
                'sync_process_id' => $status?->sync_process_id
            ];
        }

        return $statuses;
    }

    /**
     * Scope: Get status untuk sync type spesifik
     */
    public static function getLastStatusBySyncType(int $institusiId, string $syncType): ?array
    {
        $status = self::where('institusi_id', $institusiId)
            ->where('sync_type', $syncType)
            ->orderByDesc('created_at')
            ->first();

        if (!$status) {
            return null;
        }

        $labels = [
            'dosen' => 'Data Dosen',
            'mahasiswa' => 'Data Mahasiswa',
            'prodi' => 'Program Studi',
            'nilai_mahasiswa' => 'Nilai Mahasiswa',
            'akademik_mahasiswa' => 'Akademik Mahasiswa',
            'prestasi_mahasiswa' => 'Prestasi Mahasiswa',
            'bimbingan_ta' => 'Bimbingan TA',
            'lulusan' => 'Data Lulusan',
            'aktivitas_mahasiswa' => 'Aktivitas Mahasiswa',
            'dosen_akreditasi' => 'Dosen Akreditasi'
        ];

        $icons = [
            'dosen' => '👨‍🏫',
            'mahasiswa' => '👨‍🎓',
            'prodi' => '🎓',
            'nilai_mahasiswa' => '📊',
            'akademik_mahasiswa' => '📖',
            'prestasi_mahasiswa' => '🏆',
            'bimbingan_ta' => '📚',
            'lulusan' => '👔',
            'aktivitas_mahasiswa' => '📋',
            'dosen_akreditasi' => '🔍'
        ];

        return [
            'sync_type' => $status->sync_type,
            'label' => $labels[$syncType] ?? $syncType,
            'icon' => $icons[$syncType] ?? '📌',
            'status' => $status->status,
            'last_sync_time' => $status->last_sync_time,
            'total_records' => $status->total_records,
            'current_progress' => $status->current_progress,
            'progress_message' => $status->progress_message,
            'error_message' => $status->error_message,
            'sync_process_id' => $status->sync_process_id
        ];
    }
}

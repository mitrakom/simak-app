<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncBatchProgress extends Model
{
    protected $table = 'sync_batch_progress';

    protected $fillable = [
        'institusi_id',
        'batch_id',
        'sync_type',
        'status',
        'total_records',
        'processed_records',
        'failed_records',
        'success_count',
        'error_count',
        'progress_percentage',
        'error_message',
        'summary',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'institusi_id' => 'integer',
        'total_records' => 'integer',
        'processed_records' => 'integer',
        'failed_records' => 'integer',
        'success_count' => 'integer',
        'error_count' => 'integer',
        'progress_percentage' => 'integer',
        'summary' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relasi ke Institusi
     */
    public function institusi(): BelongsTo
    {
        return $this->belongsTo(Institusi::class);
    }

    /**
     * Update progress percentage based on processed records
     */
    public function updateProgress(int $processed, int $failed = 0): self
    {
        $this->processed_records = $processed;
        $this->failed_records = $failed;

        // Calculate success count
        $this->success_count = $processed - $failed;
        $this->error_count = $failed;

        if ($this->total_records > 0) {
            $this->progress_percentage = (int) round(($processed / $this->total_records) * 100);
        }

        $this->save();

        return $this;
    }

    /**
     * Mark batch as completed
     */
    public function markCompleted(array $summary = []): self
    {
        $this->status = 'completed';
        $this->progress_percentage = 100;
        $this->completed_at = now();
        $this->summary = $summary;
        $this->save();

        return $this;
    }

    /**
     * Mark batch as failed
     */
    public function markFailed(string $errorMessage, array $summary = []): self
    {
        $this->status = 'failed';
        $this->error_message = $errorMessage;
        $this->completed_at = now();
        $this->summary = $summary;
        $this->save();

        return $this;
    }

    /**
     * Mark batch as processing
     */
    public function markProcessing(): self
    {
        $this->status = 'processing';
        $this->started_at = $this->started_at ?? now();
        $this->save();

        return $this;
    }

    /**
     * Mark batch as cancelled
     */
    public function markCancelled(string $reason = 'Dibatalkan oleh pengguna'): self
    {
        $this->status = 'cancelled';
        $this->error_message = $reason;
        $this->completed_at = now();
        $this->save();

        return $this;
    }

    /**
     * Get human-readable status with icon
     */
    public function getStatusWithIcon(): array
    {
        $icons = [
            'pending' => '⏳',
            'processing' => '🔄',
            'completed' => '✅',
            'failed' => '❌',
            'cancelled' => '⊘',
        ];

        $labels = [
            'pending' => 'Menunggu',
            'processing' => 'Memproses',
            'completed' => 'Selesai',
            'failed' => 'Gagal',
            'cancelled' => 'Dibatalkan',
        ];

        return [
            'status' => $this->status,
            'label' => $labels[$this->status] ?? $this->status,
            'icon' => $icons[$this->status] ?? '📌',
        ];
    }
}

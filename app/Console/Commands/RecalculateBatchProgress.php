<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\SyncBatchProgress;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;

class RecalculateBatchProgress extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:recalculate-progress 
                            {--all : Recalculate all processing batches}
                            {--batch-id= : Specific batch ID to recalculate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate batch progress from Laravel batch statistics (fixes race condition issues)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔄 Starting batch progress recalculation...');
        $this->newLine();

        if ($batchId = $this->option('batch-id')) {
            // Recalculate specific batch
            return $this->recalculateSpecificBatch($batchId);
        }

        if ($this->option('all')) {
            // Recalculate all processing batches
            return $this->recalculateAllProcessing();
        }

        // Default: recalculate all finished batches that are still marked as processing
        return $this->recalculateStuckBatches();
    }

    /**
     * Recalculate specific batch by ID
     */
    protected function recalculateSpecificBatch(string $batchId): int
    {
        $batch = SyncBatchProgress::where('batch_id', $batchId)->first();

        if (! $batch) {
            $this->error("❌ Batch not found: {$batchId}");

            return self::FAILURE;
        }

        return $this->recalculateBatch($batch) ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Recalculate all processing batches
     */
    protected function recalculateAllProcessing(): int
    {
        $batches = SyncBatchProgress::where('status', 'processing')->get();

        if ($batches->isEmpty()) {
            $this->info('✅ No processing batches found');

            return self::SUCCESS;
        }

        $this->info("Found {$batches->count()} processing batch(es)");
        $this->newLine();

        $fixed = 0;
        $failed = 0;

        foreach ($batches as $batch) {
            if ($this->recalculateBatch($batch)) {
                $fixed++;
            } else {
                $failed++;
            }
        }

        $this->newLine();
        $this->info("✅ Recalculation complete: {$fixed} fixed, {$failed} failed");

        return self::SUCCESS;
    }

    /**
     * Recalculate only stuck batches (Laravel batch finished but still marked as processing)
     */
    protected function recalculateStuckBatches(): int
    {
        $batches = SyncBatchProgress::where('status', 'processing')->get();

        if ($batches->isEmpty()) {
            $this->info('✅ No processing batches found');

            return self::SUCCESS;
        }

        $this->info("Checking {$batches->count()} processing batch(es) for stuck status...");
        $this->newLine();

        $fixed = 0;
        $stillRunning = 0;
        $failed = 0;

        foreach ($batches as $batch) {
            $laravelBatch = Bus::findBatch($batch->batch_id);

            if (! $laravelBatch) {
                $this->warn("⚠️  Laravel batch not found: {$batch->batch_id}");
                $failed++;

                continue;
            }

            // Only recalculate if Laravel batch is finished
            if ($laravelBatch->finished()) {
                if ($this->recalculateBatch($batch, $laravelBatch)) {
                    $fixed++;
                } else {
                    $failed++;
                }
            } else {
                $stillRunning++;
            }
        }

        $this->newLine();

        if ($fixed > 0) {
            $this->info("✅ Fixed {$fixed} stuck batch(es)");
        }

        if ($stillRunning > 0) {
            $this->info("ℹ️  {$stillRunning} batch(es) still running");
        }

        if ($failed > 0) {
            $this->warn("⚠️  {$failed} batch(es) failed to recalculate");
        }

        return self::SUCCESS;
    }

    /**
     * Recalculate single batch
     */
    protected function recalculateBatch(SyncBatchProgress $batch, $laravelBatch = null): bool
    {
        try {
            // Get Laravel batch if not provided
            if (! $laravelBatch) {
                $laravelBatch = Bus::findBatch($batch->batch_id);

                if (! $laravelBatch) {
                    $this->error("  ❌ Laravel batch not found: {$batch->batch_id}");

                    return false;
                }
            }

            // Calculate discrepancy
            $before = $batch->processed_records;
            $processedRecords = $laravelBatch->totalJobs;
            $failedRecords = $laravelBatch->failedJobs;
            $successCount = $processedRecords - $failedRecords;
            $discrepancy = $processedRecords - $before;

            // Update batch progress
            $batch->update([
                'processed_records' => $processedRecords,
                'success_count' => $successCount,
                'failed_records' => $failedRecords,
                'progress_percentage' => 100,
            ]);

            // Mark as completed
            $summary = [
                'message' => 'Sinkronisasi selesai (auto-recalculated)',
                'total_records' => $batch->total_records,
                'processed_records' => $processedRecords,
                'success_count' => $successCount,
                'failed_records' => $failedRecords,
            ];

            $batch->markCompleted($summary);

            // Show result
            $statusIcon = $discrepancy > 0 ? '🔧' : '✅';
            $message = $discrepancy > 0
                ? "Fixed {$discrepancy} missing record(s)"
                : 'Already accurate';

            $this->line("  {$statusIcon} Batch {$batch->id}: {$processedRecords}/{$batch->total_records} - {$message}");

            return true;
        } catch (\Exception $e) {
            $this->error("  ❌ Failed to recalculate batch {$batch->id}: {$e->getMessage()}");

            return false;
        }
    }
}

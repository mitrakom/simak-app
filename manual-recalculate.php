<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\SyncBatchProgress;
use Illuminate\Support\Facades\Bus;

echo "=== Manual Batch Progress Recalculation (All Sync Types) ===\n\n";

// Get all processing batches (all sync types)
$batches = SyncBatchProgress::where('status', 'processing')
    ->orderBy('id', 'desc')
    ->get();

if ($batches->isEmpty()) {
    echo "❌ No processing batches found\n";
    exit(0);
}

echo "Found {$batches->count()} processing batch(es):\n\n";

$totalFixed = 0;
$totalBatchesFixed = 0;

foreach ($batches as $batch) {
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Batch ID: {$batch->id}\n";
    echo "  Sync Type: {$batch->sync_type}\n";
    echo "  Laravel Batch ID: {$batch->batch_id}\n";
    echo "  Current: {$batch->processed_records}/{$batch->total_records} ({$batch->progress_percentage}%)\n";
    echo "  Status: {$batch->status}\n\n";

    // Get Laravel batch
    $laravelBatch = Bus::findBatch($batch->batch_id);

    if (!$laravelBatch) {
        echo "  ❌ Laravel batch not found - skipping\n\n";
        continue;
    }

    echo "  Laravel Batch:\n";
    echo "    Total Jobs: {$laravelBatch->totalJobs}\n";
    echo "    Processed: {$laravelBatch->processedJobs()}\n";
    echo "    Failed: {$laravelBatch->failedJobs}\n";
    echo "    Finished: " . ($laravelBatch->finished() ? 'Yes' : 'No') . "\n";

    // Check if batch is finished
    if (!$laravelBatch->finished()) {
        echo "  ⏳ Batch not finished yet - skipping\n\n";
        continue;
    }

    // Calculate discrepancy
    $discrepancy = $laravelBatch->totalJobs - $batch->processed_records;
    echo "  Discrepancy: {$discrepancy} records\n\n";

    // Recalculate
    echo "  🔧 Recalculating...\n";

    $processedRecords = $laravelBatch->totalJobs;
    $failedRecords = $laravelBatch->failedJobs;
    $successCount = $processedRecords - $failedRecords;

    $batch->update([
        'processed_records' => $processedRecords,
        'success_count' => $successCount,
        'failed_records' => $failedRecords,
        'progress_percentage' => 100,
    ]);

    $summary = [
        'message' => 'Sinkronisasi selesai (manual recalculation)',
        'total_records' => $batch->total_records,
        'processed_records' => $processedRecords,
        'success_count' => $successCount,
        'failed_records' => $failedRecords,
    ];

    if ($failedRecords > 0) {
        $batch->markFailed('Some jobs failed', $summary);
    } else {
        $batch->markCompleted($summary);
    }

    echo "  ✅ Fixed! Status: {$batch->fresh()->status}\n";
    echo "    Processed: {$processedRecords}/{$batch->total_records}\n";
    echo "    Success: {$successCount}, Failed: {$failedRecords}\n";

    if ($discrepancy > 0) {
        echo "    📊 Corrected {$discrepancy} missing records\n";
        $totalFixed += $discrepancy;
    }

    $totalBatchesFixed++;
    echo "\n";
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "✅ Summary:\n";
echo "  Total batches processed: {$totalBatchesFixed}\n";
echo "  Total records corrected: {$totalFixed}\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

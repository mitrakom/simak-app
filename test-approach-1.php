<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Jobs\SyncDosenJob;
use App\Models\Institusi;
use App\Models\SyncBatchProgress;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;

echo "=== Testing Approach #1: Progress from Laravel Batch ===\n\n";

// Get institusi
$institusi = Institusi::where('slug', 'uit')->first();

if (!$institusi) {
    echo "❌ Institusi not found\n";
    exit(1);
}

echo "✓ Found institusi: {$institusi->nama} (ID: {$institusi->id})\n\n";

// Dispatch sync job
$syncProcessId = 'test_approach1_' . uniqid();
echo "Starting sync job...\n";
echo "Sync Process ID: {$syncProcessId}\n\n";

SyncDosenJob::dispatch($institusi->id, $syncProcessId);

sleep(3); // Wait for job to initialize

// Find batch progress
$batchProgress = SyncBatchProgress::where('sync_type', 'dosen')
    ->where('institusi_id', $institusi->id)
    ->latest()
    ->first();

if (!$batchProgress) {
    echo "❌ Batch progress not found\n";
    exit(1);
}

echo "✓ Batch created: ID={$batchProgress->id}\n";
echo "  Laravel Batch ID: {$batchProgress->batch_id}\n";
echo "  Total Records: {$batchProgress->total_records}\n\n";

// Monitor progress from Laravel Batch (simulating what Livewire does)
echo "Monitoring progress from Laravel Batch (polling every 5 seconds)...\n";
echo str_repeat("━", 80) . "\n";

$maxChecks = 24; // 2 minutes max
$checkCount = 0;
$previousProcessed = 0;

while ($checkCount < $maxChecks) {
    $checkCount++;

    // Refresh batch progress from DB
    $batchProgress->refresh();

    // Get Laravel Batch
    $laravelBatch = Bus::findBatch($batchProgress->batch_id);

    if (!$laravelBatch) {
        echo "❌ Laravel batch not found\n";
        break;
    }

    // Calculate progress from Laravel Batch
    $processedJobs = $laravelBatch->processedJobs();
    $totalJobs = $laravelBatch->totalJobs;
    $failedJobs = $laravelBatch->failedJobs;
    $progressPct = $totalJobs > 0 ? round(($processedJobs / $totalJobs) * 100) : 0;
    $increment = $processedJobs - $previousProcessed;

    // Display progress
    printf(
        "[%02d] Laravel Batch: %4d/%d (%3d%%) | Failed: %d | Increment: +%d | DB Status: %s\n",
        $checkCount,
        $processedJobs,
        $totalJobs,
        $progressPct,
        $failedJobs,
        $increment,
        $batchProgress->status
    );

    $previousProcessed = $processedJobs;

    // Check if finished
    if ($laravelBatch->finished()) {
        echo str_repeat("━", 80) . "\n";
        echo "✅ Laravel Batch finished!\n\n";

        // Wait a bit for callback to execute
        echo "Waiting for batch callback to finalize stats...\n";
        sleep(3);

        break;
    }

    sleep(5);
}

// Final verification
echo "\n" . str_repeat("━", 80) . "\n";
echo "=== Final Verification ===\n\n";

$batchProgress->refresh();
$finalBatch = Bus::findBatch($batchProgress->batch_id);

echo "Laravel Batch Stats:\n";
echo "  Total Jobs: {$finalBatch->totalJobs}\n";
echo "  Processed: {$finalBatch->processedJobs()}\n";
echo "  Failed: {$finalBatch->failedJobs}\n";
echo "  Finished: " . ($finalBatch->finished() ? 'Yes' : 'No') . "\n\n";

echo "Database Batch Progress:\n";
echo "  Status: {$batchProgress->status}\n";
echo "  Progress: {$batchProgress->progress_percentage}%\n";
echo "  Processed: {$batchProgress->processed_records}/{$batchProgress->total_records}\n";
echo "  Success: {$batchProgress->success_count}\n";
echo "  Failed: {$batchProgress->failed_records}\n";

if ($batchProgress->completed_at) {
    echo "  Completed: {$batchProgress->completed_at->diffForHumans()}\n";
}

echo "\n" . str_repeat("━", 80) . "\n";
echo "=== Test Results ===\n\n";

$laravelProcessed = $finalBatch->processedJobs();
$dbProcessed = $batchProgress->processed_records;
$discrepancy = abs($laravelProcessed - $dbProcessed);

if ($batchProgress->status === 'completed' && $batchProgress->progress_percentage === 100) {
    echo "✅ SUCCESS: Batch completed with 100% accuracy!\n";

    if ($discrepancy === 0) {
        echo "✅ PERFECT: Zero discrepancy between Laravel Batch and DB!\n";
    } else {
        echo "⚠️  WARNING: Discrepancy of {$discrepancy} records\n";
    }
} else {
    echo "❌ FAIL: Batch not completed properly\n";
    echo "   Status: {$batchProgress->status} (expected: completed)\n";
    echo "   Progress: {$batchProgress->progress_percentage}% (expected: 100%)\n";
}

echo "\n=== Approach #1 Test Complete ===\n";

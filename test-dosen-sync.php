<?php

declare(strict_types=1);

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Jobs\SyncDosenJob;
use App\Models\Institusi;
use App\Models\SyncBatchProgress;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;

echo '=== Testing Dosen Sync Job ===' . PHP_EOL . PHP_EOL;

// Get UIT institusi
$institusi = Institusi::where('slug', 'uit')->first();

if (! $institusi) {
    echo '❌ Institusi UIT not found!' . PHP_EOL;
    exit(1);
}

echo '✓ Found institusi: ' . $institusi->nama . ' (ID: ' . $institusi->id . ')' . PHP_EOL . PHP_EOL;

// Generate unique sync process ID
$syncProcessId = uniqid('test_sync_dosen_', true);

echo 'Starting sync job...' . PHP_EOL;
echo 'Sync Process ID: ' . $syncProcessId . PHP_EOL . PHP_EOL;

// Dispatch the job
try {
    SyncDosenJob::dispatch($institusi->id, $syncProcessId);
    echo '✓ Job dispatched successfully!' . PHP_EOL . PHP_EOL;
} catch (\Exception $e) {
    echo '❌ Failed to dispatch job: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}

// Wait a moment for job to start
echo 'Waiting 3 seconds for job to initialize...' . PHP_EOL;
sleep(3);

// Find the batch progress record
$batchProgress = SyncBatchProgress::where('institusi_id', $institusi->id)
    ->where('sync_type', 'dosen')
    ->latest('id')
    ->first();

if (! $batchProgress) {
    echo '❌ Batch progress not found!' . PHP_EOL;
    exit(1);
}

echo '✓ Found batch progress (ID: ' . $batchProgress->id . ')' . PHP_EOL;
echo '  Laravel Batch ID: ' . $batchProgress->batch_id . PHP_EOL . PHP_EOL;

// Monitor progress
echo 'Monitoring progress (will check every 5 seconds for up to 2 minutes)...' . PHP_EOL;
echo str_repeat('-', 80) . PHP_EOL;

$maxChecks = 24; // 2 minutes (24 * 5 seconds)
$checkCount = 0;
$lastProcessed = 0;

while ($checkCount < $maxChecks) {
    $checkCount++;

    // Refresh batch data
    $batchProgress->refresh();

    // Get Laravel batch info
    $laravelBatch = Bus::findBatch($batchProgress->batch_id);

    // Calculate if progress increased
    $progressIncrease = $batchProgress->processed_records - $lastProcessed;
    $lastProcessed = $batchProgress->processed_records;

    // Display progress
    echo sprintf(
        '[%02d] Status: %-10s | Progress: %3d%% | Records: %4d/%4d (+%d) | Failed: %3d | Laravel Jobs: %4d/%4d',
        $checkCount,
        $batchProgress->status,
        $batchProgress->progress_percentage,
        $batchProgress->processed_records,
        $batchProgress->total_records,
        $progressIncrease,
        $batchProgress->failed_records,
        $laravelBatch ? $laravelBatch->processedJobs() : 0,
        $laravelBatch ? $laravelBatch->totalJobs : 0
    ) . PHP_EOL;

    // Check if completed
    if ($batchProgress->status === 'completed') {
        echo str_repeat('-', 80) . PHP_EOL;
        echo '✓ Batch completed successfully!' . PHP_EOL . PHP_EOL;
        break;
    }

    // Check if all jobs finished but not marked as completed
    if ($laravelBatch && $laravelBatch->finished() && $batchProgress->status !== 'completed') {
        echo str_repeat('-', 80) . PHP_EOL;
        echo '⚠️  WARNING: Laravel batch finished but status is still: ' . $batchProgress->status . PHP_EOL;

        $missing = $batchProgress->total_records - $batchProgress->processed_records;
        if ($missing > 0) {
            echo '⚠️  Missing records: ' . $missing . ' (Race condition detected!)' . PHP_EOL;
        }

        break;
    }

    // Wait before next check
    if ($checkCount < $maxChecks && $batchProgress->status !== 'completed') {
        sleep(5);
    }
}

// Final status
echo PHP_EOL . '=== Final Status ===' . PHP_EOL;
$batchProgress->refresh();

echo 'Batch ID: ' . $batchProgress->id . PHP_EOL;
echo 'Status: ' . $batchProgress->status . PHP_EOL;
echo 'Progress: ' . $batchProgress->progress_percentage . '%' . PHP_EOL;
echo 'Total Records: ' . $batchProgress->total_records . PHP_EOL;
echo 'Processed Records: ' . $batchProgress->processed_records . PHP_EOL;
echo 'Success Count: ' . $batchProgress->success_count . PHP_EOL;
echo 'Failed Records: ' . $batchProgress->failed_records . PHP_EOL;
echo 'Started At: ' . $batchProgress->started_at . PHP_EOL;
echo 'Completed At: ' . ($batchProgress->completed_at ?? 'Not completed') . PHP_EOL;

// Verify accuracy
$laravelBatch = Bus::findBatch($batchProgress->batch_id);
if ($laravelBatch) {
    echo PHP_EOL . '=== Laravel Batch Verification ===' . PHP_EOL;
    echo 'Total Jobs: ' . $laravelBatch->totalJobs . PHP_EOL;
    echo 'Processed Jobs: ' . $laravelBatch->processedJobs() . PHP_EOL;
    echo 'Pending Jobs: ' . $laravelBatch->pendingJobs . PHP_EOL;
    echo 'Failed Jobs: ' . $laravelBatch->failedJobs . PHP_EOL;
    echo 'Finished: ' . ($laravelBatch->finished() ? 'Yes' : 'No') . PHP_EOL;

    // Check for discrepancy
    $discrepancy = $laravelBatch->totalJobs - $batchProgress->processed_records;
    echo PHP_EOL . '=== Accuracy Check ===' . PHP_EOL;

    if ($discrepancy === 0) {
        echo '✓ PASS: All jobs tracked correctly!' . PHP_EOL;
        echo '✓ No race condition detected' . PHP_EOL;
    } else {
        echo '❌ FAIL: Missing ' . abs($discrepancy) . ' records in tracking!' . PHP_EOL;
        echo '❌ Race condition still exists!' . PHP_EOL;
    }

    if ($batchProgress->status === 'completed' && $batchProgress->progress_percentage === 100) {
        echo '✓ PASS: Status correctly set to completed' . PHP_EOL;
        echo '✓ PASS: Progress correctly set to 100%' . PHP_EOL;
    } else {
        echo '❌ FAIL: Status is ' . $batchProgress->status . ' (expected: completed)' . PHP_EOL;
        echo '❌ FAIL: Progress is ' . $batchProgress->progress_percentage . '% (expected: 100%)' . PHP_EOL;
    }
}

echo PHP_EOL . '=== Test Complete ===' . PHP_EOL;

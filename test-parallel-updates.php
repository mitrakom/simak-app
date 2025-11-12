<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Testing TRUE Parallel Updates with Processes ===\n\n";

// Create test table
DB::statement('DROP TABLE IF EXISTS test_parallel');
DB::statement('CREATE TABLE test_parallel (
    id INT PRIMARY KEY AUTO_INCREMENT, 
    processed INT DEFAULT 0,
    success INT DEFAULT 0,
    total INT DEFAULT 1000
)');
DB::table('test_parallel')->insert(['processed' => 0, 'success' => 0, 'total' => 1000]);

echo "✓ Test table created\n";
echo "Starting 1000 concurrent updates using parallel processes...\n\n";

$totalUpdates = 1000;
$batchSize = 100; // Update in batches to avoid too many processes

for ($batch = 0; $batch < $totalUpdates / $batchSize; $batch++) {
    $pids = [];

    // Fork processes for parallel execution
    for ($i = 0; $i < $batchSize; $i++) {
        $pid = pcntl_fork();

        if ($pid == -1) {
            die("Failed to fork process\n");
        } elseif ($pid == 0) {
            // Child process - do the update
            DB::reconnect(); // Important: reconnect in child process

            DB::table('test_parallel')
                ->where('id', 1)
                ->update([
                    'processed' => DB::raw('processed + 1'),
                    'success' => DB::raw('success + 1'),
                ]);

            exit(0); // Exit child process
        } else {
            // Parent process - store PID
            $pids[] = $pid;
        }
    }

    // Wait for all children in this batch to finish
    foreach ($pids as $pid) {
        pcntl_waitpid($pid, $status);
    }

    // Show progress
    $current = ($batch + 1) * $batchSize;
    echo "Completed: {$current}/{$totalUpdates}\n";
}

// Check results
$result = DB::table('test_parallel')->where('id', 1)->first();

echo "\n=== Results ===\n";
echo "Expected: 1000\n";
echo "Processed: {$result->processed}\n";
echo "Success: {$result->success}\n";
echo "Missing: " . (1000 - $result->processed) . "\n";

if ($result->processed === 1000 && $result->success === 1000) {
    echo "\n✅ SUCCESS: All updates recorded correctly!\n";
    echo "✅ NO race condition detected!\n";
} else {
    echo "\n❌ FAIL: Race condition detected!\n";
    echo "❌ Lost " . (1000 - $result->processed) . " updates\n";
    echo "❌ Accuracy: " . round(($result->processed / 1000) * 100, 2) . "%\n";
}

// Cleanup
DB::statement('DROP TABLE test_parallel');

echo "\n✓ Test table cleaned up\n";

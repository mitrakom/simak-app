<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Testing Query Consistency After Atomic Update ===\n\n";

// Use latest batch from previous test
$progress = DB::table('sync_batch_progress')
    ->orderBy('id', 'desc')
    ->first();

if (!$progress) {
    echo "❌ No batch found\n";
    exit(1);
}

echo "Using batch: ID={$progress->id}, processed={$progress->processed_records}\n";
$before = $progress->processed_records;

// Update using DB::table() with DB::raw()
DB::table('sync_batch_progress')
    ->where('id', $progress->id)
    ->update([
        'processed_records' => DB::raw('processed_records + 1'),
    ]);

echo "Updated via DB::table() with DB::raw()\n\n";

// Query immediately - 5 times
echo "Testing query consistency:\n";
for ($i = 1; $i <= 5; $i++) {
    $value = DB::table('sync_batch_progress')
        ->where('id', $progress->id)
        ->value('processed_records');

    $status = ($value === $before + 1) ? '✓' : '✗';
    echo "  Query #{$i}: {$value} {$status}\n";

    usleep(10000); // 10ms delay
}

// Final check
$final = DB::table('sync_batch_progress')
    ->where('id', $progress->id)
    ->value('processed_records');

echo "\n";
if ($final === $before + 1) {
    echo "✅ All queries returned consistent, correct value\n";
} else {
    echo "❌ Queries returned inconsistent values\n";
}

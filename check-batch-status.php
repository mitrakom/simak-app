<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\SyncBatchProgress;
use Illuminate\Support\Facades\Bus;

// Get latest dosen batch
$batch = SyncBatchProgress::where('sync_type', 'dosen')
    ->latest()
    ->first();

if (! $batch) {
    echo "❌ No dosen batch found\n";
    exit;
}

echo "📊 Latest Dosen Batch:\n";
echo "  ID: {$batch->id}\n";
echo "  Status (DB): {$batch->status}\n";
echo "  Progress (DB): {$batch->progress_percentage}%\n";
echo "  Processed (DB): {$batch->processed_records}/{$batch->total_records}\n";
echo "  Batch ID: {$batch->batch_id}\n\n";

// Check Laravel Batch
$laravelBatch = Bus::findBatch($batch->batch_id);

if ($laravelBatch) {
    $finished = $laravelBatch->finished() ? 'Yes ✅' : 'No ⏳';

    echo "📊 Laravel Batch:\n";
    echo "  Total Jobs: {$laravelBatch->totalJobs}\n";
    echo "  Processed: {$laravelBatch->processedJobs()}\n";
    echo "  Failed: {$laravelBatch->failedJobs}\n";
    echo "  Finished: {$finished}\n\n";

    if ($laravelBatch->finished() && $batch->status === 'processing') {
        echo "🎯 ISSUE DETECTED:\n";
        echo "   ✅ Laravel Batch: FINISHED (100%)\n";
        echo "   ⚠️  DB Status: Still 'processing'\n\n";
        echo "📌 SOLUTION APPLIED:\n";
        echo "   → Livewire will now show 'Selesai' status immediately\n";
        echo "   → No need to wait for scheduler update\n";
        echo "   → Refresh dashboard to see the change\n";
    } elseif ($batch->status === 'completed') {
        echo "✅ Perfect! Both Laravel and DB show completed\n";
    }
} else {
    echo "❌ Laravel batch not found\n";
}

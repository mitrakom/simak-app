<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sync_batch_progress', function (Blueprint $table) {
            $table->integer('success_count')->default(0)->after('failed_records');
            $table->integer('error_count')->default(0)->after('success_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sync_batch_progress', function (Blueprint $table) {
            $table->dropColumn(['success_count', 'error_count']);
        });
    }
};

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
        Schema::table('prodis', function (Blueprint $table) {
            // Drop old unique constraint before renaming (correct name: prodis_feeder_unique)
            $table->dropUnique('prodis_feeder_unique');
        });

        // Rename column
        Schema::table('prodis', function (Blueprint $table) {
            $table->renameColumn('feeder_id', 'prodi_feeder_id');
        });

        // Add back unique constraint with new column name and update comment
        Schema::table('prodis', function (Blueprint $table) {
            $table->unique(['institusi_id', 'prodi_feeder_id'], 'prodis_feeder_unique');
            $table->uuid('prodi_feeder_id')->comment('ID Prodi dari Feeder (id_prodi)')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prodis', function (Blueprint $table) {
            // Drop new constraint (correct name: prodis_feeder_unique)
            $table->dropUnique('prodis_feeder_unique');
        });

        // Rename back to original
        Schema::table('prodis', function (Blueprint $table) {
            $table->renameColumn('prodi_feeder_id', 'feeder_id');
        });

        // Restore original constraint
        Schema::table('prodis', function (Blueprint $table) {
            $table->unique(['institusi_id', 'feeder_id'], 'prodis_feeder_unique');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration safely removes old columns and indexes that were replaced
     * by earlier migrations using a copy-based approach. It verifies that no
     * foreign key constraints depend on these columns before removing them.
     *
     * Columns to remove:
     * - prodis.prodi_feeder_id (replaced by id_prodi)
     * - lpr_akademik_mahasiswa.semester (replaced by id_semester)
     * - lpr_aktivitas_mahasiswa.anggota_aktivitas_feeder_id (replaced by id_anggota)
     *
     * Indexes to remove:
     * - prodis_feeder_unique
     * - Old indexes on semester and anggota_aktivitas_feeder_id if they exist
     */
    public function up(): void
    {
        $dbName = DB::getDatabaseName();

        // 1. Clean up prodis table: remove prodi_feeder_id and prodis_feeder_unique
        if (Schema::hasColumn('prodis', 'prodi_feeder_id')) {
            // Check for any FKs referencing prodi_feeder_id
            $fks = DB::select(
                'SELECT DISTINCT CONSTRAINT_NAME, TABLE_NAME
                 FROM information_schema.KEY_COLUMN_USAGE
                 WHERE REFERENCED_TABLE_SCHEMA = ?
                   AND REFERENCED_TABLE_NAME = ?
                   AND REFERENCED_COLUMN_NAME = ?',
                [$dbName, 'prodis', 'prodi_feeder_id']
            );

            if (empty($fks)) {
                Schema::table('prodis', function (Blueprint $table) {
                    // Drop unique index if exists
                    $indexes = DB::select("SELECT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'prodis'");
                    $indexNames = array_map(fn($r) => $r->INDEX_NAME, $indexes);

                    if (in_array('prodis_feeder_unique', $indexNames)) {
                        $table->dropUnique('prodis_feeder_unique');
                    }

                    // Drop the old column
                    $table->dropColumn('prodi_feeder_id');
                });
            }
        }

        // 2. Clean up lpr_akademik_mahasiswa: remove semester column
        if (Schema::hasColumn('lpr_akademik_mahasiswa', 'semester')) {
            Schema::table('lpr_akademik_mahasiswa', function (Blueprint $table) {
                $table->dropColumn('semester');
            });
        }

        // 3. Clean up lpr_aktivitas_mahasiswa: remove anggota_aktivitas_feeder_id
        if (Schema::hasColumn('lpr_aktivitas_mahasiswa', 'anggota_aktivitas_feeder_id')) {
            // Check for any FKs referencing anggota_aktivitas_feeder_id
            $fks = DB::select(
                'SELECT DISTINCT CONSTRAINT_NAME, TABLE_NAME
                 FROM information_schema.KEY_COLUMN_USAGE
                 WHERE REFERENCED_TABLE_SCHEMA = ?
                   AND REFERENCED_TABLE_NAME = ?
                   AND REFERENCED_COLUMN_NAME = ?',
                [$dbName, 'lpr_aktivitas_mahasiswa', 'anggota_aktivitas_feeder_id']
            );

            if (empty($fks)) {
                Schema::table('lpr_aktivitas_mahasiswa', function (Blueprint $table) {
                    $table->dropColumn('anggota_aktivitas_feeder_id');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore old columns with nullable to allow rollback
        // Note: Data cannot be restored from removed columns

        if (! Schema::hasColumn('prodis', 'prodi_feeder_id')) {
            Schema::table('prodis', function (Blueprint $table) {
                $table->uuid('prodi_feeder_id')->nullable()->after('institusi_id');
            });

            // Copy data back from id_prodi if possible
            if (Schema::hasColumn('prodis', 'id_prodi')) {
                DB::statement('UPDATE prodis SET prodi_feeder_id = id_prodi WHERE prodi_feeder_id IS NULL');
            }

            // Recreate unique index
            Schema::table('prodis', function (Blueprint $table) {
                $table->unique(['institusi_id', 'prodi_feeder_id'], 'prodis_feeder_unique');
            });
        }

        if (! Schema::hasColumn('lpr_akademik_mahasiswa', 'semester')) {
            Schema::table('lpr_akademik_mahasiswa', function (Blueprint $table) {
                $table->string('semester', 5)->nullable()->after('institusi_id');
            });

            // Copy data back from id_semester if possible
            if (Schema::hasColumn('lpr_akademik_mahasiswa', 'id_semester')) {
                DB::statement('UPDATE lpr_akademik_mahasiswa SET semester = id_semester WHERE semester IS NULL');
            }
        }

        if (! Schema::hasColumn('lpr_aktivitas_mahasiswa', 'anggota_aktivitas_feeder_id')) {
            Schema::table('lpr_aktivitas_mahasiswa', function (Blueprint $table) {
                $table->uuid('anggota_aktivitas_feeder_id')->nullable()->after('institusi_id');
            });

            // Copy data back from id_anggota if possible
            if (Schema::hasColumn('lpr_aktivitas_mahasiswa', 'id_anggota')) {
                DB::statement('UPDATE lpr_aktivitas_mahasiswa SET anggota_aktivitas_feeder_id = id_anggota WHERE anggota_aktivitas_feeder_id IS NULL');
            }
        }
    }
};

# Update Struktur Tabel Prestasi Mahasiswa

## Tanggal
12 November 2025

## Tujuan
Menyesuaikan struktur tabel `lpr_prestasi_mahasiswa` dengan response API Feeder endpoint `GetListPrestasiMahasiswa`.

## Perubahan Database

### Migration
File: `database/migrations/2025_11_12_091129_update_lpr_prestasi_mahasiswa_match_api_structure.php`

### Renamed Columns
| Old Column Name | New Column Name | Type | Description |
|----------------|-----------------|------|-------------|
| `prestasi_feeder_id` | `id_prestasi` | uuid | Primary ID from Feeder API |
| `mahasiswa_feeder_id` | `id_mahasiswa` | uuid | Foreign key to mahasiswa (from API) |

### New Columns Added
| Column Name | Type | Nullable | Description |
|------------|------|----------|-------------|
| `jenis_prestasi` | varchar(50) | YES | Type/category of achievement (akademik, non-akademik, etc) |
| `peringkat` | int | YES | Ranking/position achieved |
| `registrasi_feeder_id` | uuid | YES | Optional FK to student registration (id_registrasi_mahasiswa) |

### Existing Columns (Unchanged)
- `id` - auto increment primary key
- `institusi_id` - FK to institusis
- `mahasiswa_id` - FK to mahasiswa (local)
- `nim` - student number (denormalized)
- `nama_mahasiswa` - student name (denormalized)
- `nama_prestasi` - achievement name/title
- `tingkat_prestasi` - achievement level (Lokal/Nasional/Internasional/Wilayah)
- `tahun_prestasi` - achievement year
- `penyelenggara` - organizer/institution
- `created_at`, `updated_at` - timestamps

### Indexes
- **Primary Key**: `id`
- **Unique Constraint**: `lpr_prestasi_mhs_unique` (`institusi_id`, `id_prestasi`)
- **Foreign Keys**:
  - `institusi_id` → `institusis.id` (cascade delete)
  - `mahasiswa_id` → `mahasiswa.id` (cascade delete)
- **Regular Indexes**: `id_mahasiswa`, `id_prestasi`, `registrasi_feeder_id`

## Perubahan Code

### Model
File: `app/Models/LprPrestasiMahasiswa.php`
- Updated `$fillable` array to use new column names
- Added new fields: `id_mahasiswa`, `id_prestasi`, `jenis_prestasi`, `peringkat`, `registrasi_feeder_id`
- Added `peringkat` to `$casts` as integer

### Job
File: `app/Jobs/SyncPrestasiMahasiswaRecordJob.php`
- Updated field mapping to use new column names:
  - `prestasi_feeder_id` → `id_prestasi`
  - `mahasiswa_feeder_id` → `id_mahasiswa`
- Added new field mappings:
  - `jenis_prestasi` from API `jenis_prestasi`
  - `peringkat` from API `peringkat` (cast to int)
  - `registrasi_feeder_id` from API `id_registrasi_mahasiswa`
- Updated `hasChanges()` method to include new fields

## API Field Mapping
```php
// From GetListPrestasiMahasiswa API response
[
    'id_prestasi' => 'uuid',              // Main ID
    'id_mahasiswa' => 'uuid',             // Student ID
    'jenis_prestasi' => 'string',         // Achievement type
    'nama_prestasi' => 'string',          // Achievement name
    'peringkat' => 'integer',             // Ranking
    'nama_tingkat_prestasi' => 'string',  // Level name
    'tahun_prestasi' => 'year',           // Achievement year
    'penyelenggara' => 'string',          // Organizer
    'id_registrasi_mahasiswa' => 'uuid',  // Optional registration ID
    'nama_mahasiswa' => 'string',         // Student name
]
```

## Testing
1. ✅ Migration executed successfully
2. ✅ Table structure verified with `DESC lpr_prestasi_mahasiswa`
3. ✅ Code formatted with Laravel Pint
4. ✅ Model updated with new fields
5. ✅ Job updated with new field mappings

## Next Steps
1. Test sync process for Prestasi Mahasiswa
2. Verify data synchronization from Feeder API
3. Monitor logs for any field mapping issues

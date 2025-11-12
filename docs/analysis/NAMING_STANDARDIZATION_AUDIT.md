# Audit Standardisasi Naming Convention - Database Schema

**Tanggal**: 10 November 2025  
**Tujuan**: Standardisasi semua field `feeder_id` ke naming yang konsisten  
**Status**: AUDIT COMPLETE - Ready for Refactoring

---

## 1. POLA STANDAR YANG DISEPAKATI

### Feeder API → Database Mapping

```
Feeder API Field              Database Column Name
────────────────────────────────────────────────────────────
id_mahasiswa               →  mahasiswa_feeder_id
id_registrasi_mahasiswa    →  registrasi_feeder_id
id_prodi                   →  prodi_feeder_id
id_dosen                   →  dosen_feeder_id
id_aktivitas               →  aktivitas_feeder_id
id_prestasi                →  prestasi_feeder_id
id_penelitian              →  penelitian_feeder_id
id_anggota                 →  anggota_aktivitas_feeder_id
id_bimbing_mahasiswa       →  bimbingan_feeder_id
id_matkul                  →  matkul_feeder_id
```

### Laravel Relationship (Tidak Berubah)

```
mahasiswa_id  →  Foreign Key ke mahasiswa.id
prodi_id      →  Foreign Key ke prodis.id
dosen_id      →  Foreign Key ke dosen.id
```

---

## 2. AUDIT RESULT: STATUS PER TABEL

### ❌ TABEL YANG PERLU REFACTORING

#### 2.1 Tabel Master

| Tabel | Current Column | Should Be | Migration File |
|-------|---------------|-----------|----------------|
| **mahasiswa** | \`feeder_id\` | \`mahasiswa_feeder_id\` | \`2025_10_21_033856_create_mahasiswa_table.php\` |
| **mahasiswa** | ❌ MISSING | \`registrasi_feeder_id\` | (NEW COLUMN) |
| **prodis** | \`feeder_id\` | \`prodi_feeder_id\` | \`2025_10_20_093920_create_prodis_table.php\` |
| **dosen** | \`feeder_id\` | \`dosen_feeder_id\` | \`2025_10_21_033847_create_dosen_table.php\` |

### ✅ TABEL YANG SUDAH BENAR (LPR)

#### 2.2 Tabel Laporan

| Tabel | Columns | Status |
|-------|---------|--------|
| **lpr_akademik_mahasiswa** | \`mahasiswa_feeder_id\`<br>\`registrasi_feeder_id\` | ✅ CORRECT |
| **lpr_nilai_mahasiswa** | \`mahasiswa_feeder_id\`<br>\`registrasi_feeder_id\`<br>\`matkul_feeder_id\` | ✅ CORRECT |
| **lpr_aktivitas_mahasiswa** | \`mahasiswa_feeder_id\`<br>\`registrasi_feeder_id\`<br>\`aktivitas_feeder_id\`<br>\`anggota_aktivitas_feeder_id\` | ✅ CORRECT |
| **lpr_lulusan** | \`mahasiswa_feeder_id\`<br>\`registrasi_feeder_id\` | ✅ CORRECT |
| **lpr_prestasi_mahasiswa** | \`mahasiswa_feeder_id\`<br>\`prestasi_feeder_id\` | ✅ CORRECT<br>⚠️ MISSING \`registrasi_feeder_id\` |
| **lpr_bimbingan_ta** | \`mahasiswa_feeder_id\`<br>\`dosen_feeder_id\`<br>\`aktivitas_feeder_id\`<br>\`bimbingan_feeder_id\` | ✅ CORRECT<br>⚠️ MISSING \`registrasi_feeder_id\` |
| **lpr_penelitian_dosen** | \`dosen_feeder_id\`<br>\`penelitian_feeder_id\` | ✅ CORRECT |
| **lpr_dosen_akreditasi** | \`dosen_feeder_id\` | ✅ CORRECT |

---

## 3. SUMMARY

### Scope Pekerjaan

**3 Tabel Master** yang perlu rename column:
- mahasiswa: \`feeder_id\` → \`mahasiswa_feeder_id\`
- prodis: \`feeder_id\` → \`prodi_feeder_id\`
- dosen: \`feeder_id\` → \`dosen_feeder_id\`

**1 Kolom Baru** di tabel mahasiswa:
- \`registrasi_feeder_id\` (PRIMARY identifier untuk enrollment)

**2 Kolom Optional** di tabel LPR:
- lpr_prestasi_mahasiswa: add \`registrasi_feeder_id\`
- lpr_bimbingan_ta: add \`registrasi_feeder_id\`

### File Code yang Perlu Update

**Models (3 files):**
- app/Models/Mahasiswa.php
- app/Models/Prodi.php
- app/Models/Dosen.php

**Sync Jobs (8+ files):**
- app/Jobs/SyncMahasiswaRecordJob.php ⭐ CRITICAL
- app/Jobs/SyncProdiRecordJob.php
- app/Jobs/SyncDosenRecordJob.php
- app/Jobs/SyncAkademikMahasiswaRecordJob.php
- app/Jobs/SyncAktivitasMahasiswaRecordJob.php
- app/Jobs/SyncPrestasiMahasiswaRecordJob.php
- app/Jobs/SyncLulusanRecordJob.php
- app/Jobs/SyncBimbinganTaRecordJob.php

---

## 4. NEXT STEPS

1. **Create Migration Files** (6 migrations)
2. **Update Sync Job Code** (use new column names)
3. **Update Model Files** (if needed)
4. **Test on Staging** (full sync test)
5. **Deploy to Production**

**Estimated Time**: 4-5 hours total

---

**Document Version**: 1.0  
**Created**: November 10, 2025  
**Status**: ✅ Audit Complete - Ready for Implementation

# Manual Tests

Folder ini berisi dokumentasi test manual yang dilakukan oleh tim development.

## Format File

Gunakan format Markdown (.md) untuk setiap file test manual dengan struktur berikut:

```markdown
# Test: [Nama Fitur/Fungsi]

**Tanggal Test**: [DD/MM/YYYY]  
**Tester**: [Nama Tester]  
**Build/Version**: [Versi aplikasi]

## Tujuan Test
Deskripsi singkat tujuan dari test ini.

## Pre-kondisi
- Kondisi 1
- Kondisi 2

## Langkah Test

### Test Case 1: [Nama Test Case]
1. Langkah pertama
2. Langkah kedua
3. Langkah ketiga

**Expected Result**: Apa yang diharapkan terjadi  
**Actual Result**: Apa yang benar-benar terjadi  
**Status**: ✅ PASS / ❌ FAIL

### Test Case 2: [Nama Test Case]
...

## Catatan
Catatan tambahan atau observasi selama testing.

## Screenshot
(Opsional) Tambahkan screenshot jika diperlukan
```

## Naming Convention

- Format nama file: `YYYY-MM-DD-nama-fitur-test.md`
- Contoh: `2025-11-08-login-multi-tenant-test.md`

## Kategori Test Manual

- **Functional Testing** - Test fungsionalitas fitur
- **UI/UX Testing** - Test tampilan dan user experience
- **Integration Testing** - Test integrasi antar modul
- **Regression Testing** - Test untuk memastikan fitur lama tetap berjalan
- **Exploratory Testing** - Test eksplorasi untuk menemukan bug

## Tips

- Dokumentasikan semua bug yang ditemukan
- Sertakan environment (browser, OS, device) jika relevan
- Update status test setelah bug diperbaiki

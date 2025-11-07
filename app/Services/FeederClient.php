<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class FeederClient
{
    /**
     * Whether client is in read-only mode (no write ops to Feeder)
     */
    protected bool $readOnly;

    /**
     * Base URL for the Feeder API
     */
    protected string $baseUrl;

    /**
     * Waktu timeout dalam detik
     */
    protected int $timeout;

    /**
     * Current authenticated user
     */
    protected ?User $user = null;

    /**
     * Current institusi (for job context without user)
     */
    protected ?\App\Models\Institusi $institusi = null;

    /**
     * Constructor - Simplified (validation handled by middleware)
     */
    public function __construct()
    {
        $this->user = Auth::user();
        $this->timeout = 60;
        // Default to read-only; can be adjusted via config('feeder.read_only')
        $this->readOnly = (bool) (config('feeder.read_only', true));

        // Set base URL dari institusi user (assume user & institusi sudah divalidasi middleware)
        if ($this->user && $this->user->institusi) {
            $this->institusi = $this->user->institusi;
            $this->baseUrl = $this->user->institusi->feeder_url;

            // Log untuk monitoring
            Log::info('FeederClient initialized', [
                'user_id' => $this->user->id,
                'institusi_slug' => $this->user->institusi->slug,
                'feeder_url' => $this->user->institusi->feeder_url
            ]);
        }
    }

    /**
     * Set institusi untuk context queue job (tanpa authenticated user)
     */
    public function setInstitusi(\App\Models\Institusi $institusi): void
    {
        $this->institusi = $institusi;
        $this->baseUrl = $institusi->feeder_url;

        Log::info('FeederClient institusi set for job context', [
            'institusi_id' => $institusi->id,
            'institusi_slug' => $institusi->slug,
            'feeder_url' => $institusi->feeder_url
        ]);
    }

    /**
     * Set institusi dari request (untuk compatibility dengan middleware)
     */
    public function setInstitusiFromRequest(\Illuminate\Http\Request $request): self
    {
        if ($institusi = $request->attributes->get('institusi')) {
            // Check if user exists (untuk context yang ada user)
            if ($this->user) {
                $this->user->setRelation('institusi', $institusi);
            }
            $this->baseUrl = $institusi->feeder_url;
        }

        return $this;
    }

    /**
     * Set timeout untuk request API
     */
    public function setTimeout(int $timeout): void
    {
        $this->timeout = max(10, $timeout); // Minimal 10 detik
    }

    /**
     * Request ke API Feeder dengan timeout yang ditingkatkan
     */
    public function request(array $payload)
    {
        // Enforce read-only policy before any outbound call
        $this->assertReadOnlyAllowed((string)($payload['act'] ?? ''), $payload);

        $response = Http::timeout($this->timeout) // Set timeout yang lebih lama
            ->retry(3, 1000) // Retry otomatis jika gagal
            ->post($this->baseUrl, $payload);

        // Check for connection errors or timeout
        if ($response->failed()) {
            $error = $response->toException()->getMessage();
            Log::error("HTTP Error requesting Feeder API", [
                'institusi_slug' => $this->user?->institusi?->slug ?? $this->institusi?->slug,
                'feeder_url' => $this->baseUrl,
                'error' => $error,
                'payload' => $this->sanitizePayloadForLog($payload),
                'status' => $response->status()
            ]);

            return [
                'error_code' => -1,
                'error_desc' => "Connection error: {$error}",
            ];
        }

        $responseData = $response->json() ?? [];

        // Check for API errors
        if (($responseData['error_code'] ?? 0) != 0) {
            Log::error("Feeder API Error", [
                'institusi_slug' => $this->user?->institusi?->slug ?? $this->institusi?->slug,
                'payload' => $this->sanitizePayloadForLog($payload),
                'error_code' => $responseData['error_code'] ?? 'unknown',
                'error_desc' => $responseData['error_desc'] ?? 'Unknown Error'
            ]);
        }

        return $responseData;
    }

    /**
     * Assert that we are not attempting any write operation to Feeder.
     * This blocks Insert/Update/Delete style actions unconditionally when read-only is enabled.
     */
    private function assertReadOnlyAllowed(string $act, array $payload = []): void
    {
        if (!$this->readOnly) {
            return; // Explicitly allowed to write (not expected per policy)
        }

        $actLower = strtolower($act);

        // Deny if action name indicates write operation
        $isWriteByName = str_starts_with($actLower, 'insert')
            || str_starts_with($actLower, 'update')
            || str_starts_with($actLower, 'delete')
            || str_starts_with($actLower, 'remove')
            || str_starts_with($actLower, 'create')
            || str_starts_with($actLower, 'save');

        // Heuristic: presence of 'record' (payload data) with non-Get act often implies write
        $hasRecord = array_key_exists('record', $payload);
        $hasKey = array_key_exists('key', $payload);
        $looksLikeWritePayload = $hasRecord || $hasKey;

        if ($isWriteByName || ($looksLikeWritePayload && !str_starts_with($actLower, 'get'))) {
            Log::warning('Blocked Feeder write operation due to read-only policy', [
                'institusi_slug' => $this->user?->institusi?->slug,
                'feeder_url' => $this->baseUrl ?? null,
                'act' => $act,
            ]);
            throw new \RuntimeException('Write operations to Feeder API are blocked by read-only policy.');
        }
    }

    /**
     * Sanitize payload untuk logging (hapus data sensitif)
     */
    private function sanitizePayloadForLog(array $payload): array
    {
        $sanitized = $payload;

        // Remove password dari log
        if (isset($sanitized['password'])) {
            $sanitized['password'] = '***';
        }

        // Truncate token untuk log
        if (isset($sanitized['token'])) {
            $sanitized['token'] = substr($sanitized['token'], 0, 10) . '...';
        }

        return $sanitized;
    }
    /**
     * Generate cache key yang unik per institusi untuk token storage
     */
    private function getTokenCacheKey(): string
    {
        // Gunakan institusi dari user jika ada, jika tidak gunakan institusi yang di-set langsung
        $institusi = $this->user?->institusi ?? $this->institusi;

        if (!$institusi) {
            throw new \Exception('No institusi available for token cache key generation');
        }

        return 'feeder_token_' . $institusi->slug;
    }

    public function authenticate()
    {
        $tokenLifetime = 10;

        // Gunakan institusi dari user jika ada, jika tidak gunakan institusi yang di-set langsung
        $institusi = $this->user?->institusi ?? $this->institusi;

        if (!$institusi) {
            throw new \Exception('No institusi available for authentication');
        }

        $response = $this->request([
            'act' => 'GetToken',
            'username' => $institusi->feeder_username,
            'password' => $institusi->feeder_password
        ]);

        if ($response && isset($response['data']['token'])) {
            $token = $response['data']['token'];
            $expiration = now()->addMinutes($tokenLifetime);

            // Use Cache with institusi-specific key untuk avoid collision
            $cacheKey = $this->getTokenCacheKey();
            \Illuminate\Support\Facades\Cache::put($cacheKey, $token, $expiration);
            \Illuminate\Support\Facades\Cache::put($cacheKey . '_expiration', $expiration, $expiration->addMinutes(5));

            Log::info("Feeder authentication successful", [
                'institusi_slug' => $institusi->slug,
                'username' => $institusi->feeder_username,
                'cache_key' => $cacheKey
            ]);

            return $token;
        }

        Log::error("Feeder authentication failed", [
            'institusi_slug' => $institusi->slug,
            'username' => $institusi->feeder_username,
            'error' => $response['error_desc'] ?? 'Unknown authentication error'
        ]);

        return ['error' => 'Authentication failed', 'response' => $response];
    }

    private function ensureAuthenticated()
    {
        $cacheKey = $this->getTokenCacheKey();
        $token = Cache::get($cacheKey);
        $expirationKey = $cacheKey . '_expiration';
        $expiration = Cache::get($expirationKey);

        // Periksa apakah token ada dan belum kedaluwarsa
        if ($token && $expiration && now()->lessThan($expiration)) {
            return $token;
        }

        // Jika token tidak ada atau sudah kedaluwarsa, lakukan autentikasi ulang
        return $this->authenticate();
    }

    public function fetch(string $act, array $filter = [], string $order = '', int $limit = 10, int $offset = 0)
    {
        $token = $this->ensureAuthenticated();
        if (!$token) return null;

        $filterString = $this->buildFilterString($filter);

        return $this->request([
            'act' => $act,
            'token' => $token,
            'filter' => $filterString,
            'order' => $order,
            'limit' => $limit,
            'offset' => $offset
        ]);
    }

    public function fetchs(string $act, string $filter = "", string $order = '', int $limit = 10, int $offset = 0)
    {
        $token = $this->ensureAuthenticated();
        if (!$token) return null;

        return $this->request([
            'act' => $act,
            'token' => $token,
            'filter' => $filter ?? "",  // Pastikan filter dalam bentuk string SQL
            'order' => $order,
            'limit' => $limit,
            'offset' => $offset
        ]);
    }

    private function buildFilterString(array $filter): string
    {
        if (empty($filter)) {
            return ""; // Kembalikan string kosong jika tidak ada filter
        }

        $filterArray = [];

        foreach ($filter as $column => $condition) {
            // Check if condition is already a complete SQL condition
            if (str_contains($condition, '=') || str_contains($condition, '<') || str_contains($condition, '>') || str_contains($condition, 'LIKE')) {
                $filterArray[] = "$column $condition";
            } else {
                // For simple equality check, wrap value in quotes
                $filterArray[] = "$column='$condition'";
            }
        }

        return implode(' AND ', $filterArray);
    }

    public function insert(string $act, array $record)
    {
        // Hard block insert per kebijakan: aplikasi ini HANYA baca dari Feeder
        $this->assertReadOnlyAllowed($act, ['record' => $record]);
        throw new \RuntimeException('Insert to Feeder is disabled by policy (read-only).');
    }

    public function update(string $act, array $keys, array $record)
    {
        // Hard block update per kebijakan: aplikasi ini HANYA baca dari Feeder
        $this->assertReadOnlyAllowed($act, ['key' => $keys, 'record' => $record]);
        throw new \RuntimeException('Update to Feeder is disabled by policy (read-only).');
    }

    public function delete(string $act, array $keys)
    {
        // Hard block delete per kebijakan: aplikasi ini HANYA baca dari Feeder
        $this->assertReadOnlyAllowed($act, ['key' => $keys]);
        throw new \RuntimeException('Delete on Feeder is disabled by policy (read-only).');
    }

    /**
     * Get current authenticated user
     */
    public function getCurrentUser(): User
    {
        return $this->user;
    }

    /**
     * Get current user's institusi
     */
    public function getCurrentInstitusi(): \App\Models\Institusi
    {
        $institusi = $this->user?->institusi ?? $this->institusi;

        if (!$institusi) {
            throw new \Exception('No institusi available');
        }

        return $institusi;
    }

    /**
     * Get list dosen from Feeder API using GetListDosen endpoint
     * 
     * @param array $filter Filter conditions for the query
     * @param string $order Order clause for sorting
     * @param int $limit Maximum number of records to fetch
     * @param int $offset Offset for pagination
     * @return array|null Response from Feeder API
     */
    public function getListDosen(array $filter = [], string $order = '', int $limit = 0, int $offset = 0): ?array
    {
        return $this->fetch('GetListDosen', $filter, $order, $limit, $offset);
    }

    /**
     * Get list mahasiswa from Feeder API using GetListMahasiswa endpoint
     * 
     * @param array $filter Filter conditions for the query (e.g., ['id_periode' => '20241'])
     * @param string $order Order clause for sorting
     * @param int $limit Maximum number of records to fetch (0 = all)
     * @param int $offset Offset for pagination
     * @return array|null Response from Feeder API
     */
    /**
     * Get list mahasiswa from Feeder API
     * 
     * @param array|string $filter Filter conditions - can be array or PostgreSQL filter string
     *                            Example array: ['nim' => '12345']
     *                            Example string: "left(id_periode,4)='2024'"
     * @param string $order Order clause for sorting
     * @param int $limit Maximum number of records to fetch (0 = all)
     * @param int $offset Offset for pagination
     * @return array|null Response from Feeder API
     */
    public function getListMahasiswa(array|string $filter = [], string $order = '', int $limit = 0, int $offset = 0): ?array
    {
        // If filter is string (PostgreSQL syntax), use fetchs()
        if (is_string($filter)) {
            return $this->fetchs('GetListMahasiswa', $filter, $order, $limit, $offset);
        }

        // Otherwise use fetch() with array filter
        return $this->fetch('GetListMahasiswa', $filter, $order, $limit, $offset);
    }

    /**
     * Get biodata mahasiswa detail from Feeder API using GetBiodataMahasiswa endpoint
     * 
     * @param array $filter Filter conditions (e.g., ['id_mahasiswa' => 'uuid'])
     * @param string $order Order clause for sorting
     * @param int $limit Maximum number of records to fetch
     * @param int $offset Offset for pagination
     * @return array|null Response from Feeder API
     */
    public function getBiodataMahasiswa(array $filter = [], string $order = '', int $limit = 0, int $offset = 0): ?array
    {
        return $this->fetch('GetBiodataMahasiswa', $filter, $order, $limit, $offset);
    }

    /**
     * Get list perkuliahan mahasiswa (riwayat akademik per semester) dari Feeder
     * 
     * Response includes: IPS, IPK, SKS semester, SKS total, status mahasiswa per semester
     * 
     * @param array $filter Filter conditions (e.g., ['angkatan' => '2024'])
     * @param string $order Order clause for sorting
     * @param int $limit Maximum number of records to fetch
     * @param int $offset Offset for pagination
     * @return array|null Response from Feeder API with academic records per semester
     */
    public function getListPerkuliahanMahasiswa(array $filter = [], string $order = '', int $limit = 0, int $offset = 0): ?array
    {
        return $this->fetch('GetListPerkuliahanMahasiswa', $filter, $order, $limit, $offset);
    }

    /**
     * Get list mahasiswa bimbingan dosen from Feeder API using GetMahasiswaBimbinganDosen endpoint
     * 
     * Response includes: bimbingan data with mahasiswa, dosen, aktivitas info
     * 
     * @param array $filter Filter conditions (e.g., ['id_dosen' => 'uuid'])
     * @param string $order Order clause for sorting
     * @param int $limit Maximum number of records to fetch
     * @param int $offset Offset for pagination
     * @return array|null Response from Feeder API with bimbingan records
     */
    public function getMahasiswaBimbinganDosen(array $filter = [], string $order = '', int $limit = 0, int $offset = 0): ?array
    {
        return $this->fetch('GetMahasiswaBimbinganDosen', $filter, $order, $limit, $offset);
    }

    /**
     * Get riwayat pendidikan dosen from Feeder API using GetRiwayatPendidikanDosen endpoint
     * 
     * @param array $filter Filter conditions for the query
     * @param string $order Order by clause
     * @param int $limit Maximum number of records to fetch
     * @param int $offset Offset for pagination
     * @return array|null Response from Feeder API with pendidikan records
     */
    public function getRiwayatPendidikanDosen(array $filter = [], string $order = '', int $limit = 0, int $offset = 0): ?array
    {
        return $this->fetch('GetRiwayatPendidikanDosen', $filter, $order, $limit, $offset);
    }

    /**
     * Get riwayat jabatan fungsional dosen from Feeder API using GetRiwayatFungsionalDosen endpoint
     * 
     * @param array $filter Filter conditions for the query
     * @param string $order Order by clause
     * @param int $limit Maximum number of records to fetch
     * @param int $offset Offset for pagination
     * @return array|null Response from Feeder API with jabatan fungsional records
     */
    public function getRiwayatFungsionalDosen(array $filter = [], string $order = '', int $limit = 0, int $offset = 0): ?array
    {
        return $this->fetch('GetRiwayatFungsionalDosen', $filter, $order, $limit, $offset);
    }

    /**
     * Get riwayat sertifikasi dosen from Feeder API using GetRiwayatSertifikasiDosen endpoint
     * 
     * @param array $filter Filter conditions for the query
     * @param string $order Order by clause
     * @param int $limit Maximum number of records to fetch
     * @param int $offset Offset for pagination
     * @return array|null Response from Feeder API with sertifikasi records
     */
    public function getRiwayatSertifikasiDosen(array $filter = [], string $order = '', int $limit = 0, int $offset = 0): ?array
    {
        return $this->fetch('GetRiwayatSertifikasiDosen', $filter, $order, $limit, $offset);
    }

    /**
     * Get list mahasiswa lulus/DO from Feeder API using GetListMahasiswaLulusDO endpoint
     * 
     * @param array $filter Filter conditions for the query
     * @param string $order Order by clause
     * @param int $limit Maximum number of records to fetch
     * @param int $offset Offset for pagination
     * @return array|null Response from Feeder API with mahasiswa lulus/DO records
     */
    /**
     * Get list mahasiswa lulus/DO from Feeder API
     * 
     * @param array|string $filter Filter conditions - can be array or PostgreSQL filter string
     *                            Example array: ['angkatan' => '2024']
     *                            Example string: "angkatan='2024' and right(tanggal_keluar,4)='2024'"
     * @param string $order Order clause for sorting
     * @param int $limit Maximum number of records to fetch (0 = all)
     * @param int $offset Offset for pagination
     * @return array|null Response from Feeder API
     */
    public function getListMahasiswaLulusDO(array|string $filter = [], string $order = '', int $limit = 0, int $offset = 0): ?array
    {
        // If filter is string (PostgreSQL syntax), use fetchs()
        if (is_string($filter)) {
            return $this->fetchs('GetListMahasiswaLulusDO', $filter, $order, $limit, $offset);
        }

        // Otherwise use fetch() with array filter
        return $this->fetch('GetListMahasiswaLulusDO', $filter, $order, $limit, $offset);
    }

    /**
     * Ambil data prestasi mahasiswa dari Feeder
     * 
     * @param array $filter Filter parameters untuk query
     * @param string $order Order by column
     * @param int $limit Limit jumlah record (0 = no limit)
     * @param int $offset Offset untuk pagination
     * @return array|null Response dari API atau null jika gagal
     */
    public function getListPrestasiMahasiswa(array $filter = [], string $order = '', int $limit = 0, int $offset = 0): ?array
    {
        return $this->fetch('GetListPrestasiMahasiswa', $filter, $order, $limit, $offset);
    }

    /**
     * Mengambil data aktivitas mahasiswa dari API Feeder
     * 
     * @param array $filter Filter untuk query (misal: id_semester)
     * @param string $order Field untuk sorting
     * @param int $limit Batas jumlah data (0 = unlimited)
     * @param int $offset Offset untuk pagination
     * @return array|null Response dari API atau null jika gagal
     */
    public function getListAktivitasMahasiswa(array $filter = [], string $order = '', int $limit = 0, int $offset = 0): ?array
    {
        return $this->fetch('GetListAktivitasMahasiswa', $filter, $order, $limit, $offset);
    }

    /**
     * Mengambil data anggota aktivitas mahasiswa dari API Feeder
     * 
     * @param array $filter Filter untuk query (misal: id_aktivitas)
     * @param string $order Field untuk sorting
     * @param int $limit Batas jumlah data (0 = unlimited)
     * @param int $offset Offset untuk pagination
     * @return array|null Response dari API atau null jika gagal
     */
    public function getListAnggotaAktivitasMahasiswa(array $filter = [], string $order = '', int $limit = 0, int $offset = 0): ?array
    {
        return $this->fetch('GetListAnggotaAktivitasMahasiswa', $filter, $order, $limit, $offset);
    }

    /**
     * Test connection untuk institusi user saat ini
     */
    public function testConnection(): array
    {
        try {
            $institusi = $this->user?->institusi ?? $this->institusi;

            if (!$institusi) {
                return [
                    'success' => false,
                    'error' => 'No institusi available for connection test'
                ];
            }

            $authResult = $this->authenticate();

            if (is_array($authResult)) {
                return [
                    'success' => false,
                    'error' => $authResult['error'] ?? 'Authentication failed',
                    'institusi' => [
                        'slug' => $institusi->slug,
                        'nama' => $institusi->nama
                    ]
                ];
            }

            return [
                'success' => true,
                'message' => 'Connection successful',
                'institusi' => [
                    'slug' => $institusi->slug,
                    'nama' => $institusi->nama,
                    'feeder_url' => $institusi->feeder_url
                ]
            ];
        } catch (\Exception $e) {
            $institusi = $this->user?->institusi ?? $this->institusi;

            Log::error('Feeder connection test failed', [
                'institusi_slug' => $institusi?->slug ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'institusi' => [
                    'slug' => $institusi?->slug ?? 'unknown',
                    'nama' => $institusi?->nama ?? 'unknown'
                ]
            ];
        }
    }

    /**
     * Clear cached token untuk institusi saat ini
     */
    public function clearToken(): void
    {
        $cacheKey = $this->getTokenCacheKey();
        $expirationKey = $cacheKey . '_expiration';

        Cache::forget($cacheKey);
        Cache::forget($expirationKey);

        Log::info('Feeder token cleared', [
            'institusi_slug' => $this->user?->institusi?->slug ?? $this->institusi?->slug ?? 'unknown',
            'cache_key' => $cacheKey
        ]);
    }

    // contoh penggunaan pada controller


    /**
     * Get riwayat penelitian dosen data from Feeder API
     */
    public function getRiwayatPenelitianDosen(array $filter = [], string $order = '', int $limit = 0, int $offset = 0): ?array
    {
        return $this->fetch('GetRiwayatPenelitianDosen', $filter, $order, $limit, $offset);
    }

    /*
        *
    
        $filter = [
            'nama_dosen' => "like '%ga%'",     // Nama dosen mengandung "ga"
            'usia' => "> 30",                 // Usia lebih dari 30
            'status' => "not in ('cuti')",    // Status bukan "cuti"
            'id_dosen' => "in ('123', '456', '789')" // ID Dosen dalam daftar
        ];
        $data = $this->feederClient->fetch('GetListDosen', $filter, 'nama_dosen ASC', 10, 0);

        WHERE nama_dosen LIKE '%ga%'
        AND usia > 30
        AND status NOT IN ('cuti')
        AND id_dosen IN ('123', '456', '789')
        ORDER BY nama_dosen ASC
        LIMIT 10 OFFSET 0

        Contoh Kombinasi AND dan OR
        $filter = ['usia' => "> 30 OR status = 'aktif'"];
        hasil:
        WHERE usia > 30 OR status = 'aktif'

        */
}

# API Documentation

Dokumentasi API endpoints aplikasi SIMAK.

## Contents

1. **Authentication** - API authentication endpoints
2. **Endpoints** - Daftar semua API endpoints
3. **Error Handling** - Format error response
4. **Rate Limiting** - API rate limits

## Base URL

```
Production: https://simak.example.com/api
Development: http://localhost:8900/api
```

## Authentication

API menggunakan Laravel Sanctum untuk token-based authentication.

```bash
# Login untuk mendapatkan token
POST /api/{institusi}/auth/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password"
}

# Response
{
  "token": "1|xxxxxxxxxxxxxxxxxxxx",
  "user": {...}
}
```

## Common Response Format

### Success Response
```json
{
  "success": true,
  "data": {...},
  "message": "Success message"
}
```

### Error Response
```json
{
  "success": false,
  "error": "error_code",
  "message": "Error message",
  "errors": {...}
}
```

## Rate Limiting

API endpoints dilindungi dengan rate limiting:
- 60 requests per menit untuk authenticated users
- 10 requests per menit untuk unauthenticated users

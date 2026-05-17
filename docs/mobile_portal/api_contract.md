# Mobile Portal API Contract

## Base URLs

Use environment variables instead of hard-coded URLs.

```text
MOBILE_AUTH_API_URL=https://mobile-auth-api.example.com
LEAVE_APP_URL=https://leave-management.example.com
MEDICAL_APP_URL=https://medical-management.example.com
```

## Authentication

Expo-to-Mobile-Auth requests use a bearer token after login:

```http
Authorization: Bearer <mobile_jwt>
Accept: application/json
```

Existing Laravel apps call the Mobile Auth API using a server-to-server secret:

```http
Authorization: Bearer <server_integration_secret>
Accept: application/json
```

The server integration secret must not be bundled in the Expo app.

## Common Error Shape

```json
{
  "success": false,
  "error": {
    "code": "invalid_credentials",
    "message": "Invalid username or password."
  }
}
```

Recommended status codes:

- `400` for malformed requests.
- `401` for invalid credentials or missing authentication.
- `403` for disabled users or missing system access.
- `404` for missing mappings or users where revealing detail is acceptable.
- `409` for already-used temporary tokens.
- `422` for validation errors.
- `500` for unexpected server errors.

## `POST /api/mobile/login`

Purpose:

- Authenticate a mobile user and issue a mobile JWT.

Request:

```json
{
  "login": "admin@leavemgmt.com",
  "password": "password"
}
```

Successful response:

```json
{
  "success": true,
  "token": "mobile_jwt_token",
  "user": {
    "id": 1,
    "employee_id": null,
    "name": "John Admin",
    "email": "admin@leavemgmt.com",
    "access": {
      "leave": true,
      "medical": true
    }
  }
}
```

Failure cases:

- Invalid credentials.
- Disabled mobile user.
- User has no enabled system access.

## `GET /api/mobile/me`

Purpose:

- Restore mobile session and return current user profile.

Headers:

```http
Authorization: Bearer <mobile_jwt>
```

Successful response:

```json
{
  "success": true,
  "user": {
    "id": 1,
    "employee_id": null,
    "name": "John Admin",
    "email": "admin@leavemgmt.com",
    "access": {
      "leave": true,
      "medical": true
    }
  }
}
```

## `POST /api/mobile/logout`

Purpose:

- Revoke or invalidate the mobile JWT where supported.

Headers:

```http
Authorization: Bearer <mobile_jwt>
```

Successful response:

```json
{
  "success": true
}
```

## `POST /api/mobile/token/leave`

Purpose:

- Generate a one-time login URL for the Leave Management app.

Headers:

```http
Authorization: Bearer <mobile_jwt>
```

Successful response:

```json
{
  "success": true,
  "system": "leave",
  "expires_at": "2026-05-17T13:01:00Z",
  "loginUrl": "https://leave-management.example.com/mobile-login?token=plain_token_value"
}
```

Rules:

- User must have `can_access_leave = true`.
- User must have a valid `leave_user_id` mapping.
- Token expiration should be 60 seconds for MVP.
- Store only `token_hash`, not the plain token.

## `POST /api/mobile/token/medical`

Purpose:

- Generate a one-time login URL for the Medical Management app.

Headers:

```http
Authorization: Bearer <mobile_jwt>
```

Successful response:

```json
{
  "success": true,
  "system": "medical",
  "expires_at": "2026-05-17T13:01:00Z",
  "loginUrl": "https://medical-management.example.com/mobile-login?token=plain_token_value"
}
```

Rules:

- User must have `can_access_medical = true`.
- User must have a valid `medical_user_id` mapping.
- Token expiration should be 60 seconds for MVP.
- Store only `token_hash`, not the plain token.

## `POST /api/mobile/verify-token`

Purpose:

- Allow Leave or Medical Laravel apps to verify one-time login tokens.

Headers:

```http
Authorization: Bearer <server_integration_secret>
```

Request:

```json
{
  "token": "plain_token_value",
  "system": "leave"
}
```

Successful Leave response:

```json
{
  "success": true,
  "system": "leave",
  "mobile_user": {
    "id": 1,
    "employee_id": null,
    "name": "John Admin"
  },
  "system_user": {
    "id": 25
  }
}
```

Successful Medical response:

```json
{
  "success": true,
  "system": "medical",
  "mobile_user": {
    "id": 1,
    "employee_id": null,
    "name": "John Admin"
  },
  "system_user": {
    "id": 88
  }
}
```

Rules:

- Token must exist by hash.
- Token must not be expired.
- Token must not have `used_at`.
- Token `system_target` must match the request `system`.
- User must still be active.
- User must still have access to the requested system.
- Mark token as used during successful verification.

Failure cases:

- Missing token.
- Invalid token.
- Expired token.
- Already-used token.
- Wrong target system.
- Missing local user mapping.
- Invalid server integration secret.

## Leave `GET /api/mobile/users`

Purpose:

- Allow Mobile Auth API to sync Leave users by email.

This endpoint belongs to the Leave Management app, not the Mobile Auth API.

Headers:

```http
Authorization: Bearer <server_integration_secret>
Accept: application/json
```

Successful response:

```json
{
  "success": true,
  "data": [
    {
      "leave_user_id": 1,
      "name": "John Admin",
      "email": "admin@leavemgmt.com",
      "username": "admin",
      "status": "active",
      "archived": false,
      "updated_at": "2026-05-17T14:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 100,
    "total": 1
  }
}
```

Rules:

- Must require the server integration secret.
- Must not return passwords or remember tokens.
- `status` should be `inactive` when the Leave user is archived or inactive.

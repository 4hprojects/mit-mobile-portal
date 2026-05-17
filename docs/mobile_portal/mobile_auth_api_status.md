# Mobile Auth API Status

## Current Scope

The Mobile Auth API has been scaffolded in:

```text
mobile-auth-api/
```

Repository note:

- This folder belongs in the planned `mit-mobile-portal` GitHub repository.

The current implementation supports the first vertical slice:

```text
Expo login -> Mobile Auth API -> Leave token -> Leave /mobile-login -> Leave dashboard
```

Medical Management is intentionally present as a placeholder until its repository, schema, or auth files are available.

Login model:

- Mobile users sign in with the same email used by Leave or Medical.
- The mobile password is managed by Mobile Auth.
- The Mobile Auth mapping table controls which Leave or Medical local user account is opened.

## Implemented

- Laravel Mobile Auth API project.
- `mobile_users` migration.
- `mobile_user_system_access` migration.
- `temporary_login_tokens` migration.
- Mobile JWT issuing and validation.
- `POST /api/mobile/login`.
- `GET /api/mobile/me`.
- `POST /api/mobile/logout`.
- `POST /api/mobile/token/leave`.
- `POST /api/mobile/token/medical` placeholder response.
- `POST /api/mobile/verify-token`.
- `php artisan mobile-users:sync-leave` to sync Leave users by email.
- Server-to-server integration secret middleware.
- Tests for login, Leave token generation, one-time token verification, and Medical placeholder behavior.

## Medical Placeholder Behavior

Medical fields are kept in the schema:

```text
medical_user_id
can_access_medical
```

But Medical endpoints currently return:

```json
{
  "success": false,
  "error": {
    "code": "medical_placeholder",
    "message": "Medical Management integration is not enabled yet because its auth schema is still pending."
  }
}
```

This keeps the API contract stable while preventing accidental Medical access before the Medical app is inspected.

## Local Development Notes

The global PHP CLI install is missing several enabled extensions in its default `php.ini`, so this workspace uses a local config:

```text
.codex/php.ini
```

Run Laravel commands with:

```powershell
$env:PHPRC = (Resolve-Path "..\.codex").Path
php artisan test
```

From the repository root, use:

```powershell
$env:PHPRC = (Resolve-Path ".\.codex").Path
php .codex\composer.phar --version
```

## Verification

Current test status:

```text
php artisan test
7 tests passed
29 assertions passed
```

## Leave User Sync

Option C is implemented for Leave.

Mobile Auth can pull Leave users through the protected Leave endpoint:

```text
GET {LEAVE_APP_URL}/api/mobile/users
Authorization: Bearer {LEAVE_APP_SYNC_SECRET}
```

Run:

```powershell
php artisan mobile-users:sync-leave --password=password
```

Sync behavior:

- `mobile_users.email` comes from `leave.users.email`.
- `mobile_users.username` comes from `leave.users.username`.
- `mobile_users.name` comes from the Leave display name.
- `mobile_user_system_access.leave_user_id` comes from `leave.users.user_id`.
- Active Leave users get `can_access_leave = true`.
- Archived or inactive Leave users get `status = inactive` and `can_access_leave = false`.

## Next Implementation Step

The next implementation step is to run the Leave-only integration locally:

1. Start `mobile-auth-api`.
2. Seed a mobile user mapped to a Leave `users.user_id`.
3. Start `leave-management`.
4. Generate a Leave login URL from `POST /api/mobile/token/leave`.
5. Open the returned `/mobile-login?token=...` URL and confirm it lands on the Leave dashboard.

After that, start the Expo app foundation with login, dashboard, and Leave WebView.

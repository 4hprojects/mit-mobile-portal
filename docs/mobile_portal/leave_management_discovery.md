# Leave Management Discovery

## Repository

- GitLab: `https://gitlab.com/mit-111/leave-management`
- Local path: `leave-management/`
- Branch inspected: `main`
- Deployed URL: `https://leave-management-mdjw.onrender.com/`

Ownership note:

- Leave Management remains in GitLab as an external integrated system.
- Do not commit the `leave-management/` folder into the mobile portal GitHub repository.

## Stack

- Laravel
- Vue/Vite frontend
- PostgreSQL-compatible schema
- Session-based web guard
- Custom JWT support for API routes

## Authentication Findings

Relevant files:

- `routes/web.php`
- `app/Http/Controllers/AuthController.php`
- `app/Services/AuthService.php`
- `app/Models/Users.php`
- `config/auth.php`
- `database/migrations/0001_01_01_000000_create_users_table.php`

The app supports normal Laravel web sessions through the default `web` guard.

The app also has custom JWT API routes:

- `POST /api/login`
- `GET /api/auth/user`
- `GET /api/auth/session`
- `POST /api/logout`

For browser login, `AuthService::login()` calls:

```php
Auth::login($user);
$request->session()->regenerate();
```

This means the mobile auto-login route can use the same Laravel session mechanism.

## Login Fields

The existing login accepts a single `username` field and checks it against:

- `users.username`
- `users.email`

Password is checked against `users.password`.

Archived users are blocked:

```php
where('archived', false)
```

## User Model and Local Mapping ID

The user model is `App\Models\Users`.

Database table:

- `users`

Primary key:

- `user_id`

Important user fields:

- `user_id`
- `first_name`
- `last_name`
- `email`
- `username`
- `password`
- `gender`
- `hire_date`
- `userstatus`
- `archived`

Current schema does not show an `employee_id` field. For the Mobile Auth mapping table, Leave should use:

```text
leave_user_id -> users.user_id
```

If Medical also lacks a shared employee ID, the safest MVP mapping strategy is manual mapping by local system user IDs.

## Redirect Behavior

After login, the app redirects by role:

- Admin users: `/leave-management`
- Non-admin users: `/my-applications`

This behavior is already used in `AuthService::login()` and `/dashboard`.

The mobile login route should preserve this behavior after creating the session.

## Recommended Leave Mobile Login Route

Add:

```text
GET /mobile-login?token=...
```

Current implementation status:

- Implemented in `leave-management/app/Http/Controllers/MobileLoginController.php`.
- Route added in `leave-management/routes/web.php`.
- Service config added in `leave-management/config/services.php`.
- Environment variables added to `leave-management/.env.example`.
- Feature tests added in `leave-management/tests/Feature/MobileLoginTest.php`.

Expected flow:

1. Validate that `token` exists.
2. Call Mobile Auth API `POST /api/mobile/verify-token`.
3. Send `system = leave`.
4. Use the server-to-server integration secret.
5. Read `system_user.id` from the response.
6. Find `Users::whereKey(system_user.id)->where('archived', false)->first()`.
7. Log the user in with `Auth::login($user)`.
8. Regenerate the session.
9. Redirect admin users to `/leave-management`.
10. Redirect non-admin users to `/my-applications`.

## Environment Variables Needed

Add to the Leave deployment:

```text
MOBILE_AUTH_API_URL=
MOBILE_AUTH_INTEGRATION_SECRET=
```

`MOBILE_AUTH_API_URL` must point to the deployed Mobile Auth API base URL, without a trailing `/api`.

Example:

```text
MOBILE_AUTH_API_URL=https://mobile-auth-api.onrender.com
```

Production session settings should be checked:

```text
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
```

## Open Questions

- Is `userstatus = INACTIVE` currently used to block login, or is `archived` the only active-login control?
- Should mobile login also reject users with `userstatus = INACTIVE`?
- Should the mobile login route redirect to role-based default pages or always to one mobile-friendly page?

## Verification

Current test status after implementation:

```text
php artisan test
20 tests passed
96 assertions passed
```

Frontend assets had to be built locally with `npm run build` before the full test suite could render `/`.

## Mobile User Sync Endpoint

Option C user sync is implemented through:

```text
GET /api/mobile/users
Authorization: Bearer {MOBILE_AUTH_INTEGRATION_SECRET}
```

The endpoint returns safe user data for Mobile Auth:

```text
leave_user_id
name
email
username
status
archived
updated_at
```

It does not expose passwords or session data.

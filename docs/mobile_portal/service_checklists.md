# Mobile Portal Service Checklists

## Mobile Auth API Checklist

Configuration:

- `APP_URL`
- `DATABASE_URL` or PostgreSQL host/user/password/database variables
- `JWT_SECRET`
- `LEAVE_APP_URL`
- `MEDICAL_APP_URL`
- `SERVER_INTEGRATION_SECRET`
- Token expiration setting, default `60` seconds

Database:

- Create `mobile_users`.
- Create `mobile_user_system_access`.
- Create `temporary_login_tokens`.
- Add indexes for login fields, `mobile_user_id`, `system_target`, `expires_at`, and `used_at`.
- Store temporary token hash, not plain token.

Implementation:

- Login by email or username.
- Password hash verification.
- Active/disabled user status check.
- Access flags returned to Expo.
- JWT issuing.
- JWT invalidation or client-side logout strategy.
- Temporary token generation.
- Temporary token verification.
- One-time token usage enforcement.

Tests:

- Valid login.
- Invalid login.
- Disabled user login.
- `/me` with valid token.
- `/me` with invalid token.
- Generate Leave token with access.
- Generate Leave token without access.
- Generate Medical token with access.
- Generate Medical token without access.
- Verify valid temporary token.
- Reject expired temporary token.
- Reject reused temporary token.
- Reject wrong-system temporary token.

## Leave Management Checklist

Configuration:

- `MOBILE_AUTH_API_URL`
- `SERVER_INTEGRATION_SECRET`
- Dashboard redirect path after mobile login
- `SESSION_SECURE_COOKIE=true` in production
- `SESSION_SAME_SITE=lax`

Implementation:

- Add `GET /mobile-login`.
- Validate `token` query parameter.
- Call Mobile Auth API `POST /api/mobile/verify-token` with `system = leave`.
- Resolve returned `system_user.id` to a local Leave user.
- Use the existing Laravel auth guard to log in the user.
- Regenerate the Laravel session after login.
- Redirect to Leave dashboard.
- Show a safe error page for invalid or expired token.

Tests:

- Valid token logs in mapped Leave user.
- Expired token fails.
- Reused token fails.
- Medical token fails.
- Missing token fails.
- Missing local user fails.
- Existing normal web login still works.

## Medical Management Checklist

Current status:

- Placeholder only.
- Do not enable `MEDICAL_APP_ENABLED=true` until the Medical user table, primary key, auth guard, and dashboard route are confirmed.

Configuration:

- `MOBILE_AUTH_API_URL`
- `SERVER_INTEGRATION_SECRET`
- Dashboard redirect path after mobile login
- `SESSION_SECURE_COOKIE=true` in production
- `SESSION_SAME_SITE=lax`

Implementation:

- Add `GET /mobile-login`.
- Validate `token` query parameter.
- Call Mobile Auth API `POST /api/mobile/verify-token` with `system = medical`.
- Resolve returned `system_user.id` to a local Medical user.
- Use the existing Laravel auth guard to log in the user.
- Regenerate the Laravel session after login.
- Redirect to Medical dashboard.
- Show a safe error page for invalid or expired token.

Tests:

- Valid token logs in mapped Medical user.
- Expired token fails.
- Reused token fails.
- Leave token fails.
- Missing token fails.
- Missing local user fails.
- Existing normal web login still works.

## Expo Mobile App Checklist

Configuration:

- `EXPO_PUBLIC_MOBILE_AUTH_API_URL`

Dependencies:

- React Navigation
- React Native WebView
- Expo SecureStore

Implementation:

- Login screen.
- Dashboard screen.
- Secure JWT storage.
- Session restore with `/api/mobile/me`.
- Access-gated dashboard buttons.
- Leave WebView screen.
- Medical WebView screen.
- Loading states.
- Error states.
- Logout action.

Tests:

- Login success.
- Login failure.
- App restart restores session.
- Logout clears session.
- Leave-only user sees only Leave.
- Medical-only user sees only Medical.
- User with both systems sees both.
- WebView opens Leave login URL and lands on dashboard.
- WebView opens Medical login URL and lands on dashboard.
- Expired login URL shows recoverable error.

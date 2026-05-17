# Mobile Portal Testing Plan

## Test Environments

Minimum environments:

- Local development for each service.
- Render staging or preview services if available.
- Production smoke test after deployment.

Minimum devices:

- Android physical device.
- Android emulator if physical device is unavailable.
- iOS simulator or device if iOS is in scope.

## Test Users

Create or identify these users:

- `mobile_both`: can access Leave and Medical.
- `mobile_leave_only`: can access Leave only.
- `mobile_medical_only`: can access Medical only.
- `mobile_disabled`: disabled mobile account.
- `mobile_unmapped`: active mobile user with incomplete system mapping.

## API Tests

Mobile Auth API:

- Valid login returns JWT and access flags.
- Invalid password returns `401`.
- Disabled user returns `403`.
- `/me` works with valid JWT.
- `/me` rejects missing or invalid JWT.
- Leave token generation requires Leave access.
- Medical token generation requires Medical access.
- Token verification accepts valid token once.
- Token verification rejects reused token.
- Token verification rejects expired token.
- Token verification rejects wrong target system.
- Token verification rejects invalid server integration secret.

## Laravel Integration Tests

Leave Management:

- `/mobile-login?token=<valid_leave_token>` logs in mapped Leave user.
- Missing token shows error.
- Expired token shows error.
- Reused token shows error.
- Medical token does not log into Leave.
- Normal web login still works after changes.

Medical Management:

- `/mobile-login?token=<valid_medical_token>` logs in mapped Medical user.
- Missing token shows error.
- Expired token shows error.
- Reused token shows error.
- Leave token does not log into Medical.
- Normal web login still works after changes.

## Expo App Tests

Authentication:

- User can log in.
- Wrong password shows error.
- Disabled user shows error.
- App restart restores session.
- Logout clears stored JWT.

Dashboard:

- Leave-only user sees Leave access only.
- Medical-only user sees Medical access only.
- User with both sees both.
- User with no access is handled safely.

WebView:

- Tapping Leave opens Leave WebView and lands on the Leave dashboard.
- Tapping Medical opens Medical WebView and lands on the Medical dashboard.
- Loading indicator appears while WebView loads.
- Expired temporary token shows a recoverable error.
- App background and resume preserve expected WebView session behavior.

## End-to-End Acceptance Tests

Scenario 1: User with both systems

1. Log in to Expo.
2. Confirm dashboard shows Leave and Medical.
3. Open Leave.
4. Confirm Leave dashboard is authenticated as the mapped user.
5. Return to mobile dashboard.
6. Open Medical.
7. Confirm Medical dashboard is authenticated as the mapped user.
8. Log out from Expo.

Scenario 2: User with Leave only

1. Log in to Expo.
2. Confirm dashboard shows Leave only.
3. Open Leave.
4. Confirm Leave dashboard is authenticated.
5. Confirm Medical access cannot be generated from the API.

Scenario 3: Temporary token replay protection

1. Generate a Leave login URL.
2. Open it once and confirm login succeeds.
3. Open the same URL again.
4. Confirm login fails.

Scenario 4: Expired temporary token

1. Generate a Leave or Medical login URL.
2. Wait past the configured expiration.
3. Open the URL.
4. Confirm login fails with a clear error.

## Production Smoke Tests

After deployment:

- Confirm Mobile Auth API health endpoint or login endpoint responds.
- Confirm Expo can log in against production Mobile Auth API.
- Confirm Leave WebView login works.
- Confirm Medical WebView login works.
- Confirm invalid/reused token fails in production.
- Confirm logs do not expose passwords, JWTs, or plain temporary tokens.

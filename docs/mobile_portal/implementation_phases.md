# Mobile Portal Implementation Phases

## Purpose

This document breaks the PRD into implementation phases that can be built, tested, and deployed independently.

Repository ownership:

- The mobile portal GitHub repository should contain `docs/`, `mobile-auth-api/`, and `mobile-portal-app/`.
- Leave Management remains external in GitLab: `https://gitlab.com/mit-111/leave-management`.
- Medical Management remains external and pending source confirmation.

## Phase 0: Discovery and Compatibility Check

Goal:

- Confirm the existing systems can support mobile auto-login with minimal changes.

Tasks:

- Inspect Leave Management authentication guards, user table, roles, and session configuration.
- Inspect Medical Management authentication guards, user table, roles, and session configuration.
- Confirm deployed Render URLs for Leave, Medical, and the future Mobile Auth API.
- Decide the identity mapping key: `employee_id`, `email`, username, or manual mapping.
- Confirm whether Leave and Medical users already share a common employee identifier.
- Confirm CORS and outbound HTTP access from each Laravel app to the Mobile Auth API.
- Confirm Expo WebView can persist Laravel sessions on the target devices.

Exit criteria:

- Identity mapping strategy is documented.
- Required Laravel auth/session changes are known.
- Render environment variable list is drafted.
- No unresolved blocker exists for WebView auto-login.

Current Leave status:

- Leave repository has been cloned and inspected.
- Findings are documented in [Leave Management Discovery](leave_management_discovery.md).
- Leave uses `users.user_id` as the local mapping ID.
- Leave does not currently show an `employee_id` field in the inspected migration.

Current Mobile Auth API status:

- Mobile Auth API has been scaffolded in `mobile-auth-api/`.
- Leave token generation and verification are implemented.
- Medical fields are present but Medical behavior is placeholdered until the Medical app is available.
- Status is documented in [Mobile Auth API Status](mobile_auth_api_status.md).

## Phase 1: Mobile Auth API Foundation

Goal:

- Build the standalone authentication service without integrating the existing systems yet.

Tasks:

- Create the Laravel Mobile Auth API project.
- Configure PostgreSQL.
- Add migrations for `mobile_users`, `mobile_user_system_access`, and `temporary_login_tokens`.
- Implement password hashing and mobile user status checks.
- Implement JWT authentication.
- Implement `POST /api/mobile/login`.
- Implement `GET /api/mobile/me`.
- Implement `POST /api/mobile/logout`.
- Seed test users and mappings.

Exit criteria:

- A test mobile user can log in and receive a JWT.
- Disabled users cannot log in.
- `/me` returns profile and system access flags.
- Basic API tests pass.

## Phase 2: Temporary Token Contract

Goal:

- Build and verify the one-time token flow before modifying the existing Laravel apps.

Tasks:

- Implement `POST /api/mobile/token/leave`.
- Implement `POST /api/mobile/token/medical`.
- Implement `POST /api/mobile/verify-token`.
- Store only hashed temporary tokens.
- Enforce token expiration.
- Enforce one-time use.
- Enforce `system_target`.
- Return mapped system user IDs only after successful verification.
- Add API tests for valid, expired, reused, wrong-system, and unauthorized tokens.

Exit criteria:

- Temporary token flow works through API tests.
- Token verification marks the token as used.
- Wrong-system token validation fails.
- Unauthorized users cannot generate system login URLs.

## Phase 3: Leave Management Integration

Goal:

- Add mobile auto-login to the Leave Management Laravel app.

Current status:

- Implemented.
- `GET /mobile-login?token=...` verifies the token through the Mobile Auth API.
- Valid mapped users are logged in through Laravel's `web` guard.
- Admin users redirect to `/leave-management`.
- Non-admin users redirect to `/my-applications`.
- Tests pass.

Tasks:

- Add a `GET /mobile-login` route.
- Add a controller action for mobile login.
- Validate the `token` query parameter with the Mobile Auth API.
- Check that the response target is `leave`.
- Resolve `leave_user_id` to a local Leave user.
- Create the Laravel session using the existing auth guard.
- Redirect to the existing Leave dashboard.
- Add clear error handling for expired, invalid, or unmapped tokens.

Exit criteria:

- A valid mobile token logs the mapped user into Leave.
- Expired and reused tokens do not create sessions.
- Invalid mappings do not create sessions.
- Flow works in browser before Expo WebView testing.

## Phase 4: Medical Management Integration

Goal:

- Add mobile auto-login to the Medical Management Laravel app.

Current status:

- Deferred.
- Medical remains a placeholder in Mobile Auth API until its auth files are available.
- The Medical user mapping target is now known: `medical.users.id`.
- Current schema notes are documented in [Medical Management Discovery](medical_management_discovery.md).

Tasks:

- Add a `GET /mobile-login` route.
- Add a controller action for mobile login.
- Validate the `token` query parameter with the Mobile Auth API.
- Check that the response target is `medical`.
- Resolve `medical_user_id` to a local Medical user.
- Create the Laravel session using the existing auth guard.
- Redirect to the existing Medical dashboard.
- Add clear error handling for expired, invalid, or unmapped tokens.

Exit criteria:

- A valid mobile token logs the mapped user into Medical.
- Expired and reused tokens do not create sessions.
- Invalid mappings do not create sessions.
- Flow works in browser before Expo WebView testing.

## Phase 5: Expo Mobile MVP

Goal:

- Build the mobile portal that consumes the Mobile Auth API and opens each system in WebView.

Current status:

- Initial Expo app is implemented in `mobile-portal-app/`.
- Login, session restore, dashboard, Leave WebView, and logout are implemented.
- Medical is shown as a disabled placeholder.
- Expo status is documented in [Expo App Status](expo_app_status.md).

Tasks:

- Create the Expo app.
- Install navigation, WebView, and SecureStore dependencies.
- Implement login screen.
- Implement dashboard screen.
- Implement secure JWT storage.
- Implement `/me` session restore on app launch.
- Implement Leave access button using `POST /api/mobile/token/leave`.
- Implement Medical access button using `POST /api/mobile/token/medical`.
- Implement Leave and Medical WebView screens.
- Implement loading and error states.
- Implement mobile logout.

Exit criteria:

- User can log in once from Expo.
- Dashboard shows only allowed systems.
- Leave and Medical buttons open authenticated WebView sessions.
- App restart restores the mobile session.
- Logout clears the local mobile JWT.

## Phase 6: End-to-End Hardening

Goal:

- Verify system behavior under realistic failure and session scenarios.

Tasks:

- Test token expiration and retry behavior.
- Test reused token behavior.
- Test user with Leave-only access.
- Test user with Medical-only access.
- Test user with both systems.
- Test disabled mobile user.
- Test missing local Leave or Medical user mapping.
- Test WebView session persistence after app backgrounding.
- Test logout expectations for mobile session and web sessions.
- Confirm production environment variables.

Exit criteria:

- All MVP acceptance tests pass.
- Known limitations are documented.
- Deployment checklist is complete.

## Phase 7: Deployment and Release

Goal:

- Deploy the integrated MVP safely.

Tasks:

- Deploy Mobile Auth API and database.
- Deploy Leave Management changes.
- Deploy Medical Management changes.
- Configure production environment variables.
- Run smoke tests against production URLs.
- Build Expo preview or release build.
- Test on physical Android device at minimum.

Exit criteria:

- Production smoke tests pass.
- MVP is usable by test users.
- Rollback plan is documented.

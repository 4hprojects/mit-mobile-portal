# Mobile Portal Risks and Decisions

## Key Decisions Needed Before Implementation

### Identity Mapping

Decision needed:

- Use `employee_id`, `email`, username, or manual admin-managed mapping.

Recommendation:

- Prefer `employee_id` if it exists consistently in both systems.
- Use manual mapping as the fallback when historical data is inconsistent.

Why it matters:

- Incorrect mapping can log a mobile user into the wrong Leave or Medical account.

### Token Storage

Decision:

- Store only hashed temporary login tokens in the Mobile Auth database.

Why it matters:

- If the database is exposed, plain one-time login URLs should not be reusable.

### Web Session Ownership

Decision:

- Each Laravel app owns its own web session after mobile auto-login.

Why it matters:

- The Mobile Auth API should not try to share Laravel sessions across separate apps.

### Logout Scope

Decision needed:

- Should Expo logout only clear the mobile JWT, or should it also attempt to clear WebView cookies and web sessions?

Recommendation for MVP:

- Clear the mobile JWT and reset WebView screens.
- Document that existing Laravel web sessions may remain valid until their configured session expiration unless explicit web logout endpoints are added later.

### Server-to-Server Authentication

Decision:

- Leave and Medical apps must use a server-only integration secret when calling `POST /api/mobile/verify-token`.

Why it matters:

- The Expo app must never be able to verify or consume temporary login tokens directly.

## Primary Risks

### WebView Session Behavior

Risk:

- Cookie handling can differ between Android, iOS, Expo Go, development builds, and production builds.

Mitigation:

- Test on a physical Android device early.
- Use `sharedCookiesEnabled`.
- Keep Laravel cookie settings explicit.

### Cross-System Impersonation

Risk:

- A token generated for Leave could be accepted by Medical if system targeting is not enforced.

Mitigation:

- Store `system_target`.
- Require the target app to submit its expected system during verification.
- Reject mismatches.

### Token Replay

Risk:

- A temporary login URL could be reused if `used_at` is not enforced atomically.

Mitigation:

- Mark token as used during successful verification.
- Use a database transaction or conditional update to avoid race conditions.

### Mapping Drift

Risk:

- A user might be deleted or disabled in Leave or Medical while still mapped in Mobile Auth.

Mitigation:

- Target Laravel app must still confirm the local user exists and is allowed to log in.
- Add admin or maintenance process for mappings.

### Environment Configuration Errors

Risk:

- Incorrect Render URLs, secrets, CORS settings, or session settings can break the flow.

Mitigation:

- Maintain an environment variable checklist per service.
- Run production smoke tests after every deployment.

## Known MVP Limitations

- WebView experience depends on the existing web app responsiveness.
- Offline mode is not supported.
- Push notifications are not included.
- Native Leave and Medical modules are not included.
- Expo logout may not fully invalidate already-created Laravel web sessions unless extra logout integration is added.

## Open Questions

- What is the canonical shared user identifier across Leave and Medical?
- Are Leave and Medical both using Laravel's default `web` guard?
- What are the exact dashboard redirect paths for Leave and Medical?
- Should the Mobile Auth API include an admin UI for managing mappings, or will mappings be seeded/imported?
- Is iOS required for the first MVP, or Android only?

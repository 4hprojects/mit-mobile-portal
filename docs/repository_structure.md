# Repository Structure

## Recommended GitHub Repository

Use one GitHub repository for the mobile portal work:

```text
mit-mobile-portal/
  docs/
  mobile-auth-api/
  mobile-portal-app/
```

This keeps the portal documentation, centralized authentication service, and Expo app together.

## Included In GitHub

### `docs/`

Project documentation, implementation phases, API contract, service checklists, testing notes, and integration discovery notes.

### `mobile-auth-api/`

Standalone Laravel API for:

- Mobile login
- Mobile JWT issuing
- User-to-system access mapping
- Temporary login URL generation
- Token verification for external systems

### `mobile-portal-app/`

Expo React Native app for:

- Mobile login
- Dashboard
- Leave WebView
- Medical placeholder

## External Repositories

### Leave Management

Source of truth remains GitLab:

```text
https://gitlab.com/mit-111/leave-management
```

Local development path:

```text
leave-management/
```

This app is an external integrated system. It should not be committed into the `mit-mobile-portal` GitHub repository.

### Medical Management

Source is pending. Current known information is documented in:

```text
docs/mobile_portal/medical_management_discovery.md
```

Medical should also remain an external integrated system unless the team decides otherwise.

## Excluded From GitHub

Do not commit:

```text
.codex/
leave-management/
**/vendor/
**/node_modules/
**/.env
**/.expo/
**/storage/logs/*.log
```

## Why This Structure

The mobile portal is a coordinating product with its own backend and app. Leave and Medical are existing applications with their own ownership and deployment lifecycle. Keeping them external avoids mixing source-of-truth repositories while still documenting the integration points.

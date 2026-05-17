# Expo App Status

## Current Scope

The Expo app has been scaffolded in:

```text
mobile-portal-app/
```

Repository note:

- This folder belongs in the planned `mit-mobile-portal` GitHub repository.

Current MVP flow:

```text
Login -> Dashboard -> Leave Management WebView
```

Medical Management is shown as a disabled placeholder until the Medical auth flow is confirmed.

## Implemented

- Login screen.
- Secure token storage with Expo SecureStore.
- Session restore through `GET /api/mobile/me`.
- Dashboard with user name and system access.
- Leave Management access through `POST /api/mobile/token/leave`.
- Leave Management WebView.
- Logout.
- Medical Management disabled placeholder.

## Local Expo Go Setup

Current LAN IP:

```text
192.168.1.12
```

Mobile Auth API:

```text
http://192.168.1.12:8000
```

Leave Management:

```text
http://192.168.1.12:8001
```

Expo dev server:

```text
exp://192.168.1.12:8081
```

The Expo app reads:

```text
EXPO_PUBLIC_MOBILE_AUTH_API_URL=http://192.168.1.12:8000
```

from `mobile-portal-app/.env`.

## Local Test Account

Seeded Mobile Auth user:

```text
login: admin@leavemgmt.com
password: password
```

This user uses the same email as the seeded Leave admin account and maps to Leave `users.user_id = 1`.

## Verification

Completed checks:

```text
Mobile Auth API /up: 200
Leave Management /up: 200
Mobile Auth login: success
Leave token generation: success
Leave /mobile-login: authenticated as users.user_id = 1
Expo Android bundle: 200
```

## Notes

- Node is currently `20.17.0`; Expo/React Native dependencies warn that `20.19.4` or newer is preferred.
- The app still starts and the Android bundle compiles, but upgrading Node is recommended before longer mobile testing.
- Expo Go and the laptop must be on the same Wi-Fi network for LAN mode.

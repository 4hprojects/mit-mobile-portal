# Mobile Portal Integration System PRD

## Project Title

Mobile Portal Integration System for Leave Management and Medical Management Web Applications Using Expo JS and Centralized Authentication

## Supporting Documents

This PRD defines the product scope and target architecture. Implementation details are split into focused documents:

- [Repository Structure](repository_structure.md)
- [Implementation Phases](mobile_portal/implementation_phases.md)
- [API Contract](mobile_portal/api_contract.md)
- [Service Checklists](mobile_portal/service_checklists.md)
- [Testing Plan](mobile_portal/testing_plan.md)
- [Risks and Decisions](mobile_portal/risks_and_decisions.md)
- [Leave Management Discovery](mobile_portal/leave_management_discovery.md)
- [Medical Management Discovery](mobile_portal/medical_management_discovery.md)
- [Mobile Auth API Status](mobile_portal/mobile_auth_api_status.md)
- [Expo App Status](mobile_portal/expo_app_status.md)

## 1. Overview

The project will create an Expo JS mobile application that serves as a centralized mobile portal for two existing web-based systems:

- Leave Management System
- Medical Management System

Both systems are already deployed as separate Laravel/PHP applications hosted on Render and use independent PostgreSQL databases.

The mobile application will provide:

- One mobile login
- Centralized authentication
- Access to both systems through mobile devices
- Automatic login into each web application
- A unified mobile user experience

The goal is not to merge the systems or rebuild them into native mobile modules at this stage. The mobile application will act as a secure gateway and access portal for both systems.

## 2. Objectives

### Main Objectives

- Provide a single login experience for users.
- Allow mobile access to both systems.
- Maintain separate databases for both systems.
- Avoid major modification to existing deployed systems.
- Create a scalable architecture for future native integration.
- Minimize redevelopment effort.
- Provide secure cross-system authentication.

### Secondary Objectives

- Support future expansion to additional systems.
- Enable gradual transition from WebView to native mobile modules.
- Improve accessibility and user convenience.
- Centralize mobile authentication and access control.

## 3. Existing Systems

### Leave Management System

Current stack:

- Laravel PHP
- PostgreSQL
- Vue/Vite frontend
- Render deployment

Existing features:

- User authentication
- Leave application submission
- Leave approval workflow
- Leave balance checking
- Role and permission management
- Unit management
- Dashboard and reports

### Medical Management System

Current stack:

- Laravel PHP
- PostgreSQL
- Render deployment

Existing features:

- Medical records management
- User management
- Medical requests and processing
- Dashboard and reporting

## 4. Proposed Architecture

```text
Expo JS Mobile Application
  -> Central Mobile Authentication API
  -> Mobile Authentication Database
  -> Leave Management Web Application
  -> Medical Management Web Application
```

The central mobile authentication service owns mobile login, mobile session tokens, user-to-system mapping, and temporary web login tokens. Each existing Laravel application keeps its own database and application session.

## 5. System Components

### Expo JS Mobile Application

Purpose:

- Acts as the centralized mobile portal.

Responsibilities:

- User login
- Session management
- Dashboard display
- Access control display
- WebView integration
- Secure token storage
- Logout handling

Recommended stack:

- Expo JS
- React Native
- React Navigation
- React Native WebView
- Expo SecureStore

Initial screens:

- Login screen
- Dashboard screen
- Leave WebView screen
- Medical WebView screen
- Profile screen, optional for MVP

### Central Mobile Authentication API

Purpose:

- Acts as the centralized authentication service.

Responsibilities:

- User authentication
- Mobile JWT generation
- User mapping
- Access validation
- Temporary login token generation
- Cross-system access coordination

Recommended stack:

- Laravel PHP
- PostgreSQL
- JWT authentication
- Render deployment

### Existing Laravel Web Applications

Required update in each application:

- Add a `/mobile-login?token=...` route.
- Validate the temporary token with the Mobile Auth API.
- Resolve the mapped local user.
- Create a Laravel session for the local web application.
- Redirect to the existing web dashboard.

## 6. Database Design

### `mobile_users`

```text
id
employee_id
name
email
username
password_hash
status
created_at
updated_at
```

### `mobile_user_system_access`

```text
id
mobile_user_id
leave_user_id
medical_user_id
can_access_leave
can_access_medical
created_at
updated_at
```

### `temporary_login_tokens`

```text
id
mobile_user_id
token_hash
system_target
expires_at
used_at
created_at
```

Notes:

- Store only a hash of the temporary login token.
- `system_target` must be limited to known values such as `leave` and `medical`.
- Tokens must be one-time use and short lived.

## 7. Authentication Flow

1. User logs in from the Expo app through `POST /api/mobile/login`.
2. Mobile Auth API validates credentials, status, and access permissions.
3. Mobile Auth API returns a mobile JWT and user profile.
4. Expo stores the mobile JWT using SecureStore.
5. User taps Leave or Medical from the dashboard.
6. Expo requests a temporary login URL from the Mobile Auth API.
7. Expo opens the returned URL in a WebView.
8. The target Laravel app validates the temporary token through the Mobile Auth API.
9. The target Laravel app creates its own web session and redirects to its dashboard.

## 8. API Design

The API contract is defined in [API Contract](mobile_portal/api_contract.md).

Required endpoints:

- `POST /api/mobile/login`
- `POST /api/mobile/logout`
- `GET /api/mobile/me`
- `POST /api/mobile/token/leave`
- `POST /api/mobile/token/medical`
- `POST /api/mobile/verify-token`

## 9. Security Design

Security goals:

- Prevent unauthorized access.
- Prevent temporary token reuse.
- Prevent cross-system impersonation.
- Protect local Laravel session integrity.

Security measures:

- Use HTTPS for all deployed services.
- Use mobile JWTs for Expo-to-Mobile-Auth communication.
- Use short-lived temporary login tokens for WebView auto-login.
- Store only hashed temporary tokens in the database.
- Mark temporary tokens as used immediately after successful verification.
- Configure Laravel session cookies securely.

Recommended Laravel session settings:

```text
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
```

## 10. Expo Application Structure

Recommended structure:

```text
mobile-portal/
  app/
  screens/
    LoginScreen.js
    DashboardScreen.js
    LeaveWebViewScreen.js
    MedicalWebViewScreen.js
    ProfileScreen.js
  services/
    api.js
    auth.js
    storage.js
  components/
  navigation/
  assets/
  package.json
```

## 11. WebView Design

Recommended behavior:

- Shared cookies enabled.
- Third-party cookies enabled where needed.
- Session persistence.
- Loading indicators.
- Clear expired-token and access-denied states.

Example:

```jsx
<WebView
  source={{ uri: loginUrl }}
  sharedCookiesEnabled={true}
  thirdPartyCookiesEnabled={true}
/>
```

## 12. Deployment Architecture

Current deployed systems:

- Leave Management: Render Web Service and PostgreSQL database.
- Medical Management: Render Web Service and PostgreSQL database.

New services:

- Mobile Authentication API: Render Web Service.
- Mobile Authentication Database: Render PostgreSQL.

## 13. Recommended MVP Scope

Included:

- Expo login.
- Centralized authentication.
- Leave Management integration.
- Medical Management integration.
- WebView access.
- Automatic login.
- Basic logout.

Excluded initially:

- Native module rewrite.
- Offline support.
- Push notifications.
- Real-time synchronization.
- Cross-system unified reporting.
- Biometric login.

## 14. Future Enhancements

- Native mobile modules.
- Push notifications.
- Offline caching.
- Biometric authentication.
- Mobile attendance tracking.
- Multi-system dashboard analytics.
- Unified profile management.
- Centralized notifications.

## 15. Conclusion

The proposed Mobile Portal Integration System provides a practical way to integrate multiple existing web applications into a unified mobile experience while preserving the independence of each deployed system.

Implementation should follow the phased plan in [Implementation Phases](mobile_portal/implementation_phases.md), because the highest-risk areas are cross-system authentication, WebView session handling, and user identity mapping.

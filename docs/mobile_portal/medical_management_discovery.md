# Medical Management Discovery

## Current Status

Medical Management remains a placeholder in the current implementation until its auth flow is confirmed.

The other team provided the user table schema, which is enough to confirm the mapping target but not enough to implement auto-login safely.

## Confirmed User Mapping

Medical local user mapping should use:

```text
medical_user_id -> medical.users.id
```

## Provided User Table

Table: `users`

```text
id: BIGINT UNSIGNED, primary key
first_name: VARCHAR(50), nullable
middle_initial: VARCHAR(10), nullable, default 'N/A'
last_name: VARCHAR(50), nullable
suffix: VARCHAR(10), nullable
archived: BOOLEAN, default false
email: VARCHAR(255), unique
email_verified_at: TIMESTAMP, nullable
password: VARCHAR(255)
remember_token: VARCHAR(100), nullable
created_at: TIMESTAMP, nullable
updated_at: TIMESTAMP, nullable
```

## Still Needed For Auto-Login

- Confirm whether Medical is Laravel.
- Confirm whether it uses Laravel's default `web` guard.
- Confirm login route/controller.
- Confirm dashboard route after successful login.
- Confirm whether `archived = true` blocks login.
- Confirm user model namespace, likely `App\Models\User`.

## Current Mobile Auth API Behavior

The Mobile Auth API schema already supports:

```text
medical_user_id
can_access_medical
```

But Medical endpoints intentionally return `medical_placeholder` until the auth flow is confirmed.

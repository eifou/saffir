# Saffir — Plain PHP API

No frameworks, no Composer. Just PHP + MySQL running on XAMPP.

## File structure

```
saffir-api/
├── api/
│   ├── register.php       POST   Create account
│   ├── login.php          POST   Login → returns Bearer token
│   ├── logout.php         POST   Revoke token
│   ├── me.php             GET    Current user | PUT Update profile
│   ├── schedules.php      GET    List/search schedules
│   ├── routes.php         GET    All routes with stops
│   ├── alerts.php         GET    Open system alerts
│   ├── subscriptions.php  GET/POST/DELETE  Manage subscriptions
│   └── wallet.php         GET    Balance + history | POST Top up
├── config/
│   ├── database.php       PDO connection (edit credentials here)
│   ├── helpers.php        json(), error(), validate(), requireAuth()…
│   └── cors.php           CORS headers for React dev server
└── create_tokens_table.sql  Run this once in phpMyAdmin
```

## API endpoints

| Method | File | Auth | Description |
|--------|------|------|-------------|
| POST | `/api/register.php` | ✗ | Register `{first_name, last_name, email, password, role}` |
| POST | `/api/login.php` | ✗ | Login `{email, password}` → `{token, user}` |
| POST | `/api/logout.php` | ✓ | Revoke current token |
| GET | `/api/me.php` | ✓ | My profile + wallet balance |
| PUT | `/api/me.php` | ✓ | Update username/email |
| GET | `/api/schedules.php` | ✗ | List schedules `?route_id=&date=` |
| GET | `/api/schedules.php?id=` | ✗ | Single schedule |
| GET | `/api/routes.php` | ✗ | All routes with stops |
| GET | `/api/routes.php?id=` | ✗ | Single route with stops & schedules |
| GET | `/api/alerts.php` | ✗ | Open alerts `?status=OPEN\|RESOLVED` |
| GET | `/api/subscriptions.php` | ✓ | My subscriptions |
| POST | `/api/subscriptions.php` | ✓ | Buy `{type: DAILY\|WEEKLY\|MONTHLY\|YEARLY}` |
| DELETE | `/api/subscriptions.php?id=` | ✓ | Cancel subscription |
| GET | `/api/wallet.php` | ✓ | Balance + last 20 transactions |
| POST | `/api/wallet.php` | ✓ | Top up `{amount}` |

Authenticated routes require header: `Authorization: Bearer <token>`

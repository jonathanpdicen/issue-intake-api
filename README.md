# Issue Intake API

A Laravel API where a support team submits issues and comments on them. Each issue gets a short summary and a suggested next action, generated in a background job.

## Endpoints

| Method | Endpoint | Purpose |
|---|---|---|
| POST | `/api/v1/auth/register` | Create an account (`name`, `email`, `password`, `password_confirmation`, `device_name`) and get a bearer token |
| POST | `/api/v1/auth/login` | Exchange `email`, `password` and `device_name` for a bearer token |
| POST | `/api/v1/auth/logout` | Revoke the current token |
| POST | `/api/issues` | Create an issue |
| GET | `/api/issues?status=open&priority=high&category=billing` | List issues (filters can be combined) |
| GET | `/api/issues/{id}` | View one issue with its comments |
| PATCH | `/api/issues/{id}` | Update an issue |
| POST | `/api/issues/{id}/comments` | Add a comment |

All issue and comment endpoints require authentication. Send the token as `Authorization: Bearer <token>`; missing or invalid tokens return `401` with `error.code` of `unauthenticated`.

---

## Option A: Run with Lando (MySQL + Redis)

Requires [Lando](https://lando.dev).

```bash
cp .env.example .env
lando start
lando composer install
lando artisan key:generate
lando artisan migrate --seed
lando npm install
lando npm run build
```

Start the queue worker (keep it running in its own terminal):

```bash
lando artisan queue:work
```

The app is at **https://issue-intake.test** (or https://issue-intake-api.lndo.site).

Run the tests:

```bash
lando artisan test
```

---

## Option B: Run without Lando (SQLite)

Requires PHP 8.3+, Composer and Node.js.

```bash
cp .env.example .env
```

In `.env`, set these values and **delete** the `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` and `DB_PASSWORD` lines:

```dotenv
DB_CONNECTION=sqlite
QUEUE_CONNECTION=database
CACHE_STORE=database
SESSION_DRIVER=database
```

Then:

```bash
composer install
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

Start the queue worker in a second terminal:

```bash
php artisan queue:work
```

The app is at **http://127.0.0.1:8000**.

Run the tests:

```bash
php artisan test
```

# AGENTS.md

## Cursor Cloud specific instructions

Blog API — a Laravel 12 backend exposing a hybrid API: **GraphQL (Lighthouse) for reads** and **REST for writes**, with Sanctum auth (token or session). It is API-only; there is no web UI in this repo (the `blog-fe` Next.js frontend lives in a separate repo). Hitting `/` returns 404 — that is expected. Real endpoints live under `/api/*` and `/graphql`.

### Runtime / versions (non-obvious)
- **PHP 8.4 is required**, even though `composer.json` declares `php: ^8.2`. `composer.lock` pins Symfony 8 packages (e.g. `symfony/clock`) that require `php >=8.4`, so `composer install` fails on PHP 8.3. The update script's `composer install` and `npm install` refresh dependencies; PHP 8.4 + Composer + Node 22 are provided by the VM image.
- Do not "fix" the PHP constraint by running `composer update` — that would rewrite the lock file. Just use PHP 8.4.

### First-time database bootstrap (not in the update script)
The DB is SQLite at `database/database.sqlite` and the `.env` file are git-ignored, so on a truly fresh checkout you must create them once:
```bash
cp -n .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --force
php artisan db:seed --force            # DatabaseSeeder: test@example.com / password
php artisan db:seed --class=UserSeeder --force   # superadmin@example.com / Password@1234
```
Note: `DatabaseSeeder` does NOT call `UserSeeder`, so run `UserSeeder` explicitly if you need the super admin. Seeded credentials:
- `superadmin@example.com` / `Password@1234`
- `test@example.com` / `password`

### Running the app
- API only: `php artisan serve --host=0.0.0.0 --port=8000` → http://localhost:8000
- Full dev stack (server + queue worker + log tailer + Vite): `composer dev` (runs 4 concurrent processes via `concurrently`; kills all if one exits). For API/GraphQL testing you only need `php artisan serve`.
- Frontend build (optional, for Blade/Tailwind assets): `npm run build`.

### Lint / test
- Tests: `composer test` (clears config, then `php artisan test`; 138 tests, uses in-memory SQLite via `phpunit.xml`).
- Lint/format: `./vendor/bin/pint` (or `./vendor/bin/pint --test` to check without writing). The existing codebase has many pre-existing Pint style violations — `--test` reporting failures is not caused by your changes.

### Gotchas
- The GraphQL `Blog.created_by` / `Blog.updated_by` relations are non-nullable `User!`. Blogs created by a since-deleted user (or via factories that create throwaway users) will make those sub-fields error with `Cannot return null for non-nullable field "User.name"`. This is app/data behavior, not an environment problem — avoid selecting those relations in ad-hoc smoke queries.

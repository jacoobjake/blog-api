# AGENTS.md

## Cursor Cloud specific instructions

### Service
Laravel 12 backend. **GraphQL for reads** (Lighthouse, endpoint `POST /graphql`), **REST for writes**, Sanctum for admin auth. Default database is file-based SQLite (`database/database.sqlite`) — no DB server required.

New modules should expose list/detail queries via GraphQL and create/update/delete via REST. Do not add REST `GET` routes.

### Runtime gotchas
- **Requires PHP 8.4**, not 8.2. `composer.json` declares `php: ^8.2`, but `composer.lock` pins Symfony 8 components that need `php >= 8.4`, so `composer install` fails on PHP 8.3. Use PHP 8.4.
- First-time DB setup (not in the update script): copy `.env` from `.env.example`, `php artisan key:generate`, `php artisan migrate`, `php artisan db:seed`. These persist across sessions once done. The seeder only creates a test user (`test@example.com`) — it does **not** create any blogs.
- `blogsPublic` only returns published posts, so to see content on the frontend `/blogs` page you must create a Blog with `is_published = true` (e.g. via `php artisan tinker`).

### Run / test
- Full dev stack: `composer dev` (concurrently runs `artisan serve` + queue listener + pail + vite). API-only is enough for the frontend: `php artisan serve --host=0.0.0.0 --port=8000`.
- Tests: `composer test` (PHPUnit) — full suite passes.

### RBAC (Tier B Phase 1)
- Roles via **Spatie Permission**: `superadmin`, `admin`, `editor`, `author`. Seeded users: `superadmin@example.com` (superadmin), `test@example.com` (author).
- Run `php artisan db:seed --class=RolePermissionSeeder` on existing DBs after pulling.
- Password reset requires `FRONTEND_URL` in `.env` (reset link target) and SMTP/Mailtrap mail config.

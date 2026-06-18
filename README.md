# Blog API

Laravel backend for the blogging platform. It serves the [blog-fe](../blog-fe) frontend with a hybrid API: GraphQL for reads and REST for writes.

Public clients fetch published posts via GraphQL (`blogsPublic`, `blogPublic`). Admins authenticate with Laravel Sanctum (session or token), read data through GraphQL, and create or update content through REST endpoints for blogs, assets, and auth.

## Tech Stack

Laravel 12, Lighthouse (GraphQL), Sanctum, Spatie Media Library, Spatie Tags

## Getting Started

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
composer dev
```

Or run `composer setup` for a one-shot install. The API runs at [http://localhost:8000](http://localhost:8000).

For Docker:

```bash
docker compose -f compose.dev.yaml up -d
```

Run tests with `composer test`.

## Related Projects

- [blog-fe](../blog-fe) — Next.js frontend

# Parish Registry Laravel migration

This directory is the Laravel 12 port of the existing parish registry. It uses the existing `parish_registry` MySQL database without transforming or deleting any application data.

## Serve it

Configure the Apache virtual host (or XAMPP alias) document root to `parish-registry-laravel/public`. Do not point Apache at the Laravel project root.

The existing frontend is retained at `public/parish-registry.html` and continues to call `/api/v1/...` on the same origin.

## Database and uploads

The included `.env` is configured for the local XAMPP MySQL database and `Asia/Manila`. Uploaded images use Laravel's `public` storage disk; the `public/storage` link has already been created.

Run `php artisan migrate --pretend` before `php artisan migrate`. The parish-schema migration intentionally checks for each existing table, so it records the current schema without recreating tables. Do not run `migrate:fresh` against the existing parish database.

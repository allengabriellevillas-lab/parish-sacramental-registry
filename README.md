# Parish Sacramental Registry

This PHP 8/MySQL build uses the supplied XAMPP runtime rather than adding a separate Node service. Copy `config.example.php` to `config.php`, import `database/migrations/001_initial_schema.sql`, then optionally import `database/seeds/demo.sql` (development login: `admin` / `password`). Point Apache's document root at `public/`.

API endpoints are under `/api/v1`; all non-login endpoints require a session. CSV uploads are capped at 5MB and staged in `import_staged_rows`, then revalidated during transactional commit. Back up daily with `mysqldump --single-transaction parish_registry > parish_registry_YYYY-MM-DD.sql` and retain encrypted off-host copies.

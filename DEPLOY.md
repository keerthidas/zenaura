# Deploy Zenaura

Please put this Laravel app on the production server. It is a quotations and tax-invoice system (PHP 8.2, MySQL, Livewire).

You will receive the application source. If `vendor/` or `public/build/` are missing, generate them on the server with the commands below. Do not reuse a local `.env` file if one is included by mistake. Create a new one from `.env.example`.

## Requirements

- PHP 8.2+
- MySQL
- Composer
- Node.js (needed to build CSS/JS unless `public/build/` is already in the package)
- PHP extensions: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `gd`
- The site document root **must** be the `public` folder inside the project, not the project root

## 1. Place the code

Put the project on the server, for example `/var/www/zenora`, and:

```bash
cd /path/to/zenora
```

## 2. Create the database

Create a **new empty** MySQL database and a user with access to it. You do not need a SQL dump. Migrations will create the tables.

Example:

```sql
CREATE DATABASE zenora CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'zenora'@'localhost' IDENTIFIED BY 'a-strong-password';
GRANT ALL PRIVILEGES ON zenora.* TO 'zenora'@'localhost';
FLUSH PRIVILEGES;
```

## 3. Configure the environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` and set:

```env
APP_NAME=Zenaura
APP_ENV=production
APP_DEBUG=false
APP_URL=https://the-real-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=zenora
DB_USERNAME=zenora
DB_PASSWORD=a-strong-password

FILESYSTEM_DISK=public
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=sync
```

`APP_URL` must be the live URL. Image and PDF links depend on it. Keep `APP_DEBUG=false`.

## 4. Install and build

```bash
composer install --no-dev --optimize-autoloader

npm ci
npm run build
```

If Composer or Node are not available on the server, ask for a package that already includes `vendor/` and `public/build/`.

## 5. Run migrations

```bash
php artisan migrate --force
```

This creates all tables. Do not skip this step.

Then create the first admin user and starter catalogue data:

```bash
php artisan db:seed --force
```

Login after seeding:

- Email: `admin@zenaura.ae`
- Password: `password`

Change that password as soon as the site is up.

## 6. Storage and permissions

```bash
php artisan storage:link
chmod -R ug+rwx storage bootstrap/cache
```

The web server user must be able to write to `storage/` and `bootstrap/cache/`. Uploaded product and quotation images will not work without `storage:link`.

## 7. Cache config for production

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 8. Web server

Point Apache or Nginx at:

```text
/path/to/zenora/public
```

Enable HTTPS on the domain.


## Checklist

- [ ] Document root is `public/`
- [ ] Empty MySQL database created
- [ ] `.env` created on the server (`APP_ENV=production`, `APP_DEBUG=false`, correct `APP_URL` and DB settings)
- [ ] `php artisan key:generate`
- [ ] `composer install --no-dev --optimize-autoloader`
- [ ] `npm ci` and `npm run build` (or `public/build/` already present)
- [ ] `php artisan migrate --force`
- [ ] `php artisan db:seed --force` (then change the admin password)
- [ ] `php artisan storage:link`
- [ ] `storage/` and `bootstrap/cache/` writable
- [ ] HTTPS enabled

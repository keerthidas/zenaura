# Deploy Zenaura

## Requirements

PHP 8.2 or newer, MySQL, Composer, and Node.js/npm. PHP needs `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, and `gd`.

## Code

```bash
git clone https://github.com/keerthidas/zenaura.git
cd zenaura
```

## Database

Create the database and user, then import `zenaura-backup.sql` from the project root:

```sql
CREATE DATABASE zenaura CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'zenaura'@'localhost' IDENTIFIED BY 'CHOOSE_A_STRONG_PASSWORD';
GRANT ALL PRIVILEGES ON zenaura.* TO 'zenaura'@'localhost';
FLUSH PRIVILEGES;
```

```bash
mysql -h 127.0.0.1 -P 3306 -u zenaura -p zenaura < zenaura-backup.sql
```

## Uploaded images

Images are not in Git. Copy the supplied `storage/app/public` contents into the project's `storage/app/public` folder. Keep the existing folders (`products`, `quotation-items`, `company`, and any others).

## Application

```bash
cp .env.example .env
php artisan key:generate
```

Set these in `.env`:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=zenaura
DB_USERNAME=zenaura
DB_PASSWORD=CHOOSE_A_STRONG_PASSWORD

FILESYSTEM_DISK=public
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=sync
```

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

The web server user needs write access to `storage` and `bootstrap/cache`. Point the document root at `public` and enable HTTPS.

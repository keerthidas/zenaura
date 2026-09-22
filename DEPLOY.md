# Deploy Zenaura

PHP 8.2+, MySQL, Composer, Node.js.  
PHP extensions: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `gd`.  
Document root = `public/` (not the project root).



## On the server

### 1. Put the code

```bash
cd /path/to/zenaura
```

### 2. Create an empty MySQL database

```sql
CREATE DATABASE zenaura CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'zenaura'@'localhost' IDENTIFIED BY 'a-strong-password';
GRANT ALL PRIVILEGES ON zenaura.* TO 'zenaura'@'localhost';
FLUSH PRIVILEGES;
```

### 3. Create `.env`

```bash
cp .env.example .env
php artisan key:generate
```

Set:

```env
APP_NAME=Zenaura
APP_ENV=production
APP_DEBUG=false
APP_URL=https://the-real-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=zenaura
DB_USERNAME=zenaura
DB_PASSWORD=a-strong-password

FILESYSTEM_DISK=public
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=sync
```

### 4. Install

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

### 5. Storage (before seed)

```bash
mkdir -p storage/app/public
php artisan storage:link
chmod -R ug+rwx storage bootstrap/cache
```

### 6. Database

```bash
php artisan migrate --force
php artisan db:seed --force
```

Seed takes a few minutes. It creates the admin user, company profile, and catalogue photos in `storage/app/public`.

Login:

- Email: `admin@zenaura.ae`
- Password: `password`

Change the password. Open **Products** and confirm photos show.

### 7. Cache

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 8. Web server

Point Apache/Nginx at `/path/to/zenaura/public`. Enable HTTPS.

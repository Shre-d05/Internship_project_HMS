# Hostel Management System — Deployment Guide

## What's Included
- `hostel_management_deploy.zip` — all PHP files, assets, includes
- `deploy.sql` — database schema + sample data

## Steps

### 1. Upload files
- Extract `hostel_management_deploy.zip` into your hosting's `public_html/` (or a subfolder like `public_html/hostel/`)

### 2. Create Database
- In your hosting cPanel → **MySQL Databases**
- Create a new database
- Create a user with password
- Assign the user to the database (all privileges)
- Open **phpMyAdmin**, select your database, click **Import**, choose `deploy.sql`, click **Go**

### 3. Update Config
Open `includes/config.php` and update these 4 lines:
```php
define('DB_HOST', 'localhost');       // Your MySQL host (usually localhost)
define('DB_USER', 'root');            // Your DB username
define('DB_PASS', '');                // Your DB password
define('DB_NAME', 'hostel_management'); // Your database name
```

### 4. Test
Visit your domain. Login credentials:
- **Admin**:  `admin123` / `admin123`
- **Student**: `student123` / `student123`

## Folder Structure Note
If you deployed to a **subfolder** (e.g. `yourdomain.com/hostel/`), the app should work automatically — redirect paths are calculated dynamically. Assets use relative paths.

## Troubleshooting
| Problem | Fix |
|---|---|
| "Database connection failed" | Double-check credentials in `config.php` |
| Blank white page | Enable error reporting: add `ini_set('display_errors', 1);` to `config.php` |
| Login loops back to login | Session not persisting — check if `session.save_path` is writable on your host |
| 404 on CSS/JS | If you deployed to a subfolder, ensure relative paths (`../assets/...`) resolve correctly |

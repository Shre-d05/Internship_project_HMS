# InfinityFree Deployment Guide

## 1. Create InfinityFree Account
- Go to https://infinityfree.net
- Sign up → verify email
- Create hosting account → choose subdomain (e.g. `yourname.infinityfreeapp.com`)

## 2. Create Database
1. InfinityFree Client Area → **Control Panel** (cPanel)
2. **MySQL Databases** → Create:
   - Database: `hostel` → becomes `if0_XXXXXXXX_hostel`
   - User: `hostel_user` → becomes `if0_XXXXXXXX_hostel_user`
   - Password: **generate strong** → save it
3. Add user to database → **All Privileges**

## 3. Note Credentials
```
Host: sql200.infinityfree.com (or sqlXXX.infinityfree.com - check panel)
DB User: if0_XXXXXXXX_hostel_user
DB Pass: your_generated_password
DB Name: if0_XXXXXXXX_hostel
```

## 4. Update Config
Edit `includes/config.php` with your actual credentials:
```php
define('DB_HOST', 'sql200.infinityfree.com');
define('DB_USER', 'if0_XXXXXXXX_hostel_user');
define('DB_PASS', 'your_generated_password');
define('DB_NAME', 'if0_XXXXXXXX_hostel');
```

## 5. Import Database
1. cPanel → **phpMyAdmin**
2. Select your DB (`if0_XXXXXXXX_hostel`)
3. **Import** → Choose `database.sql` → **Go**
   - If error: Use **SQL** tab → paste cleaned SQL (without CREATE DATABASE)

## 6. Upload Files
**File Manager:**
1. cPanel → **File Manager** → `htdocs`
2. Delete default files
3. Upload **all project files** (not folder) → Extract if zipped

**FTP (FileZilla):**
- Host: `ftpupload.net` (or your domain)
- User: `if0_XXXXXXXX`
- Pass: **cPanel password** (not DB password)
- Port: 21
- Upload to `/htdocs/`

## 7. Set Permissions (File Manager)
- Folders: `755` (`includes/`, `admin/`, `student/`, `assets/`, `tmp/`)
- Files: `644` (all `.php`, `.css`, `.js`, `.sql`, `.htaccess`)
- `tmp/sessions`: `700`

## 8. Test
Visit: `https://yourname.infinityfreeapp.com/`
- Login: **admin** / **admin123**
- Student: **2021CS001** / **student123**

## Common Issues

| Issue | Fix |
|-------|-----|
| DB connection failed | Verify host (sql200-399.infinityfree.com), user, pass, name |
| Session errors | Ensure `tmp/sessions` exists with `700` permissions |
| White screen | Check PHP version → **Select PHP Version** → 8.1+ |
| CSS/JS 404 | Ensure `assets/` in `htdocs/assets/` |

## Default Logins
| Role | Username | Password |
|------|----------|----------|
| Admin | admin | admin123 |
| Student | 2021CS001 | student123 |
| Student | 2021CS002 | student123 |

## Security Notes
- Change default passwords after first login
- `includes/config.php` is protected by `.htaccess`
- Error display disabled in production
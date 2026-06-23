# Hostel Management System

A complete PHP/MySQL hostel management system with separate admin and student portals.

## Features

### Admin Portal
- **Dashboard** - Live statistics (students, rooms, occupancy, complaints)
- **Student Management** - Add/edit/delete students, assign rooms
- **Room Management** - CRUD rooms, filter by status/hostel, occupancy tracking
- **Hostel Management** - Manage hostel blocks, view occupancy stats
- **Complaint Management** - View/update/delete all complaints, filter by status/category
- **Mess & Menu** - Manage mess facilities, weekly meal schedule editor

### Student Portal
- **Dashboard** - Room details, today's mess menu, recent complaints
- **Profile** - View personal information
- **Room Details** - Assigned room info with hostel address
- **Complaints** - Submit new complaints, track status, withdraw pending
- **Mess Menu** - Full weekly menu view

## Tech Stack
- **Backend:** PHP 8.1+ (procedural, prepared statements)
- **Database:** MySQL/MariaDB
- **Frontend:** HTML5, CSS3 (custom properties, responsive), vanilla JS
- **Auth:** Session-based, password_hash()/password_verify()
- **Hosting:** InfinityFree / any shared PHP hosting

## Default Credentials

| Role | Username | Password |
|------|----------|----------|
| Admin | `admin` | `admin123` |
| Student | `2021CS001` | `student123` |
| Student | `2021CS002` | `student123` |

## Live Demo
**Website:** [https://hostelmanagamentapp.site.je](https://hostelmanagamentapp.site.je)

## Installation

### 1. Database Setup
```sql
-- Create database and import schema
CREATE DATABASE hostel_management;
-- Import database.sql via phpMyAdmin
```

### 2. Configuration
Edit `includes/config.php` with your credentials:
```php
define('DB_HOST', 'your_host');
define('DB_USER', 'your_user');
define('DB_PASS', 'your_pass');
define('DB_NAME', 'your_db');
```

### 3. Deploy
Upload all files to web root (`public_html/` or `htdocs/`).

### 4. Permissions
```bash
chmod 755 includes/ admin/ student/ assets/ tmp/
chmod 644 *.php *.css *.js *.sql .htaccess
chmod 700 tmp/sessions/
```

## Project Structure
```
hostel_management/
├── index.php              # Login page
├── includes/
│   ├── config.php         # DB config & session│   └── auth.php           # Auth helpers
├── admin/                 # Admin portal
│   ├── dashboard.php│   ├── students.php│   ├── rooms.php│   ├── hostels.php
│   ├── complaints.php
│   ├── mess.php
│   ├── header.php
│   └── footer.php
├── student/               # Student portal│   ├── dashboard.php
│   ├── profile.php
│   ├── room.php│   ├── complaints.php
│   ├── mess_menu.php
│   ├── header.php
│   └── footer.php
├── assets/
│   ├── css/style.css│   └── js/main.js
├── database.sql           # Full schema + seed data
├── .htaccess              # Security & performance
├── logout.php
├── setup.php
└── tmp/sessions/          # Session storage
```

## Security Features
- Prepared statements (SQL injection prevention)
- Password hashing (bcrypt)
- XSS protection (htmlspecialchars on output)
- CSRF protection via session tokens
- Protected sensitive files via .htaccess
- Error display disabled in production

## Requirements
- PHP 8.0+ (uses `match` expressions)
- MySQL 5.7+ / MariaDB 10.3+
- mysqli extension
- session extension

## License
MIT License - feel free to use for educational/commercial purposes.

## Contributing
Pull requests welcome. For major changes, open an issue first.
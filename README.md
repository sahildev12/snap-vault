# SnapVault

Team image management system built with **Core PHP 8+**, MySQL, Bootstrap 5, and vanilla JavaScript. No frameworks.

## Requirements

- XAMPP (PHP 8+, MySQL/MariaDB, Apache)
- PHP extensions: `pdo_mysql`, `fileinfo`, `gd` or image support

## Setup

1. Place the project in `C:\xampp\htdocs\snap-vault` (already there if you opened this folder).

2. Start **Apache** and **MySQL** in XAMPP.

3. Import the database:
   - Open phpMyAdmin → Import → select [`database/snap_vault.sql`](database/snap_vault.sql)
   - Or from CLI:
     ```bash
     mysql -u root < database/snap_vault.sql
     ```

4. Ensure `uploads/` and `uploads/profiles/` are writable by Apache.

5. Open: [http://localhost/snap-vault/](http://localhost/snap-vault/)

## Default Login

| Role  | Username | Password  |
|-------|----------|-----------|
| Admin | `admin`  | `Admin@123` |

Create team members from **Admin → Team Members**.

## Features

- Admin dashboard with stats & charts
- Team CRUD (create, edit, delete, reset password, enable/disable)
- File-explorer style folder cards per member
- Member camera / file upload with Normal / Important flags
- Galleries, filters, lightbox, download, delete
- CSRF, PDO prepared statements, hashed passwords, role guards

## Config

Edit [`config/config.php`](config/config.php) for DB credentials and `BASE_URL` if your folder name differs.

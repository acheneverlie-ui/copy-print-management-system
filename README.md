# Copy & Print Management System — Starter Project

A starter web application for recording copy/print usage, attaching files, managing printers, and viewing reports.

## Stack
- PHP 8+
- MySQL 8+
- HTML/CSS/JavaScript
- Runs locally with XAMPP, WAMP, or a PHP/MySQL server

## Features in this starter
- User login with password hashing
- Manual copy/print record entry
- Printer groups and printer IDs
- PDF, PNG, JPG/JPEG attachment upload
- Daily, weekly, monthly, yearly report filters
- Top users by recorded copy count
- Basic admin/user roles and permission examples

## Important
This is a starter template, not a production-ready system. Before deploying:
- Set strong database credentials and secure the web server.
- Use HTTPS.
- Restrict upload size and validate files with server-side MIME checks.
- Store uploaded files outside the public web root in production.
- Add CSRF protection, audit logging, backups, pagination, and full per-feature authorization.
- Automatic printer counter integration is not included; this version uses manual entry.

## Setup
1. Install XAMPP or another PHP 8+ and MySQL environment.
2. Copy this folder into the server's web directory (for XAMPP, `htdocs`).
3. Create a MySQL database and import `database/schema.sql`.
4. Edit `config.php` with your database credentials.
5. Open `http://localhost/copy_print_management_system/` in your browser.
6. Create an initial admin account using the SQL instructions below.

## Create the first admin
Generate a PHP password hash:
```php
<?php echo password_hash('ChangeThisPassword123!', PASSWORD_DEFAULT);
```
Run the snippet in a temporary PHP file, copy the generated hash, then execute:
```sql
INSERT INTO users (username, password_hash, display_name, role)
VALUES ('admin', 'PASTE_HASH_HERE', 'System Administrator', 'admin');
```
Delete the temporary hash-generation file immediately.

## Color/B&W and customer types (added)
- **Color mode**: copy and print records now require `Color` or `B/W`; scans store no color mode.
- **Customer type**: every user account has a type (`Teacher`, `TA`, `Student`). Admins set it when creating a user or change it later in *Users*. Each record stores a snapshot of the type at the time it was recorded, so reports stay correct if a user's type changes.
- **Dashboard**: Color vs B/W totals and a usage-by-type table.
- **Reports**: filter by customer type and color mode (works together with the daily/weekly/monthly/yearly filters), with totals per type and color.
- **Upgrading an existing database**: import `database/migration_color_usertype.sql` once. New installs only need `database/schema.sql`.

## Included pages
- `index.php` — login
- `dashboard.php` — summary dashboard
- `record.php` — add copy/print record and upload attachment
- `reports.php` — report filters and top users
- `logout.php` — logout
- `database/schema.sql` — database schema


## Updated visual design
The stylesheet now uses a modern indigo/cyan palette, soft gradients, responsive cards, improved tables and form controls, and a redesigned login screen. The app functionality and database schema are unchanged.

# Practical 12 — Event Management CRUD with Poster Upload

PHP + MySQLi event admin module for XAMPP. Includes event CRUD, poster upload validation, event category/status filtering, and audit logging.

## Features
- Create, view, edit and delete events
- Poster upload, replacement and removal
- Server-side image validation: JPEG/JPG, PNG, WEBP; maximum 2 MB
- MIME type checked using PHP Fileinfo, then image validity checked using `getimagesize`
- Random server-generated poster filenames
- Category and open/closed status filters
- Search by title, description or venue; pagination
- Success and failure messages
- MySQLi prepared statements, HTML output escaping and CSRF token checks
- Confirmation before permanent event deletion
- Audit log for create/update/delete actions
- Poster upload directory disables listing and blocks PHP-like files using `.htaccess`

## Requirements
- XAMPP with Apache, MySQL and PHP 8+
- PHP extensions `mysqli`, `fileinfo`
- phpMyAdmin

## Installation
1. Extract the folder to `C:\xampp\htdocs\practical-12-event-management`.
2. In XAMPP, start Apache and MySQL.
3. Open `http://localhost/phpmyadmin`, choose **Import**, select `database.sql`, and import it.
4. Check database credentials in `db.php`. Default XAMPP is `root` with an empty password.
5. Make sure `uploads/posters` is writable by the Apache/PHP process. The folder is included; PHP can also create it.
6. Visit `http://localhost/practical-12-event-management/`.

## File structure
```text
practical-12-event-management/
├── index.php
├── add.php
├── edit.php
├── delete.php
├── db.php
├── database.sql
├── css/style.css
├── uploads/posters/.htaccess
├── uploads/audit/.gitkeep
├── README.md
├── PRACTICAL-12-REPORT.md
└── TEST-CASES.md
```

## Poster rules
- Accepted formats: JPEG/JPG, PNG, WEBP
- Maximum size: 2 MB
- The server checks the actual MIME type, validates the image, and generates a random filename. It does not trust the original filename or browser-supplied extension.
- Configure PHP `upload_max_filesize` and `post_max_size` to allow at least 2M (for example, `upload_max_filesize = 8M`, `post_max_size = 10M`) if uploads fail before reaching the validation code.

## Security notes
This is an educational admin module. It includes CSRF checks and safe SQL parameters but **does not implement real user authentication**. It assumes a trusted local admin is using the application. Add authentication and role checks before deploying to a public server. `.htaccess` protection depends on Apache's AllowOverride configuration; verify it is enabled. Never use this demo unchanged for a public production site.

## Audit log
Create/update/delete events are recorded in `event_audit_log`. For this practical, the actor is the placeholder `local-admin`; in an authenticated application this should come from the logged-in admin's identity.

## Troubleshooting
- **Database connection failed:** start MySQL, import `database.sql`, and verify `db.php`.
- **Upload rejected:** use JPG/PNG/WEBP under 2 MB.
- **Uploads fail instantly:** inspect `upload_max_filesize` and `post_max_size` in `php.ini`, then restart Apache.
- **Poster does not appear:** check `uploads/posters` permissions and ensure the saved file exists.
- **404:** verify the folder name and URL.

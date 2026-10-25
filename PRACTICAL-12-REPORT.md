# Practical 12: Event Management CRUD with Poster Upload and File Validation

## Problem Definition
Develop an event-management module for an administrator to add, view, update, delete and upload event posters, with server-side file type and size validation.

## Objectives
1. Implement event Create, Read, Update and Delete operations.
2. Link the UI forms to the MySQL database using MySQLi.
3. Validate uploaded poster files by MIME type, image validity and file size.
4. Display clear success and failure messages.
5. Support event category and open/closed status filters.
6. Use randomly generated filenames, protect the upload directory and record changes in an audit log.

## Tools and Technologies
- PHP 8+, MySQL / MariaDB, MySQLi
- XAMPP, Apache, phpMyAdmin
- HTML, CSS and JavaScript confirmation prompt

## Database Design
Database: `event_management`

### `events` table
| Field | Purpose |
|---|---|
| id | Primary key, auto increment |
| title | Event title |
| description | Event description |
| category | Event category |
| event_date | Scheduled date |
| venue | Event location |
| status | `open` or `closed` |
| poster_path | Relative path to uploaded poster; nullable |
| created_at | Creation timestamp |
| updated_at | Last update timestamp |

### `event_audit_log` table
Stores the event ID, actor, action (`CREATE`, `UPDATE`, `DELETE`), brief details and timestamp for event changes.

## Theory / Logic
1. `db.php` connects to the `event_management` database using MySQLi and defines helper functions for output escaping, CSRF validation, flash messages, poster validation/storage and audit logging.
2. `index.php` retrieves event records using SELECT statements and displays them as responsive event cards. Search can match title, description or venue.
3. Category and open/closed status dropdowns add optional filter conditions. Pagination displays six records per page.
4. `add.php` validates required event details and accepts an optional poster. It checks upload errors, a maximum size of 2 MB, actual MIME type through Fileinfo, and image validity using `getimagesize`. It then creates a random filename and inserts the event using a prepared statement.
5. `edit.php` retrieves the selected event and updates its fields using a prepared UPDATE statement. The administrator can upload a replacement poster or remove the existing poster.
6. `delete.php` accepts POST requests, validates the CSRF token, records the deletion in the audit log, removes the database record, and deletes its poster file. A confirmation prompt appears before the request is sent.
7. Create, update and delete actions are recorded in `event_audit_log`.
8. Success and error messages use session flash messages. HTML output is escaped to reduce cross-site scripting risk.

## Key Questions / Analysis / Interpretation

### 1. Are add, update, and delete functionalities implemented correctly?
Yes. The Add Event form inserts a new event, Edit Event updates an existing event, and Delete removes the event record after confirmation. Prepared statements are used for the database values, and event changes are written to the audit log.

### 2. Is the UI linked with the database correctly?
Yes. The UI reads event records from MySQL, submits add/edit/delete forms to PHP handlers, and reflects database changes after redirecting to the event listing page. Search, category filters, status filters and pagination are database-driven.

### 3. Are poster uploads validated for type and size?
Yes. The server accepts JPEG, PNG and WEBP images up to 2 MB. It checks the upload status, file size, actual MIME type using PHP Fileinfo, and image validity using `getimagesize`. A random filename is generated rather than trusting the original name. Invalid uploads display an error message.

### 4. Are success and failure messages displayed?
Yes. Session-based flash messages display success messages after successful operations and error messages when validation or database operations fail. Validation errors are also displayed beside the forms.

## Test Data
`database.sql` creates the `event_management` database, creates `events` and `event_audit_log`, and seeds eight sample events. Posters are optional and can be uploaded during testing.

## Screenshots to Attach
1. Event listing with sample events.
2. Add Event form.
3. Successful event creation.
4. Edit Event form and updated record.
5. Delete confirmation prompt.
6. Successful event deletion.
7. Valid poster upload with image shown on the event card.
8. Invalid file type rejection (for example, a `.txt` file).
9. Oversized image rejection (more than 2 MB).
10. Category and open/closed status filtering.
11. Audit log records in phpMyAdmin.

## Conclusion
The practical implements an event-management module using PHP and MySQLi with complete CRUD operations, validated poster uploads, category/status filtering, search, pagination, user feedback and audit logging. It demonstrates safe database handling and server-side validation for uploaded files.

## Viva Questions
- **What is CRUD?** Create, Read, Update and Delete.
- **Why use prepared statements?** They separate SQL structure from values and help prevent SQL injection.
- **Why validate file uploads on the server?** Browser validation can be bypassed; server validation enforces the actual rules.
- **Why check MIME type instead of extension only?** File extensions can be renamed; MIME inspection helps identify the actual file content.
- **Why use random filenames?** To avoid collisions and avoid trusting user-supplied filenames.
- **What is an audit log?** A record of actions and timestamps used to track changes.
- **What is CSRF?** Cross-Site Request Forgery, where an attacker tries to cause a user's browser to submit an unwanted request.
- **Why protect the upload folder?** To prevent directory listing and reduce the risk of uploaded scripts being executed.

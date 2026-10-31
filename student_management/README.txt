EduTrack SMS - Student Management System (PHP 8 + PDO + MySQL + Bootstrap 5)

INSTALL ON WAMP
1. Copy this folder to  C:\wamp64\www\student_management
2. WAMP tray icon > Apache > Apache modules > tick "rewrite_module" (and headers, expires).
   Make sure the www directory allows overrides: AllowOverride All
   (WAMP: Apache > httpd.conf, <Directory "${INSTALL_DIR}/www/"> ... AllowOverride All), then restart.
3. Open http://localhost/phpmyadmin > Import > choose sql/database.sql > Go
   (creates the database "student_management" with sample data).
4. If your MySQL user/password/port differ, edit config/config.php (DB_USER, DB_PASS, DB_PORT).
5. Browse to  http://localhost/student_management/

LOGINS  (change after first sign-in)
  admin      / admin123      -> /dashboard/admin
  lecturer1  / lecturer123   -> /dashboard/lecturer   (CS101, CS102, IT201)
  lecturer2  / lecturer123   -> /dashboard/lecturer   (IT202, MA101, EN101)
  S2024001   / student123    -> /dashboard/student    (any student reg no)
New students created by the admin get a login: username = Reg No, password = student123.

CLEAN URLS  /login /students /attendance /marks /dashboard/{role} /report/{student_id}
If you rename the folder, update RewriteBase in .htaccess and APP_BASE in config/config.php.

NOTES
- Bootstrap, DataTables, flatpickr, Chart.js load from CDNs, so the browser needs internet access.
- GPA = average marks / 25 (4.0 scale). Marks are validated 0-100 in JS and PHP, plus a DB CHECK (MySQL 8 / MariaDB 10.2+).
- PDF uses FPDF (bundled in lib/fpdf).
- Remove the "Demo accounts" box from login.php before going live; set APP_DEBUG=false.

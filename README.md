# EduTrack---Student-Management-System
A complete Role-Based Student Management System built with PHP &amp; MySQL. Manage students, live attendance, automatic GPA calculation, and one-click PDF result sheets in one place.


# 🎓 EduTrack SMS — Student Management System

A complete, role-based **Student Management System** built with **core PHP 8 (OOP + PDO)**, **MySQL**, **Bootstrap 5** and vanilla JavaScript. No framework — runs out of the box on **WAMP / XAMPP**.

![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-5.7%2B%20%7C%208-4479A1?logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?logo=bootstrap&logoColor=white)
![License](https://img.shields.io/badge/license-MIT-green)

![Login](docs/screenshots/login.png)

## ✨ Features

| Module | What it does |
|---|---|
| 🔐 **Authentication** | PHP sessions, `password_hash()` / `password_verify()`, session-ID regeneration, CSRF tokens, login lock-out after 5 failures, role-based redirect (admin / lecturer / student) |
| 👥 **Student CRUD** | DataTables table, Add/Edit modal, **AJAX unique `reg_no` check**, course enrolment, auto-created student login |
| 📅 **Attendance** | Pick course + date (flatpickr), load roster via AJAX, CSS toggle switches, "all present/absent", **bulk save in a PDO transaction** |
| 📝 **Marks Entry** | 0–100 validation in **JS and PHP** (plus DB `CHECK`), live grade + **GPA = average ÷ 25** before submit |
| 📊 **3 Dashboards** | Admin, Lecturer and Student dashboards: Bootstrap cards, animated CSS **circle progress** for attendance %, Chart.js result charts |
| 📄 **PDF Result Sheet** | Generated with **FPDF** — students can only download their own |
| 🎨 **Premium UI** | Responsive layout, gradient cards, CSS animations, toasts, reduced-motion support |

## 🧰 Tech Stack

- **Frontend:** HTML5, CSS3, Bootstrap 5, vanilla JavaScript, DataTables, flatpickr, Chart.js
- **Backend:** Core PHP 8 (OOP, PDO prepared statements), MySQL / MariaDB
- **Server:** Apache with `.htaccess` clean URLs
- **PDF:** [FPDF](http://www.fpdf.org/) (bundled in `lib/fpdf`)

## 🚀 Installation (WAMP)

1. **Clone** into the WAMP web root:
   ```bash
   cd C:/wamp64/www
   git clone https://github.com/<your-username>/student_management.git
   ```
2. **Enable URL rewriting:** WAMP tray → *Apache → Apache modules* → tick `rewrite_module` (and `headers`, `expires`).  
   Make sure the web root allows overrides (`AllowOverride All` in `httpd.conf`), then **restart Apache**.
3. **Import the database:** open `http://localhost/phpmyadmin` → *Import* → choose `sql/database.sql` → *Go*.  
   (It creates the `student_management` database and loads sample data.)
4. **Configure** `config/config.php` only if your MySQL user / password / port differ from the WAMP defaults (`root`, empty password, port `3306`).
5. Open **http://localhost/student_management/**

> The front-end libraries load from CDNs, so the browser needs internet access.

### Default accounts

| Role | Username | Password |
|---|---|---|
| Admin | `admin` | `admin123` |
| Lecturer | `lecturer1` / `lecturer2` | `lecturer123` |
| Student | `S2024001` (any reg no) | `student123` |

⚠️ Change these passwords and remove the *Demo accounts* box in `login.php` before deploying. Set `APP_DEBUG` to `false` in `config/config.php`.

## 📁 Project Structure

```
student_management/
├── .htaccess                 # clean URLs + blocks config/includes/sql
├── index.php  login.php  logout.php  dashboard.php
├── config/
│   ├── config.php            # DB + app settings
│   └── db_connect.php        # PDO singleton (Database::getConnection())
├── includes/
│   ├── auth.php              # Auth class: sessions, roles, lock-out
│   ├── functions.php         # helpers, CSRF, grading, autoloader
│   ├── header.php  footer.php
│   └── models/               # StudentModel, AttendanceModel, MarkModel, CourseModel, DashboardModel
├── modules/
│   ├── students/   attendance/   marks/     # index.php + api.php (JSON)
│   ├── dashboard/  (admin.php, lecturer.php, student.php)
│   └── reports/    result_pdf.php
├── assets/
│   ├── css/style.css
│   └── js/  main.js students.js attendance.js marks.js dashboard.js
├── lib/fpdf/                 # FPDF library
└── sql/database.sql          # schema + sample data
```

## 🔗 Clean URLs

`/login` · `/students` · `/attendance` · `/marks` · `/dashboard/{admin|lecturer|student}` · `/report/{student_id}`

If you rename the folder, update `RewriteBase` in `.htaccess` and `APP_BASE` in `config/config.php`.

## 🗄️ Database

`users` · `students` · `courses` · `enrollments` · `attendance` · `marks` — all `InnoDB` with foreign keys and `ON DELETE CASCADE`. Attendance has a unique key per student/course/day; marks has a unique key per student/course and a `CHECK (marks BETWEEN 0 AND 100)`.

**Grading:** GP = marks ÷ 25 (4.0 scale). A+ ≥ 90 · A ≥ 80 · B+ ≥ 75 · B ≥ 70 · C+ ≥ 65 · C ≥ 60 · D ≥ 50 · F < 50.

## 🔒 Security

- PDO prepared statements everywhere (real, not emulated)
- `password_hash()` with transparent rehash, session fixation protection, HttpOnly + SameSite cookies
- CSRF token on every state-changing request
- Role checks on every page **and** API endpoint; server-side validation duplicates all client-side rules
- Output escaped with `htmlspecialchars`; `.htaccess` blocks direct access to `config/`, `includes/`, `sql/`

## 🖼️ Screenshots

| Admin dashboard | Student dashboard |
|---|---|
| ![Admin](docs/screenshots/admin.png) | ![Student](docs/screenshots/student.png) |
| **Student CRUD** | **Add / Edit modal** |
| ![Students](docs/screenshots/students.png) | ![Modal](docs/screenshots/modal.png) |
| **Attendance** | **Marks entry** |
| ![Attendance](docs/screenshots/attendance.png) | ![Marks](docs/screenshots/marks.png) |

**PDF result sheet**

<img src="docs/screenshots/result-pdf.png" width="360" alt="Result PDF">

## 🛠️ Troubleshooting

- **404 on `/login`, `/students`…** → `rewrite_module` is off or `AllowOverride` isn't `All`.
- **"Database connection failed"** → import `sql/database.sql`; check port/password in `config/config.php`.
- **Blank styles/charts** → no internet access to the CDNs.

## 📜 License

MIT — free to use for learning and projects.

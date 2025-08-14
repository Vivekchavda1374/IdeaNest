# IdeaNest – Collaborative Academic Project Platform

IdeaNest is a collaborative platform to manage, share, and review academic projects. It supports students, sub-admins, and administrators with a streamlined workflow from submission to approval.

---

## 📚 Table of Contents

* [Features](#-features)

  * [Student](#student-features)
  * [Admin](#admin-features)
  * [Sub-Admin](#sub-admin-features)
* [Project Structure](#-project-structure)
* [Database Structure](#-database-structure)
* [Tech Stack & Dependencies](#-tech-stack--dependencies)
* [Installation](#-installation)
* [Configuration](#-configuration)
* [Usage Guide](#-usage-guide)
* [Security](#-security)
* [User Roles](#-user-roles)
* [Contributing](#-contributing)
* [Testing Checklist](#-testing-checklist)
* [Troubleshooting](#-troubleshooting)
* [Roadmap](#-roadmap)
* [License](#-license)
* [Contact](#-contact)

---

## 🚀 Features

### Student Features

* **Project Submission:** Upload images, videos, code archives, and PDFs.
* **Browse & Search:** Keyword/category search & filters.
* **Bookmarking:** Save projects for quick access.
* **Profile Management:** Update personal info, avatar, and bio.
* **Submission Tracking:** Monitor review/approval status.
* **Blog/List Views:** Switch between blog-style and compact list layouts.

### Admin Features

* **Approve/Reject Projects:** Inline review workflow with feedback.
* **User Management:** Create, update, restrict user roles.
* **Email Notifications:** PHPMailer-based templated emails.
* **System Settings:** Configure SMTP, branding, and global flags.
* **Sub-Admin Management:** Assign domains and permissions.
* **Notification Handling:** Centralized project notification logs.

### Sub-Admin Features

* **Domain-Specific Review:** Evaluate projects in assigned categories.
* **Profile Management:** Update personal details.
* **Assignment Handling:** Manage review queues and actions.

---

## 🗂️ Project Structure

```text
IdeaNest/
├── Admin/                       # Administrative Interface
│   ├── admin_view_project.php    # Project review interface
│   ├── admin.php                 # Admin dashboard
│   ├── project_approvel.php      # Project approval system
│   ├── settings.php              # System settings UI
│   ├── user_manage_by_admin.php  # User management
│   └── subadmin/                 # Sub-admin section
│       ├── add_subadmin.php
│       ├── dashboard.php
│       └── profile.php
├── Login/                        # Authentication System
│   ├── dashboard.php
│   ├── db.php                    # Database connection
│   ├── login.php                 # Login system
│   └── register.php              # User registration
├── user/                         # User Interface
│   ├── all_projects.php          # Project listing
│   ├── bookmark.php              # Bookmarking system
│   ├── project_details.php       # Detailed project view
│   ├── search.php                # Search functionality
│   ├── Blog/                     # Project blog system
│   │   ├── form.php
│   │   └── list-project.php
│   └── forms/                    # Project submission
│       └── new_project_add.php
├── db/                           # Database Scripts
│   └── ideanest.sql              # Main database structure
└── uploads/                      # (Create at runtime) file storage
    ├── images/
    ├── videos/
    ├── docs/
    └── code/
```

> **Note:** Ensure `uploads/` and its subfolders are writable by the web server.

---

## 💾 Database Structure

**Key tables** (see `db/ideanest.sql` for full schema):

* `admin_approved_projects` – Stores approved project references and metadata.
* `projects` – Raw project submissions & statuses (pending/approved/rejected).
* `register` – Users with role-based access.
* `bookmark` – User ↔ project bookmarks.
* `subadmins` – Sub-admin profiles and domain assignments.
* `notification_logs` – Email/system notifications audit.
* `admin_settings` – System configuration & SMTP details.

---

## 🧰 Tech Stack & Dependencies

* **Backend:** PHP ≥ 8.2.4
* **Database:** MariaDB/MySQL ≥ 10.4.28
* **Server:** Apache/Nginx (rewrite enabled recommended)
* **Composer:** Dependency manager for PHP
* **Email:** [PHPMailer](https://github.com/PHPMailer/PHPMailer)

---

## 🚀 Installation

```bash
# 1) Clone
git clone https://github.com/Vivekchavda1374/IdeaNest.git
cd IdeaNest

# 2) Install PHP dependencies (Composer required)
composer install

# 3) Create environment config (optional but recommended)
cp .env.example .env   # if provided; otherwise see Configuration section

# 4) Create database & import schema
# Create a database (e.g., ideanest) in your MySQL/MariaDB server
# Then import the SQL file
mysql -u <user> -p < db/ideanest.sql

# 5) Configure database connection
# Edit Login/db.php to match your DB credentials

# 6) Set file/folder permissions for uploads
mkdir -p uploads/images uploads/videos uploads/docs uploads/code
chmod -R 775 uploads

# 7) Configure virtual host (Apache/Nginx) to serve project root
# Ensure PHP 8.2+ is enabled
```

---

## ⚙️ Configuration

### Database (required)

Edit `Login/db.php` and set:

```php
$host = 'localhost';
$db   = 'ideanest';
$user = 'your_db_user';
$pass = 'your_db_password';
$port = 3306; // adjust if needed
```

### SMTP / Email (recommended)

Configure via **Admin → Settings** UI. Typical settings:

* SMTP Host, Port, Encryption (`tls`/`ssl`)
* SMTP Username, Password
* From Email, From Name

> PHPMailer is used to send transactional emails (registration, approvals, rejections, password resets if implemented).

### File Uploads

* Allowed types: **Images** (`.jpg`, `.png`, `.gif`), **Videos** (`.mp4`), **Docs** (`.pdf`), **Code** (`.zip`).
* Max sizes are enforced at PHP level (`upload_max_filesize`, `post_max_size`) and in form validation.

---

## 🧭 Usage Guide

1. **Register & Login** via `Login/register.php` and `Login/login.php`.
2. **Students** submit projects at `user/forms/new_project_add.php`.
3. **Admins/Sub-Admins** review in `Admin/admin_view_project.php` and approve/reject.
4. **Users** explore projects in `user/all_projects.php`, search in `user/search.php`, and bookmark via `user/bookmark.php`.
5. **Admins** manage users, settings, and sub-admins from `Admin/admin.php` and `Admin/settings.php`.

---

## 🔐 Security

* **Password Hashing:** `password_hash()` and `password_verify()`.
* **SQL Injection Prevention:** Prepared statements/parameterized queries.
* **Input Sanitization:** Server-side validation & escaping.
* **Session Management:** Regenerate session IDs after login; secure cookies recommended.
* **Upload Validation:** MIME/type checks, size limits, and storage outside web root when possible.

> **Tip:** Set `session.cookie_httponly=1`, `session.cookie_secure=1` (on HTTPS), and use CSRF tokens on forms.

---

## 👥 User Roles

### Admin

* Full access, user management, project approvals, and system configuration.

### Sub-Admin

* Limited admin features; assigned domain/category reviews.

### User

* Submit, view, search, and bookmark projects; manage profile.

---

## 🤝 Contributing

We welcome contributions! To get started:

1. **Fork** the repo and create a feature branch: `git checkout -b feat/your-feature`
2. **Code style:** Keep PHP 8+ standards, meaningful names, and comments.
3. **Commits:** Use conventional commits: `feat:`, `fix:`, `docs:`, `refactor:`, etc.
4. **Pull Request:** Describe changes, screenshots (if UI), and testing steps.

**Areas where help is most impactful:**

* UI/UX improvements
* Security hardening
* Performance optimization (DB indexes, caching)
* Documentation
* Bug fixes

---


## 🛠️ Troubleshooting

* **Blank page / 500 error:** Check PHP error logs; ensure PHP 8.2+ is active.
* **Cannot upload files:** Verify `uploads/` permissions and `php.ini` size limits.
* **Emails not sending:** Confirm SMTP credentials, port, and encryption. Test from Admin → Settings.
* **DB connection fails:** Recheck `Login/db.php` credentials and DB host/port.
* **Routes not found:** Ensure correct document root and that your web server points to project root.


---

## 📝 License

This project is licensed under the **MIT License**. See the [`LICENSE`](LICENSE) file for details.

---

## 📧 Contact

* Email: **[ideanest.ict@gmail.com](mailto:ideanest.ict@gmail.com)**

> If you use IdeaNest in your institution, we’d love to hear about it—open a discussion or PR!


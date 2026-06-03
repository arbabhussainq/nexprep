# ⚡ NexPrep — Engineering Exam Prep Platform

Pakistan's premier MCQ-based exam prep platform for MUET, NED, ECAT and other engineering entrance tests.

---

## 📁 Project Structure

```
nexprep/
├── index.php                  ← Login / Register landing page
├── database.sql               ← Full DB schema + seed data
├── .htaccess                  ← Apache security & URL rules
│
├── includes/
│   ├── config.php             ← DB config, session helpers, constants
│   └── layout.php             ← Sidebar, topbar, head/foot partials
│
├── api/
│   ├── auth.php               ← Login, register, logout, change password
│   ├── tests.php              ← Test CRUD, questions, attempts, submit
│   └── users.php              ← User management, profile, stats
│
├── student/
│   ├── dashboard.php          ← Student home with stats & recent activity
│   ├── tests.php              ← Browse & filter available tests
│   ├── take_test.php          ← Live quiz interface with countdown timer
│   ├── result_detail.php      ← Detailed results & answer review
│   ├── results.php            ← Full test history
│   ├── leaderboard.php        ← Rankings with podium
│   ├── profile.php            ← Edit profile, change password
│   └── notifications.php     ← Notification inbox
│
├── admin/
│   ├── dashboard.php          ← Admin overview stats
│   ├── tests.php              ← Create / edit / delete tests
│   ├── questions.php          ← Add / edit / delete MCQs (max 25/test)
│   ├── users.php              ← Manage all users (suspend/activate/delete)
│   ├── employees.php          ← Manage employee accounts
│   ├── leaderboard.php        ← Admin leaderboard view
│   ├── reports.php            ← Analytics & performance charts
│   └── settings.php           ← Admin profile, password, system info
│
└── assets/
    ├── css/nexprep.css        ← Full dark engineering theme
    └── js/nexprep.js          ← Sidebar toggle, alerts, utils
```

---

## 🚀 Local Setup (XAMPP)

### Step 1 — Install XAMPP
Download from https://www.apachefriends.org — install with Apache + MySQL + PHP 8.1+

### Step 2 — Copy project files
```
C:\xampp\htdocs\nexprep\        (Windows)
/opt/lampp/htdocs/nexprep/      (Linux/Mac)
```

### Step 3 — Create the database
1. Start XAMPP → Start Apache + MySQL
2. Open `http://localhost/phpmyadmin`
3. Click **New** → Name: `nexprep` → Create
4. Click **Import** → Choose `nexprep/database.sql` → Go

### Step 4 — Configure database connection
Edit `includes/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');          // Your MySQL password
define('DB_NAME', 'nexprep');
define('APP_URL',  'http://localhost/nexprep');
```

### Step 5 — Open in browser
```
http://localhost/nexprep
```

---

## 🔑 Default Login Credentials

| Role     | Email                  | Password |
|----------|------------------------|----------|
| Admin    | admin@nexprep.pk       | password |
| Employee | ali@nexprep.pk         | password |
| Student  | ahmed@student.com      | password |

> ⚠️ **Change all passwords immediately after first login!**

---

## 👥 User Roles

| Role     | Permissions |
|----------|-------------|
| **Admin**    | Full access — manage users, employees, tests, MCQs, view all reports |
| **Employee** | Create/edit tests, add/edit MCQs, view leaderboard |
| **Student**  | Take tests, view own results, leaderboard, profile |

---

## 📚 Subjects Supported
- ⚡ **Physics**
- 🧪 **Chemistry**
- 🔢 **Mathematics**
- 📖 **English**

## 🎯 Exam Types
MUET · NED · ECAT · GIKI · NUST · Custom (anything you type)

---

## 🏆 Scoring System

| Event              | Points       |
|--------------------|--------------|
| Correct answer     | +4 pts       |
| Wrong answer       | -1 pt        |
| Skipped question   | 0 pts        |
| Time bonus (max)   | +10 pts      |

**Leaderboard** ranks by total rank points, then by fastest time as a tiebreaker.
Only the **best attempt** per student per test appears on the leaderboard.

---

## ☁️ Deployment Options

### Option A — Shared Hosting (Recommended for beginners) ~$1–3/month
**Best picks:** Hostinger, Namecheap, or InfinityFree (free tier)

1. Purchase hosting with PHP 8+ and MySQL
2. In cPanel → MySQL Databases → create database `nexprep`
3. Import `database.sql` via phpMyAdmin
4. Update `includes/config.php` with hosting DB credentials
5. Upload all files via cPanel File Manager or FTP (FileZilla)
6. Set `APP_URL` to your domain: `https://yourdomain.com`

### Option B — Hostinger (Cheapest paid, ~$1.99/month)
1. Buy "Single Web Hosting" plan
2. hPanel → Databases → Create DB
3. hPanel → File Manager → Upload zip → Extract to `public_html/`
4. Update config, import SQL

### Option C — InfinityFree (100% Free)
1. Register at https://www.infinityfree.net
2. Create account → MySQL DB → Import SQL
3. Upload via FTP
4. Note: free hosting disables some PHP functions — test thoroughly

### Option D — Railway / Render (Cloud, free tier)
For more technical users. Deploy PHP app + MySQL on:
- https://railway.app (MySQL + PHP support)
- https://render.com (needs Docker)

### Option E — VPS (Most control) ~$4–6/month
DigitalOcean / Vultr / Linode droplet:
```bash
sudo apt install apache2 php8.2 libapache2-mod-php8.2 mysql-server php8.2-mysql
sudo mysql -u root -p < database.sql
# Copy files to /var/www/html/nexprep/
```

---

## 🔧 Common Issues

**Blank page / errors?**
- Check `display_errors = On` in php.ini during development
- Ensure `mod_rewrite` is enabled: `sudo a2enmod rewrite`

**Database connection failed?**
- Verify credentials in `config.php`
- Ensure MySQL is running in XAMPP panel

**Session issues?**
- Check `session.save_path` is writable
- Clear browser cookies and retry

**Images/CSS not loading?**
- Verify `APP_URL` in config.php matches your actual URL exactly

---

## 📝 Adding MCQs — Quick Guide

1. Login as Admin or Employee
2. Go to **Tests** → Create a new test (subject, exam type, time limit)
3. Click the **MCQs** button on any test
4. Click **Add MCQ** → Fill question, 4 options, mark correct answer
5. Add up to **25 questions** per test
6. Test becomes visible to students once marked **Active**

---

## 🔒 Security Notes

- All passwords are **bcrypt hashed** (never stored plain text)
- Sessions expire after **30 minutes** of inactivity
- SQL uses **PDO prepared statements** (no SQL injection)
- `.htaccess` blocks directory listing and direct access to `includes/`
- For production: enable HTTPS and uncomment HSTS header in `.htaccess`

---

## 📞 Tech Stack

| Layer      | Technology |
|------------|------------|
| Backend    | PHP 8.1+   |
| Database   | MySQL 8    |
| Frontend   | HTML5, Bootstrap 5.3, Bootstrap Icons |
| Fonts      | Exo 2, Inter, Fira Code (Google Fonts) |
| Dev Server | XAMPP      |
| API style  | AJAX / JSON (fetch API) |

---

*Built with ⚡ for Pakistani engineering students.*

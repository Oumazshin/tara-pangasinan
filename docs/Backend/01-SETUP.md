# 01 — Environment Setup

**Goal:** Get the existing frontend running through Apache (instead of Live Server), get a MySQL database ready, and lay down the folder structure for the backend code.

**Time estimate:** 45 minutes for the whole team if one person sets up XAMPP while another creates the database.

---

## 1. Install XAMPP

XAMPP bundles Apache, MySQL, and PHP into a single installer.

1. Download from <https://www.apachefriends.org/> — pick the version with **PHP 8.0 or higher**.
2. Install to the default path. On Windows that's `C:\xampp\`. On macOS it's `/Applications/XAMPP/`.
3. Launch the **XAMPP Control Panel**.
4. Click **Start** on both **Apache** and **MySQL**. Both should turn green.
5. Verify:
   - <http://localhost/> opens the XAMPP dashboard.
   - <http://localhost/phpmyadmin> opens phpMyAdmin (database management UI).

If Apache won't start, the most common cause is **port 80 being occupied** (Skype, IIS, World Wide Web Publishing Service). The dashboard's logs will tell you. Either stop the conflicting service or change Apache's port in `Config → Apache (httpd.conf) → Listen 80` to `Listen 8080`.

---

## 2. Move the Project into `htdocs`

Apache serves files from XAMPP's `htdocs` folder. Move (or copy) the project there.

**Windows:**

```
C:\xampp\htdocs\tara-pangasinan\
```

**macOS / Linux:**

```
/Applications/XAMPP/htdocs/tara-pangasinan/
```

The project root (the folder containing `index.html`) should be **directly** inside `htdocs`. Don't bury it under another folder.

Confirm the move worked by visiting:

```
http://localhost/tara-pangasinan/
```

You should see the existing frontend rendered identically to Live Server.

> **Pro tip for teamwork:** Add a `.gitignore` entry for `config/database.php` once you create it (Phase 3), so each teammate keeps their own local DB credentials.

---

## 3. Create the Folder Structure for Backend Code

Inside `C:\xampp\htdocs\tara-pangasinan\` create these empty folders. (You don't need to create files yet — each module phase will create its own.)

```
tara-pangasinan/
├── api/
│   ├── auth/
│   ├── spots/
│   ├── saved/
│   ├── reviews/
│   ├── bookings/
│   ├── promos/
│   ├── contact/
│   └── profile/
├── config/
├── includes/
└── sql/
```

**One-shot command (Windows PowerShell), run from the project root:**

```powershell
mkdir api, api\auth, api\spots, api\saved, api\reviews, api\bookings, api\promos, api\contact, api\profile, config, includes, sql
```

**One-shot command (macOS/Linux):**

```bash
mkdir -p api/{auth,spots,saved,reviews,bookings,promos,contact,profile} config includes sql
```

---

## 4. Create the MySQL Database

1. Open <http://localhost/phpmyadmin>.
2. On the left sidebar, click **New**.
3. Database name: `tara_pangasinan`
4. Collation: `utf8mb4_unicode_ci` (supports Filipino characters and emoji)
5. Click **Create**.

The schema and table creation happens in **`02-DATABASE.md`** — but the empty database needs to exist first.

---

## 5. Create a Database User (Optional but Recommended)

The default `root` user with no password works, but for academic best practice, create a dedicated user:

In phpMyAdmin → **User accounts** → **Add user account**:

| Field | Value |
|---|---|
| User name | `tara_user` |
| Host name | `localhost` |
| Password | (set something simple for dev — e.g. `tarapass`) |
| Re-type | (same) |
| Database for user account | ☑ Grant all privileges on database `tara_pangasinan` |

Save these credentials — you'll paste them into `config/database.php` in Phase 3.

If you skip this step, you'll use `root` with empty password (default XAMPP). That's fine for the course project.

---

## 6. Verify PHP Works

Create a temporary test file to confirm Apache can execute PHP.

**File:** `tara-pangasinan/test.php`

```php
<?php
phpinfo();
```

Visit <http://localhost/tara-pangasinan/test.php>. You should see a long page listing your PHP configuration. Confirm:

- **PHP Version:** 7.4+ (8.0+ ideal)
- **PDO drivers:** includes `mysql`
- **json:** enabled

**Delete `test.php` afterwards** — it leaks server info and should never be in production.

---

## 7. Configure Apache to Serve `home.html` (Optional Convenience)

Currently <http://localhost/tara-pangasinan/> opens `index.html` (good — that's the landing page). If you want to also allow URLs without `.html` extensions, you can add an `.htaccess` file. **Skip this for now if you're new to Apache** — it's not required.

**File:** `tara-pangasinan/.htaccess` *(optional)*

```apache
# Default document order
DirectoryIndex index.html home.html

# Allow CORS for local development (helps if you ever serve frontend from a different port)
<IfModule mod_headers.c>
    Header set Access-Control-Allow-Origin "*"
</IfModule>

# Disable directory listing
Options -Indexes
```

---

## 8. Recommended Editor Setup

VS Code makes this project significantly easier:

| Extension | Why |
|---|---|
| **PHP Intelephense** | PHP autocomplete and inline errors |
| **PHP Server** | (Alternative to XAMPP) — not needed if XAMPP is running |
| **MySQL** (by Jun Han) | Run SQL queries from inside VS Code |
| **REST Client** | Test API endpoints with `.http` files |
| **Live Server** | *Disable while developing the backend.* Live Server bypasses Apache and PHP files just download instead of executing. |

**Open the project in VS Code via Apache:**

Instead of right-clicking `index.html` → Open with Live Server, just type the URL: <http://localhost/tara-pangasinan/>.

---

## 9. Verify Frontend Still Works

After moving to `htdocs`, click through the existing pages and confirm:

- [ ] <http://localhost/tara-pangasinan/index.html> — landing page renders
- [ ] <http://localhost/tara-pangasinan/explore.html> — cards load (this means `data/spots.json` is being fetched OK)
- [ ] <http://localhost/tara-pangasinan/details.html?id=hundred-islands> — destination details render
- [ ] <http://localhost/tara-pangasinan/map.html> — Leaflet map shows markers
- [ ] <http://localhost/tara-pangasinan/plan.html> — booking wizard works
- [ ] Open DevTools → Network: `spots.json` shows status **200**

If any of these break, **stop and fix the path issues first** before continuing. Common cause: when copying into `htdocs`, the folder ended up as `tara-pangasinan/tara-pangasinan-main/` (one too deep). The URL would then be `/tara-pangasinan/tara-pangasinan-main/index.html`.

---

## Done When

- [ ] XAMPP is installed; Apache + MySQL are running (both green in the control panel).
- [ ] The project lives at `C:\xampp\htdocs\tara-pangasinan\` and renders at <http://localhost/tara-pangasinan/>.
- [ ] All `api/`, `config/`, `includes/`, `sql/` folders exist (still empty).
- [ ] An empty database `tara_pangasinan` exists in phpMyAdmin.
- [ ] You know your DB credentials (host = `localhost`, user, password, db name).

Continue to **[`02-DATABASE.md`](./02-DATABASE.md)** to build the schema and seed it.

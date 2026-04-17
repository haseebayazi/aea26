# AEA26 — System Reference for Claude Sessions

This document is the single source of truth for AI-assisted development on the
**CUI Alumni Excellence Awards 2026 Review Portal**. Read it fully before
making any changes.

---

## Repository Layout

```
aea26/                          ← repo root
├── SYSTEM.md                   ← this file
├── README.md
├── portal/                     ← Laravel 11 application (all PHP work goes here)
│   ├── app/
│   │   ├── Exports/            ← Maatwebsite Excel export classes
│   │   ├── Http/
│   │   │   ├── Controllers/    ← 10 controllers (see list below)
│   │   │   └── Middleware/     ← RequireAuth, RequireRole
│   │   ├── Models/             ← 11 Eloquent models
│   │   └── Providers/
│   ├── bootstrap/app.php       ← middleware aliases registered here
│   ├── config/
│   │   └── rubric.php          ← CAAC rubric definition + Excel column map
│   ├── database/
│   │   ├── migrations/         ← 14 migrations (000000–000011 + cache + jobs)
│   │   └── seeders/            ← 5 seeders
│   ├── resources/views/        ← Blade templates (no build step)
│   ├── routes/web.php
│   ├── deploy.sh               ← cPanel deployment script
│   └── .env.example            ← production template (MySQL)
│
├── Professional Achievement program wise.xlsx
├── Distinguished Young Alumni program wise.xlsx
├── Innovation &amp; Entrepreneursh.xlsx          ← literal &amp; in filename (HTML-encoded)
├── Social Impact &amp; Community Service.xlsx    ← literal &amp; in filename
├── 1-Professional Achievement/               ← student PDF folders
├── 2-Distinguished Young Alumni/
├── 3-Innovation &amp; Entrepreneurship/
└── 4-Social Impact &amp; Community Service/
```

---

## Tech Stack

| Layer | Choice | Notes |
|---|---|---|
| Framework | Laravel 11 / PHP 8.2+ | No Breeze/Jetstream — manual auth |
| Database (dev) | SQLite | `portal/database/database.sqlite` — gitignored |
| Database (prod) | MySQL 8 | cPanel / Cloudways |
| Frontend | Blade + Alpine.js v3 + Tailwind CSS + Chart.js | **All via CDN — zero build step, no Node.js** |
| Excel import | PhpSpreadsheet | Reads the 4 xlsx data files |
| Excel export | Maatwebsite/Laravel-Excel | Full review export |
| PDF export | barryvdh/laravel-dompdf | Winners report + per-student PDF |
| Auth | Session-based (manual) | Rate-limited: 5 attempts / 300 s |

---

## Roles

| Role | Access |
|---|---|
| `admin` | Everything — users, import, export, analytics, all students |
| `reviewer` | Only students in their assigned categories; can score |
| `viewer` | Read-only; students in their assigned categories |

Middleware aliases (registered in `bootstrap/app.php`):
- `auth.portal` → `App\Http\Middleware\RequireAuth`
- `role` → `App\Http\Middleware\RequireRole`

---

## Database Schema (11 custom tables)

```
users               role ENUM(admin,reviewer,viewer), is_active, last_login_at
categories          name, slug, color, sort_order
rubric_items        rubric_type ENUM(caac,uaac), dimension, sub_indicator_key,
                    sub_indicator_label, max_score — UNIQUE(rubric_type, sub_indicator_key)
students            submission_id UNIQUE, name, email, phone, batch, department,
                    campus, category_id FK, citation, cv_path, citation_path
self_scores         student_id FK, rubric_item_id FK, score, remarks
                    UNIQUE(student_id, rubric_item_id)
reviewer_assignments user_id FK, category_id FK, assigned_by FK
                    UNIQUE(user_id, category_id)
reviews             student_id FK, reviewer_id FK, status ENUM(pending,in_progress,completed)
                    overall_remarks, started_at, completed_at
                    UNIQUE(student_id, reviewer_id)
review_scores       review_id FK, rubric_item_id FK, score, remarks
                    UNIQUE(review_id, rubric_item_id)
student_files       student_id FK, file_type ENUM(cv,citation,supporting,other),
                    original_name, file_path, file_size, mime_type, uploaded_by FK
activity_logs       user_id FK nullable, action, subject_type, subject_id,
                    details JSON, ip_address
settings            key UNIQUE, value
```

Plus standard Laravel tables: `sessions`, `cache`, `jobs`, `password_reset_tokens`.

---

## CAAC Rubric (hardcoded in `config/rubric.php`)

4 dimensions, 16 sub-indicators, 100 points total:

| Dimension | Weight | Points | Sub-indicators |
|---|---|---|---|
| Impact & Achievement | 40% | 40 | 4 items × 10 pts each |
| Leadership & Service | 25% | 25 | 4 items × ~6 pts each |
| Innovation & Creativity | 20% | 20 | 4 items × 5 pts each |
| Ethics & Engagement | 15% | 15 | 4 items × ~4 pts each |

Key: `config('rubric.caac')` — used by `RubricItemSeeder`, `AnalyticsController`, `StudentController`, and all review views.

---

## Controllers

| Controller | Routes | Notes |
|---|---|---|
| `AuthController` | `GET/POST /login`, `POST /logout` | RateLimiter 5/300s |
| `DashboardController` | `GET /` | Branches on role: admin vs reviewer view |
| `StudentController` | `GET /students`, `GET /students/{id}` | Enforces category access for reviewers |
| `ReviewController` | `POST/PUT /students/{id}/review`, autosave, complete, myReviews | Autosave is JSON endpoint |
| `AnalyticsController` | `GET /analytics`, `GET /analytics/chart/{chart}` | 6 chart types (see below) |
| `UserController` | `/admin/users` resource + assign/toggle/reset-password | Cannot demote other admins |
| `FileController` | download, upload, destroy, bulkUpload | Path traversal protected via realpath + allowed-base check |
| `ImportController` | 5-step session wizard: index, upload, map, execute, reset | PhpSpreadsheet preview |
| `ExportController` | full Excel, summary CSV, winners PDF, student PDF | Uses fputcsv for CSV |

### Analytics Chart Types (`/analytics/chart/{chart}`)

- `category-distribution` — doughnut: students per category
- `self-vs-reviewer` — scatter: self score vs reviewer avg
- `top-candidates` — horizontal bar: top 15 by reviewer avg
- `dimension-averages` — grouped bar: per-category dimension averages
- `reviewer-agreement` — table: std dev across reviewers, flags >5 pts spread
- `student-radar` — per-student radar: self vs reviewer per dimension

---

## Models & Key Methods

| Model | Key helpers |
|---|---|
| `User` | `isAdmin()`, `isReviewer()`, `canAccessCategory($id)`, scopes: `active()`, `reviewers()` |
| `Student` | `reviewByUser($user)`, `selfScoreTotal()`, `avgReviewerScore()`, scopes: `forCategory()`, `search()` |
| `Review` | `totalScore()`, `isPending/isInProgress/isCompleted()`, scopes: `completed()`, `forReviewer()` |
| `ActivityLog` | `static record($action, $subject, $details)` — logs with user_id + IP |
| `Setting` | `static get($key, $default)`, `static set($key, $value)` — cached |
| `RubricItem` | scopes: `caac()`, `forDimension($dim)`, `ordered()` |

---

## Known Quirks & Gotchas

### 1. Filenames have literal `&amp;` (HTML-encoded ampersand)
The two xlsx files and two folder names contain the byte sequence `&amp;`
(6 chars: `&`, `a`, `m`, `p`, `;`) — NOT a real `&`. This is already reflected
in `config/rubric.php` and `StudentDataSeeder::linkStudentFiles()`.
**Do not "fix" these to `&`** or file loading will break.

```php
// Correct (in config/rubric.php):
'file' => 'Innovation &amp; Entrepreneursh.xlsx'
'file' => 'Social Impact &amp; Community Service.xlsx'
```

### 2. Professional Achievement has 2 extra columns
This file has `Student Detail` and `Professional Detail` columns not present in
the other three files. It has `name_col=2`, `score_start=16` vs `name_col=1`,
`score_start=14` for others. All offsets are in `config/rubric.php`.

### 3. Seeded files have absolute paths
`StudentDataSeeder` stores absolute filesystem paths in `student_files.file_path`
(e.g. `/home/user/aea26/1-Professional Achievement/1-Dr. Kalsoom/cv.pdf`).
`FileController::download()` and `FileController::view()` both validate these via
`realpath()` + allowed-base containment before serving. On production, use
**Bulk Upload ZIP** instead to get relative `storage/`-managed paths.

### 4. `reviewer-agreement` filtering is PHP-side
SQLite doesn't support `HAVING` on virtual `withCount()` columns.
`AnalyticsController::reviewerAgreement()` loads all students with completed
reviews and filters `->count() >= 2` in PHP. Fine for ≤1000 students.

### 5. No Node.js / build step
All frontend assets (Tailwind, Alpine.js v3, Chart.js) are loaded via CDN in
`layouts/app.blade.php`. Running `npm install` or `vite build` is unnecessary
and will have no effect on the served UI.

---

## Seeding (162 students, 2592 self-scores)

```bash
php artisan db:seed --force
```

Seeder order (enforced in `DatabaseSeeder`):
1. `CategorySeeder` — 4 categories
2. `RubricItemSeeder` — 16 CAAC items from `config/rubric.php`
3. `AdminUserSeeder` — `admin@alumni-awards.com` / `Alumni@2026`
4. `StudentDataSeeder` — reads xlsx files from parent directory (`../`)

The seeder searches these paths for the xlsx files:
```
base_path('../')          → portal/../ (i.e. aea26/)
/home/user/aea26/
base_path('../../aea26/')
```

---

## Environment

Dev `.env` (SQLite, not committed):
```env
APP_ENV=local
APP_DEBUG=true
DB_CONNECTION=sqlite
```

Production `.env` template: `portal/.env.example` (MySQL, `APP_DEBUG=false`).

Admin credentials (default, seeded):
- Email: `admin@alumni-awards.com`
- Password: `Alumni@2026`
- **Change immediately after first deploy.**

---

## Development Workflow

```bash
# Run dev server (from portal/)
php artisan serve --port=8765

# Fresh database + re-seed
php artisan migrate:fresh --seed

# After any config/route/view change in production
php artisan config:cache && php artisan route:cache && php artisan view:cache

# Clear caches in development
php artisan optimize:clear
```

Active branch: `claude/fix-ui-footer-updates-H0WEB`
Target repo: `haseebayazi/aea26`

---

## Deployment

### cPanel
```bash
# 1. Upload portal/ to ~/aea26-portal/
# 2. SSH in:
cd ~/aea26-portal && bash deploy.sh
# 3. Symlink webroot:
ln -sfn ~/aea26-portal/public ~/public_html/alumni
```

### Cloudways
1. Create Laravel app → PHP 8.2+
2. Deploy via Git (branch `claude/build-alumni-award-portal-VMmVN`)
3. Set Web Root to `public_html/portal/public`
4. SSH → `composer install --no-dev` → edit `.env` → `php artisan migrate --force --seed` → `php artisan optimize`

Full guides: see repo conversation history or `portal/deploy.sh` comments.

---

## Security Notes

- File downloads validate `realpath()` is within `storage/app` or repo root — never serve arbitrary paths
- Login rate-limited: 5 attempts per 300 s window (`AuthController`)
- Admin role changes to other admin accounts are blocked (`UserController::update`)
- All forms include `@csrf`; file downloads and views are throttled `120 req/min`
- `SESSION_ENCRYPT=true` in production `.env.example`
- `APP_DEBUG=false` in production — never commit `.env` to git

---

## UI & Branding Notes

### Sidebar (mobile)
The sidebar uses `fixed` positioning on mobile (`z-50`) and slides in with CSS
`translate-x` transition. On desktop (`lg:`) it reverts to `relative` (normal
flow). The overlay (`z-40`) sits below the sidebar. All nav links call
`@click="sidebarOpen = false"` to close the drawer after navigation.

### COMSATS Logo
Place the logo file at `portal/public/images/comsats-logo.png`. The sidebar and
login page reference `asset('images/comsats-logo.png')` and fall back to a
"CUI" badge via an `onerror` handler if the file is missing.

### File Viewing (inline)
`FileController::view()` serves files with `Content-Disposition: inline` so the
browser renders them in-tab (PDFs, images). The student profile shows a **View**
button (opens in a new tab) for PDFs and images, alongside the existing
**Download** button. Route: `GET /files/{file}/view` → `files.view`.

### Indicator / Self-Assessment Text
Student profile rubric rows display the applicant's self-assessment remarks with
an Alpine.js expand/collapse toggle ("Read more ▼" / "Show less ▲"). The toggle
only renders when the text exceeds 100 characters.

### Dashboard Charts
Admin dashboard chart canvases are wrapped in `<div style="height:260px">` so
Chart.js (`maintainAspectRatio: false`) fills the container without growing
indefinitely. Analytics page charts already used this pattern.

### Footer / Branding
Footer text updated from "Registrar Secretariat" to
"Office of Career Development & Alumni Affairs" across `layouts/app.blade.php`
and `auth/login.blade.php`.

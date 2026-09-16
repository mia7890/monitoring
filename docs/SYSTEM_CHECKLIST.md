# Monitoring System - How It Works

## Overview
This is a Laravel-based **Task & Appointment Monitoring System** for managing Field Assessment Educators (FAEs). It tracks task assignments, progress updates, schedules appointments, and maintains an administrative event calendar. The system uses role-based access control with two main user types: **Admin** (full control) and **FAE** (task executor and appointment booker).

---

## 1. System Architecture
### Technology Stack
- **Backend**: PHP 8+ with Laravel Framework
- **Frontend**: Blade Templates with Vite asset bundler
- **Database**: SQLite (default, configurable)
- **Session Storage**: Database
- **Cache**: Database
- **Authentication**: Custom session-based (not Laravel Auth)

### Core Components
1. **Models** - Database representations (User, FaeUser, Task, Appointment, etc.)
2. **Controllers** - Handle HTTP requests and business logic
3. **Services** - MonitoringAuth handles authentication/session
4. **Views** - Blade templates rendered to users
5. **Routes** - Web routes with middleware protection
6. **Middleware** - `auth.monitoring`, `admin.monitoring`

---

## 2. User Roles & Authentication
### Two Role Types
1. **Admin** (system administrator)
   - Created via `MONITORING_ADMIN_KEY` environment variable (default: "Admin12345!")
   - Login with admin key on access page
   - Full access to all features
   - Can create, edit, delete tasks, FAE users, appointments, events
   - Can view all FAE data and statistics

2. **FAE** (Field Assessment Educator)
   - Created in system by admin
   - Each FAE has unique `fae_code` for login
   - Can view assigned tasks only
   - Can update task progress and post task updates
   - Can book appointments
   - Can view own profile

### Session-Based Authentication
- Uses Laravel sessions (stored in database)
- Session keys stored:
  - `monitoring_role` - "admin" or "fae"
  - `monitoring_fae_id` - FAE user's ID (if FAE)
  - `monitoring_fae_name` - FAE user's name
  - `monitoring_fae_code` - FAE unique code

### Authentication Flow
1. User visits `/access` (login page)
2. Enters either admin key or FAE code
3. System validates credentials
4. Creates session and redirects to `/dashboard`
5. All subsequent requests check session middleware
6. Unauthenticated users redirect to `/access`

---

## 3. Database Models & Data Structure

### FaeUser Model
**Table**: `fae_users`
- `id` - Primary key
- `name` - FAE full name
- `fae_code` - Unique identifier for login
- `email` - Contact email
- `department` - Department/unit assignment
- `phone` - Contact phone
- `profile_image` - Photo path
- `created_at`, `updated_at` - Timestamps

**Relationships**:
- Has many Tasks
- Has many Appointments

### Task Model
**Table**: `tasks`
- `id` - Primary key
- `fae_id` - FK to FaeUser (who does the task)
- `region` - Geographic/organizational region
- `course` - Course or category
- `task_name` - Title of task
- `description` - Detailed description
- `deadline` - Date when task is due
- `status` - Pending, In Progress, Completed, Overdue
- `progress` - 0-100% completion percentage
- `priority` - Task importance level
- `created_at`, `updated_at` - Timestamps

**Relationships**:
- Belongs to FaeUser
- Has many TaskUpdates

### TaskUpdate Model
**Table**: `task_updates`
- `id` - Primary key
- `task_id` - FK to Task
- `fae_id` - FK to FaeUser (who posted update)
- `author_role` - "admin" or "fae"
- `author_name` - Name of who posted
- `message` - Update message/comments
- `attachment` - File path if attached
- `progress_at_update` - Progress % when update posted
- `status_at_update` - Task status when update posted
- `created_at`, `updated_at` - Timestamps

**Relationships**:
- Belongs to Task

### Appointment Model
**Table**: `appointments`
- `id` - Primary key
- `fae_id` - FK to FaeUser (who has appointment)
- `user_name` - Person's name requesting appointment
- `appointment_date` - Scheduled date
- `reason` - Reason for appointment
- `status` - pending, approved, rejected
- `admin_comment` - Admin notes/response
- `created_at`, `updated_at` - Timestamps

**Relationships**:
- Belongs to FaeUser

### AdminEvent Model
**Table**: `admin_events`
- `id` - Primary key
- `title` - Event name
- `event_date` - When event occurs
- `description` - Event details
- `category` - Event classification
- `created_at`, `updated_at` - Timestamps

---

## 4. Core Features: Tasks
- [ ] **Authentication System**
  - [ ] Review `app/Http/Controllers/AuthController.php`
  - [ ] Verify login endpoint works (`/login`)
  - [ ] Verify logout endpoint works (`/logout`)
  - [ ] Check access page displays correctly (`/access`)
  - [ ] Test FAE linking with code (`/fae-link/{code}`)

- [ ] **Custom Auth Service**
  - [ ] Review `app/Services/MonitoringAuth.php`
  - [ ] Verify `role()` method returns correct user role
  - [ ] Verify authentication middleware is applied to protected routes

- [ ] **Middleware**
  - [ ] Verify `auth.monitoring` middleware is configured
  - [ ] Verify `admin.monitoring` middleware restricts admin-only routes
  - [ ] Test unauthenticated access redirects to login

- [ ] **Authorization**
  - [ ] Admin users can create/edit/delete tasks
  - [ ] Regular users can only view assigned tasks
  - [ ] Only admins can manage FAE users
  - [ ] Only admins can view all appointments

---

## 4. Core Features - Tasks
- [ ] **Task Management**
  - [ ] Tasks can be created by admins (`POST /tasks`)
  - [ ] Tasks can be updated by admins (`PUT /tasks/{task}`)
  - [ ] Tasks can be deleted by admins (`DELETE /tasks/{task}`)
  - [ ] All users can view tasks (`GET /tasks`)
  - [ ] Task progress can be updated (`POST /tasks/update-progress/{task}`)

- [ ] **Task Fields**
  - [ ] FAE assignment (`fae_id`)
  - [ ] Region specification
  - [ ] Course/Category
  - [ ] Task name
  - [ ] Description
  - [ ] Deadline date
  - [ ] Status tracking
  - [ ] Progress percentage (0-100)
  - [ ] Priority level

- [ ] **Task Timeline**
  - [ ] Timeline view works (`GET /tasks/timeline`)
  - [ ] Timeline displays tasks chronologically
  - [ ] Progress is visible on timeline

- [ ] **Task Updates**
  - [ ] Task updates can be stored (`POST /tasks/store-update`)
  - [ ] Updates are linked to tasks
  - [ ] Updates show creation timestamp
  - [ ] Updates are ordered by newest first

---

## 5. Core Features - Calendar & Appointments
- [ ] **Calendar Functionality**
  - [ ] Calendar view displays (`GET /calendar`)
  - [ ] Calendar shows existing appointments
  - [ ] Calendar is styled correctly with `calendar.css`
  - [ ] Calendar navigation works (previous/next months)

- [ ] **Appointment Booking**
  - [ ] Appointment booking works (`POST /calendar/book`)
  - [ ] Appointments are assigned to FAE users
  - [ ] Appointment fields are captured:
    - [ ] FAE user selection
    - [ ] Appointment date
    - [ ] Reason for appointment
    - [ ] User name
  - [ ] Appointment status tracking (pending/approved/rejected)
  - [ ] Admin comments on appointments

---

## 6. Core Features - FAE Management
- [ ] **FAE User Management**
  - [ ] FAE users list displays
  - [ ] FAE users can be created by admins
  - [ ] FAE users can be updated by admins
  - [ ] FAE users can be deleted by admins
  - [ ] FAE users can authenticate with their code

- [ ] **FAE User Fields**
  - [ ] Name
  - [ ] Unique FAE code
  - [ ] Email address
  - [ ] Department
  - [ ] Phone number
  - [ ] Profile image/photo

- [ ] **FAE Profile**
  - [ ] Profile photos can be updated (`POST /profile/update-photo`)
  - [ ] Profile displays on dashboard

---

## 7. Dashboard
- [ ] **Dashboard Display**
  - [ ] Dashboard loads for authenticated users (`GET /dashboard`)
  - [ ] Redirects unauthenticated users to access page
  - [ ] Shows summary of assigned tasks
  - [ ] Shows upcoming appointments
  - [ ] Shows pending task updates

---

## 8. Admin Events
- [ ] **Admin Event Management**
  - [ ] Admin events can be created
  - [ ] Admin events can be viewed
  - [ ] Admin events can be updated
  - [ ] Admin events can be deleted
  - [ ] Events display on calendar

---

## 9. User Interface & Views
- [ ] **Blade Templates**
  - [ ] `resources/views/access.blade.php` - Login/access page
  - [ ] `resources/views/dashboard.blade.php` - Main dashboard
  - [ ] `resources/views/welcome.blade.php` - Welcome/intro page
  - [ ] `resources/views/layouts/` - Layout templates
  - [ ] `resources/views/tasks/` - Task-related views
  - [ ] `resources/views/calendar/` - Calendar views
  - [ ] `resources/views/fae/` - FAE management views

- [ ] **Styling**
  - [ ] CSS files compiled to `public/css/app.css`
  - [ ] Legacy styles available in `public/style.css`
  - [ ] Calendar styles in `public/calendar.css`
  - [ ] Responsive design works on mobile/tablet

- [ ] **JavaScript**
  - [ ] JS bundled in `public/js/app.js`
  - [ ] Bootstrap included in `resources/js/bootstrap.js`
  - [ ] Vite asset loading works correctly
  - [ ] Legacy JS available in `public/script.js`

---

## 10. File Upload & Storage
- [ ] **Upload Directory**
  - [ ] `public/uploads/` directory exists and is writable
  - [ ] Profile images saved to uploads folder
  - [ ] File permissions are correct

- [ ] **Storage Configuration**
  - [ ] Local filesystem configured in `config/filesystems.php`
  - [ ] `storage/app/public/` linked to `public/storage`
  - [ ] All uploaded files are accessible

---

## 11. Services & Providers
- [ ] **Service Provider**
  - [ ] `app/Providers/AppServiceProvider.php` configured
  - [ ] All service providers registered in `config/app.php`
  - [ ] Custom services are loaded

- [ ] **Custom Services**
  - [ ] `MonitoringAuth` service works for authentication checks
  - [ ] Service methods are callable from controllers

---

## 12. Configuration Files
- [ ] **Core Config**
  - [ ] `config/app.php` - App settings correct
  - [ ] `config/auth.php` - Authentication configured
  - [ ] `config/database.php` - Database drivers configured
  - [ ] `config/session.php` - Session settings correct

- [ ] **Features Config**
  - [ ] `config/cache.php` - Cache driver configured
  - [ ] `config/queue.php` - Queue driver configured
  - [ ] `config/mail.php` - Mail driver configured
  - [ ] `config/logging.php` - Logging configured

---

## 13. API Endpoints & Routes
- [ ] **Public Routes**
  - [ ] `GET /` - Root redirect based on auth
  - [ ] `GET /access` - Access/login page
  - [ ] `POST /login` - Login endpoint
  - [ ] `GET /logout` - Logout endpoint
  - [ ] `GET /fae-link/{code}` - FAE direct linking
  - [ ] `GET /fae.php` - Legacy FAE routing support

- [ ] **Protected Routes (Authenticated)**
  - [ ] `GET /dashboard` - Dashboard view
  - [ ] `GET /tasks` - Tasks list
  - [ ] `GET /tasks/timeline` - Timeline view
  - [ ] `POST /tasks/update-progress/{task}` - Update progress
  - [ ] `POST /tasks/store-update` - Store task update
  - [ ] `POST /profile/update-photo` - Update profile photo
  - [ ] `GET /calendar` - Calendar view
  - [ ] `POST /calendar/book` - Book appointment

- [ ] **Admin-Only Routes**
  - [ ] `POST /tasks` - Create task
  - [ ] `PUT /tasks/{task}` - Update task
  - [ ] `DELETE /tasks/{task}` - Delete task
  - [ ] FAE management endpoints
  - [ ] Appointment management endpoints

---

## 14. Testing
- [ ] **Test Setup**
  - [ ] `phpunit.xml` configured
  - [ ] `tests/TestCase.php` base class configured
  - [ ] Test database configured

- [ ] **Test Files**
  - [ ] Feature tests in `tests/Feature/`
  - [ ] Unit tests in `tests/Unit/`
  - [ ] Run `php artisan test` successfully

- [ ] **Test Coverage**
  - [ ] Authentication tests
  - [ ] Task management tests
  - [ ] Appointment booking tests
  - [ ] Authorization tests

---

## 15. Logging & Debugging
- [ ] **Logging**
  - [ ] Logs stored in `storage/logs/`
  - [ ] Log channel configured in `.env` (`LOG_CHANNEL=stack`)
  - [ ] Log level appropriate (`LOG_LEVEL=debug` for dev)
  - [ ] Laravel Debugbar installed (if needed)

- [ ] **Error Handling**
  - [ ] 404 errors handled gracefully
  - [ ] 403 Forbidden errors for unauthorized access
  - [ ] Server errors logged properly
  - [ ] Debug mode shows errors in development

---

## 16. Performance & Optimization
- [ ] **Caching**
  - [ ] Cache is configured (`CACHE_STORE=database`)
  - [ ] Cache prefix set if needed
  - [ ] Cache clear command works (`php artisan cache:clear`)

- [ ] **Database**
  - [ ] Queries are optimized (no N+1 queries)
  - [ ] Database indexes on frequently queried columns
  - [ ] Migration rollback tested

- [ ] **Assets**
  - [ ] CSS/JS minified in production
  - [ ] Vite caching configured
  - [ ] Images optimized

---

## 17. Security
- [ ] **CSRF Protection**
  - [ ] CSRF tokens in forms
  - [ ] CSRF middleware applied

- [ ] **SQL Injection Prevention**
  - [ ] All database queries use parameterized queries/ORM
  - [ ] No raw SQL with user input

- [ ] **XSS Prevention**
  - [ ] All user input escaped in views
  - [ ] `{{ }}` used instead of `{!! !!}` for untrusted content

- [ ] **Authentication Security**
  - [ ] Passwords hashed with bcrypt
  - [ ] Session security configured
  - [ ] HTTPS enforced in production
  - [ ] Secure cookie flags set

- [ ] **Environment Variables**
  - [ ] `.env` file is in `.gitignore`
  - [ ] `.env.example` has no sensitive values
  - [ ] Production credentials secured

---

## 18. Legacy Code Management
- [ ] **Legacy Files**
  - [ ] `legacy/` folder contains old PHP code
  - [ ] Old routes redirected to new Laravel routes
  - [ ] Legacy assets available but not required
  - [ ] Plan for deprecating legacy code

- [ ] **Migration Path**
  - [ ] Document which legacy features are replaced
  - [ ] Update documentation for new routes
  - [ ] Test compatibility layer (if used)

---

## 19. Deployment Preparation
- [ ] **Pre-Production Checklist**
  - [ ] `.env.example` updated with all required variables
  - [ ] Production `.env` file created securely
  - [ ] `APP_DEBUG=false` in production
  - [ ] `APP_ENV=production` set

- [ ] **Database Prep**
  - [ ] Backup existing database before migration
  - [ ] Run migrations on production server
  - [ ] Verify data integrity
  - [ ] Test rollback procedure

- [ ] **Build & Deployment**
  - [ ] `composer install --optimize-autoloader --no-dev`
  - [ ] `npm run build`
  - [ ] `php artisan cache:clear`
  - [ ] `php artisan config:cache`
  - [ ] `php artisan route:cache`
  - [ ] `php artisan view:cache`
  - [ ] File permissions set correctly (storage, bootstrap/cache)

- [ ] **Server Configuration**
  - [ ] PHP version meets requirements
  - [ ] All required PHP extensions installed
  - [ ] Web server (Apache/Nginx) configured
  - [ ] SSL certificate installed
  - [ ] Domain/DNS configured

---

## 20. Documentation & Maintenance
- [ ] **Code Documentation**
  - [ ] README.md updated with setup instructions
  - [ ] API documentation completed
  - [ ] Code comments for complex logic
  - [ ] PHPDoc comments on classes/methods

- [ ] **User Documentation**
  - [ ] User guide for task management
  - [ ] Guide for booking appointments
  - [ ] FAQ section
  - [ ] Troubleshooting guide

- [ ] **Maintenance Schedule**
  - [ ] Regular backups scheduled
  - [ ] Database maintenance (optimization)
  - [ ] Log rotation configured
  - [ ] Dependency updates checked monthly

- [ ] **Version Control**
  - [ ] `.gitignore` configured properly
  - [ ] All commits have meaningful messages
  - [ ] Release tags created
  - [ ] Development and main branches protected

---

## 21. Google Drive Backup Integration
- [ ] **Setup (if using)**
  - [ ] Create OAuth 2.0 web client in Google Cloud
  - [ ] Enable Google Drive API
  - [ ] Configure callback URL: `${APP_URL}/google-drive/callback`
  - [ ] Set environment variables:
    - [ ] `GOOGLE_DRIVE_CLIENT_ID`
    - [ ] `GOOGLE_DRIVE_CLIENT_SECRET`
    - [ ] `GOOGLE_DRIVE_REDIRECT_URI`
    - [ ] `GOOGLE_DRIVE_FOLDER_ID` (optional)

- [ ] **Backup Process**
  - [ ] Admin can authorize Google account
  - [ ] Drive Backup feature accessible in admin panel
  - [ ] Backups created and stored in Drive
  - [ ] Backup restore capability tested

---

## 22. Known Issues & Fixes
- [ ] **Legacy Compatibility**
  - [ ] `/fae.php` redirects to `/fae-link` if code provided
  - [ ] Legacy routes maintained for backward compatibility

---

## Quick Start Commands
```bash
# Setup
cp .env.example .env
php artisan key:generate
composer install
npm install
npm run build

# Database
php artisan migrate
php artisan db:seed

# Development
php artisan serve
npm run dev

# Testing
php artisan test
php artisan tinker

# Production
composer install --optimize-autoloader --no-dev
npm run build
php artisan cache:clear
php artisan config:cache
```

---

## Status Tracking
- **Last Updated:** 2026-09-02
- **Project Status:** [In Development / Ready for Testing / Production Ready]
- **Completion:** ___% (Update as items are checked)


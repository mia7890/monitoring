# Monitoring System - How It Works

## Overview
This is a Laravel-based **Task & Appointment Monitoring System** for managing Field Assessment Educators (FAEs). It tracks task assignments, progress updates, schedules appointments, and maintains an administrative event calendar. The system uses role-based access control with two main user types: **Admin** (full control) and **FAE** (task executor).

---

## 1. System Architecture

### Technology Stack
- **Backend**: PHP 8+ with Laravel Framework
- **Frontend**: Blade Templates with Vite asset bundler
- **Database**: SQLite (default, configurable to MySQL)
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

**Admin (System Administrator)**
- Authentication: Login with `MONITORING_ADMIN_KEY` (default: "Admin12345!")
- Access: Full system access
- Can:
  - Create, edit, delete FAE users
  - Create, edit, delete tasks
  - Manage all appointments
  - Create admin events
  - View all FAE data and system statistics
  - View task progress across all FAEs

**FAE (Field Assessment Educator)**
- Authentication: Login with unique `fae_code` assigned by admin
- Access: Limited to own tasks and profile
- Can:
  - View tasks assigned to them
  - Update task progress (0-100%)
  - Post task updates with comments/attachments
  - Book appointments
  - Update profile photo
  - View own appointments

### Session-Based Authentication
- Uses Laravel sessions (stored in database)
- Session keys:
  - `monitoring_role` → "admin" or "fae"
  - `monitoring_fae_id` → FAE's database ID
  - `monitoring_fae_name` → FAE's full name
  - `monitoring_fae_code` → FAE's unique code

### Login Flow
1. Unauthenticated users redirected to `/access`
2. Admin enters admin key OR FAE enters FAE code
3. System validates against stored credentials
4. Creates session and redirects to `/dashboard`
5. All pages check `auth.monitoring` middleware
6. Logout destroys session

---

## 3. Database Models & Data Structure

### FaeUser Model
**Table**: `fae_users` - Represents a Field Assessment Educator
- `id` - Primary key
- `name` - Full name
- `fae_code` - Unique code for login (e.g., "FAE001")
- `email` - Contact email
- `department` - Department/region assignment
- `phone` - Contact phone number
- `profile_image` - Filename/path to profile photo
- `created_at`, `updated_at` - Audit timestamps

**Relationships**:
- One FaeUser has many Tasks
- One FaeUser has many Appointments
- One FaeUser has many TaskUpdates

**Example**: Mary Smith (FAE001) in HR Department, assigned multiple tasks

---

### Task Model
**Table**: `tasks` - Represents a work task/assignment
- `id` - Primary key
- `fae_id` - Foreign key to FaeUser (who must complete this)
- `region` - Geographic or organizational region
- `course` - Course name or category
- `task_name` - Title (e.g., "Site Inspection")
- `description` - Detailed description of what to do
- `deadline` - Due date (DATE format)
- `status` - One of: Pending, In Progress, Completed, Overdue
- `progress` - INTEGER 0-100% completion
- `priority` - Task importance (e.g., High, Medium, Low)
- `created_at`, `updated_at` - Audit timestamps

**Relationships**:
- Belongs to one FaeUser
- Has many TaskUpdates

**Example**: Task assigned to Mary to complete "Monthly Report" by 2026-09-15, currently 60% done

---

### TaskUpdate Model
**Table**: `task_updates` - History of task progress and communications
- `id` - Primary key
- `task_id` - Foreign key to Task
- `fae_id` - Foreign key to FaeUser (who posted the update)
- `author_role` - Who wrote it: "admin" or "fae"
- `author_name` - Display name of person who wrote it
- `message` - The update comment/message
- `attachment` - File path if user attached something
- `progress_at_update` - What was the progress % when posted?
- `status_at_update` - What was the task status when posted?
- `created_at`, `updated_at` - When posted

**Purpose**: Maintains timeline of all communications and progress changes for a task

**Example**: Mary posts update "Report 60% done, waiting for Q3 data" with progress at 60%

---

### Appointment Model
**Table**: `appointments` - Calendar appointments for FAEs
- `id` - Primary key
- `fae_id` - Foreign key to FaeUser (who has the appointment)
- `user_name` - Name of person requesting/having appointment
- `appointment_date` - Scheduled date (DATE format)
- `reason` - Reason/purpose of appointment
- `status` - pending, approved, or rejected
- `admin_comment` - Admin's response/notes
- `created_at`, `updated_at` - Audit timestamps

**Relationships**:
- Belongs to one FaeUser

**Example**: Mary Smith has appointment on 2026-09-10 for "Training Session", status: approved

---

### AdminEvent Model
**Table**: `admin_events` - System-wide events created by admin
- `id` - Primary key
- `title` - Event name
- `event_date` - When it occurs (DATE format)
- `description` - Event details
- `category` - Event type (e.g., "Holiday", "System Maintenance")
- `created_at`, `updated_at` - Audit timestamps

**Purpose**: Display important system/organizational events on calendar for all users

**Example**: "Q3 Audit" on 2026-09-20

---

## 4. Core Features: Tasks

### Task Lifecycle
1. **Admin Creates Task**
   - Admin selects FAE user, enters task details (name, deadline, description)
   - Task created with Status = "Pending", Progress = 0%
   - Appears in FAE's task list

2. **FAE Views & Updates Progress**
   - FAE sees task in `/tasks` list
   - Can increase progress % using progress slider
   - System updates task progress

3. **FAE Posts Updates**
   - FAE can add comments/updates on task
   - Can attach files
   - System records what progress % was at time of update
   - Updates appear in task's update feed (newest first)

4. **Admin Reviews & Manages**
   - Admin sees all tasks with FAE statistics
   - Can filter by status (Pending, In Progress, Completed, Overdue)
   - Can edit task details
   - Can delete task

5. **Task Auto-Status**
   - If deadline passes and status ≠ Completed → Status becomes "Overdue"
   - Admin manually changes status as task progresses

### Task Views & Lists

**`GET /tasks` - Tasks List Page**
- Shows all FAEs (or single FAE if user is FAE)
- Displays for each FAE:
  - Total tasks assigned
  - Count of: Pending, In Progress, Completed, Overdue
  - Average progress across all tasks
  - Trend indicators
- Shows all tasks with:
  - Task name, FAE name, deadline, status, progress
  - Count of updates posted
  - Clickable to see task details

**`GET /tasks/timeline` - Timeline View**
- Chronological list of tasks
- Sorted by deadline
- Visual representation of progress
- Highlight overdue tasks

### Task API Endpoints
- `POST /tasks` - Admin creates task
- `PUT /tasks/{id}` - Admin edits task
- `DELETE /tasks/{id}` - Admin deletes task
- `POST /tasks/update-progress/{id}` - FAE updates progress %
- `POST /tasks/store-update` - FAE posts task update message/attachment
- `GET /tasks/timeline` - Get timeline view

---

## 5. Core Features: Calendar & Appointments

### Calendar System
- Month/year view calendar
- Navigation to previous/next months
- Shows:
  - Admin events (system-wide)
  - Appointments for viewing user (own if FAE, all if admin)

### Appointment Booking
1. **FAE Books Appointment** (`POST /calendar/book`)
   - FAE clicks date on calendar
   - Enters: appointment date, reason, name
   - System creates appointment with Status = "pending"
   - Appears in admin's appointment list

2. **Admin Reviews Appointment**
   - Admin sees all pending appointments
   - Can approve, reject, or add comments
   - FAE can see approval status

### Admin Events
- Admin-only feature to mark important dates
- Created/edited via admin panel
- Display on calendar for all users
- Examples: Company holidays, maintenance windows, audit dates

---

## 6. Dashboard

### Dashboard Display (`GET /dashboard`)
Shows authenticated user summary:

**If Admin**:
- Total FAEs in system
- Total tasks across all FAEs
- Tasks by status: Pending, In Progress, Completed, Overdue
- Upcoming appointments (next 7 days)
- Upcoming deadlines (next 7 days)
- Key metrics and trends

**If FAE**:
- Tasks assigned to them only
- Tasks by status
- Upcoming appointments (own)
- Upcoming deadlines (own)
- Average progress on assigned tasks

### Dashboard Purpose
Quick overview of what needs attention - main entry point after login

---

## 7. User/FAE Management

### Creating FAE Users (Admin Only)
1. Admin goes to FAE management page
2. Enters:
   - Name (required)
   - FAE Code (required, unique) - e.g., "FAE001", "ENG-2026"
   - Email (optional)
   - Department (optional)
   - Phone (optional)
   - Profile image/photo (optional, image file)
3. System stores FAE in `fae_users` table
4. FAE can now login with their code

### FAE Profile Updates
- FAE can update own profile photo (`POST /profile/update-photo`)
- Photo stored in `public/uploads/` directory
- Profile image displays on dashboard

### Admin Controls
- View all FAEs with their statistics
- Edit FAE details
- Delete FAE (removes FAE and associated data)
- Assign tasks to FAE users

---

## 8. System Statistics & Monitoring

### Task Statistics
- **Total Assigned**: Number of tasks given to FAE
- **Completed**: Tasks with Status = "Completed"
- **In Progress**: Tasks with Status = "In Progress"
- **Pending**: Tasks not yet started (Status = "Pending")
- **Overdue**: Tasks past deadline and not completed
- **Average Progress**: Mean progress % across all tasks

### FAE Performance Indicators
- Completion rate (completed / total)
- Average progress on current tasks
- Number of overdue tasks
- Responsiveness (how often they post updates)

### Dashboard Metrics
- Count of all FAEs
- Total tasks in system
- Task breakdown by status
- Upcoming events (7-day window)

---

## 9. File & Photo Storage

### Upload Directory
- Location: `public/uploads/`
- Used for: FAE profile photos, task update attachments
- Must be web-accessible (inside `public/`)

### Photo Management
- Supported formats: JPG, JPEG, PNG, GIF, WebP
- Max size: 2MB
- Stored with filename
- Referenced in database by path

---

## 10. Routes & Entry Points

### Public Routes
- `GET /` - Redirects to `/dashboard` if logged in, else `/access`
- `GET /access` - Login/access page
- `POST /login` - Process login credentials
- `GET /logout` - Logout, destroy session
- `GET /fae-link/{code}` - Direct FAE login with code (alternative access method)

### Protected Routes (Authentication Required)
- `GET /dashboard` - Dashboard with overview
- `GET /tasks` - Task list view
- `GET /tasks/timeline` - Timeline view
- `POST /tasks/update-progress/{id}` - Update task progress
- `POST /tasks/store-update` - Post task update
- `POST /profile/update-photo` - Update profile photo
- `GET /calendar` - Calendar view
- `POST /calendar/book` - Book appointment

### Admin-Only Routes (Admin + Authentication Required)
- `POST /tasks` - Create new task
- `PUT /tasks/{id}` - Edit task
- `DELETE /tasks/{id}` - Delete task
- FAE management endpoints (create, edit, delete FAE users)
- Appointment management endpoints
- Admin event management endpoints

---

## 11. Middleware & Access Control

### `auth.monitoring` Middleware
- Checks if user has valid session (`monitoring_role` set)
- If not: redirect to `/access`
- Allows both admin and FAE users

### `admin.monitoring` Middleware
- Checks if user is admin role (`monitoring_role === 'admin'`)
- If not: abort with 403 Forbidden
- Used on routes restricted to admins only

### Route Protection
- All authenticated routes require `auth.monitoring`
- Admin routes also require `admin.monitoring`
- Nested middleware groups in routes/web.php

---

## 12. Views & User Interface

### Blade Templates Located in `resources/views/`

**Core Pages**:
- `access.blade.php` - Login/access page
- `dashboard.blade.php` - Dashboard overview
- `welcome.blade.php` - Welcome/intro page

**Directories**:
- `tasks/` - Task listing, details, timeline views
- `calendar/` - Calendar and appointment views
- `fae/` - FAE management (admin only)
- `layouts/` - Shared layout templates, navigation

### Styling
- `public/css/app.css` - Main compiled CSS (from resources/css/)
- `public/calendar.css` - Calendar-specific styles
- `public/style.css` - Legacy styles (for backward compatibility)

### JavaScript
- `public/js/app.js` - Compiled JS bundle (from resources/js/)
- `public/script.js` - Legacy scripts
- Uses Vite for asset bundling
- Bootstrap JavaScript included

---

## 13. Key Features Summary

### For FAEs
- ✅ View assigned tasks
- ✅ Update task progress (0-100%)
- ✅ Post updates with comments and attachments
- ✅ Update profile photo
- ✅ Book appointments
- ✅ View appointment status
- ✅ See task deadlines and upcoming events

### For Admins
- ✅ Create, edit, delete tasks
- ✅ Assign tasks to FAEs
- ✅ Create FAE user accounts
- ✅ Edit FAE profiles
- ✅ View all task progress in real-time
- ✅ Read task updates and comments
- ✅ Approve/reject appointments
- ✅ Create system events
- ✅ View comprehensive statistics and dashboards

### System Features
- ✅ Real-time task progress tracking
- ✅ Task update history with timestamps
- ✅ Calendar with appointments and events
- ✅ Role-based access control
- ✅ Photo/image upload for profiles
- ✅ Session-based authentication
- ✅ Task status management (Pending → In Progress → Completed or Overdue)

---

## 14. Data Flow Example

### Typical Workflow
1. **Admin logs in** with admin key → sees dashboard
2. **Admin creates FAE user** "Mary Smith" with code "FAE001"
3. **Admin creates task** assigned to Mary: "Complete Q3 Report", deadline 2026-09-20
4. **Mary logs in** with code "FAE001" → sees dashboard with new task
5. **Mary clicks task** → sees details and update history (empty)
6. **Mary updates progress to 25%** → system records change
7. **Mary posts update** "Started gathering Q3 data" → stored in TaskUpdate table
8. **Mary updates progress to 60%** → continues working
9. **Mary posts another update** "Report 60% done, waiting for final numbers"
10. **Admin reviews** → sees Mary's progress and comments in task view
11. **Admin edits task** → changes deadline or description if needed
12. **Mary completes task** → updates progress to 100%, changes status to Completed
13. **Entire timeline** viewable by both Mary and admin with all updates

---

## 15. Configuration

### Environment Variables (`.env`)
- `MONITORING_ADMIN_KEY` - Password for admin login (default: "Admin12345!")
- `APP_URL` - Base URL of application
- `APP_DEBUG` - Debug mode on/off
- `DB_CONNECTION` - Database type (sqlite, mysql, etc.)
- `SESSION_DRIVER` - Session storage (database)
- `CACHE_STORE` - Cache driver (database)

---

## 16. Legacy Code

### Backward Compatibility
- `/fae.php` → redirects to new Laravel routes
- Legacy code in `legacy/` folder (old PHP implementation)
- Old static files in `public/` (calendar.css, style.css, script.js)
- System maintains both old and new routes for transition period


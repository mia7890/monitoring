# Step-by-Step Deployment Guide: Laravel Application with MySQL on Railway

This guide provides complete, step-by-step instructions for deploying the **HYTECH Monitoring System** Laravel application and a **MySQL Database** to **Railway** for free.

---

## Overview & Free Tier Details
- **Platform**: [Railway.app](https://railway.app)
- **Free Tier Benefits**: Railway provides **$5.00 in free trial credits** (or 500 execution hours per month) for new accounts.
- **Key Features**:
  - Automatic builds via Nixpacks (detects PHP, Composer, Node, and Vite automatically).
  - One-click MySQL database provisioning.
  - Automatic environment variable linking between services.
  - Free custom domain (`*.up.railway.app`) with SSL included.

---

## Prerequisites
1. A **GitHub Account**.
2. A **Railway Account** (sign up at [railway.app](https://railway.app) using your GitHub account).
3. Your Laravel project pushed to a GitHub repository.

---

## Step 1: Create a Railway Project & Add MySQL

1. Log in to [Railway Dashboard](https://railway.app/dashboard).
2. Click **+ New Project**.
3. Select **Provision MySQL**.
4. Railway will create a MySQL database instance.
5. *(Optional)* Click on the **MySQL** card -> Go to **Variables** to view auto-generated credentials (such as `MYSQLHOST`, `MYSQLPORT`, `MYSQLDATABASE`, `MYSQLUSER`, `MYSQLPASSWORD`).

---

## Step 2: Deploy Your Laravel Repository

1. Inside the same Railway project workspace, click **+ New** (or top-right **+ Add Service**).
2. Select **GitHub Repo**.
3. Choose your repository (e.g., `HYTECH` or `monitoring`).
4. Railway will automatically analyze the codebase and prepare a build pipeline using Nixpacks.

---

## Step 3: Configure Environment Variables

1. Click on your **Laravel Application Service** card in Railway.
2. Navigate to the **Variables** tab.
3. Click **Raw Editor** (or add individual variables) and paste the following configuration:

```ini
APP_NAME="HYTECH Monitoring System"
APP_ENV=production
APP_KEY=base64:w8xFmVJFQ2ypVnkUXtjpjG2tc/6v6ykcTup1ucH42b8=
APP_DEBUG=false
APP_URL=https://${{RAILWAY_PUBLIC_DOMAIN}}

LOG_CHANNEL=stderr
LOG_LEVEL=info

# Set Debian Bookworm to prevent APT GPG signature verification errors
NIXPACKS_DEBIAN_VERSION=bookworm

# Railway MySQL Variable Links (Auto-connected by Railway)
DB_CONNECTION=mysql
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_DATABASE=${{MySQL.MYSQLDATABASE}}
DB_USERNAME=${{MySQL.MYSQLUSER}}
DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}

SESSION_DRIVER=database
SESSION_LIFETIME=120
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local

MONITORING_ADMIN_KEY=YourStrongAdminKey123!
MONITORING_ADMIN_EMAIL=your-email@domain.com
```

> [!NOTE]
> Railway automatically resolves references like `${{MySQL.MYSQLHOST}}` from your provisioned MySQL service.

---

## Step 4: Configure Build & Start Commands

1. In your Laravel service card, go to **Settings**.
2. Scroll down to the **Build & Deploy** section:
   - **Build Command**: Set to:
     ```bash
     composer install --no-dev --optimize-autoloader && npm run build
     ```
   - **Start Command**: Set to run migrations and start the web server on Railway's dynamic `$PORT`:
     ```bash
     php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=$PORT
     ```

---

## Step 5: Fixing Build Errors (APT / OpenPGP Signature Verification Failed)

If your Railway build logs show an error like:
```text
W: OpenPGP signature verification failed: http://deb.debian.org/debian-security trixie-security InRelease
E: The repository 'http://deb.debian.org/debian-security trixie-security InRelease' is not signed.
```

Fix it using **Option A** or **Option B**:

### Option A: Add `NIXPACKS_DEBIAN_VERSION` Variable in Railway (Easiest)
1. In your Railway Laravel service, go to **Variables**.
2. Add a new variable:
   - **Key**: `NIXPACKS_DEBIAN_VERSION`
   - **Value**: `bookworm`
3. Click **Redeploy**. This forces Nixpacks to use the stable **Debian Bookworm** image instead of Debian Trixie (testing).

### Option B: Use `nixpacks.toml` (Included in Repository)
A `nixpacks.toml` file is included in your repository root specifying Nix package providers (`php83`, `nodejs_20`), bypassing APT repository issues completely.

---

## Step 6: Generate Public Domain & Verify

1. In your Laravel service card, navigate to the **Settings** tab -> **Networking** section.
2. Click **Generate Domain** (e.g., `hytech-monitoring-production.up.railway.app`).
3. Open your generated domain URL to verify that the application is running.

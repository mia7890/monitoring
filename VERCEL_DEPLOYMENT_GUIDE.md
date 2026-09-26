# Step-by-Step Deployment Guide: Laravel on Vercel with Supabase PostgreSQL

This guide provides complete, step-by-step instructions for deploying your **HYTECH Monitoring System** Laravel application to **Vercel** connected to your **Supabase PostgreSQL Database**.

---

## 1. Prerequisites
1. **GitHub Account**: Your Laravel project pushed to a GitHub repository.
2. **Vercel Account**: Sign up at [vercel.com](https://vercel.com) using your GitHub account.
3. **Supabase Project**: Connected (`Monitoring Appointment system Project`, Ref: `dxbfcprrbhrmuwsyxclz`).

---

## 2. Supabase Connection Details

From your Supabase project dashboard (or Project Settings -> Database):
- **Host**: `db.dxbfcprrbhrmuwsyxclz.supabase.co` (Direct) or `aws-0-ap-southeast-1.pooler.supabase.com` (Pooler)
- **Port**: `5432` (or `6543` for Transaction Pooler - recommended for Serverless / Vercel)
- **Database**: `postgres`
- **User**: `postgres.dxbfcprrbhrmuwsyxclz` (or `postgres`)
- **Password**: *[Your Supabase Database Password]*

---

## 3. Step-by-Step Vercel Deployment

### Step 1: Push Changes to GitHub
Make sure all newly generated configuration files are committed and pushed:
```bash
git add api/index.php bootstrap/app.php vercel.json .vercelignore
git commit -m "Configure Laravel for Vercel deployment"
git push origin main
```

---

### Step 2: Import Project in Vercel
1. Log in to [vercel.com](https://vercel.com/dashboard).
2. Click **Add New...** -> **Project**.
3. Under **Import Git Repository**, select your repository.
4. Keep the **Framework Preset** as **Other** (do not select anything; `vercel.json` controls the build).
5. Expand the **Environment Variables** section.

---

### Step 3: Configure Environment Variables in Vercel

Add the following environment variables in the Vercel project settings:

| Variable Name | Recommended Value | Note |
| :--- | :--- | :--- |
| `APP_NAME` | `HYTECH Monitoring System` | |
| `APP_ENV` | `production` | |
| `APP_KEY` | `base64:w8xFmVJFQ2ypVnkUXtjpjG2tc/6v6ykcTup1ucH42b8=` | Or your generated app key |
| `APP_DEBUG` | `false` | |
| `APP_URL` | `https://your-app-name.vercel.app` | (Update with your Vercel URL after deploy) |
| `LOG_CHANNEL` | `stderr` | Critical for serverless logs |
| `DB_CONNECTION` | `pgsql` | |
| `DB_HOST` | `aws-0-ap-southeast-1.pooler.supabase.com` | (Or `db.dxbfcprrbhrmuwsyxclz.supabase.co`) |
| `DB_PORT` | `6543` | (Or `5432`) |
| `DB_DATABASE` | `postgres` | |
| `DB_USERNAME` | `postgres.dxbfcprrbhrmuwsyxclz` | (Or `postgres`) |
| `DB_PASSWORD` | `YourSupabaseDbPassword` | |
| `DB_SSLMODE` | `require` | Required for Supabase |
| `SESSION_DRIVER` | `database` | Serverless safe |
| `CACHE_STORE` | `database` | Serverless safe |
| `APP_STORAGE` | `/tmp/storage` | Required for Vercel |
| `VIEW_COMPILED_PATH` | `/tmp/storage/framework/views` | Required for Vercel |
| **Mail (Brevo HTTP API)** | | *(Recommended for Serverless)* |
| `MAIL_MAILER` | `brevo` | Uses built-in HTTP API transport |
| `BREVO_API_KEY` | `xkeysib-xxxxxxxxxxxxxxxx` | Your Brevo API Key |
| `MAIL_FROM_ADDRESS` | `your-verified-sender@domain.com` | Verified Brevo sender email |
| `MAIL_FROM_NAME` | `Monitoring System` | |
| **Mail (Brevo SMTP Alternative)**| | |
| `MAIL_MAILER` | `smtp` | |
| `MAIL_HOST` | `smtp-relay.brevo.com` | |
| `MAIL_PORT` | `587` | |
| `MAIL_ENCRYPTION` | `tls` | |
| `MAIL_USERNAME` | `your-brevo-login-email` | |
| `MAIL_PASSWORD` | `your-brevo-smtp-key` | |
| `MAIL_FROM_ADDRESS` | `your-verified-sender@domain.com` | |

---

### Step 4: Deploy
1. Click **Deploy**.
2. Vercel will build your assets using Vite, install Composer dependencies via `vercel-php`, and deploy the serverless functions.
3. Once the build finishes, click on your generated `.vercel.app` domain to visit the live app.

---

## 4. Helpful Tips for Serverless Laravel

- **Migrations**: Because Vercel has read-only serverless functions, run database migrations locally (`php artisan migrate --force`) connected to Supabase or via the Supabase SQL Editor / MCP tools.
- **Sessions & Cache**: Configured to use the PostgreSQL database (`sessions` and `cache` tables), so session state persists cleanly across serverless lambda instances.

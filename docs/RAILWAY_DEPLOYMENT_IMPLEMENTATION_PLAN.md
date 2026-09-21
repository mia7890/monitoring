# Railway Deployment Implementation Plan

This plan deploys the current Laravel monitoring application to Railway and keeps all environment-specific secrets in Railway Variables. The local `.env` file must remain uncommitted.

## 1. Pre-deployment checks

Run these commands from the repository root:

```bash
composer install
php artisan view:cache
php artisan route:list
```

Confirm that:

- The application boots without a PHP or Blade error.
- The landing page uses the corrected calendar sizing.
- The application uses `logo.jpg`, not the mislabeled legacy `logo.png`.
- The local Resend API key is not shown by `git status` or `git diff`.
- `.env` remains ignored by Git.

## 2. Commit the application changes

Review the files before staging:

```bash
git status --short
git diff --check
```

Stage and commit the tracked application and documentation changes:

```bash
git add app config resources docs public/logo.jpg
git diff --cached --check
git commit -m "Prepare Railway deployment and Resend SMTP testing"
```

Do not stage `.env`, API keys, database passwords, or local runtime files.

## 3. Create or select the Railway project

1. Open the Railway dashboard.
2. Create a project or select the existing monitoring project.
3. Add a MySQL service if one does not already exist.
4. Add the GitHub repository as the Laravel application service.
5. Confirm the service deploys from the `main` branch.

## 4. Configure Railway Variables

Add these variables to the Laravel application service. Use Railway's variable references for the MySQL service instead of local database values.

```ini
APP_NAME=HYTECH Monitoring System
APP_ENV=production
APP_KEY=base64:replace-with-the-existing-laravel-app-key
APP_DEBUG=false
APP_URL=https://${{RAILWAY_PUBLIC_DOMAIN}}

LOG_CHANNEL=stderr
LOG_LEVEL=info
NIXPACKS_DEBIAN_VERSION=bookworm

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

MONITORING_ADMIN_KEY=replace-with-a-strong-admin-key
MONITORING_ADMIN_EMAIL=verified-test-recipient@example.com
```

Do not copy the local `DB_HOST`, `DB_PORT`, `DB_PASSWORD`, or `APP_URL` values into Railway. Railway's internal MySQL hostname works only inside Railway.

If the Railway service already has the existing production environment, keep the current `APP_*`, `DB_*`, `SESSION_*`, `CACHE_*`, queue, and monitoring access variables. Replace only the old Gmail mail variables with the Resend variables below. Do not paste the existing Railway environment dump into Git or into the local `.env` file.

## 5. Configure Resend SMTP for testing

For a no-domain Resend test, add these values as Railway Variables:

```ini
MAIL_MAILER=smtp
MAIL_HOST=smtp.resend.com
MAIL_PORT=465
MAIL_USERNAME=resend
MAIL_PASSWORD=replace-with-resend-api-key
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=onboarding@resend.dev
MAIL_FROM_NAME=Monitoring System Test
MAIL_TIMEOUT=10
```

Resend's no-domain test mode is limited:

- Send only to the email address associated with the Resend account.
- Use `onboarding@resend.dev` as the sender.
- Do not use this configuration for production recipients.
- Keep the API key in Railway Variables only.
- Rotate the key after testing if it was shared outside the secret manager.

For production email, verify a domain in Resend and replace the sender with an address on that domain.

## 6. Configure build and start commands

Use these Railway service commands:

**Build command**

```bash
composer install --no-dev --optimize-autoloader && npm run build
```

**Start command**

```bash
php artisan migrate --force && php artisan config:clear && php artisan serve --host=0.0.0.0 --port=$PORT
```

Railway must provide `$PORT`; do not hard-code port `8000` in the production start command.

## 7. Deploy from GitHub

Push the commit to the configured branch:

```bash
git push origin main
```

Railway should automatically start a new deployment. Watch the build and deploy logs for:

- Composer installation
- Vite asset build
- Database migrations
- Laravel server binding to `0.0.0.0:$PORT`

## 8. Verify the deployment

1. Generate or open the Railway public domain.
2. Confirm the landing page loads over HTTPS.
3. Click **Open Workspace Portal** and confirm it stays on HTTPS.
4. Sign in with the configured monitoring access key.
5. Check dashboard, tasks, calendar, and settings routes.
6. Use **Test SMTP** with the Resend account email as the recipient.
7. Confirm the message appears in Resend activity and the recipient inbox.
8. Check Railway logs for connection or migration errors.

Useful Railway shell checks:

```bash
php artisan about
php artisan migrate:status
php artisan config:show mail
```

Never print `MAIL_PASSWORD`, `DB_PASSWORD`, or `APP_KEY` in logs or command output.

## 9. Troubleshooting

### The application returns a database error

Verify the MySQL service is running and the four Railway MySQL variable references resolve correctly. Do not use `mysql.railway.internal` from a local machine.

The Railway start command must not ignore migration failures. If `php artisan migrate --force` fails, let the deployment fail and fix the migration or Railway database variables before serving traffic.

### URLs redirect to an unreachable HTTPS address

Confirm `APP_URL` is the Railway HTTPS domain and redeploy after changing it. The application only forces HTTPS when production uses an HTTPS `APP_URL`.

### SMTP authentication fails

Confirm the Resend API key is active, the username is exactly `resend`, and the sender is `onboarding@resend.dev` for no-domain testing. Clear cached configuration by redeploying or running `php artisan config:clear`.

### Railway cannot connect to SMTP

Use port `465` with `MAIL_ENCRYPTION=ssl`. If the provider supports it and port 465 is unavailable, use port `587` with `MAIL_ENCRYPTION=tls`. Do not use port 25.

### Assets look stale

Redeploy the latest commit and perform a hard refresh. The application uses versioned asset URLs for the corrected logo.

## 10. Rollback

If the deployment fails after the push:

1. Open Railway's deployment history.
2. Redeploy the last known-good deployment.
3. Preserve the failed deployment logs for diagnosis.
4. Fix the issue locally, validate it, and push a new commit.

Do not roll back by deleting or committing production secrets.

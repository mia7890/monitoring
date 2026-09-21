# Updated Railway Environment Variables

Use this set in the Railway application service **Variables** editor. It preserves the existing production application settings, uses Railway's MySQL service references, and replaces Gmail SMTP with Resend SMTP for testing.

Replace every value marked `REPLACE_ME` in Railway. Do not commit the real values to Git or place them in the local `.env` file.

```ini
APP_DEBUG=false
APP_ENV=production
APP_FAKER_LOCALE=en_US
APP_FALLBACK_LOCALE=en
APP_KEY=REPLACE_ME_WITH_EXISTING_LARAVEL_APP_KEY
APP_LOCALE=en
APP_MAINTENANCE_DRIVER=file
APP_MAINTENANCE_STORE=file
APP_NAME="Monitoring System"
APP_TIMEZONE=Asia/Manila
APP_URL=https://monitoring-production-558b.up.railway.app

AWS_ACCESS_KEY_ID=
AWS_BUCKET=
AWS_DEFAULT_REGION=us-east-1
AWS_SECRET_ACCESS_KEY=
AWS_USE_PATH_STYLE_ENDPOINT=false

BCRYPT_ROUNDS=12
BROADCAST_CONNECTION=log
CACHE_PREFIX=
CACHE_STORE=database

DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci
DB_CONNECTION=mysql
DB_DATABASE=${{MySQL.MYSQLDATABASE}}
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_USERNAME=${{MySQL.MYSQLUSER}}

FILESYSTEM_DISK=local

LOG_CHANNEL=stderr
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=info
LOG_STACK=single

# Resend SMTP testing without a custom domain
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=onboarding@resend.dev
MAIL_FROM_NAME="Monitoring System Test"
MAIL_HOST=smtp.resend.com
MAIL_MAILER=smtp
MAIL_PASSWORD=REPLACE_ME_WITH_RESEND_API_KEY
MAIL_PORT=465
MAIL_TIMEOUT=10
MAIL_USERNAME=resend

MEMCACHED_HOST=127.0.0.1

MONITORING_ADMIN_EMAIL=REPLACE_ME_WITH_RESEND_ACCOUNT_EMAIL
MONITORING_ADMIN_KEY=REPLACE_ME_WITH_STRONG_ADMIN_KEY
NIXPACKS_DEBIAN_VERSION=bookworm

QUEUE_CONNECTION=database
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

SESSION_DOMAIN=null
SESSION_DRIVER=cookie
SESSION_ENCRYPT=false
SESSION_LIFETIME=120
SESSION_PATH=/

VITE_APP_NAME="${APP_NAME}"
```

## Resend testing requirements

- `MAIL_FROM_ADDRESS` must remain `onboarding@resend.dev` until a domain is verified in Resend.
- Send test messages only to the Resend account email configured in `MONITORING_ADMIN_EMAIL`.
- Store the Resend API key only in Railway Variables.
- Do not paste the API key into this file.
- For production email, verify a domain in Resend and replace the sender address.

## Apply and redeploy

1. Open the Railway application service.
2. Open **Variables** and use the raw editor.
3. Paste the block above.
4. Replace all `REPLACE_ME` values.
5. Confirm the MySQL variable references resolve from the MySQL service.
6. Save the variables and redeploy.
7. Check the deployment logs for successful migrations and server startup.
8. Run the application's SMTP test using the Resend account email as the recipient.

The Railway start command should remain:

```bash
php artisan migrate --force && php artisan config:clear && php artisan serve --host=0.0.0.0 --port=$PORT
```

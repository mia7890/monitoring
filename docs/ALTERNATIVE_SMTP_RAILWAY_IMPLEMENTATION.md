# Alternative SMTP Setup for Railway

This guide configures Laravel to send email through an external SMTP relay while the application is deployed on Railway. Railway hosts the application and database; the SMTP provider handles outbound email.

## Why use an external SMTP relay?

Railway does not provide an SMTP server. Some SMTP ports, especially port 25, may be restricted by hosting networks. Use a provider that supports authenticated submission over port `587` or `2525` with STARTTLS. Do not use Railway's internal hostname for SMTP.

## Recommended provider setup

Choose one provider that offers SMTP credentials, such as Brevo, SMTP2GO, Mailgun, or SendGrid. The exact free-plan limits and sender-verification requirements change over time, so confirm them in the provider dashboard.

The provider will give you:

- SMTP host, for example `smtp-relay.brevo.com`
- SMTP port, preferably `587` or `2525`
- SMTP username
- SMTP password or API-generated SMTP key
- A verified sender address or domain

For Gmail, use an App Password rather than the normal account password, and enable two-step verification first.

## Resend testing without adding a domain

Use this option for development or a short Railway smoke test only. A custom sending domain is not required, but Resend limits unverified-domain testing.

### Resend testing limitations

- Create a Resend account and verify the account email address.
- Create an API key in the Resend dashboard. Treat it as a password.
- Use `onboarding@resend.dev` for `MAIL_FROM_ADDRESS`.
- Send test messages only to the email address associated with the Resend account.
- Do not use this setup for real users, bulk delivery, or production notifications.
- For production delivery, verify a domain in Resend and change the sender to an address on that domain.

### Resend SMTP values

Add these variables to the Railway application service. Replace `re_xxxxxxxxx` with the Resend API key and replace the recipient in the application settings with the account email used for testing.

```ini
MAIL_MAILER=smtp
MAIL_HOST=smtp.resend.com
MAIL_PORT=587
MAIL_USERNAME=resend
MAIL_PASSWORD=re_xxxxxxxxx
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=onboarding@resend.dev
MAIL_FROM_NAME="Monitoring System Test"
MAIL_TIMEOUT=10
```

If the hosting network cannot connect on port `465`, use Resend's STARTTLS submission port instead:

```ini
MAIL_PORT=587
MAIL_ENCRYPTION=tls
```

Keep the Resend API key in Railway Variables only. Never place it in Git, this Markdown file, browser JavaScript, or a committed `.env` file.

### Resend implementation checklist

1. Create and verify a Resend account.
2. Create an API key with the minimum permissions needed for testing.
3. Add the SMTP variables above to the Railway application service.
4. Set the application's test recipient to the Resend account email address.
5. Redeploy the Railway service.
6. Clear Laravel's cached configuration:

	```bash
	php artisan config:clear
	```

7. Use the application's **Test SMTP** action, or trigger one controlled test email.
8. Check the Railway logs and Resend's email activity page.
9. Remove or rotate the test API key when testing is complete.

### Optional Laravel test configuration

For a local-only test, use the same values in the ignored `.env` file. Do not commit them:

```ini
MAIL_MAILER=smtp
MAIL_HOST=smtp.resend.com
MAIL_PORT=465
MAIL_USERNAME=resend
MAIL_PASSWORD=re_xxxxxxxxx
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=onboarding@resend.dev
MAIL_FROM_NAME="Monitoring System Test"
```

Run the configuration refresh before testing:

```bash
php artisan config:clear
```

## Configure Railway variables

In the Railway application service, open **Variables** and add or update these values. Replace the example values with the credentials from the selected provider.

```ini
MAIL_MAILER=smtp
MAIL_HOST=smtp-relay.example.com
MAIL_PORT=587
MAIL_USERNAME=your-smtp-username
MAIL_PASSWORD=your-smtp-password-or-key
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=verified-sender@example.com
MAIL_FROM_NAME="Monitoring System"
MAIL_TIMEOUT=10
```

Do not commit these values to `.env`, documentation, or source control. Store them only in Railway variables or a local ignored `.env` file.

## Railway application settings

Keep the database variables connected to the Railway MySQL service:

```ini
DB_CONNECTION=mysql
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_DATABASE=${{MySQL.MYSQLDATABASE}}
DB_USERNAME=${{MySQL.MYSQLUSER}}
DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}
```

The SMTP host is external and must not be set to `mysql.railway.internal` or another Railway database hostname.

After changing variables:

1. Redeploy the application service.
2. Run `php artisan config:clear` during deployment, or restart the service so Laravel reloads environment variables.
3. Confirm that `APP_DEBUG=false` remains enabled in production.

## Application deployment command

Use Railway's dynamic port for the web process:

```bash
php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=$PORT
```

Do not expose an SMTP port from the Laravel service. Laravel makes an outbound connection to the provider's SMTP submission port.

## Test delivery

Use the application's SMTP test action if available. Otherwise, run a small authenticated test from the deployed application and check both the Railway logs and the provider's delivery activity.

Expected results:

- The provider accepts the connection on port `587` or `2525`.
- The sender address is verified by the provider.
- The message appears in the provider's activity log.
- The recipient receives the message or it appears in spam/quarantine.

If connection attempts time out, switch between ports `587` and `2525` if the provider supports both. Do not fall back to port `25`.

## Common errors

### Authentication failed

Check the SMTP username and password/key. For Gmail, regenerate an App Password and do not use the normal account password.

### Sender not authorized

Verify `MAIL_FROM_ADDRESS` in the provider dashboard, or verify the sending domain and configure its SPF/DKIM records.

### Connection timed out

Use port `587` or `2525`, set `MAIL_ENCRYPTION=tls`, and confirm that the provider allows SMTP submission for the account.

### Old settings remain active

Laravel may have cached configuration. Clear it and restart or redeploy:

```bash
php artisan config:clear
php artisan cache:clear
```

## Local development

Railway's internal MySQL hostname only resolves inside Railway. Local development must use a local MySQL instance or another reachable database. Example local values:

```ini
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=monitoring
DB_USERNAME=root
DB_PASSWORD=your-local-password
```

Create the local database before running migrations:

```bash
php artisan migrate
```

If email is not needed locally, use Laravel's log mailer instead:

```ini
MAIL_MAILER=log
```

Messages will be written to `storage/logs/laravel.log` instead of being delivered.

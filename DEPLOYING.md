# Deploying the Capstone API

This project is now hosting-ready. Use the notes below to configure your environment and verify the deployment.

## Environment Variables

Set these in your hosting panel (cPanel/Plask/Render) or via Apache/Nginx config:

- DB_HOST: database host (e.g., localhost)
- DB_PORT: port (optional; e.g., 3306)
- DB_NAME: database name (e.g., capstone)
- DB_USER: database user
- DB_PASS: database password
- TIMEZONE: e.g., Asia/Manila (default)
- APP_ENV: development or production (default: production)
- APP_DEBUG: 1 to show errors in dev, empty/0 to hide
- ALLOWED_ORIGINS: comma-separated list of exact origins allowed for CORS (e.g., https://app.example.com,https://admin.example.com)

Notes:

- If ALLOWED_ORIGINS is not set, same-origin requests are allowed by default. Credentials are supported.
- QR secrets are auto-managed in the DB `system_config` table. No .env file needed.

## Webserver (Apache) examples

.htaccess (if allowed by host) to set env vars:

```
SetEnv DB_HOST localhost
SetEnv DB_NAME capstone
SetEnv DB_USER myuser
SetEnv DB_PASS mypass
SetEnv APP_ENV production
SetEnv TIMEZONE Asia/Manila
SetEnv ALLOWED_ORIGINS https://your-frontend.example.com
```

Or VirtualHost:

```
<VirtualHost *:80>
    ServerName api.example.com
    DocumentRoot /var/www/capstone
    SetEnv DB_HOST localhost
    SetEnv DB_NAME capstone
    SetEnv DB_USER myuser
    SetEnv DB_PASS mypass
    SetEnv APP_ENV production
    SetEnv TIMEZONE Asia/Manila
    SetEnv ALLOWED_ORIGINS https://your-frontend.example.com
</VirtualHost>
```

## Smoke Test

- Check health endpoint: `GET /api/health.php`
  - Expect `{ ok: true, db: "connected" }`
- Test CORS preflight: browser will issue OPTIONS automatically; server responds 204.
- Verify authenticated endpoint (cookie-based): open app, login, then call `/api/get_events.php`.

## Notes

- `api/_bootstrap.php` centralizes timezone, error visibility, and CORS handling.
- File downloads (`api/tasks_download_attachment.php`) still stream files correctly.
- Most APIs return JSON and continue to use existing auth rules.

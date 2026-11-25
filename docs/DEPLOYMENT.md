# Deployment (Hostinger) and DNS cut-over

## Current state (2026-11-25)
* Hosted on a Hostinger website (Cloud Startup plan), domain **nido-entruempelung.de** connected (Hostinger DNS,
  free SSL, `www` → CNAME). Verified: pages 200, `/.env` 403, `/storage/nido.sqlite` 404.
* E-mail: Hostinger mailbox `info@nido-entruempelung.de` (MX/SPF/DKIM/DMARC records from Hostinger kept).
  The contact form sends via SMTP (`smtp.hostinger.com:465`, SSL) with that mailbox; verified delivered to the inbox
  (not spam) in hPanel → E-Mails → E-Mail-Protokolle. Without SMTP the server's `mail()` sends from
  `noreply@<server>` and lands in spam.
* `.env` on the server (`public_html/.env`, not in git): `SITE_URL`, `MAIL_TO`, `MAIL_FROM`, `SMTP_*`. If the mailbox
  password changes, update `SMTP_PASS` there – otherwise `storage/mail.log` shows "Anmeldung abgelehnt".

## First steps for the owner
1. Log in at `/admin` (admin / ChangeMe-2026!) and change the password (forced).
2. Delete test inquiries, add real photos (gallery, hero, services), review Impressum/Datenschutz.
3. Submit `https://nido-entruempelung.de/sitemap.xml` in Google Search Console.

## Updating the site later
Upload changed files into `public_html` (File Manager → upload → replace). Never upload/overwrite `storage/` or `uploads/`
(they hold the database and the owner's images). Backup: download `storage/nido.sqlite`, `uploads/`, `.env`.

## After every deployment – checks
* `/` and `/kontakt` load; `/admin` redirects to login; `/storage/nido.sqlite`, `/.env`, `/app/bootstrap.php`, `/AI_SITE_GUIDE.md` are 403/404.
* Contact form: send a test message, confirm it appears under Anfragen and that the e-mail arrives (else set SMTP in `.env`).
* Change the admin password.

## Packaging note
Hostinger's website importer refuses `.py` files. The deployment zip therefore excludes `tests/` and `source/extract_logo.py`
(they stay in the local project folder). Everything else in the project is uploaded.

# Uhai Bora

Uhai Bora is an appointment booking and management website. Patients find listed
doctors and specialties, book a session, and reschedule or cancel bookings.
Doctors publish sessions and confirm bookings; clinic administrators manage
doctors, sessions, bookings and patient accounts.

Uhai Bora handles appointment administration only. It is not an emergency
service and does not give medical advice.

## Roles

- **Patient:** register (a parent or guardian registers children under 18), book, reschedule and cancel; download, correct or delete their information.
- **Doctor:** add and edit their own sessions, confirm or update bookings, see only patients booked into their own sessions.
- **Administrator:** manage doctors, sessions, bookings and patients; set a new password for a patient who has forgotten theirs; view the audit log.

## Run with Docker

```sh
docker compose up --build -d
```

Open <http://localhost:8081> (set `APP_PORT` to use another port). A new
database volume is initialised from `SQL_Database_uhaibora.sql`. Stop with
`docker compose down`.

The image runs PHP 8.3 with Apache and the production `php.ini` (errors are
logged, never shown). `docker-compose.yml` is set up for local development: it
mounts the source folder and uses development database passwords. For a real
deployment set `DB_PASSWORD`, `DB_ROOT_PASSWORD` and `DB_USER` through the
environment, remove the source-code volume, serve the site over HTTPS, and set
`APP_FORCE_HTTPS=1` if TLS ends at a proxy.

Upgrading an existing Docker setup: the database image moved from MariaDB 10.5
to 10.11 and upgrades its data automatically (`MARIADB_AUTO_UPGRADE`). Back up
the volume first.

### Demo accounts (development only)

| Role | Email | Password |
| --- | --- | --- |
| Administrator | `admin@uhaibora.test` | `UhaiBora-Demo-2026` |
| Doctor (General Practice) | `doctor@uhaibora.test` | `UhaiBora-Demo-2026` |
| Doctor (Paediatrics) | `paediatrics@uhaibora.test` | `UhaiBora-Demo-2026` |
| Patient | `patient@uhaibora.test` | `UhaiBora-Demo-2026` |

These passwords are public. The administrator dashboard warns while any demo
account exists; delete the accounts or change their passwords before the system
holds real information.

## Run with XAMPP

1. Use XAMPP with PHP 7.3 or newer and start Apache and MySQL/MariaDB.
2. Copy the project folder into `htdocs` (for example `htdocs/uhai-bora-hms`).
3. Create a database named `uhaibora` and import `SQL_Database_uhaibora.sql` (new installs only — it replaces existing tables).
4. Open `http://localhost/uhai-bora-hms/`.

Database settings come from the environment variables `DB_HOST`, `DB_USER`,
`DB_PASSWORD`, `DB_NAME` and `DB_PORT` (XAMPP defaults: `localhost`, `root`, no
password, `uhaibora`). The app detects its subfolder automatically; set
`APP_BASE_PATH` if it is served through an alias. In a subfolder, edit the
`ErrorDocument` lines in `.htaccess` to include the folder name (the main 404
handling works without that change).

## Upgrading an existing database

Older Uhai Bora databases are upgraded automatically the first time the app
connects: new tables (`app_meta`, `login_attempts`, `audit_log`) and new nullable
columns are added; nothing is dropped or rewritten. To run the upgrade yourself:

```sh
php database/migrate.php            # or: docker compose exec web php database/migrate.php
```

Accounts created by the original template stored passwords as plain text. Each
one is converted to a secure hash the next time that person logs in. Accounts
that never log in keep the old value, so ask an administrator to set new
passwords for them.

## Configuration: operator details

`config/site.php` holds the details that identify the organisation running the
service: legal name, registrations, contact phone and email, privacy contact,
hosting location, retention periods and verified emergency numbers. They are
deliberately empty. Until they are filled in, pages say the details will be
published before launch, and the legal pages show a “Draft” notice.

Put deployment-specific values in `config/site.local.php` (ignored by git):

```php
<?php
return [
    'operator' => ['legal_name' => 'Example Health Ltd'],
    'contact' => ['email' => 'help@example.org', 'phone' => '...', 'hours' => 'Mon–Fri, 8am–5pm'],
    'privacy' => ['email' => 'privacy@example.org'],
];
```

Only publish information you have verified. The Privacy notice, Terms and
Cookie notice describe how this code behaves, but they still need review by a
qualified Kenyan advocate or privacy professional before launch.

## Project structure

| Path | Purpose |
| --- | --- |
| `index.php`, `services.php`, `about.php`, `contact.php`, `how-it-works.php` | Public pages |
| `privacy.php`, `your-data.php`, `terms.php`, `cookies.php`, `accessibility.php` | Policies |
| `login.php`, `signup.php`, `logout.php`, `book.php`, `404.php` | Accounts, the “Book Now” router and the not-found page |
| `patient/`, `doctor/`, `admin/` | Signed-in areas |
| `includes/` | Bootstrap, database access, auth, layouts, forms and booking rules |
| `config/site.php` | Operator details (see above) |
| `css/base.css`, `css/site.css`, `css/app.css`, `js/site.js` | Design system, public pages, signed-in areas, progressive enhancement |
| `assets/` | Illustrations, logo, photos — see `assets/CREDITS.md` |
| `.htaccess` | Branded 404s, legacy `.html` redirects, blocks internal files, caching |
| `database/migrate.php` | Command-line schema upgrade |

## Security and privacy features

- Prepared statements for every query; output escaping everywhere.
- CSRF tokens on every form; nothing changes on a plain link (GET request).
- Passwords hashed with `password_hash`; legacy plain-text values upgraded on login.
- Sign-in throttling (5 failures per account or 30 per network address in 15 minutes).
- Session cookie is HttpOnly and SameSite=Lax (Secure over HTTPS), set only once someone logs in or submits a form; sign-out after 30 minutes without activity.
- Content-Security-Policy and related security headers; no third-party scripts, fonts or trackers.
- Doctors see only patients booked into their own sessions; views and changes of patient records are written to an audit log without health details.
- Errors are logged with a reference number and shown as calm, branded 404, 500 and 503 pages.
- Internal files (`.git`, SQL, configuration, includes) are never served.

## Accessibility

The interface targets WCAG 2.2 AA: keyboard-operable menus with visible focus,
a skip link, labelled form fields with error summaries, 16px minimum text,
checked colour contrast, touch targets of 40px or more (navigation 44px or more;
WCAG 2.2 requires 24px), reflow at 320px, reduced-motion
support, and menus and forms that work without JavaScript.

## Licence

MIT — see `LICENSE`. Original appointment-system template © 2022 Hashen Udara.
Fonts and media are credited in `assets/CREDITS.md`.

# Uhai Bora

Uhai Bora helps patients find doctors, compare available sessions, and manage appointments. Administrators manage doctor accounts and clinic sessions, while doctors and patients can update their profiles and manage the records associated with their accounts.

## Roles

- **Administrator:** manage doctors, sessions, appointments, and patient records.
- **Doctor:** review appointments and patients, manage personal sessions, and update account details.
- **Patient:** discover doctors and sessions, book or reschedule appointments, and manage account details.

## Run with Docker

From the project directory:

```sh
docker compose up --build -d
```

Open [http://localhost:8081](http://localhost:8081) (or set `APP_PORT` to another free port). On a new database volume, Docker initializes the `uhaibora` database from `SQL_Database_uhaibora.sql`. Stop the services with `docker compose down`.

The demo accounts are:

| Role | Email | Password |
| --- | --- | --- |
| Administrator | `admin@uhaibora.com` | `123` |
| Doctor | `doctor@uhaibora.com` | `123` |
| Patient | `patient@uhaibora.com` | `123` |

The SQL file recreates the application tables and demo records. Back up any existing data before importing it into a database that already contains application data.

## Run with XAMPP

1. Start Apache and MySQL from the XAMPP Control Panel.
2. Copy the project folder into XAMPP's `htdocs` directory.
3. Create a database named `uhaibora` in phpMyAdmin.
4. Import `SQL_Database_uhaibora.sql`.
5. Open the project folder from `http://localhost/`.

## Stack

PHP, MySQL/MariaDB, HTML, CSS, and JavaScript.

## Brand and media

The Uhai Bora logo variants and original SVG illustrations live in `assets/brand/` and `assets/illustrations/`. Two short, silent MP4 loops in `assets/video/` add subtle motion to the landing page; they pause when off-screen and are disabled when reduced motion is enabled. Healthcare photographs are stored locally in `assets/photos/`: `doctor-consultation.jpg` is sourced from [Unsplash](https://images.unsplash.com/photo-1584515933487-779824d29309), and `care-team.jpg`, `patient-care.jpg`, and `clinic-care.jpg` are sourced from Pexels photo IDs 5327585, 7659569, and 5452201. The site uses its existing local Inter font and browser-native JavaScript and CSS, with no external UI or animation plugins required.

## Landing page

The homepage highlights appointment booking, directory specialties, a three-step getting-started path, and common booking questions. Specialty names are drawn from the application's seeded directory; sign-in is required to browse currently listed doctors and sessions. Uhai Bora is an appointment tool, not an emergency service.

## Public pages

The public site includes the homepage, [Services](./services.html), [About Uhai Bora](./about.html), [Contact](./contact.html), and [How it works](./how-it-works.html). Its shared navigation links to these public pages, highlights Book Now, and provides Register and Login from an accessible Account menu. The Contact page does not invent clinic contact details; the operator should publish verified contact channels before production. Account creation uses `signup.php` and `create-account.php`; newly registered patient passwords are stored with PHP's password hashing API.

## Policies

The landing-page footer links to the project-level [Privacy Policy](./privacy.html), [Terms of Use](./terms.html), [Cookie Notice](./cookies.html), and [Accessibility Statement](./accessibility.html). Review and adapt these pages to the actual operating organization, hosting configuration, service practices, and applicable law before production use.

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

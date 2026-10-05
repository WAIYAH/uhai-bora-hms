# Uhai Bora Hospital Management System (UB-HMS)

A web-based hospital management system for **Uhai Bora Hospital, Nairobi, Kenya**. It replaces paper files and separate departmental registers with one secure database shared by reception, triage, consultation, the laboratory, the pharmacy and billing.

> Diploma in Information Technology project (DIT-503 Programming Project), KCA University, September–December 2026.
> Student: Susan Wanjiku Waweru (25/04035) · Supervisor: David Oloo Ouma

## Modules and progress

The system is built with the Agile (Scrum) method in five two-week sprints, as set out in Chapter Three of the proposal.

| Sprint | Dates (2026) | Module | Status |
|---|---|---|---|
| 1 | 5–16 Oct | User management and security; patient registration and records | Done |
| 2 | 19–30 Oct | Appointments; clinical consultation (triage and consultation records) | Planned |
| 3 | 2–13 Nov | Laboratory management; pharmacy and inventory | Planned |
| 4 | 16–27 Nov | Billing and reports | Planned |
| 5 | 30 Nov–11 Dec | Integration, security review and corrections | Planned |

The database for **all** modules is already designed (`database/schema.sql`); screens are added sprint by sprint.

### What works now (Sprint 1)

- First-time setup page that creates the System Administrator account (no default passwords).
- Log-in with personal accounts, eight staff roles and role-based access to each page.
- Passwords stored as secure hashes; new staff must set their own password at first log-in.
- Account lock after 5 failed log-ins (15 minutes); automatic log-out after 30 minutes of inactivity.
- User accounts: create, edit, change role, reset password, deactivate (accounts are never deleted).
- Patient registration with a unique outpatient number (e.g. `UB-2026-00001`), safe even when two receptionists register at the same moment.
- Warning when a patient may already be registered (same name and birth year, or same phone number).
- Date of birth, or estimated age when the date is unknown; Kenyan phone numbers checked and stored in one format.
- Patient search by outpatient number, name, phone or ID number; patient record page and editing.
- Audit trail of log-ins, failed log-ins, account changes and every patient record opened or changed (Data Protection Act, 2019).

## Technology

| Layer | Technology |
|---|---|
| Interface | HTML5, CSS3, JavaScript (works without JavaScript) |
| Application | PHP 8 (no framework, PDO prepared statements) |
| Database | MySQL / MariaDB |
| Server | Apache on XAMPP 8.x |
| Tools | Visual Studio Code, Git, Diagrams.net |

## Installation on XAMPP (Windows)

1. Install [XAMPP 8.x](https://www.apachefriends.org/) and start **Apache** and **MySQL** in the XAMPP Control Panel.
2. Copy this folder to `C:\xampp\htdocs\uhai-bora-hms` (or clone the repository there).
3. Open <http://localhost/phpmyadmin>, choose **Import**, and import:
   1. `database/schema.sql` (creates the `ubhms` database and all tables)
   2. `database/seed.sql` (staff roles and sample price lists)
4. Copy `config/config.sample.php` to `config/config.php`. The XAMPP defaults (user `root`, empty password) work as they are; change them if your MySQL has a password.
5. Open <http://localhost/uhai-bora-hms>. You will be taken to the setup page to create the administrator account.
6. Log in as the administrator and create accounts for other staff under **User accounts**.

To use the system from other computers on the hospital network, open `http://<server-computer-IP>/uhai-bora-hms`.

## Staff roles and permissions

| Permission | Admin | Manager | Reception | Nurse | Doctor | Lab | Pharmacy | Cashier |
|---|:-:|:-:|:-:|:-:|:-:|:-:|:-:|:-:|
| View patient records | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Register and edit patients | ✓ | | ✓ | | | | | |
| Manage user accounts | ✓ | | | | | | | |
| View audit trail | ✓ | ✓ | | | | | | |

Permissions are defined in one place, `includes/auth.php`, and grow as each sprint adds modules.

## Testing

**Unit tests** (outpatient numbers, phone numbers, age and patient validation):

```bash
php tests/run_tests.php
# XAMPP on Windows:
C:\xampp\php\php.exe tests\run_tests.php
```

**End-to-end tests** (37 browser-style checks of setup, log-in, roles, registration, search, security and the audit trail). Run them only on a **fresh, empty database**, because they create test accounts:

```bash
bash tests/e2e.sh http://localhost/uhai-bora-hms
```

## Security measures

- PDO prepared statements on every query (protection against SQL injection).
- All output escaped with `htmlspecialchars` (protection against cross-site scripting).
- CSRF token on every form.
- `password_hash` / `password_verify` for passwords; session id renewed at log-in and log-out.
- Session cookie marked HttpOnly and SameSite.
- `config/`, `includes/`, `database/` and `tests/` blocked from the web by `.htaccess`.
- Only synthetic (made-up) data is used during development and testing.

## Folder structure

```
uhai-bora-hms/
├── assets/            CSS and JavaScript
├── audit/             Audit trail page
├── config/            config.sample.php (copy to config.php)
├── database/          schema.sql and seed.sql
├── docs/              Product backlog and sprint records
├── includes/          Shared code: database, log-in, permissions, layout
├── patients/          Patient registration, search, record and editing
├── tests/             Unit and end-to-end tests
├── users/             User account management
├── index.php          Sends users to the log-in page or dashboard
├── login.php / logout.php / change-password.php
├── dashboard.php
└── setup.php          First-time administrator setup (locks itself after use)
```

## Outpatient number format

`PREFIX-YEAR-SEQUENCE`, for example `UB-2026-00001`. The numbering restarts each year. The prefix and number of digits can be changed in `config/config.php` to match the hospital's existing convention once it is confirmed during document review.

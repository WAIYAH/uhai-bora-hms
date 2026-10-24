-- Uhai Bora — fresh installation and development data.
--
-- WARNING: this script DROPS and recreates every Uhai Bora table. Use it only
-- for a new, empty database (Docker runs it automatically on a new volume).
-- Never import it into a database that holds real patient information:
-- existing databases are upgraded automatically and additively by the app
-- (see includes/schema.php or run `php database/migrate.php`).
--
-- The demo accounts below are for development only. Their password is
-- published in the README, so delete them or change their passwords before
-- the system holds real information.

CREATE DATABASE IF NOT EXISTS uhaibora CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE uhaibora;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS audit_log;
DROP TABLE IF EXISTS login_attempts;
DROP TABLE IF EXISTS app_meta;
DROP TABLE IF EXISTS appointment;
DROP TABLE IF EXISTS schedule;
DROP TABLE IF EXISTS doctor;
DROP TABLE IF EXISTS patient;
DROP TABLE IF EXISTS admin;
DROP TABLE IF EXISTS specialties;
DROP TABLE IF EXISTS webuser;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE webuser (
    email VARCHAR(255) NOT NULL,
    usertype CHAR(1) NOT NULL,
    PRIMARY KEY (email),
    CONSTRAINT chk_webuser_type CHECK (usertype IN ('a', 'd', 'p'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE specialties (
    id SMALLINT UNSIGNED NOT NULL,
    sname VARCHAR(100) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_specialties_name (sname)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO specialties (id, sname) VALUES
    (1, 'Accident and emergency medicine'),
    (2, 'Allergology'),
    (3, 'Anaesthetics'),
    (4, 'Biological hematology'),
    (5, 'Cardiology'),
    (6, 'Child psychiatry'),
    (7, 'Clinical biology'),
    (8, 'Clinical chemistry'),
    (9, 'Clinical neurophysiology'),
    (10, 'Clinical radiology'),
    (11, 'Dental, oral and maxillo-facial surgery'),
    (12, 'Dermato-venerology'),
    (13, 'Dermatology'),
    (14, 'Endocrinology'),
    (15, 'Gastro-enterologic surgery'),
    (16, 'Gastroenterology'),
    (17, 'General hematology'),
    (18, 'General Practice'),
    (19, 'General surgery'),
    (20, 'Geriatrics'),
    (21, 'Immunology'),
    (22, 'Infectious diseases'),
    (23, 'Internal medicine'),
    (24, 'Laboratory medicine'),
    (25, 'Maxillo-facial surgery'),
    (26, 'Microbiology'),
    (27, 'Nephrology'),
    (28, 'Neuro-psychiatry'),
    (29, 'Neurology'),
    (30, 'Neurosurgery'),
    (31, 'Nuclear medicine'),
    (32, 'Obstetrics and gynecology'),
    (33, 'Occupational medicine'),
    (34, 'Ophthalmology'),
    (35, 'Orthopaedics'),
    (36, 'Otorhinolaryngology'),
    (37, 'Paediatric surgery'),
    (38, 'Paediatrics'),
    (39, 'Pathology'),
    (40, 'Pharmacology'),
    (41, 'Physical medicine and rehabilitation'),
    (42, 'Plastic surgery'),
    (43, 'Podiatric Medicine'),
    (44, 'Podiatric Surgery'),
    (45, 'Psychiatry'),
    (46, 'Public health and Preventive Medicine'),
    (47, 'Radiology'),
    (48, 'Radiotherapy'),
    (49, 'Respiratory medicine'),
    (50, 'Rheumatology'),
    (51, 'Stomatology'),
    (52, 'Thoracic surgery'),
    (53, 'Tropical medicine'),
    (54, 'Urology'),
    (55, 'Vascular surgery'),
    (56, 'Venereology');

CREATE TABLE admin (
    aemail VARCHAR(255) NOT NULL,
    apassword VARCHAR(255) NOT NULL,
    PRIMARY KEY (aemail),
    CONSTRAINT fk_admin_webuser FOREIGN KEY (aemail) REFERENCES webuser (email)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE patient (
    pid INT UNSIGNED NOT NULL AUTO_INCREMENT,
    pemail VARCHAR(255) NOT NULL,
    pname VARCHAR(255) NOT NULL,
    ppassword VARCHAR(255) NOT NULL,
    paddress VARCHAR(255) NOT NULL,
    pnic VARCHAR(32) NOT NULL,
    pdob DATE NOT NULL,
    ptel VARCHAR(32) NOT NULL,
    pguardian_name VARCHAR(255) NULL DEFAULT NULL,
    pconsent_version VARCHAR(20) NULL DEFAULT NULL,
    pconsented_at DATETIME NULL DEFAULT NULL,
    pcreated_at DATETIME NULL DEFAULT NULL,
    PRIMARY KEY (pid),
    UNIQUE KEY uq_patient_email (pemail),
    CONSTRAINT fk_patient_webuser FOREIGN KEY (pemail) REFERENCES webuser (email)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE doctor (
    docid INT UNSIGNED NOT NULL AUTO_INCREMENT,
    docemail VARCHAR(255) NOT NULL,
    docname VARCHAR(255) NOT NULL,
    docpassword VARCHAR(255) NOT NULL,
    docnic VARCHAR(32) NOT NULL,
    doctel VARCHAR(32) NOT NULL,
    specialties SMALLINT UNSIGNED NOT NULL,
    PRIMARY KEY (docid),
    UNIQUE KEY uq_doctor_email (docemail),
    KEY idx_doctor_specialty (specialties),
    CONSTRAINT fk_doctor_webuser FOREIGN KEY (docemail) REFERENCES webuser (email)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_doctor_specialty FOREIGN KEY (specialties) REFERENCES specialties (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE schedule (
    scheduleid INT UNSIGNED NOT NULL AUTO_INCREMENT,
    docid INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    scheduledate DATE NOT NULL,
    scheduletime TIME NOT NULL,
    nop SMALLINT UNSIGNED NOT NULL,
    PRIMARY KEY (scheduleid),
    KEY idx_schedule_doctor_date (docid, scheduledate, scheduletime),
    KEY idx_schedule_date (scheduledate, scheduletime),
    CONSTRAINT fk_schedule_doctor FOREIGN KEY (docid) REFERENCES doctor (docid)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE appointment (
    appoid INT UNSIGNED NOT NULL AUTO_INCREMENT,
    pid INT UNSIGNED NOT NULL,
    apponum SMALLINT UNSIGNED NOT NULL,
    scheduleid INT UNSIGNED NOT NULL,
    appodate DATE NOT NULL,
    status ENUM('pending', 'confirmed', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    created_at DATETIME NULL DEFAULT NULL,
    PRIMARY KEY (appoid),
    UNIQUE KEY uq_appointment_number (scheduleid, apponum),
    KEY idx_appointment_patient_date (pid, appodate),
    CONSTRAINT fk_appointment_patient FOREIGN KEY (pid) REFERENCES patient (pid)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_appointment_schedule FOREIGN KEY (scheduleid) REFERENCES schedule (scheduleid)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE login_attempts (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    email_hash CHAR(64) NOT NULL,
    ip VARCHAR(45) NOT NULL,
    attempted_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_login_attempts_email (email_hash, attempted_at),
    KEY idx_login_attempts_ip (ip, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_log (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    actor_email VARCHAR(255) NULL,
    actor_role CHAR(1) NULL,
    action VARCHAR(64) NOT NULL,
    subject_type VARCHAR(32) NULL,
    subject_id INT UNSIGNED NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_audit_created (created_at),
    KEY idx_audit_subject (subject_type, subject_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE app_meta (
    meta_key VARCHAR(64) NOT NULL,
    meta_value VARCHAR(255) NOT NULL,
    PRIMARY KEY (meta_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO app_meta (meta_key, meta_value) VALUES ('schema_version', '1');

-- ---------------------------------------------------------------------------
-- Development demo data. Password for every demo account: UhaiBora-Demo-2026
-- (stored only as a bcrypt hash). The .test domain is reserved and never routable.
-- ---------------------------------------------------------------------------
INSERT INTO webuser (email, usertype) VALUES
    ('admin@uhaibora.test', 'a'),
    ('doctor@uhaibora.test', 'd'),
    ('paediatrics@uhaibora.test', 'd'),
    ('patient@uhaibora.test', 'p');

INSERT INTO admin (aemail, apassword) VALUES
    ('admin@uhaibora.test', '$2y$10$wbOekvF0so3RVPV4PbSUdOt4y2AkqHvgiGg9pWGt6gtIIuBjSSC4G');

INSERT INTO doctor (docid, docemail, docname, docpassword, docnic, doctel, specialties) VALUES
    (1, 'doctor@uhaibora.test', 'Demo Doctor (General Practice)', '$2y$10$wbOekvF0so3RVPV4PbSUdOt4y2AkqHvgiGg9pWGt6gtIIuBjSSC4G', 'DEMO-D-0001', '+254 000 000 001', 18),
    (2, 'paediatrics@uhaibora.test', 'Demo Doctor (Paediatrics)', '$2y$10$wbOekvF0so3RVPV4PbSUdOt4y2AkqHvgiGg9pWGt6gtIIuBjSSC4G', 'DEMO-D-0002', '+254 000 000 002', 38);

INSERT INTO patient (pid, pemail, pname, ppassword, paddress, pnic, pdob, ptel, pconsent_version, pconsented_at, pcreated_at) VALUES
    (1, 'patient@uhaibora.test', 'Demo Patient', '$2y$10$wbOekvF0so3RVPV4PbSUdOt4y2AkqHvgiGg9pWGt6gtIIuBjSSC4G', 'Demo address, Nairobi', 'DEMO-P-0001', '1990-01-01', '+254 000 000 003', '2026-10-24', NOW(), NOW());

-- Session dates are relative to the install date so the demo always has upcoming sessions.
INSERT INTO schedule (scheduleid, docid, title, scheduledate, scheduletime, nop) VALUES
    (1, 1, 'General consultation', CURDATE() + INTERVAL 1 DAY, '09:00:00', 10),
    (2, 1, 'Follow-up clinic', CURDATE() + INTERVAL 2 DAY, '14:00:00', 6),
    (3, 2, 'Children''s clinic', CURDATE() + INTERVAL 3 DAY, '09:30:00', 8),
    (4, 1, 'General consultation', CURDATE() + INTERVAL 5 DAY, '10:30:00', 8),
    (5, 2, 'Children''s clinic', CURDATE() + INTERVAL 8 DAY, '11:00:00', 1),
    (6, 1, 'General consultation', CURDATE() - INTERVAL 7 DAY, '09:00:00', 5);

INSERT INTO appointment (appoid, pid, apponum, scheduleid, appodate, status, created_at) VALUES
    (1, 1, 1, 1, CURDATE(), 'pending', NOW()),
    (2, 1, 1, 6, CURDATE() - INTERVAL 10 DAY, 'completed', NOW() - INTERVAL 10 DAY);

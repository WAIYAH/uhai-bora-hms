CREATE DATABASE IF NOT EXISTS uhaibora CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE uhaibora;

SET FOREIGN_KEY_CHECKS = 0;
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

INSERT INTO webuser (email, usertype) VALUES
    ('admin@uhaibora.com', 'a'),
    ('doctor@uhaibora.com', 'd'),
    ('patient@uhaibora.com', 'p');

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

INSERT INTO admin (aemail, apassword) VALUES ('admin@uhaibora.com', '123');

CREATE TABLE patient (
    pid INT UNSIGNED NOT NULL AUTO_INCREMENT,
    pemail VARCHAR(255) NOT NULL,
    pname VARCHAR(255) NOT NULL,
    ppassword VARCHAR(255) NOT NULL,
    paddress VARCHAR(255) NOT NULL,
    pnic VARCHAR(32) NOT NULL,
    pdob DATE NOT NULL,
    ptel VARCHAR(32) NOT NULL,
    PRIMARY KEY (pid),
    UNIQUE KEY uq_patient_email (pemail),
    CONSTRAINT fk_patient_webuser FOREIGN KEY (pemail) REFERENCES webuser (email)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO patient (pid, pemail, pname, ppassword, paddress, pnic, pdob, ptel) VALUES
    (1, 'patient@uhaibora.com', 'Test Patient', '123', 'Sri Lanka', '0000000000', '2000-01-01', '0120000000');

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

INSERT INTO doctor (docid, docemail, docname, docpassword, docnic, doctel, specialties) VALUES
    (1, 'doctor@uhaibora.com', 'Test Doctor', '123', '000000000', '0110000000', 1);

CREATE TABLE schedule (
    scheduleid INT UNSIGNED NOT NULL AUTO_INCREMENT,
    docid INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    scheduledate DATE NOT NULL,
    scheduletime TIME NOT NULL,
    nop SMALLINT UNSIGNED NOT NULL,
    PRIMARY KEY (scheduleid),
    KEY idx_schedule_doctor_date (docid, scheduledate, scheduletime),
    CONSTRAINT fk_schedule_doctor FOREIGN KEY (docid) REFERENCES doctor (docid)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO schedule (scheduleid, docid, title, scheduledate, scheduletime, nop) VALUES
    (1, 1, 'General consultation', '2050-01-01', '18:00:00', 50),
    (2, 1, 'General consultation', '2022-06-10', '20:36:00', 1),
    (3, 1, 'Follow-up clinic', '2022-06-10', '20:33:00', 1),
    (4, 1, 'General consultation', '2022-06-10', '12:32:00', 1),
    (5, 1, 'Follow-up clinic', '2022-06-10', '20:35:00', 1),
    (6, 1, 'General consultation', '2022-06-10', '20:35:00', 1),
    (7, 1, 'General consultation', '2022-06-24', '20:36:00', 1),
    (8, 1, 'Follow-up clinic', '2022-06-10', '13:33:00', 1);

CREATE TABLE appointment (
    appoid INT UNSIGNED NOT NULL AUTO_INCREMENT,
    pid INT UNSIGNED NOT NULL,
    apponum SMALLINT UNSIGNED NOT NULL,
    scheduleid INT UNSIGNED NOT NULL,
    appodate DATE NOT NULL,
    status ENUM('pending', 'confirmed', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    PRIMARY KEY (appoid),
    UNIQUE KEY uq_appointment_number (scheduleid, apponum),
    KEY idx_appointment_patient_date (pid, appodate),
    CONSTRAINT fk_appointment_patient FOREIGN KEY (pid) REFERENCES patient (pid)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_appointment_schedule FOREIGN KEY (scheduleid) REFERENCES schedule (scheduleid)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO appointment (appoid, pid, apponum, scheduleid, appodate) VALUES
    (1, 1, 1, 1, '2022-06-03');

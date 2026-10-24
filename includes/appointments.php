<?php
/*
 * Sessions, appointments and specialties: queries and booking rules shared by
 * the patient, doctor and administrator areas.
 *
 * Booking rules:
 *  - only future sessions can be booked or moved to;
 *  - cancelled bookings do not count towards a session's capacity;
 *  - a patient can hold one active booking per session;
 *  - the session row is locked while booking so two people cannot take the last place.
 */

// SQL condition for a session (aliased "s") that has not started yet.
const UPCOMING_SQL = '(s.scheduledate > CURDATE() OR (s.scheduledate = CURDATE() AND s.scheduletime > CURTIME()))';
const ACTIVE_STATUSES_SQL = "('pending', 'confirmed')";

function appointment_statuses(): array
{
    return [
        'pending' => 'Pending confirmation',
        'confirmed' => 'Confirmed',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];
}

function status_badge(string $status): string
{
    $labels = appointment_statuses();
    $icons = ['pending' => 'clock', 'confirmed' => 'check-circle', 'completed' => 'check', 'cancelled' => 'x'];
    $status = isset($labels[$status]) ? $status : 'pending';
    return '<span class="badge badge--' . e($status) . '">' . icon($icons[$status]) . e($labels[$status]) . '</span>';
}

function specialties(): array
{
    static $list = null;
    if ($list === null) {
        $list = [];
        foreach (db_all('SELECT id, sname FROM specialties ORDER BY sname') as $row) {
            $list[(int) $row['id']] = $row['sname'];
        }
    }
    return $list;
}

/** Neutral one-line descriptions: what a specialty covers, never what is offered. */
function specialty_description(string $name): string
{
    $descriptions = [
        'general practice' => 'Care for everyday health concerns.',
        'cardiology' => 'The heart and blood vessels.',
        'paediatrics' => 'Health care for babies, children and teenagers.',
        'obstetrics and gynecology' => 'Pregnancy, childbirth and reproductive health.',
        'dermatology' => 'The skin, hair and nails.',
        'orthopaedics' => 'Bones, joints and muscles.',
        'ophthalmology' => 'The eyes and vision.',
        'otorhinolaryngology' => 'Ear, nose and throat.',
        'psychiatry' => 'Mental health.',
        'child psychiatry' => 'Mental health for children and young people.',
        'neurology' => 'The brain, spinal cord and nerves.',
        'endocrinology' => 'Hormones, including diabetes and thyroid conditions.',
        'gastroenterology' => 'The digestive system.',
        'internal medicine' => 'Adult health and long-term conditions.',
        'urology' => 'The urinary system.',
        'nephrology' => 'The kidneys.',
        'respiratory medicine' => 'The lungs and breathing.',
        'rheumatology' => 'Joints, muscles and autoimmune conditions.',
        'geriatrics' => 'Health care for older adults.',
        'infectious diseases' => 'Infections and how they are treated.',
        'general surgery' => 'Assessment for surgical care.',
        'physical medicine and rehabilitation' => 'Recovering movement and everyday function.',
        'dental, oral and maxillo-facial surgery' => 'The mouth, teeth, jaw and face.',
        'allergology' => 'Allergies and allergic reactions.',
        'immunology' => 'The immune system.',
        'accident and emergency medicine' => 'Emergencies are not booked online. If you need urgent help, call your local emergency number.',
    ];
    return $descriptions[mb_strtolower(trim($name))] ?? '';
}

/** One session with its doctor, specialty and active booking count. */
function find_session(int $scheduleId): ?array
{
    return db_one(
        "SELECT s.scheduleid, s.docid, s.title, s.scheduledate, s.scheduletime, s.nop,
                d.docname, d.specialties AS specialty_id, sp.sname,
                (SELECT COUNT(*) FROM appointment a WHERE a.scheduleid = s.scheduleid AND a.status IN " . ACTIVE_STATUSES_SQL . ") AS booked
         FROM schedule s
         INNER JOIN doctor d ON d.docid = s.docid
         LEFT JOIN specialties sp ON sp.id = d.specialties
         WHERE s.scheduleid = ?",
        [$scheduleId]
    );
}

function spaces_left(array $session): int
{
    return max(0, (int) $session['nop'] - (int) $session['booked']);
}

/** Lowest appointment number not yet used in the session (numbers stay unique). */
function next_appointment_number(int $scheduleId): int
{
    $used = [];
    foreach (db_all('SELECT apponum FROM appointment WHERE scheduleid = ?', [$scheduleId]) as $row) {
        $used[(int) $row['apponum']] = true;
    }
    $number = 1;
    while (isset($used[$number])) {
        $number++;
    }
    return $number;
}

function booking_error_message(string $code): string
{
    $messages = [
        'not_found' => 'We could not find that session. It may have been removed.',
        'past' => 'That session has already started or finished, so it can no longer be booked.',
        'full' => 'That session has just filled up. Please choose another time.',
        'duplicate' => 'You already have a booking for this session.',
        'same_session' => 'Choose a different session from your current booking.',
        'not_movable' => 'This booking can no longer be changed.',
    ];
    return $messages[$code] ?? 'We could not complete that request.';
}

/** Book a session for a patient. Returns ['ok' => bool, 'error' => code, 'appointment_id' => int]. */
function book_session(int $patientId, int $scheduleId): array
{
    return db_transaction(function () use ($patientId, $scheduleId) {
        $session = db_one('SELECT scheduleid, scheduledate, scheduletime, nop FROM schedule WHERE scheduleid = ? FOR UPDATE', [$scheduleId]);
        if (!$session) {
            return ['ok' => false, 'error' => 'not_found'];
        }
        if (!is_future_slot($session['scheduledate'], $session['scheduletime'])) {
            return ['ok' => false, 'error' => 'past'];
        }
        $existing = db_value(
            'SELECT appoid FROM appointment WHERE scheduleid = ? AND pid = ? AND status IN ' . ACTIVE_STATUSES_SQL . ' LIMIT 1',
            [$scheduleId, $patientId]
        );
        if ($existing !== null) {
            return ['ok' => false, 'error' => 'duplicate', 'appointment_id' => (int) $existing];
        }
        $active = (int) db_value('SELECT COUNT(*) FROM appointment WHERE scheduleid = ? AND status IN ' . ACTIVE_STATUSES_SQL, [$scheduleId]);
        if ($active >= (int) $session['nop']) {
            return ['ok' => false, 'error' => 'full'];
        }
        $number = next_appointment_number($scheduleId);
        db_exec(
            "INSERT INTO appointment (pid, apponum, scheduleid, appodate, status, created_at) VALUES (?, ?, ?, CURDATE(), 'pending', NOW())",
            [$patientId, $number, $scheduleId]
        );
        return ['ok' => true, 'appointment_id' => db_insert_id(), 'number' => $number];
    });
}

/** Move a patient's upcoming booking to another future session. */
function reschedule_appointment(int $patientId, int $appointmentId, int $newScheduleId): array
{
    return db_transaction(function () use ($patientId, $appointmentId, $newScheduleId) {
        $appointment = db_one(
            'SELECT a.appoid, a.scheduleid, a.status, s.scheduledate, s.scheduletime
             FROM appointment a INNER JOIN schedule s ON s.scheduleid = a.scheduleid
             WHERE a.appoid = ? AND a.pid = ? FOR UPDATE',
            [$appointmentId, $patientId]
        );
        if (!$appointment) {
            return ['ok' => false, 'error' => 'not_found'];
        }
        if (!in_array($appointment['status'], ['pending', 'confirmed'], true)
            || !is_future_slot($appointment['scheduledate'], $appointment['scheduletime'])) {
            return ['ok' => false, 'error' => 'not_movable'];
        }
        if ((int) $appointment['scheduleid'] === $newScheduleId) {
            return ['ok' => false, 'error' => 'same_session'];
        }
        $session = db_one('SELECT scheduleid, scheduledate, scheduletime, nop FROM schedule WHERE scheduleid = ? FOR UPDATE', [$newScheduleId]);
        if (!$session) {
            return ['ok' => false, 'error' => 'not_found'];
        }
        if (!is_future_slot($session['scheduledate'], $session['scheduletime'])) {
            return ['ok' => false, 'error' => 'past'];
        }
        $duplicate = db_value(
            'SELECT appoid FROM appointment WHERE scheduleid = ? AND pid = ? AND status IN ' . ACTIVE_STATUSES_SQL . ' LIMIT 1',
            [$newScheduleId, $patientId]
        );
        if ($duplicate !== null) {
            return ['ok' => false, 'error' => 'duplicate'];
        }
        $active = (int) db_value('SELECT COUNT(*) FROM appointment WHERE scheduleid = ? AND status IN ' . ACTIVE_STATUSES_SQL, [$newScheduleId]);
        if ($active >= (int) $session['nop']) {
            return ['ok' => false, 'error' => 'full'];
        }
        $number = next_appointment_number($newScheduleId);
        db_exec(
            "UPDATE appointment SET scheduleid = ?, apponum = ?, appodate = CURDATE(), status = 'pending' WHERE appoid = ? AND pid = ?",
            [$newScheduleId, $number, $appointmentId, $patientId]
        );
        return ['ok' => true, 'appointment_id' => $appointmentId, 'number' => $number];
    });
}

/** A patient cancels their own upcoming booking (the record is kept as "Cancelled"). */
function cancel_patient_appointment(int $patientId, int $appointmentId): bool
{
    return db_exec(
        "UPDATE appointment a INNER JOIN schedule s ON s.scheduleid = a.scheduleid
         SET a.status = 'cancelled'
         WHERE a.appoid = ? AND a.pid = ? AND a.status IN " . ACTIVE_STATUSES_SQL . ' AND ' . UPCOMING_SQL,
        [$appointmentId, $patientId]
    ) === 1;
}

/** Appointments joined with session, doctor, specialty and patient details. */
function appointment_select_sql(): string
{
    return "SELECT a.appoid, a.apponum, a.appodate, a.status, a.pid,
                   s.scheduleid, s.title, s.scheduledate, s.scheduletime, s.nop,
                   d.docid, d.docname, sp.sname,
                   p.pname, p.pemail, p.ptel,
                   " . UPCOMING_SQL . " AS is_upcoming
            FROM appointment a
            INNER JOIN schedule s ON s.scheduleid = a.scheduleid
            INNER JOIN doctor d ON d.docid = s.docid
            INNER JOIN patient p ON p.pid = a.pid
            LEFT JOIN specialties sp ON sp.id = d.specialties";
}

function patient_appointments(int $patientId, string $view, int $limit = 100): array
{
    $sql = appointment_select_sql() . ' WHERE a.pid = ?';
    if ($view === 'upcoming') {
        $sql .= ' AND a.status IN ' . ACTIVE_STATUSES_SQL . ' AND ' . UPCOMING_SQL . ' ORDER BY s.scheduledate, s.scheduletime';
    } else {
        $sql .= ' AND (a.status IN (\'completed\', \'cancelled\') OR NOT ' . UPCOMING_SQL . ') ORDER BY s.scheduledate DESC, s.scheduletime DESC';
    }
    return db_all($sql . ' LIMIT ' . (int) $limit, [$patientId]);
}

/** Upcoming sessions with booking counts. $filters: specialty, doctor, q, from, patient (to flag own bookings). */
function upcoming_sessions(array $filters, int $limit, int $offset): array
{
    $patientId = (int) ($filters['patient'] ?? 0);
    $sql = "SELECT s.scheduleid, s.title, s.scheduledate, s.scheduletime, s.nop,
                   d.docid, d.docname, sp.id AS specialty_id, sp.sname,
                   (SELECT COUNT(*) FROM appointment a WHERE a.scheduleid = s.scheduleid AND a.status IN " . ACTIVE_STATUSES_SQL . ") AS booked,
                   (SELECT a2.appoid FROM appointment a2 WHERE a2.scheduleid = s.scheduleid AND a2.pid = ? AND a2.status IN " . ACTIVE_STATUSES_SQL . " LIMIT 1) AS my_appointment
            FROM schedule s
            INNER JOIN doctor d ON d.docid = s.docid
            LEFT JOIN specialties sp ON sp.id = d.specialties
            WHERE " . UPCOMING_SQL;
    $params = [$patientId];
    if (!empty($filters['specialty'])) {
        $sql .= ' AND d.specialties = ?';
        $params[] = (int) $filters['specialty'];
    }
    if (!empty($filters['doctor'])) {
        $sql .= ' AND d.docid = ?';
        $params[] = (int) $filters['doctor'];
    }
    if (!empty($filters['from'])) {
        $sql .= ' AND s.scheduledate >= ?';
        $params[] = $filters['from'];
    }
    if (!empty($filters['q'])) {
        $sql .= ' AND (d.docname LIKE ? OR s.title LIKE ? OR sp.sname LIKE ?)';
        $pattern = like_pattern($filters['q']);
        array_push($params, $pattern, $pattern, $pattern);
    }
    $sql .= ' ORDER BY s.scheduledate, s.scheduletime, s.scheduleid LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
    return db_all($sql, $params);
}

/** Specialties that have at least one listed doctor, with counts and the next session date. */
function listed_specialties(): array
{
    return db_all(
        "SELECT sp.id, sp.sname,
                COUNT(DISTINCT d.docid) AS doctors,
                (SELECT MIN(s.scheduledate) FROM schedule s INNER JOIN doctor d2 ON d2.docid = s.docid
                  WHERE d2.specialties = sp.id AND " . UPCOMING_SQL . ") AS next_session,
                (SELECT COUNT(*) FROM schedule s INNER JOIN doctor d3 ON d3.docid = s.docid
                  WHERE d3.specialties = sp.id AND " . UPCOMING_SQL . ") AS upcoming_sessions
         FROM specialties sp
         INNER JOIN doctor d ON d.specialties = sp.id
         GROUP BY sp.id, sp.sname
         ORDER BY upcoming_sessions DESC, sp.sname"
    );
}

/** A patient's booking as a list item, with reschedule and cancel actions when allowed. */
function patient_booking_record(array $a, bool $withActions = true): string
{
    $canChange = (int) $a['is_upcoming'] === 1 && in_array($a['status'], ['pending', 'confirmed'], true);
    $id = (int) $a['appoid'];
    $html = '<li class="record' . ($canChange ? '' : ' record--muted') . '" id="booking-' . $id . '">'
        . '<div class="record__main">'
        . '<h3 class="record__title"><a href="' . e(url('patient/appointment.php', ['id' => $id])) . '">' . e($a['title']) . '</a></h3>'
        . '<ul class="record__meta">'
        . '<li>' . session_when($a['scheduledate'], $a['scheduletime']) . '</li>'
        . '<li>' . icon('stethoscope') . e($a['docname']) . ($a['sname'] ? ' · ' . e($a['sname']) : '') . '</li>'
        . '<li>Ref ' . e(appointment_reference($id)) . ' · No. ' . (int) $a['apponum'] . '</li>'
        . '</ul></div>'
        . '<div class="record__aside">' . status_badge($a['status']) . '</div>';
    if ($withActions && $canChange) {
        $html .= '<div class="record__actions">'
            . '<a class="button button--secondary button--small" href="' . e(url('patient/reschedule.php', ['id' => $id])) . '">' . icon('refresh') . 'Reschedule</a>'
            . confirm_action([
                'label' => 'Cancel booking',
                'icon' => 'x',
                'question' => 'Cancel this booking?',
                'detail' => $a['title'] . ' with ' . $a['docname'] . ' on ' . format_date($a['scheduledate']) . ' at ' . format_time($a['scheduletime']) . '. Your place will be released.',
                'confirm' => 'Yes, cancel booking',
                'action' => 'patient/appointment.php',
                'fields' => ['action' => 'cancel', 'id' => $id],
            ])
            . '</div>';
    }
    return $html . '</li>';
}

/** Render a session's date and time block used across lists. */
function session_when(string $date, string $time): string
{
    return '<span class="when">' . icon('calendar') . '<span><time datetime="' . e($date) . '">' . e(format_date($date)) . '</time>'
        . ' · <time datetime="' . e(substr($time, 0, 5)) . '">' . e(format_time($time)) . '</time></span></span>';
}

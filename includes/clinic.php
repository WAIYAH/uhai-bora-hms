<?php
/*
 * Shared logic for clinic staff (doctors and administrators): sessions,
 * appointment lists and status changes. Pass a doctor id to scope every query
 * to that doctor's own sessions; pass null for administrators.
 */

/** Validate the session form. Returns [values, errors]. */
function validate_session_input(bool $needsDoctor, int $minimumCapacity = 0): array
{
    $values = [
        'title' => post_string('title'),
        'date' => post_string('date'),
        'time' => post_string('time'),
        'nop' => post_string('nop'),
        'docid' => post_int('docid'),
    ];
    $errors = [];
    if (($error = validate_text($values['title'], 'a session title', 2, 255)) !== '') {
        $errors['title'] = $error;
    }
    if (!is_valid_date($values['date'])) {
        $errors['date'] = 'Enter a real date for the session.';
    } elseif ($values['date'] < today()) {
        $errors['date'] = 'The session date must be today or later.';
    }
    if (!is_valid_time($values['time'])) {
        $errors['time'] = 'Enter a start time, like 09:30.';
    } elseif (!isset($errors['date']) && !is_future_slot($values['date'], $values['time'])) {
        $errors['time'] = 'The start time has already passed. Choose a later time.';
    }
    $capacity = filter_var($values['nop'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 999]]);
    if ($capacity === false) {
        $errors['nop'] = 'Enter the number of places as a whole number from 1 to 999.';
    } elseif ($capacity < $minimumCapacity) {
        $errors['nop'] = 'This session already has ' . plural($minimumCapacity, 'active booking') . ', so it needs at least that many places.';
    }
    if ($needsDoctor && (!$values['docid'] || db_value('SELECT 1 FROM doctor WHERE docid = ?', [$values['docid']]) === null)) {
        $errors['docid'] = 'Choose the doctor for this session.';
    }
    $values['nop'] = $capacity === false ? 0 : (int) $capacity;
    $values['time'] = substr($values['time'], 0, 5);
    return [$values, $errors];
}

function session_form_fields(array $session = [], bool $withDoctor = false): string
{
    $html = '';
    if ($withDoctor) {
        $doctors = [];
        foreach (db_all('SELECT d.docid, d.docname, sp.sname FROM doctor d LEFT JOIN specialties sp ON sp.id = d.specialties ORDER BY d.docname') as $row) {
            $doctors[(int) $row['docid']] = $row['docname'] . ($row['sname'] ? ' — ' . $row['sname'] : '');
        }
        $html .= select_field(['name' => 'docid', 'label' => 'Doctor', 'placeholder' => 'Choose a doctor', 'options' => $doctors,
            'default' => isset($session['docid']) ? (string) $session['docid'] : '']);
    }
    $html .= input_field(['name' => 'title', 'label' => 'Session title', 'default' => $session['title'] ?? '',
        'hint' => 'Shown to patients, for example “General consultation” or “Follow-up clinic”.', 'attrs' => ['maxlength' => '255']]);
    $html .= '<div class="form-grid form-grid--2">';
    $html .= input_field(['name' => 'date', 'label' => 'Date', 'type' => 'date', 'default' => $session['scheduledate'] ?? '', 'attrs' => ['min' => today()]]);
    $html .= input_field(['name' => 'time', 'label' => 'Start time', 'type' => 'time', 'default' => isset($session['scheduletime']) ? substr($session['scheduletime'], 0, 5) : '',
        'hint' => 'East Africa Time.']);
    $html .= '</div>';
    $html .= input_field(['name' => 'nop', 'label' => 'Number of places', 'type' => 'number', 'default' => isset($session['nop']) ? (string) $session['nop'] : '',
        'hint' => 'The maximum number of patients who can book this session.', 'attrs' => ['min' => '1', 'max' => '999', 'inputmode' => 'numeric']]);
    return $html;
}

/** Sessions with booking counts. $filters: doctor, date, period (upcoming|past|all). */
function staff_sessions(array $filters, int $limit = 200): array
{
    $sql = "SELECT s.scheduleid, s.docid, s.title, s.scheduledate, s.scheduletime, s.nop, d.docname, sp.sname,
                   (SELECT COUNT(*) FROM appointment a WHERE a.scheduleid = s.scheduleid AND a.status IN " . ACTIVE_STATUSES_SQL . ") AS booked,
                   " . UPCOMING_SQL . " AS is_upcoming
            FROM schedule s
            INNER JOIN doctor d ON d.docid = s.docid
            LEFT JOIN specialties sp ON sp.id = d.specialties
            WHERE 1 = 1";
    $params = [];
    if (!empty($filters['doctor'])) {
        $sql .= ' AND s.docid = ?';
        $params[] = (int) $filters['doctor'];
    }
    if (!empty($filters['date'])) {
        $sql .= ' AND s.scheduledate = ?';
        $params[] = $filters['date'];
    }
    $period = $filters['period'] ?? 'upcoming';
    if ($period === 'upcoming') {
        $sql .= ' AND ' . UPCOMING_SQL . ' ORDER BY s.scheduledate, s.scheduletime';
    } elseif ($period === 'past') {
        $sql .= ' AND NOT ' . UPCOMING_SQL . ' ORDER BY s.scheduledate DESC, s.scheduletime DESC';
    } else {
        $sql .= ' ORDER BY s.scheduledate DESC, s.scheduletime DESC';
    }
    return db_all($sql . ' LIMIT ' . (int) $limit, $params);
}

/** Appointments for staff lists. $filters: doctor, date, status, period, patient. */
function staff_appointments(array $filters, int $limit = 300): array
{
    $sql = appointment_select_sql() . ' WHERE 1 = 1';
    $params = [];
    if (!empty($filters['doctor'])) {
        $sql .= ' AND s.docid = ?';
        $params[] = (int) $filters['doctor'];
    }
    if (!empty($filters['patient'])) {
        $sql .= ' AND a.pid = ?';
        $params[] = (int) $filters['patient'];
    }
    if (!empty($filters['session'])) {
        $sql .= ' AND s.scheduleid = ?';
        $params[] = (int) $filters['session'];
    }
    if (!empty($filters['date'])) {
        $sql .= ' AND s.scheduledate = ?';
        $params[] = $filters['date'];
    }
    if (!empty($filters['status']) && isset(appointment_statuses()[$filters['status']])) {
        $sql .= ' AND a.status = ?';
        $params[] = $filters['status'];
    }
    $period = $filters['period'] ?? 'upcoming';
    if ($period === 'upcoming') {
        $sql .= ' AND ' . UPCOMING_SQL . ' ORDER BY s.scheduledate, s.scheduletime, a.apponum';
    } elseif ($period === 'past') {
        $sql .= ' AND NOT ' . UPCOMING_SQL . ' ORDER BY s.scheduledate DESC, s.scheduletime DESC, a.apponum';
    } else {
        $sql .= ' ORDER BY s.scheduledate DESC, s.scheduletime DESC, a.apponum';
    }
    return db_all($sql . ' LIMIT ' . (int) $limit, $params);
}

/** Change an appointment's status; doctors can only change bookings in their own sessions. */
function update_appointment_status(int $appointmentId, string $status, ?int $doctorId): bool
{
    if (!isset(appointment_statuses()[$status])) {
        return false;
    }
    $sql = 'UPDATE appointment a INNER JOIN schedule s ON s.scheduleid = a.scheduleid SET a.status = ? WHERE a.appoid = ?';
    $params = [$status, $appointmentId];
    if ($doctorId !== null) {
        $sql .= ' AND s.docid = ?';
        $params[] = $doctorId;
    }
    db_exec($sql, $params);
    // Unchanged rows report 0 affected rows, so confirm the record exists and is in scope.
    $check = 'SELECT a.status FROM appointment a INNER JOIN schedule s ON s.scheduleid = a.scheduleid WHERE a.appoid = ?';
    $checkParams = [$appointmentId];
    if ($doctorId !== null) {
        $check .= ' AND s.docid = ?';
        $checkParams[] = $doctorId;
    }
    return db_value($check, $checkParams) === $status;
}

function delete_appointment(int $appointmentId, ?int $doctorId): bool
{
    $sql = 'DELETE a FROM appointment a INNER JOIN schedule s ON s.scheduleid = a.scheduleid WHERE a.appoid = ?';
    $params = [$appointmentId];
    if ($doctorId !== null) {
        $sql .= ' AND s.docid = ?';
        $params[] = $doctorId;
    }
    return db_exec($sql, $params) === 1;
}

/** Handle the shared appointment POST actions (status, delete). Returns true when handled. */
function handle_appointment_action(?int $doctorId): bool
{
    $action = post_string('action');
    $appointmentId = post_int('appoid');
    if (!$appointmentId || !in_array($action, ['status', 'delete'], true)) {
        return false;
    }
    if ($action === 'status') {
        $status = post_string('status');
        if (update_appointment_status($appointmentId, $status, $doctorId)) {
            audit('appointment.status_' . $status, 'appointment', $appointmentId);
            flash('success', 'Booking ' . appointment_reference($appointmentId) . ' is now “' . appointment_statuses()[$status] . '”.');
        } else {
            flash('error', 'That booking could not be updated. It may have been removed.');
        }
    } else {
        if (delete_appointment($appointmentId, $doctorId)) {
            audit('appointment.deleted', 'appointment', $appointmentId);
            flash('success', 'Booking ' . appointment_reference($appointmentId) . ' has been removed.');
        } else {
            flash('error', 'That booking could not be removed. It may already have been removed.');
        }
    }
    return true;
}

function status_select_form(array $appointment, string $returnPath): string
{
    $options = '';
    foreach (appointment_statuses() as $value => $label) {
        $options .= '<option value="' . e($value) . '"' . ($value === $appointment['status'] ? ' selected' : '') . '>' . e($label) . '</option>';
    }
    $id = 'status-' . (int) $appointment['appoid'];
    return '<form class="inline-form" method="post" action="' . e(url($returnPath)) . '">' . csrf_field()
        . '<input type="hidden" name="action" value="status"><input type="hidden" name="appoid" value="' . (int) $appointment['appoid'] . '">'
        . '<label class="visually-hidden" for="' . $id . '">Status for ' . e(appointment_reference((int) $appointment['appoid'])) . '</label>'
        . '<select class="input select" id="' . $id . '" name="status">' . $options . '</select>'
        . '<button class="button button--secondary button--small" type="submit">Save<span class="visually-hidden"> status for ' . e($appointment['pname']) . '</span></button></form>';
}

/** Appointment table used by doctors and administrators. */
function staff_appointment_table(array $rows, string $returnPath, string $patientViewPath, bool $showDoctor): string
{
    $html = '<div class="table-wrap"><table class="data-table"><thead><tr>'
        . '<th scope="col">Patient</th><th scope="col">Session</th>' . ($showDoctor ? '<th scope="col">Doctor</th>' : '')
        . '<th scope="col">Date and time</th><th scope="col">No.</th><th scope="col">Status</th><th scope="col"><span class="visually-hidden">Actions</span></th>'
        . '</tr></thead><tbody>';
    foreach ($rows as $row) {
        $id = (int) $row['appoid'];
        $html .= '<tr>'
            . '<th scope="row" data-label="Patient"><a href="' . e(url($patientViewPath, ['id' => (int) $row['pid']])) . '">' . e($row['pname']) . '</a><br><small class="muted nowrap">' . e(appointment_reference($id)) . '</small></th>'
            . '<td data-label="Session">' . e($row['title']) . '</td>'
            . ($showDoctor ? '<td data-label="Doctor">' . e($row['docname']) . '</td>' : '')
            . '<td data-label="Date and time">' . e(format_date($row['scheduledate'])) . '<br>' . e(format_time($row['scheduletime'])) . '</td>'
            . '<td data-label="Number">' . (int) $row['apponum'] . '</td>'
            . '<td data-label="Status">' . status_select_form($row, $returnPath) . '</td>'
            . '<td class="cell-actions"><div class="row-actions">' . confirm_action([
                'label' => 'Remove',
                'question' => 'Remove this booking?',
                'detail' => 'This deletes the booking record for ' . $row['pname'] . '. To keep a record, set the status to “Cancelled” instead.',
                'confirm' => 'Yes, remove booking',
                'action' => $returnPath,
                'fields' => ['action' => 'delete', 'appoid' => $id],
            ]) . '</div></td>'
            . '</tr>';
    }
    return $html . '</tbody></table></div>';
}

/** Table of sessions for staff. */
function staff_session_table(array $rows, string $basePath, bool $showDoctor): string
{
    $html = '<div class="table-wrap"><table class="data-table"><thead><tr>'
        . '<th scope="col">Session</th>' . ($showDoctor ? '<th scope="col">Doctor</th>' : '')
        . '<th scope="col">Date and time</th><th scope="col">Booked</th><th scope="col"><span class="visually-hidden">Actions</span></th>'
        . '</tr></thead><tbody>';
    foreach ($rows as $row) {
        $id = (int) $row['scheduleid'];
        $booked = (int) $row['booked'];
        $html .= '<tr>'
            . '<th scope="row" data-label="Session"><a href="' . e(url($basePath, ['id' => $id])) . '">' . e($row['title']) . '</a></th>'
            . ($showDoctor ? '<td data-label="Doctor">' . e($row['docname']) . '</td>' : '')
            . '<td data-label="Date and time">' . e(format_date($row['scheduledate'])) . '<br>' . e(format_time($row['scheduletime'])) . '</td>'
            . '<td data-label="Booked">' . $booked . ' of ' . (int) $row['nop']
            . ($booked >= (int) $row['nop'] ? ' <span class="badge badge--full">Full</span>' : '') . '</td>'
            . '<td class="cell-actions"><div class="row-actions">'
            . '<a class="button button--secondary button--small" href="' . e(url($basePath, ['id' => $id])) . '">' . icon('eye') . 'View<span class="visually-hidden"> ' . e($row['title']) . '</span></a>'
            . ((int) $row['is_upcoming'] === 1 ? '<a class="button button--secondary button--small" href="' . e(url($basePath, ['action' => 'edit', 'id' => $id])) . '">' . icon('edit') . 'Edit<span class="visually-hidden"> ' . e($row['title']) . '</span></a>' : '')
            . '</div></td></tr>';
    }
    return $html . '</tbody></table></div>';
}

function period_tabs(string $current, string $path, array $query = []): string
{
    $tabs = ['upcoming' => 'Upcoming', 'past' => 'Past', 'all' => 'All'];
    $html = '<ul class="tabs">';
    foreach ($tabs as $key => $label) {
        $html .= '<li><a href="' . e(url($path, array_merge($query, ['period' => $key === 'upcoming' ? null : $key]))) . '"'
            . ($current === $key ? ' aria-current="page"' : '') . '>' . e($label) . '</a></li>';
    }
    return $html . '</ul>';
}

<?php
/*
 * Right of access and data portability: a machine-readable copy of the
 * signed-in patient's account details and bookings (no password hash).
 */
require dirname(__DIR__) . '/includes/bootstrap.php';
$user = require_role('p');
$patientId = (int) $user['id'];

$patient = db_one(
    'SELECT pid, pname, pemail, ptel, paddress, pdob, pnic, pguardian_name, pconsent_version, pconsented_at, pcreated_at
     FROM patient WHERE pid = ?',
    [$patientId]
);
if (!$patient) {
    not_found();
}

$bookings = [];
foreach (db_all(appointment_select_sql() . ' WHERE a.pid = ? ORDER BY s.scheduledate, s.scheduletime', [$patientId]) as $row) {
    $bookings[] = [
        'reference' => appointment_reference((int) $row['appoid']),
        'appointment_number' => (int) $row['apponum'],
        'status' => $row['status'],
        'session' => $row['title'],
        'doctor' => $row['docname'],
        'specialty' => $row['sname'],
        'date' => $row['scheduledate'],
        'time' => substr($row['scheduletime'], 0, 5),
        'booked_on' => $row['appodate'],
    ];
}

$export = [
    'exported_at' => date('c'),
    'service' => 'Uhai Bora',
    'account' => [
        'patient_id' => (int) $patient['pid'],
        'name' => $patient['pname'],
        'email' => $patient['pemail'],
        'phone' => $patient['ptel'],
        'town_or_address' => $patient['paddress'],
        'date_of_birth' => $patient['pdob'],
        'id_or_passport_number' => $patient['pnic'],
        'parent_or_guardian' => $patient['pguardian_name'],
        'consent_version' => $patient['pconsent_version'],
        'consented_at' => $patient['pconsented_at'],
        'account_created_at' => $patient['pcreated_at'],
    ],
    'bookings' => $bookings,
];

audit('patient.data_exported', 'patient', $patientId);
while (ob_get_level() > 0) {
    ob_end_clean();
}
header('Content-Type: application/json; charset=utf-8');
header('Content-Disposition: attachment; filename="uhai-bora-my-information-' . date('Y-m-d') . '.json"');
echo json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

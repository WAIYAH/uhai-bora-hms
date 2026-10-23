<?php
session_start();

if (($_SESSION['usertype'] ?? '') !== 'd' || empty($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: appointment.php');
    exit;
}

require_once '../connection.php';
$appointmentId = filter_input(INPUT_POST, 'appoid', FILTER_VALIDATE_INT);
$status = $_POST['status'] ?? '';
$allowedStatuses = ['pending', 'confirmed', 'completed', 'cancelled'];

if (!$appointmentId || !in_array($status, $allowedStatuses, true)) {
    header('Location: appointment.php?action=invalid-update');
    exit;
}

$update = $database->prepare('UPDATE appointment SET status = ? WHERE appoid = ? AND scheduleid IN (SELECT scheduleid FROM schedule WHERE docid = (SELECT docid FROM doctor WHERE docemail = ?))');
$update->bind_param('sis', $status, $appointmentId, $_SESSION['user']);

if (!$update->execute()) {
    error_log('Unable to update appointment status: ' . $database->error);
    http_response_code(500);
    exit('Unable to update the appointment.');
}

header('Location: appointment.php?action=appointment-updated');
exit;

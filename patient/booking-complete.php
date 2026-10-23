<?php
session_start();

if (($_SESSION['usertype'] ?? '') !== 'p' || empty($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: schedule.php');
    exit;
}

require_once '../connection.php';
$scheduleId = filter_input(INPUT_POST, 'scheduleid', FILTER_VALIDATE_INT);
$patientLookup = $database->prepare('SELECT pid FROM patient WHERE pemail = ?');
$patientLookup->bind_param('s', $_SESSION['user']);
$patientLookup->execute();
$patient = $patientLookup->get_result()->fetch_assoc();

if (!$patient || !$scheduleId) {
    header('Location: schedule.php?action=booking-error');
    exit;
}

$database->begin_transaction();
$scheduleLookup = $database->prepare('SELECT nop FROM schedule WHERE scheduleid = ? FOR UPDATE');
$scheduleLookup->bind_param('i', $scheduleId);
$scheduleLookup->execute();
$schedule = $scheduleLookup->get_result()->fetch_assoc();

if (!$schedule) {
    $database->rollback();
    header('Location: schedule.php?action=booking-error');
    exit;
}

$countLookup = $database->prepare('SELECT apponum FROM appointment WHERE scheduleid = ?');
$countLookup->bind_param('i', $scheduleId);
$countLookup->execute();
$occupiedNumbers = [];
$reservations = $countLookup->get_result();
while ($reservation = $reservations->fetch_assoc()) {
    $occupiedNumbers[(int) $reservation['apponum']] = true;
}

$appointmentNumber = 1;
while (isset($occupiedNumbers[$appointmentNumber]) && $appointmentNumber <= (int) $schedule['nop']) {
    $appointmentNumber++;
}

if ($appointmentNumber > (int) $schedule['nop']) {
    $database->rollback();
    header('Location: schedule.php?action=booking-full');
    exit;
}

$bookingDate = date('Y-m-d');
$insert = $database->prepare('INSERT INTO appointment (pid, apponum, scheduleid, appodate) VALUES (?, ?, ?, ?)');
$insert->bind_param('iiis', $patient['pid'], $appointmentNumber, $scheduleId, $bookingDate);

if (!$insert->execute()) {
    $database->rollback();
    error_log('Unable to save appointment: ' . $database->error);
    http_response_code(500);
    exit('Unable to save your appointment.');
}

$database->commit();
header('Location: appointment.php?action=booking-added&id=' . $appointmentNumber);
exit;

<?php
session_start();

if (($_SESSION['usertype'] ?? '') !== 'a' || empty($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: schedule.php');
    exit;
}

require_once '../connection.php';
$id = filter_input(INPUT_POST, 'scheduleid', FILTER_VALIDATE_INT);
$title = trim($_POST['title'] ?? '');
$capacity = filter_input(INPUT_POST, 'nop', FILTER_VALIDATE_INT);
$date = $_POST['date'] ?? '';
$time = $_POST['time'] ?? '';
$dateObject = DateTime::createFromFormat('Y-m-d', $date);
$validDate = $dateObject && $dateObject->format('Y-m-d') === $date && $date >= date('Y-m-d');
$validTime = preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $time) === 1;

if (!$id || $title === '' || !$capacity || !$validDate || !$validTime) {
    header('Location: schedule.php?action=invalid-session');
    exit;
}

$database->begin_transaction();
$sessionLookup = $database->prepare('SELECT scheduleid FROM schedule WHERE scheduleid = ? FOR UPDATE');
$sessionLookup->bind_param('i', $id);
$sessionLookup->execute();

if ($sessionLookup->get_result()->num_rows !== 1) {
    $database->rollback();
    header('Location: schedule.php');
    exit;
}

$bookingCount = $database->prepare('SELECT COUNT(*) AS booked FROM appointment WHERE scheduleid = ?');
$bookingCount->bind_param('i', $id);
$bookingCount->execute();
$booked = (int) $bookingCount->get_result()->fetch_assoc()['booked'];

if ($capacity < $booked) {
    $database->rollback();
    header('Location: schedule.php?action=capacity-too-low');
    exit;
}

$update = $database->prepare('UPDATE schedule SET title = ?, scheduledate = ?, scheduletime = ?, nop = ? WHERE scheduleid = ?');
$update->bind_param('sssii', $title, $date, $time, $capacity, $id);

if (!$update->execute()) {
    $database->rollback();
    error_log('Unable to update schedule: ' . $database->error);
    http_response_code(500);
    exit('Unable to update the session.');
}

$database->commit();
header('Location: schedule.php?action=session-updated');
exit;

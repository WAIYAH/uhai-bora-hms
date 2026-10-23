<?php
session_start();

if (($_SESSION['usertype'] ?? '') !== 'd' || empty($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: schedule.php');
    exit;
}

require_once '../connection.php';
$title = trim($_POST['title'] ?? '');
$capacity = filter_input(INPUT_POST, 'nop', FILTER_VALIDATE_INT);
$date = $_POST['date'] ?? '';
$time = $_POST['time'] ?? '';
$dateObject = DateTime::createFromFormat('Y-m-d', $date);
$validDate = $dateObject && $dateObject->format('Y-m-d') === $date && $date >= date('Y-m-d');
$validTime = preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $time) === 1;
$email = $_SESSION['user'];

if ($title === '' || !$capacity || !$validDate || !$validTime) {
    header('Location: schedule.php?action=invalid-session');
    exit;
}

$insert = $database->prepare('INSERT INTO schedule (docid, title, scheduledate, scheduletime, nop) SELECT docid, ?, ?, ?, ? FROM doctor WHERE docemail = ?');
$insert->bind_param('sssis', $title, $date, $time, $capacity, $email);

if (!$insert->execute() || $insert->affected_rows !== 1) {
    error_log('Unable to create doctor session: ' . $database->error);
    http_response_code(500);
    exit('Unable to create the session.');
}

header('Location: schedule.php?action=session-added');
exit;

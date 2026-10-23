<?php
session_start();

if (($_SESSION['usertype'] ?? '') !== 'p' || empty($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}

require_once '../connection.php';
$email = $_SESSION['user'];
$delete = $database->prepare("DELETE FROM webuser WHERE email = ? AND usertype = 'p'");
$delete->bind_param('s', $email);

if (!$delete->execute() || $delete->affected_rows !== 1) {
    error_log('Unable to delete patient account: ' . $database->error);
    http_response_code(500);
    exit('Unable to delete your account.');
}

header('Location: ../logout.php');
exit;

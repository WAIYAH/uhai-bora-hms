<?php
session_start();

if (($_SESSION['usertype'] ?? '') !== 'd' || empty($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}

require_once '../connection.php';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$delete = $database->prepare('DELETE FROM webuser WHERE email = ? AND usertype = ? AND EXISTS (SELECT 1 FROM doctor WHERE docid = ? AND docemail = ?)');
$role = 'd';
$delete->bind_param('ssis', $_SESSION['user'], $role, $id, $_SESSION['user']);

if ($id && $delete->execute() && $delete->affected_rows === 1) {
    header('Location: ../logout.php');
    exit;
}

header('Location: settings.php');
exit;

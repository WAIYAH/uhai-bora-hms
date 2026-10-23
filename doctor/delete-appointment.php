<?php
session_start();

if (($_SESSION['usertype'] ?? '') !== 'd' || empty($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}

require_once '../connection.php';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($id) {
    $delete = $database->prepare('DELETE FROM appointment WHERE appoid = ? AND scheduleid IN (SELECT scheduleid FROM schedule WHERE docid = (SELECT docid FROM doctor WHERE docemail = ?))');
    $delete->bind_param('is', $id, $_SESSION['user']);
    $delete->execute();
}

header('Location: appointment.php');
exit;

<?php
session_start();

if (($_SESSION['usertype'] ?? '') !== 'p' || empty($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}

require_once '../connection.php';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($id) {
    $delete = $database->prepare('DELETE FROM appointment WHERE appoid = ? AND pid = (SELECT pid FROM patient WHERE pemail = ?)');
    $delete->bind_param('is', $id, $_SESSION['user']);
    $delete->execute();
}

header('Location: appointment.php');
exit;

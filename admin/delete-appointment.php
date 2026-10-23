<?php
session_start();

if (($_SESSION['usertype'] ?? '') !== 'a' || empty($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}

require_once '../connection.php';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($id) {
    $delete = $database->prepare('DELETE FROM appointment WHERE appoid = ?');
    $delete->bind_param('i', $id);
    $delete->execute();
}

header('Location: appointment.php');
exit;

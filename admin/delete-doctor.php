<?php
session_start();

if (($_SESSION['usertype'] ?? '') !== 'a' || empty($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}

require_once '../connection.php';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($id) {
    $lookup = $database->prepare('SELECT docemail FROM doctor WHERE docid = ?');
    $lookup->bind_param('i', $id);
    $lookup->execute();
    $doctor = $lookup->get_result()->fetch_assoc();

    if ($doctor) {
        $delete = $database->prepare('DELETE FROM webuser WHERE email = ? AND usertype = ?');
        $role = 'd';
        $delete->bind_param('ss', $doctor['docemail'], $role);
        $delete->execute();
    }
}

header('Location: doctors.php');
exit;

<?php
session_start();

if (($_SESSION['usertype'] ?? '') !== 'a' || empty($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}

require_once '../connection.php';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($id) {
    $lookup = $database->prepare('SELECT pemail FROM patient WHERE pid = ?');
    $lookup->bind_param('i', $id);
    $lookup->execute();
    $patient = $lookup->get_result()->fetch_assoc();

    if ($patient) {
        $delete = $database->prepare('DELETE FROM webuser WHERE email = ? AND usertype = ?');
        $role = 'p';
        $delete->bind_param('ss', $patient['pemail'], $role);
        $delete->execute();
    }
}

header('Location: patient.php');
exit;

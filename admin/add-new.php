<?php
session_start();

if (($_SESSION['usertype'] ?? '') !== 'a' || empty($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: doctors.php');
    exit;
}

require_once '../connection.php';

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$nic = trim($_POST['nic'] ?? '');
$telephone = trim($_POST['Tele'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['cpassword'] ?? '';
$specialty = filter_input(INPUT_POST, 'spec', FILTER_VALIDATE_INT);
$error = '3';

if ($name !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) && $nic !== '' && $telephone !== '' && $specialty && $password !== '') {
    if ($password !== $confirmPassword) {
        $error = '2';
    } else {
        $check = $database->prepare('SELECT 1 FROM webuser WHERE email = ?');
        $check->bind_param('s', $email);
        $check->execute();

        if ($check->get_result()->num_rows > 0) {
            $error = '1';
        } else {
            $database->begin_transaction();
            $user = $database->prepare("INSERT INTO webuser (email, usertype) VALUES (?, 'd')");
            $user->bind_param('s', $email);
            $doctor = $database->prepare('INSERT INTO doctor (docemail, docname, docpassword, docnic, doctel, specialties) VALUES (?, ?, ?, ?, ?, ?)');
            $doctor->bind_param('sssssi', $email, $name, $password, $nic, $telephone, $specialty);

            if ($user->execute() && $doctor->execute()) {
                $database->commit();
                $error = '4';
            } else {
                $database->rollback();
                error_log('Unable to create doctor account: ' . $database->error);
                http_response_code(500);
                exit('Unable to create the doctor account.');
            }
        }
    }
}

header('Location: doctors.php?action=add&error=' . $error);
exit;

<?php
session_start();

if (($_SESSION['usertype'] ?? '') !== 'p' || empty($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}

require_once '../connection.php';

$id = filter_input(INPUT_POST, 'id00', FILTER_VALIDATE_INT);
$oldEmail = trim($_POST['oldemail'] ?? '');
$email = trim($_POST['email'] ?? '');
$name = trim($_POST['name'] ?? '');
$nic = trim($_POST['nic'] ?? '');
$telephone = trim($_POST['Tele'] ?? '');
$address = trim($_POST['address'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['cpassword'] ?? '';
$error = '3';

if ($id && $oldEmail === $_SESSION['user'] && $name !== '' && $nic !== '' && $telephone !== '' && $address !== '' && $password !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
    if ($password !== $confirmPassword) {
        $error = '2';
    } else {
        $owner = $database->prepare('SELECT 1 FROM patient WHERE pid = ? AND pemail = ?');
        $owner->bind_param('is', $id, $oldEmail);
        $owner->execute();

        $duplicate = $database->prepare('SELECT 1 FROM webuser WHERE email = ? AND email <> ?');
        $duplicate->bind_param('ss', $email, $oldEmail);
        $duplicate->execute();

        if ($owner->get_result()->num_rows !== 1) {
            $error = '3';
        } elseif ($duplicate->get_result()->num_rows > 0) {
            $error = '1';
        } else {
            $database->begin_transaction();
            $updateUser = $database->prepare('UPDATE webuser SET email = ? WHERE email = ?');
            $updateUser->bind_param('ss', $email, $oldEmail);
            $updatePatient = $database->prepare('UPDATE patient SET pname = ?, ppassword = ?, pnic = ?, ptel = ?, paddress = ? WHERE pid = ?');
            $updatePatient->bind_param('sssssi', $name, $password, $nic, $telephone, $address, $id);

            if ($updateUser->execute() && $updatePatient->execute()) {
                $database->commit();
                $_SESSION['user'] = $email;
                $_SESSION['username'] = $name;
                $error = '4';
            } else {
                $database->rollback();
                error_log('Unable to update patient account: ' . $database->error);
                http_response_code(500);
                exit('Unable to update your account.');
            }
        }
    }
}

header('Location: settings.php?action=edit&error=' . $error . '&id=' . urlencode((string) $id));
exit;

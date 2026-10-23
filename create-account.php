<?php
session_start();
date_default_timezone_set('Asia/Colombo');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: signup.php');
    exit;
}

$submittedToken = $_POST['csrf_token'] ?? '';
$sessionToken = $_SESSION['signup_csrf'] ?? '';
if (!is_string($submittedToken) || !is_string($sessionToken) || $sessionToken === '' || !hash_equals($sessionToken, $submittedToken)) {
    $_SESSION['signup_error'] = 'Your registration session expired. Please review your details and try again.';
    header('Location: signup.php');
    exit;
}

$fields = ['fname', 'lname', 'address', 'nic', 'dob', 'newemail', 'tele'];
$values = [];
foreach ($fields as $field) {
    $postedValue = $_POST[$field] ?? '';
    $values[$field] = is_string($postedValue) ? trim($postedValue) : '';
}

$firstName = $values['fname'];
$lastName = $values['lname'];
$name = trim($firstName . ' ' . $lastName);
$address = $values['address'];
$nic = $values['nic'];
$dob = $values['dob'];
$email = $values['newemail'];
$telephone = $values['tele'];
$password = $_POST['newpassword'] ?? '';
$confirmPassword = $_POST['cpassword'] ?? '';
$acceptedTerms = ($_POST['accept_terms'] ?? '') === '1';
$dateOfBirth = DateTime::createFromFormat('!Y-m-d', $dob);
$validDate = $dateOfBirth instanceof DateTime
    && $dateOfBirth->format('Y-m-d') === $dob
    && $dob <= date('Y-m-d');

$error = '';
if (
    $firstName === '' || $lastName === '' || $address === '' || $nic === ''
    || $telephone === '' || strlen($address) > 255 || strlen($nic) > 32
    || strlen($telephone) > 32 || strlen($name) > 255
    || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255
    || !$validDate
) {
    $error = 'Please check your details and enter valid information in every field.';
} elseif (!is_string($password) || strlen($password) < 10) {
    $error = 'Choose a password with at least 10 characters.';
} elseif (!is_string($confirmPassword) || !hash_equals($password, $confirmPassword)) {
    $error = 'Password confirmation does not match.';
} elseif (!$acceptedTerms) {
    $error = 'Please agree to the Terms of use to create your account.';
}

if ($error === '') {
    require_once 'connection.php';
    $check = $database->prepare('SELECT 1 FROM webuser WHERE email = ?');
    $check->bind_param('s', $email);
    $check->execute();

    if ($check->get_result()->num_rows > 0) {
        $error = 'An account already exists for this email address. Try logging in instead.';
    } else {
        $database->begin_transaction();
        $user = $database->prepare("INSERT INTO webuser (email, usertype) VALUES (?, 'p')");
        $user->bind_param('s', $email);
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $patient = $database->prepare('INSERT INTO patient (pemail, pname, ppassword, paddress, pnic, pdob, ptel) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $patient->bind_param('sssssss', $email, $name, $passwordHash, $address, $nic, $dob, $telephone);

        if ($user->execute() && $patient->execute()) {
            $database->commit();
            session_regenerate_id(true);
            $_SESSION['user'] = $email;
            $_SESSION['usertype'] = 'p';
            $_SESSION['username'] = $name;
            unset($_SESSION['signup_csrf'], $_SESSION['signup_error'], $_SESSION['signup_values']);
            header('Location: patient/index.php');
            exit;
        }

        $database->rollback();
        error_log('Unable to create patient account: ' . $database->error);
        http_response_code(500);
        exit('Unable to create your account. Please try again.');
    }
}

$_SESSION['signup_error'] = $error;
$_SESSION['signup_values'] = $values;
header('Location: signup.php');
exit;

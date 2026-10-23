<?php
session_start();

if (empty($_SESSION['personal']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: signup.php');
    exit;
}

require_once 'connection.php';

$personal = $_SESSION['personal'];
$name = trim(($personal['fname'] ?? '') . ' ' . ($personal['lname'] ?? ''));
$address = trim($personal['address'] ?? '');
$nic = trim($personal['nic'] ?? '');
$dob = $personal['dob'] ?? '';
$email = trim($_POST['newemail'] ?? '');
$telephone = trim($_POST['tele'] ?? '');
$password = $_POST['newpassword'] ?? '';
$confirmPassword = $_POST['cpassword'] ?? '';

if ($password !== $confirmPassword || $name === '' || $address === '' || $nic === '' || $dob === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = $password !== $confirmPassword ? 'Password confirmation does not match.' : 'Please check your details and try again.';
} else {
    $check = $database->prepare('SELECT 1 FROM webuser WHERE email = ?');
    $check->bind_param('s', $email);
    $check->execute();

    if ($check->get_result()->num_rows > 0) {
        $error = 'An account already exists for this email address.';
    } else {
        $database->begin_transaction();
        $user = $database->prepare("INSERT INTO webuser (email, usertype) VALUES (?, 'p')");
        $user->bind_param('s', $email);
        $patient = $database->prepare('INSERT INTO patient (pemail, pname, ppassword, paddress, pnic, pdob, ptel) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $patient->bind_param('sssssss', $email, $name, $password, $address, $nic, $dob, $telephone);

        if ($user->execute() && $patient->execute()) {
            $database->commit();
            $_SESSION['user'] = $email;
            $_SESSION['usertype'] = 'p';
            $_SESSION['username'] = $name;
            unset($_SESSION['personal']);
            header('Location: patient/index.php');
            exit;
        }

        $database->rollback();
        error_log('Unable to create patient account: ' . $database->error);
        http_response_code(500);
        exit('Unable to create your account. Please try again.');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/signup.css">
    <link rel="stylesheet" href="css/index.css">
    <title>Uhai Bora | Create account</title>
</head>
<body>
    <center>
        <div class="container">
            <a class="brand" href="index.html"><span class="brand-mark">u</span><span>uhai <strong>bora</strong></span></a>
            <p class="header-text">Let's get started</p>
            <p class="sub-text"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
            <a class="hover-link1 non-style-link" href="signup.php">Back to sign up</a>
        </div>
    </center>
</body>
</html>

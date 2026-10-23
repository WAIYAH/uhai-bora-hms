<?php
session_start();
date_default_timezone_set('Asia/Colombo');
$_SESSION['date'] = date('Y-m-d');
require_once 'connection.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['useremail'] ?? '');
    $password = $_POST['userpassword'] ?? '';
    $lookup = $database->prepare('SELECT usertype FROM webuser WHERE email = ?');
    $lookup->bind_param('s', $email);
    $lookup->execute();
    $account = $lookup->get_result()->fetch_assoc();

    $tables = [
        'a' => ['admin', 'aemail', 'apassword', 'admin/index.php'],
        'd' => ['doctor', 'docemail', 'docpassword', 'doctor/index.php'],
        'p' => ['patient', 'pemail', 'ppassword', 'patient/index.php'],
    ];

    if ($account && isset($tables[$account['usertype']])) {
        [$table, $emailColumn, $passwordColumn, $destination] = $tables[$account['usertype']];
        $credentialQuery = $database->prepare("SELECT {$passwordColumn} AS password FROM {$table} WHERE {$emailColumn} = ?");
        $credentialQuery->bind_param('s', $email);
        $credentialQuery->execute();
        $credential = $credentialQuery->get_result()->fetch_assoc();
        $storedPassword = (string) ($credential['password'] ?? '');
        $passwordMatches = $storedPassword !== '' && (
            password_verify($password, $storedPassword) ||
            hash_equals($storedPassword, $password)
        );

        if ($passwordMatches) {
            session_regenerate_id(true);
            $_SESSION['user'] = $email;
            $_SESSION['usertype'] = $account['usertype'];
            header('Location: ' . $destination);
            exit;
        }
    }

    $error = 'We could not verify those details. Check your email and password and try again.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="css/animations.css">
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/login.css">
    <link rel="stylesheet" href="css/index.css">
    <title>Uhai Bora | Log in</title>
</head>
<body class="login-page">
    <center>
        <div class="container">
            <a href="index.html" class="brand login-brand"><span class="brand-mark">u</span><span>uhai <strong>bora</strong></span></a>
            <p class="header-text">Welcome back</p>
            <p class="sub-text">Log in to continue to your care.</p>
            <form method="post" action="login.php" class="login-form">
                <label class="form-label" for="useremail">Email</label>
                <input id="useremail" type="email" name="useremail" class="input-text" autocomplete="username" required>
                <label class="form-label" for="userpassword">Password</label>
                <input id="userpassword" type="password" name="userpassword" class="input-text" autocomplete="current-password" required>
                <?php if ($error !== ''): ?>
                    <p class="form-error" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endif; ?>
                <button type="submit" class="login-btn btn-primary btn">Log in</button>
            </form>
            <p class="sub-text">New to Uhai Bora? <a href="signup.php" class="hover-link1 non-style-link">Create an account</a></p>
        </div>
    </center>
</body>
</html>

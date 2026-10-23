<?php
session_start();
date_default_timezone_set('Asia/Colombo');
$_SESSION['date'] = date('Y-m-d');
require_once 'connection.php';

$error = '';
$csrfToken = $_SESSION['login_csrf'] ?? '';
if ($csrfToken === '') {
    $csrfToken = bin2hex(random_bytes(32));
    $_SESSION['login_csrf'] = $csrfToken;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!is_string($submittedToken) || !hash_equals($csrfToken, $submittedToken)) {
        $error = 'Your sign-in session expired. Refresh this page and try again.';
    } else {
        $submittedEmail = $_POST['useremail'] ?? '';
        $submittedPassword = $_POST['userpassword'] ?? '';
        $email = is_string($submittedEmail) ? trim($submittedEmail) : '';
        $password = is_string($submittedPassword) ? $submittedPassword : '';
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
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/auth.css">
    <script src="js/auth.js" defer></script>
    <title>Log in | Uhai Bora</title>
</head>
<body class="auth-page">
    <main class="auth-layout">
        <section class="auth-panel">
            <a href="index.html" class="brand auth-brand" aria-label="Uhai Bora home">
                <img class="brand-logo" src="assets/brand/uhai-bora.svg" alt="Uhai Bora">
            </a>
            <a class="auth-back-link" href="index.html"><span aria-hidden="true">←</span> Back to home</a>
            <div class="auth-content">
                <p class="auth-eyebrow">YOUR CARE, ALL IN ONE PLACE</p>
                <h1>Welcome<br>back.</h1>
                <p class="auth-intro">Sign in to find doctors, explore available sessions, and keep up with your appointments.</p>
                <form method="post" action="login.php" class="auth-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                    <label class="auth-label" for="useremail">Email address</label>
                    <input id="useremail" type="email" name="useremail" class="auth-input" autocomplete="username" placeholder="you@example.com" required>
                    <label class="auth-label" for="userpassword">Password</label>
                    <div class="password-field">
                        <input id="userpassword" type="password" name="userpassword" class="auth-input" autocomplete="current-password" placeholder="Enter your password" required>
                        <button class="password-toggle" type="button" data-password-toggle="userpassword" aria-label="Show password">Show</button>
                    </div>
                <?php if ($error !== ''): ?>
                    <p class="auth-error" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endif; ?>
                    <button type="submit" class="auth-submit">Log in <span aria-hidden="true">↗</span></button>
                </form>
                <p class="auth-switch">New to Uhai Bora? <a href="signup.php">Create a patient account</a></p>
            </div>
            <footer class="auth-footer">
                <a href="privacy.html">Privacy</a>
                <a href="terms.html">Terms</a>
                <span>© <?php echo date('Y'); ?> Uhai Bora</span>
            </footer>
        </section>
        <aside class="auth-visual auth-visual-login" aria-label="A doctor listening to a patient">
            <img src="assets/photos/doctor-consultation.jpg" alt="A clinician offering a reassuring hand to a patient">
            <div class="auth-visual-overlay">
                <span class="auth-visual-kicker">CARE STARTS WITH CONNECTION</span>
                <p>Find the right care,<br><em>one step at a time.</em></p>
                <span class="auth-visual-caption">Explore doctors and appointment sessions in one place.</span>
            </div>
            <a class="auth-image-credit" href="https://unsplash.com/" target="_blank" rel="noopener noreferrer">Photo via Unsplash</a>
        </aside>
    </main>
</body>
</html>

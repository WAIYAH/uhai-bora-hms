<?php
session_start();

date_default_timezone_set('Asia/Colombo');
$_SESSION['date'] = date('Y-m-d');

if (empty($_SESSION['signup_csrf'])) {
    $_SESSION['signup_csrf'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['signup_csrf'];
$error = $_SESSION['signup_error'] ?? '';
$values = $_SESSION['signup_values'] ?? [];
unset($_SESSION['signup_error'], $_SESSION['signup_values']);

function signupValue(array $values, string $key): string
{
    return htmlspecialchars((string) ($values[$key] ?? ''), ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Create a Uhai Bora patient account to browse doctors, find sessions, and manage appointments.">
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/auth.css">
    <script src="js/auth.js" defer></script>
    <title>Create your account | Uhai Bora</title>
</head>
<body class="auth-page auth-page-register">
    <main class="auth-layout auth-layout-register">
        <section class="auth-panel">
            <a href="index.html" class="brand auth-brand" aria-label="Uhai Bora home">
                <img class="brand-logo" src="assets/brand/uhai-bora.svg" alt="Uhai Bora">
            </a>
            <a class="auth-back-link" href="index.html"><span aria-hidden="true">←</span> Back to home</a>
            <div class="auth-content auth-content-register">
                <p class="auth-eyebrow">A BETTER WAY TO FIND CARE</p>
                <h1>Let’s get<br>you started.</h1>
                <p class="auth-intro">Create your patient account to find a doctor and manage your appointments.</p>
                <form method="post" action="create-account.php" class="auth-form register-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                    <?php if ($error !== ''): ?>
                        <p class="auth-error" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php endif; ?>
                    <div class="auth-field-grid">
                        <div class="auth-field">
                            <label class="auth-label" for="fname">First name</label>
                            <input id="fname" type="text" name="fname" class="auth-input" autocomplete="given-name" maxlength="120" value="<?php echo signupValue($values, 'fname'); ?>" required>
                        </div>
                        <div class="auth-field">
                            <label class="auth-label" for="lname">Last name</label>
                            <input id="lname" type="text" name="lname" class="auth-input" autocomplete="family-name" maxlength="120" value="<?php echo signupValue($values, 'lname'); ?>" required>
                        </div>
                        <div class="auth-field auth-field-wide">
                            <label class="auth-label" for="newemail">Email address</label>
                            <input id="newemail" type="email" name="newemail" class="auth-input" autocomplete="email" maxlength="255" placeholder="you@example.com" value="<?php echo signupValue($values, 'newemail'); ?>" required>
                        </div>
                        <div class="auth-field">
                            <label class="auth-label" for="tele">Phone number</label>
                            <input id="tele" type="tel" name="tele" class="auth-input" autocomplete="tel" maxlength="32" value="<?php echo signupValue($values, 'tele'); ?>" required>
                        </div>
                        <div class="auth-field">
                            <label class="auth-label" for="dob">Date of birth</label>
                            <input id="dob" type="date" name="dob" class="auth-input" autocomplete="bday" max="<?php echo date('Y-m-d'); ?>" value="<?php echo signupValue($values, 'dob'); ?>" required>
                        </div>
                        <div class="auth-field auth-field-wide">
                            <label class="auth-label" for="address">Address</label>
                            <input id="address" type="text" name="address" class="auth-input" autocomplete="street-address" maxlength="255" value="<?php echo signupValue($values, 'address'); ?>" required>
                        </div>
                        <div class="auth-field auth-field-wide">
                            <label class="auth-label" for="nic">National identification number</label>
                            <input id="nic" type="text" name="nic" class="auth-input" autocomplete="off" maxlength="32" value="<?php echo signupValue($values, 'nic'); ?>" required>
                        </div>
                        <div class="auth-field auth-field-wide">
                            <label class="auth-label" for="newpassword">Create password</label>
                            <div class="password-field">
                                <input id="newpassword" type="password" name="newpassword" class="auth-input" autocomplete="new-password" minlength="10" aria-describedby="password-hint" required>
                                <button class="password-toggle" type="button" data-password-toggle="newpassword" aria-label="Show password">Show</button>
                            </div>
                            <span class="auth-hint" id="password-hint">Use at least 10 characters.</span>
                        </div>
                        <div class="auth-field auth-field-wide">
                            <label class="auth-label" for="cpassword">Confirm password</label>
                            <div class="password-field">
                                <input id="cpassword" type="password" name="cpassword" class="auth-input" autocomplete="new-password" minlength="10" required>
                                <button class="password-toggle" type="button" data-password-toggle="cpassword" aria-label="Show password">Show</button>
                            </div>
                        </div>
                    </div>
                    <label class="auth-consent">
                        <input type="checkbox" name="accept_terms" value="1" required>
                        <span>I agree to the <a href="terms.html">Terms of use</a> and have read the <a href="privacy.html">Privacy policy</a>.</span>
                    </label>
                    <button type="submit" class="auth-submit">Create patient account <span aria-hidden="true">↗</span></button>
                </form>
                <p class="auth-switch">Already have an account? <a href="login.php">Log in</a></p>
            </div>
            <footer class="auth-footer">
                <a href="privacy.html">Privacy</a>
                <a href="terms.html">Terms</a>
                <span>© <?php echo date('Y'); ?> Uhai Bora</span>
            </footer>
        </section>
        <aside class="auth-visual auth-visual-register" aria-label="A clinician talking with a patient">
            <img src="assets/photos/patient-care.jpg" alt="A clinician discussing care with a patient">
            <div class="auth-visual-overlay">
                <span class="auth-visual-kicker">START WITH WHAT MATTERS</span>
                <p>Your care journey,<br><em>at your pace.</em></p>
                <span class="auth-visual-caption">One account to explore doctors, sessions, and your bookings.</span>
            </div>
            <a class="auth-image-credit" href="https://www.pexels.com/" target="_blank" rel="noopener noreferrer">Photo via Pexels</a>
        </aside>
    </main>
</body>
</html>

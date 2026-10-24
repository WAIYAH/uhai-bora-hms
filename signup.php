<?php
require __DIR__ . '/includes/bootstrap.php';

$next = is_post() ? post_string('next') : query_string('next');

$user = current_user();
if ($user) {
    flash('info', 'You are already signed in.');
    redirect(role_info($user['role'])['home']);
}

if (is_post()) {
    $firstName = post_string('first_name');
    $lastName = post_string('last_name');
    $dob = post_string('dob');
    $email = post_string('email');
    $phone = preg_replace('/\s+/', ' ', post_string('phone'));
    $address = post_string('address');
    $idNumber = post_string('id_number');
    $guardianName = post_string('guardian_name');
    $password = post_secret('password');
    $confirmation = post_secret('password_confirmation');

    $errors = array_filter([
        'first_name' => validate_person_name($firstName, 'first name'),
        'last_name' => validate_person_name($lastName, 'last name'),
        'dob' => validate_date_of_birth($dob),
        'email' => validate_email_address($email),
        'phone' => validate_phone($phone),
        'address' => validate_text($address, 'your town or address', 2, 255),
        'id_number' => validate_id_number($idNumber),
    ]);
    $passwordError = new_password_error($password, $confirmation, $email);
    if ($passwordError !== '') {
        $errors[$passwordError === 'The passwords do not match.' ? 'password_confirmation' : 'password'] = $passwordError;
    }

    $isMinor = !isset($errors['dob']) && age_on($dob) < 18;
    if ($isMinor) {
        $guardianError = validate_full_name($guardianName, 'parent or guardian’s full name');
        if ($guardianError !== '') {
            $errors['guardian_name'] = $guardianError;
        }
        if (post_string('guardian_confirm') !== '1') {
            $errors['guardian_confirm'] = 'Confirm that you are the parent or legal guardian of the person being registered.';
        }
    }
    if (post_string('accept_terms') !== '1') {
        $errors['accept_terms'] = 'Agree to the Terms of use to create an account.';
    }
    if (post_string('consent_privacy') !== '1') {
        $errors['consent_privacy'] = 'Confirm you agree to your information being used to manage your appointments.';
    }
    if (!isset($errors['email']) && email_taken($email)) {
        $errors['email'] = 'An account already exists with this email address. Log in instead, or use a different email address.';
    }

    if ($errors) {
        fail_validation($errors, 'signup.php', ['next' => $next]);
    }

    try {
        $patientId = db_transaction(function () use ($email, $firstName, $lastName, $address, $idNumber, $dob, $phone, $password, $isMinor, $guardianName) {
            db_exec("INSERT INTO webuser (email, usertype) VALUES (?, 'p')", [$email]);
            db_exec(
                'INSERT INTO patient (pemail, pname, ppassword, paddress, pnic, pdob, ptel, pguardian_name, pconsent_version, pconsented_at, pcreated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())',
                [
                    $email, trim($firstName . ' ' . $lastName), password_hash($password, PASSWORD_DEFAULT), $address, $idNumber,
                    $dob, $phone, $isMinor ? $guardianName : null, (string) config('policy_version'),
                ]
            );
            return db_insert_id();
        });
    } catch (mysqli_sql_exception $exception) {
        if ((int) $exception->getCode() === 1062) {
            fail_validation(['email' => 'An account already exists with this email address. Log in instead, or use a different email address.'], 'signup.php', ['next' => $next]);
        }
        throw $exception;
    }

    start_user_session($email, 'p');
    audit('patient.registered', 'patient', $patientId);
    flash('success', 'Welcome to Uhai Bora. Your patient account is ready and you can now book an appointment.');
    redirect(safe_next($next, 'p') ?? 'patient/index.php');
}

$privacyContact = config('privacy.email') ?? config('contact.email');
$guardianHasError = field_error('guardian_name') !== '' || field_error('guardian_confirm') !== '';
$oldDob = old('dob');
$showGuardian = $guardianHasError || ($oldDob !== '' && is_valid_date($oldDob) && $oldDob <= today() && age_on($oldDob) < 18);

page_start([
    'title' => 'Register',
    'description' => 'Create a Uhai Bora patient account to book appointments and manage your bookings.',
    'body_class' => 'page-auth',
]);
?>
<div class="container auth">
    <div class="auth__grid">
        <section class="auth__card" aria-labelledby="register-title">
            <p class="eyebrow">Patient account</p>
            <h1 id="register-title">Register</h1>
            <p class="muted">You’ll need an email address, a phone number and an ID or passport number. All fields are needed unless they say “optional”.</p>
            <?= error_summary(null, ['guardian_confirm' => 'field-guardian-confirm', 'accept_terms' => 'field-accept-terms', 'consent_privacy' => 'field-consent-privacy']) ?>
            <form class="form" method="post" action="<?= e(url('signup.php')) ?>" novalidate data-submit-once>
                <?= csrf_field() ?>
                <input type="hidden" name="next" value="<?= e($next) ?>">

                <fieldset class="form-section">
                    <legend>About the patient</legend>
                    <div class="form-grid form-grid--2">
                        <?= input_field(['name' => 'first_name', 'label' => 'First name', 'autocomplete' => 'given-name', 'attrs' => ['maxlength' => '120']]) ?>
                        <?= input_field(['name' => 'last_name', 'label' => 'Last name', 'autocomplete' => 'family-name', 'attrs' => ['maxlength' => '120']]) ?>
                        <?= input_field([
                            'name' => 'dob', 'label' => 'Date of birth', 'type' => 'date', 'autocomplete' => 'bday', 'class' => 'field--wide',
                            'hint' => 'Helps the clinic identify you correctly. If the patient is under 18, a parent or guardian completes this form.',
                            'attrs' => ['max' => today(), 'min' => '1900-01-01', 'data-dob' => true],
                        ]) ?>
                    </div>
                    <div class="guardian-section" data-guardian<?= $guardianHasError || $showGuardian ? ' data-guardian-error="true"' : '' ?>>
                        <p><strong>Registering a child under 18?</strong> A parent or legal guardian must register for them. Use your own email address and phone number below.</p>
                        <div class="form">
                            <?= input_field([
                                'name' => 'guardian_name', 'label' => 'Parent or guardian’s full name', 'autocomplete' => 'name',
                                'required' => false, 'hide_optional' => true, 'hint' => 'Only needed when the patient is under 18.', 'attrs' => ['maxlength' => '120'],
                            ]) ?>
                            <?= checkbox_field([
                                'name' => 'guardian_confirm', 'id' => 'field-guardian-confirm', 'required' => false,
                                'label_html' => 'I am this person’s parent or legal guardian and I consent to their information being used to manage their appointments.',
                            ]) ?>
                        </div>
                    </div>
                </fieldset>

                <fieldset class="form-section">
                    <legend>Contact details</legend>
                    <div class="form-grid form-grid--2">
                        <?= input_field([
                            'name' => 'email', 'label' => 'Email address', 'type' => 'email', 'autocomplete' => 'email', 'class' => 'field--wide',
                            'hint' => 'You’ll use this to log in.',
                            'attrs' => ['inputmode' => 'email', 'spellcheck' => 'false', 'autocapitalize' => 'none', 'maxlength' => '255'],
                        ]) ?>
                        <?= input_field([
                            'name' => 'phone', 'label' => 'Phone number', 'type' => 'tel', 'autocomplete' => 'tel',
                            'hint' => 'So the clinic can contact you about appointments. For example 0712 345 678.',
                            'attrs' => ['inputmode' => 'tel', 'maxlength' => '32'],
                        ]) ?>
                        <?= input_field([
                            'name' => 'address', 'label' => 'Town or address', 'autocomplete' => 'street-address',
                            'hint' => 'Your area and county are enough, for example “Kilimani, Nairobi”.', 'attrs' => ['maxlength' => '255'],
                        ]) ?>
                    </div>
                </fieldset>

                <fieldset class="form-section">
                    <legend>Identification</legend>
                    <?= input_field([
                        'name' => 'id_number', 'label' => 'ID or passport number', 'autocomplete' => 'off',
                        'hint' => 'Kenyan national ID, passport, or alien or refugee ID number. For a child, use the number on their birth certificate or the guardian’s ID.',
                        'attrs' => ['maxlength' => '32', 'spellcheck' => 'false'],
                    ]) ?>
                </fieldset>

                <fieldset class="form-section">
                    <legend>Password</legend>
                    <div class="form-grid">
                        <?= input_field([
                            'name' => 'password', 'label' => 'Create a password', 'type' => 'password', 'autocomplete' => 'new-password',
                            'hint' => 'Use at least ' . PASSWORD_MIN_LENGTH . ' characters. A short phrase of three or four words works well.',
                            'attrs' => ['minlength' => (string) PASSWORD_MIN_LENGTH, 'maxlength' => '200'],
                        ]) ?>
                        <?= input_field([
                            'name' => 'password_confirmation', 'label' => 'Confirm your password', 'type' => 'password', 'autocomplete' => 'new-password',
                            'attrs' => ['data-password-confirm' => 'field-password', 'maxlength' => '200'],
                        ]) ?>
                    </div>
                </fieldset>

                <fieldset class="form-section">
                    <legend>Your information and agreement</legend>
                    <details class="privacy-panel" open>
                        <summary>How we use your information</summary>
                        <ul>
                            <li>We use these details to create your account, identify you correctly and manage your appointments with the clinic’s doctors.</li>
                            <li>Your bookings can reveal information about your health, so we treat them as sensitive. Doctors see bookings for their own sessions; clinic administrators manage the service. We don’t sell your information or use advertising trackers.</li>
                            <li>These details are needed to create an online account. If you’d rather not provide them, contact the clinic directly to make a booking.</li>
                            <li>You can view, download, correct or delete your information, object to how it’s used, or withdraw your consent at any time. See <a href="<?= e(url('your-data.php')) ?>">Your data rights</a>.</li>
                            <li>We protect accounts with hashed passwords, role-based access, automatic sign-out and a log of who views patient records.</li>
                            <li><?php if ($privacyContact !== null): ?>Privacy questions: <a href="mailto:<?= e($privacyContact) ?>"><?= e($privacyContact) ?></a>.<?php else: ?>The operator’s privacy contact details will be published in the <a href="<?= e(url('privacy.php')) ?>">Privacy notice</a> before launch.<?php endif; ?></li>
                        </ul>
                    </details>
                    <div class="form">
                        <?= checkbox_field([
                            'name' => 'consent_privacy', 'id' => 'field-consent-privacy',
                            'label_html' => 'I have read the <a href="' . e(url('privacy.php')) . '">Privacy notice</a> and agree to my information, including booking details related to my health, being used to manage my appointments.',
                        ]) ?>
                        <?= checkbox_field([
                            'name' => 'accept_terms', 'id' => 'field-accept-terms',
                            'label_html' => 'I agree to the <a href="' . e(url('terms.php')) . '">Terms of use</a>.',
                        ]) ?>
                    </div>
                </fieldset>

                <div class="button-row">
                    <button class="button button--primary" type="submit" data-loading-label="Creating your account…">Create account</button>
                </div>
            </form>
            <p class="auth__switch">Already have an account? <a href="<?= e(url('login.php', $next !== '' ? ['next' => $next] : [])) ?>">Log in</a></p>
        </section>
        <aside class="auth__aside" aria-label="Why register">
            <img src="<?= e(asset('assets/illustrations/booking.svg')) ?>" alt="" width="360" height="240">
            <h2>With an account you can</h2>
            <ul class="check-list">
                <li><?= icon('calendar-plus') ?><span>Book sessions with listed doctors.</span></li>
                <li><?= icon('clipboard') ?><span>Follow each booking from “Pending confirmation” to “Confirmed”.</span></li>
                <li><?= icon('refresh') ?><span>Reschedule or cancel without phoning.</span></li>
                <li><?= icon('download') ?><span>Download, correct or delete your information.</span></li>
            </ul>
            <div class="callout">
                <?= icon('heart-pulse') ?>
                <p><strong>Not for emergencies.</strong> If you need urgent help, call your local emergency number or go to the nearest emergency department.</p>
            </div>
        </aside>
    </div>
</div>
<?php
page_end();

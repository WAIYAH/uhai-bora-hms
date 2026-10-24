<?php
require __DIR__ . '/includes/bootstrap.php';

$operator = config('operator.legal_name');
$odpc = config('operator.odpc_registration');
$address = config('operator.address');
$hosting = config('privacy.hosting');
$retention = config('privacy.retention');

legal_start([
    'title' => 'Privacy notice',
    'eyebrow' => 'Your information',
    'intro' => 'How Uhai Bora collects, uses and protects your personal information, and the rights you have under Kenya’s Data Protection Act, 2019.',
    'sections' => [
        'who' => 'Who we are',
        'collect' => 'What we collect',
        'use' => 'How and why we use it',
        'health' => 'Health information',
        'sharing' => 'Who can see it',
        'storage' => 'Where it is stored',
        'retention' => 'How long we keep it',
        'security' => 'How we protect it',
        'rights' => 'Your rights',
        'children' => 'Children',
        'cookies' => 'Cookies',
        'complaints' => 'Questions and complaints',
        'changes' => 'Changes to this notice',
    ],
]);
?>
<h2 id="who">1. Who we are</h2>
<?php if ($operator !== null): ?>
    <p><strong><?= e($operator) ?></strong> (“we”, “us”) operates Uhai Bora and is the data controller for the personal information described here.<?= $odpc !== null ? ' Our registration number with the Office of the Data Protection Commissioner is ' . e($odpc) . '.' : '' ?><?= $address !== null ? ' Our address is ' . e($address) . '.' : '' ?></p>
<?php else: ?>
    <p>Uhai Bora is operated by an organisation that is the data controller for the personal information described here. Its legal name, registration with the Office of the Data Protection Commissioner and contact details will be added to this section before the service is used with real patient information.</p>
<?php endif; ?>
<p>To ask about this notice or your information, <?= privacy_contact_html() ?>.</p>

<h2 id="collect">2. What we collect</h2>
<ul>
    <li><strong>Account details:</strong> your name, email address, phone number, town or address, date of birth and ID or passport number. Your password is stored only as a one-way secure hash.</li>
    <li><strong>Parent or guardian details:</strong> for patients under 18, the name of the parent or guardian who registered them.</li>
    <li><strong>Booking details:</strong> the sessions you book, the doctor and specialty, dates and times, your booking reference and number, and the booking status.</li>
    <li><strong>Your agreement:</strong> which version of this notice and the Terms of use you agreed to, and when.</li>
    <li><strong>Security records:</strong> failed sign-in attempts (a one-way code of the email address used and the network address, kept for no more than one day) and a log of when staff view or change patient records.</li>
    <li><strong>Technical information:</strong> the web server may keep standard logs, such as network addresses and pages requested, to run and secure the service.</li>
</ul>
<p>The fields on the registration form are needed to create an online account. If you prefer not to provide them, you can contact the clinic directly to make a booking instead.</p>

<h2 id="use">3. How and why we use it</h2>
<ul>
    <li><strong>To provide the service you ask for</strong> — creating your account, listing sessions, taking and updating your bookings, and helping the clinic identify you correctly. This is necessary to provide the service (Data Protection Act, 2019, section 30(1)(b)(i)), and we ask for your consent to use booking details related to your health (sections 30(1)(a) and 32).</li>
    <li><strong>To keep the service secure</strong> — limiting repeated sign-in attempts and recording access to patient records. This is in our legitimate interest in protecting your information (section 30(1)(b)(vii)).</li>
    <li><strong>To meet legal obligations</strong> where the law requires us to keep or share information (section 30(1)(b)(ii)).</li>
</ul>
<p>We do not use your information for advertising, we do not sell it, and the website does not include advertising or analytics trackers.</p>

<h2 id="health">4. Health information</h2>
<p>Under the Data Protection Act, 2019, information collected when you register for or use health services — including which doctor or specialty you book — is health data and is treated as sensitive personal data. It is processed only by, or under the responsibility of, the health care provider and people who owe a duty of confidentiality (section 46).</p>
<p>Uhai Bora only handles appointment administration. Please do not enter medical history or symptoms anywhere in the service.</p>

<h2 id="sharing">5. Who can see it</h2>
<ul>
    <li><strong>Doctors</strong> see the details of patients booked into their own sessions.</li>
    <li><strong>Clinic administrators</strong> manage doctors, sessions, bookings and patient accounts, and can help you with your record.</li>
    <li><strong>Service providers</strong> that host and run the service on our behalf, under contract and only on our instructions.</li>
    <li><strong>Authorities</strong> only where the law requires it.</li>
</ul>

<h2 id="storage">6. Where it is stored</h2>
<?php if ($hosting !== null): ?>
    <p><?= e($hosting) ?></p>
<?php else: ?>
    <p>The service’s database is stored on infrastructure chosen by the operator. Its location and hosting provider will be stated here before launch.</p>
<?php endif; ?>
<p>Personal information is only transferred outside Kenya where the law allows it, with appropriate safeguards and, for sensitive information, your consent (sections 48 and 49).</p>

<h2 id="retention">7. How long we keep it</h2>
<?php if ($retention !== null): ?>
    <p><?= e($retention) ?></p>
<?php else: ?>
    <p>We keep your account and booking records while your account is open. When you delete your account, your account details and bookings are removed from the live database straight away. Records of failed sign-in attempts are deleted after one day. The operator’s full retention schedule, including how long backups are kept, will be published here before launch.</p>
<?php endif; ?>

<h2 id="security">8. How we protect it</h2>
<ul>
    <li>Passwords are stored as secure hashes, never as readable text.</li>
    <li>Access depends on your role: patients see only their own information and doctors only their own sessions.</li>
    <li>You are signed out automatically after 30 minutes without activity.</li>
    <li>Repeated failed sign-in attempts are temporarily blocked.</li>
    <li>Forms are protected against requests made from other websites.</li>
    <li>Staff access to patient records is logged.</li>
</ul>
<p>If a breach affects your information and creates a real risk of harm, we will notify the Data Commissioner within 72 hours and tell you as required by section 43.</p>

<h2 id="rights">9. Your rights</h2>
<p>Under section 26 of the Data Protection Act, 2019 you have the right to:</p>
<ul>
    <li>be told how your information is used — this notice;</li>
    <li>access your information — download a copy any time from Settings in your account, or ask us (we respond within 7 days);</li>
    <li>correct information that is wrong or out of date — edit it in Settings, or ask us (we respond within 14 days);</li>
    <li>have your information deleted — delete your account in Settings, or ask us (we respond within 14 days);</li>
    <li>object to the use of all or part of your information;</li>
    <li>withdraw your consent at any time — this does not affect bookings handled before you withdrew, but it means we can no longer manage your bookings online.</li>
</ul>
<p>Read <a href="<?= e(url('your-data.php')) ?>">Your data rights</a> for step-by-step instructions, or <?= privacy_contact_html() ?>.</p>

<h2 id="children">10. Children</h2>
<p>A parent or legal guardian must register patients under 18 and agree to this notice on their behalf (section 33). The parent or guardian can exercise the child’s rights described above.</p>

<h2 id="cookies">11. Cookies</h2>
<p>Uhai Bora uses one essential cookie to keep you signed in. Read the <a href="<?= e(url('cookies.php')) ?>">Cookie notice</a>.</p>

<h2 id="complaints">12. Questions and complaints</h2>
<p>Please contact us first so we can try to help: <?= privacy_contact_html() ?>. You also have the right to complain to the Office of the Data Protection Commissioner (ODPC) at <a href="https://www.odpc.go.ke" rel="noopener noreferrer">www.odpc.go.ke</a>.</p>

<h2 id="changes">13. Changes to this notice</h2>
<p>When we change this notice in a way that matters, we will update the version and effective date at the top of this page and, where needed, ask for your agreement again.</p>
<?php
legal_end();

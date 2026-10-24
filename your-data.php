<?php
require __DIR__ . '/includes/bootstrap.php';

legal_start([
    'title' => 'Your data rights',
    'eyebrow' => 'Your information',
    'intro' => 'How to see, download, correct or delete your information, and how to object or withdraw your consent.',
    'updated' => 'Rights under the Data Protection Act, 2019 and the Data Protection (General) Regulations, 2021',
    'sections' => [
        'yourself' => 'Do it yourself in Settings',
        'ask' => 'Ask us',
        'timescales' => 'How quickly we respond',
        'others' => 'Acting for someone else',
        'complain' => 'If you are not happy',
    ],
]);
?>
<h2 id="yourself">1. Do it yourself in Settings</h2>
<p>Log in and open <strong>Settings</strong> to:</p>
<ul>
    <li><strong>See and download your information</strong> — choose “Download my information” to get a copy of your account details and bookings in a common, machine-readable format (JSON).</li>
    <li><strong>Correct your details</strong> — update your name, contact details, date of birth or ID number at any time.</li>
    <li><strong>Delete your account</strong> — this removes your account and your bookings from the live database. You will be asked for your password first.</li>
</ul>
<p><a class="button button--primary" href="<?= e(url('login.php', ['next' => 'patient/settings.php'])) ?>">Log in to Settings</a></p>

<h2 id="ask">2. Ask us</h2>
<p>You can also ask us to give you a copy of your information, correct or delete it, stop or limit how we use it, or record that you withdraw your consent. To make a request, <?= privacy_contact_html() ?>. We will check your identity before acting, to protect your information.</p>

<h2 id="timescales">3. How quickly we respond</h2>
<ul>
    <li>Requests to access your information: within 7 days.</li>
    <li>Requests to correct or delete your information: within 14 days.</li>
</ul>
<p>If we can’t do what you ask — for example because the law requires us to keep a record — we will explain why.</p>

<h2 id="others">4. Acting for someone else</h2>
<p>A parent or legal guardian can exercise these rights for a patient under 18. Someone with legal authority can act for a person who cannot manage their own affairs.</p>

<h2 id="complain">5. If you are not happy</h2>
<p>Please contact us first so we can try to put things right. You can also complain to the Office of the Data Protection Commissioner (ODPC) at <a href="https://www.odpc.go.ke" rel="noopener noreferrer">www.odpc.go.ke</a>.</p>
<?php
legal_end();

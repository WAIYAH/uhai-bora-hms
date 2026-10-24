<?php
require __DIR__ . '/includes/bootstrap.php';

$operator = config('operator.legal_name');

legal_start([
    'title' => 'Terms of use',
    'eyebrow' => 'Clear expectations',
    'intro' => 'The rules for using Uhai Bora to create an account and book appointments. Please read them together with the Privacy notice.',
    'sections' => [
        'about' => 'About these terms',
        'service' => 'What Uhai Bora does',
        'account' => 'Your account',
        'bookings' => 'Bookings',
        'use' => 'Acceptable use',
        'availability' => 'Availability',
        'privacy' => 'Privacy',
        'liability' => 'Our responsibility to you',
        'changes' => 'Changes',
        'law' => 'Law and contact',
    ],
]);
?>
<h2 id="about">1. About these terms</h2>
<p>These terms apply when you use the Uhai Bora website and account area. <?= $operator !== null
    ? 'Uhai Bora is operated by <strong>' . e($operator) . '</strong> (“we”, “us”).'
    : 'The organisation operating Uhai Bora (“we”, “us”) will be named here before launch.' ?> By creating an account you agree to these terms.</p>

<h2 id="service">2. What Uhai Bora does</h2>
<p>Uhai Bora is an appointment administration service. It lets you see doctors and sessions listed by the clinic, book and manage appointments, and follow their status.</p>
<p><strong>Uhai Bora is not an emergency service and does not provide medical advice, diagnosis or treatment.</strong> It does not replace care from a qualified health professional. If you may be having a medical emergency, call your local emergency number or go to the nearest emergency department.</p>

<h2 id="account">3. Your account</h2>
<ul>
    <li>Give accurate information and keep it up to date in Settings.</li>
    <li>Each account is for one patient. A parent or legal guardian must register patients under 18.</li>
    <li>Keep your password private and log out on shared devices. Tell us straight away if you think someone else has used your account.</li>
    <li>You can delete your account at any time from Settings.</li>
</ul>

<h2 id="bookings">4. Bookings</h2>
<ul>
    <li>Sessions and places shown depend on the clinic’s current listings and may change.</li>
    <li>New bookings are “Pending confirmation” until the clinic confirms them. A booking is not a guarantee that a doctor will be available; the clinic may need to move or cancel a session, and you will see the change in My bookings.</li>
    <li>You can reschedule or cancel a booking until the session starts. Please cancel if you can’t attend, so someone else can use the place.</li>
    <li>Uhai Bora does not take payments. Ask the clinic about any fees before your appointment.</li>
</ul>

<h2 id="use">5. Acceptable use</h2>
<p>Do not misuse the service. In particular, do not book places you don’t intend to use, impersonate anyone, try to access other people’s information, or interfere with the service or its security. We may suspend accounts that are misused, to protect patients and the service.</p>

<h2 id="availability">6. Availability</h2>
<p>We work to keep Uhai Bora available, but it may sometimes be unavailable, for example during maintenance. Don’t rely on it as your only record of an appointment — note the date, time and booking reference.</p>

<h2 id="privacy">7. Privacy</h2>
<p>How we use your information is explained in the <a href="<?= e(url('privacy.php')) ?>">Privacy notice</a> and the <a href="<?= e(url('cookies.php')) ?>">Cookie notice</a>.</p>

<h2 id="liability">8. Our responsibility to you</h2>
<p>We provide Uhai Bora with reasonable care and skill. Nothing in these terms limits any rights you have under Kenyan law, including the Consumer Protection Act, or any liability that cannot be limited by law.</p>

<h2 id="changes">9. Changes</h2>
<p>We may update these terms when the service or the law changes. The version and effective date are shown at the top of this page. If a change matters, we will tell you and, where needed, ask for your agreement again.</p>

<h2 id="law">10. Law and contact</h2>
<p>These terms are governed by the laws of Kenya. For questions about them, use the details on our <a href="<?= e(url('contact.php')) ?>">Contact page</a>.</p>
<?php
legal_end();

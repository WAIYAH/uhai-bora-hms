<?php
require __DIR__ . '/includes/bootstrap.php';

legal_start([
    'title' => 'Cookie notice',
    'eyebrow' => 'Cookies',
    'intro' => 'Uhai Bora uses a single essential cookie to keep you signed in. There are no advertising or analytics cookies.',
    'sections' => [
        'what' => 'What cookies are',
        'ours' => 'The cookie we use',
        'not' => 'What we don’t use',
        'manage' => 'Managing cookies',
    ],
]);
?>
<h2 id="what">1. What cookies are</h2>
<p>Cookies are small files a website stores in your browser so it can remember something between pages, such as the fact that you are signed in.</p>

<h2 id="ours">2. The cookie we use</h2>
<div class="card">
    <dl class="details-list">
        <div><dt>Name</dt><dd><code>uhaibora_session</code></dd></div>
        <div><dt>Purpose</dt><dd>Keeps you signed in, remembers form progress and protects forms against requests made from other websites.</dd></div>
        <div><dt>Type</dt><dd>Essential — logging in and booking cannot work without it.</dd></div>
        <div><dt>When it is set</dt><dd>Only when you log in, register or send a form. Reading the public pages does not set any cookie.</dd></div>
        <div><dt>Set by</dt><dd>Uhai Bora only (first-party).</dd></div>
        <div><dt>How long</dt><dd>Until you close your browser. You are also signed out after 30 minutes without activity.</dd></div>
    </dl>
</div>

<h2 id="not">3. What we don’t use</h2>
<p>Uhai Bora does not use advertising, analytics or social media tracking cookies, and does not embed content from other websites. The share links in the footer are ordinary links: the social network you choose may set its own cookies only after you visit it.</p>
<p>If this changes, we will update this notice and ask for your consent before setting any non-essential cookie.</p>

<h2 id="manage">4. Managing cookies</h2>
<p>You can delete or block cookies in your browser settings. If you block the essential cookie, you can still read the public pages, but you won’t be able to log in or book.</p>
<?php
legal_end();

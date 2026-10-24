<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
$user = require_role('d');
$doctorId = (int) $user['id'];

if (is_post()) {
    handle_appointment_action($doctorId);
    redirect_back('doctor/patient.php');
}

/* ---------- One patient (only patients booked into this doctor's sessions) ---------- */
$id = query_int('id');
if ($id) {
    $patient = db_one(
        'SELECT p.pid, p.pname, p.pemail, p.ptel, p.pdob, p.pguardian_name
         FROM patient p
         WHERE p.pid = ? AND EXISTS (
            SELECT 1 FROM appointment a INNER JOIN schedule s ON s.scheduleid = a.scheduleid WHERE a.pid = p.pid AND s.docid = ?
         )',
        [$id, $doctorId]
    );
    if (!$patient) {
        not_found();
    }
    audit('patient.viewed', 'patient', $id);
    $bookings = staff_appointments(['doctor' => $doctorId, 'patient' => $id, 'period' => 'all'], 200);

    app_start([
        'title' => $patient['pname'],
        'nav' => 'patients',
        'back' => ['Back to my patients', 'doctor/patient.php'],
        'eyebrow' => 'Patient',
        'lead' => 'Contact details and this patient’s bookings with you. Your access to this record is logged.',
    ]);
    ?>
    <div class="split">
        <section class="panel" aria-labelledby="contact-title">
            <h2 id="contact-title">Details</h2>
            <dl class="details-list">
                <div><dt>Name</dt><dd><?= e($patient['pname']) ?></dd></div>
                <div><dt>Age</dt><dd><?= e(age_on($patient['pdob'])) ?> (born <?= e(format_date($patient['pdob'])) ?>)</dd></div>
                <div><dt>Phone</dt><dd><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $patient['ptel'])) ?>"><?= e($patient['ptel']) ?></a></dd></div>
                <div><dt>Email</dt><dd><a href="mailto:<?= e($patient['pemail']) ?>"><?= e($patient['pemail']) ?></a></dd></div>
                <?php if (!empty($patient['pguardian_name'])): ?><div><dt>Parent or guardian</dt><dd><?= e($patient['pguardian_name']) ?></dd></div><?php endif; ?>
            </dl>
        </section>
        <aside class="panel">
            <h2>Privacy</h2>
            <p class="muted">Only use these details to manage this patient’s appointments with you. Address and ID details are visible only to clinic administrators.</p>
        </aside>
    </div>
    <div class="section-title"><h2>Bookings with you</h2></div>
    <?= staff_appointment_table($bookings, 'doctor/patient.php?id=' . $id, 'doctor/patient.php', false) ?>
    <?php
    app_end();
    exit;
}

/* ---------- List ---------- */
$search = mb_substr(query_string('q'), 0, 80);
$sql = "SELECT p.pid, p.pname, p.ptel,
               COUNT(a.appoid) AS bookings,
               MAX(s.scheduledate) AS last_date,
               SUM(CASE WHEN a.status IN ('pending', 'confirmed') AND " . UPCOMING_SQL . " THEN 1 ELSE 0 END) AS upcoming
        FROM patient p
        INNER JOIN appointment a ON a.pid = p.pid
        INNER JOIN schedule s ON s.scheduleid = a.scheduleid
        WHERE s.docid = ?";
$params = [$doctorId];
if ($search !== '') {
    $sql .= ' AND (p.pname LIKE ? OR p.pemail LIKE ? OR p.ptel LIKE ?)';
    $pattern = like_pattern($search);
    array_push($params, $pattern, $pattern, $pattern);
}
$patients = db_all($sql . ' GROUP BY p.pid, p.pname, p.ptel ORDER BY p.pname LIMIT 500', $params);

app_start([
    'title' => 'My patients',
    'nav' => 'patients',
    'lead' => 'Patients who have booked one of your sessions.',
]);
?>
<form class="filters" method="get" action="<?= e(url('doctor/patient.php')) ?>" role="search" aria-label="Search my patients">
    <?= input_field(['name' => 'q', 'label' => 'Name, email or phone', 'type' => 'search', 'required' => false, 'value' => $search, 'attrs' => ['maxlength' => '80']]) ?>
    <div class="filters__actions">
        <button class="button button--secondary" type="submit"><?= icon('search') ?>Search</button>
        <?php if ($search !== ''): ?><a class="button button--ghost" href="<?= e(url('doctor/patient.php')) ?>">Clear</a><?php endif; ?>
    </div>
</form>

<?php if ($patients): ?>
    <p class="result-count" role="status"><?= e(plural(count($patients), 'patient')) ?></p>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th scope="col">Patient</th><th scope="col">Phone</th><th scope="col">Bookings</th><th scope="col">Upcoming</th><th scope="col">Latest session</th></tr></thead>
            <tbody>
            <?php foreach ($patients as $patient): ?>
                <tr>
                    <th scope="row" data-label="Patient"><a href="<?= e(url('doctor/patient.php', ['id' => (int) $patient['pid']])) ?>"><?= e($patient['pname']) ?></a></th>
                    <td data-label="Phone"><?= e($patient['ptel']) ?></td>
                    <td data-label="Bookings"><?= (int) $patient['bookings'] ?></td>
                    <td data-label="Upcoming"><?= (int) $patient['upcoming'] ?></td>
                    <td data-label="Latest session"><?= e(format_date($patient['last_date'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <?= empty_state(
        $search !== '' ? 'No patients match your search' : 'No patients yet',
        $search !== '' ? 'Check the spelling or clear the search.' : 'Patients appear here after they book one of your sessions.',
        $search !== '' ? button_link('Clear search', 'doctor/patient.php', 'secondary') : '',
        'community-care'
    ) ?>
<?php endif; ?>
<?php
app_end();

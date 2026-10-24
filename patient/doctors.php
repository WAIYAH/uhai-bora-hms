<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
require_role('p');

$search = mb_substr(query_string('q'), 0, 80);
$specialty = query_int('specialty');

$sql = 'SELECT d.docid, d.docname, sp.sname,
               (SELECT COUNT(*) FROM schedule s WHERE s.docid = d.docid AND ' . UPCOMING_SQL . ') AS upcoming,
               (SELECT MIN(s.scheduledate) FROM schedule s WHERE s.docid = d.docid AND ' . UPCOMING_SQL . ') AS next_date
        FROM doctor d LEFT JOIN specialties sp ON sp.id = d.specialties WHERE 1 = 1';
$params = [];
if ($search !== '') {
    $sql .= ' AND (d.docname LIKE ? OR sp.sname LIKE ?)';
    $pattern = like_pattern($search);
    array_push($params, $pattern, $pattern);
}
if ($specialty) {
    $sql .= ' AND d.specialties = ?';
    $params[] = $specialty;
}
$doctors = db_all($sql . ' ORDER BY d.docname', $params);
// Doctors with upcoming sessions first, then alphabetical.
usort($doctors, function ($a, $b) {
    $byAvailability = ((int) $b['upcoming'] > 0) <=> ((int) $a['upcoming'] > 0);
    return $byAvailability !== 0 ? $byAvailability : strcasecmp($a['docname'], $b['docname']);
});

$specialtyOptions = [];
foreach (db_all('SELECT DISTINCT sp.id, sp.sname FROM specialties sp INNER JOIN doctor d ON d.specialties = sp.id ORDER BY sp.sname') as $row) {
    $specialtyOptions[(int) $row['id']] = $row['sname'];
}
$filtered = $search !== '' || $specialty;

app_start([
    'title' => 'Doctors',
    'nav' => 'doctors',
    'lead' => 'Doctors listed in Uhai Bora. Choose one to see their upcoming sessions.',
]);
?>
<form class="filters" method="get" action="<?= e(url('patient/doctors.php')) ?>" role="search" aria-label="Find a doctor">
    <?= input_field(['name' => 'q', 'label' => 'Doctor name or specialty', 'type' => 'search', 'required' => false, 'value' => $search, 'attrs' => ['maxlength' => '80']]) ?>
    <?= select_field(['name' => 'specialty', 'label' => 'Specialty', 'required' => false, 'value' => (string) ($specialty ?? ''), 'placeholder' => 'All specialties', 'options' => $specialtyOptions]) ?>
    <div class="filters__actions">
        <button class="button button--primary" type="submit"><?= icon('search') ?>Search</button>
        <?php if ($filtered): ?><a class="button button--ghost" href="<?= e(url('patient/doctors.php')) ?>">Clear</a><?php endif; ?>
    </div>
</form>

<?php if (!$doctors): ?>
    <?= empty_state(
        $filtered ? 'No doctors match your search' : 'No doctors listed yet',
        $filtered ? 'Check the spelling or try a specialty instead.' : 'Doctors will appear here once the clinic adds them.',
        $filtered ? button_link('Clear search', 'patient/doctors.php', 'secondary') : '',
        'discover'
    ) ?>
<?php else: ?>
    <p class="result-count" role="status"><?= e(plural(count($doctors), 'doctor')) ?></p>
    <ul class="record-list">
        <?php foreach ($doctors as $doctor): ?>
            <li class="record">
                <div class="record__main">
                    <h2 class="record__title"><?= e($doctor['docname']) ?></h2>
                    <ul class="record__meta">
                        <li><?= icon('stethoscope') ?><?= e($doctor['sname'] ?? 'Specialty not set') ?></li>
                        <li><?= icon('calendar') ?><?= (int) $doctor['upcoming'] > 0
                            ? e(plural((int) $doctor['upcoming'], 'upcoming session')) . ' · next ' . e(format_date($doctor['next_date']))
                            : 'No upcoming sessions' ?></li>
                    </ul>
                </div>
                <div class="record__aside">
                    <?php if ((int) $doctor['upcoming'] > 0): ?>
                        <a class="button button--primary button--small" href="<?= e(url('patient/schedule.php', ['doctor' => (int) $doctor['docid']])) ?>">See sessions<span class="visually-hidden"> with <?= e($doctor['docname']) ?></span></a>
                    <?php endif; ?>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
<?php
app_end();

<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
$user = require_role('p');

$filters = [
    'q' => mb_substr(query_string('q'), 0, 80),
    'specialty' => query_int('specialty'),
    'doctor' => query_int('doctor'),
    'from' => query_date('from'),
    'patient' => (int) $user['id'],
];
$page = query_int('page') ?? 1;
$perPage = 30;
$rows = upcoming_sessions($filters, $perPage + 1, ($page - 1) * $perPage);
$hasMore = count($rows) > $perPage;
$rows = array_slice($rows, 0, $perPage);

$doctorName = null;
if ($filters['doctor']) {
    $doctorName = db_value('SELECT docname FROM doctor WHERE docid = ?', [$filters['doctor']]);
}
$specialtyOptions = [];
foreach (db_all('SELECT DISTINCT sp.id, sp.sname FROM specialties sp INNER JOIN doctor d ON d.specialties = sp.id ORDER BY sp.sname') as $row) {
    $specialtyOptions[(int) $row['id']] = $row['sname'];
}
$activeQuery = array_filter([
    'q' => $filters['q'], 'specialty' => $filters['specialty'], 'doctor' => $filters['doctor'], 'from' => $filters['from'],
]);

$groups = [];
foreach ($rows as $row) {
    $groups[$row['scheduledate']][] = $row;
}

app_start([
    'title' => 'Book an appointment',
    'nav' => 'book',
    'lead' => 'Choose a session with places available. Times are shown in East Africa Time.',
]);
?>
<form class="filters" method="get" action="<?= e(url('patient/schedule.php')) ?>" role="search" aria-label="Filter sessions">
    <?= input_field(['name' => 'q', 'label' => 'Doctor or session', 'type' => 'search', 'required' => false, 'value' => $filters['q'], 'attrs' => ['maxlength' => '80']]) ?>
    <?= select_field(['name' => 'specialty', 'label' => 'Specialty', 'required' => false, 'value' => (string) ($filters['specialty'] ?? ''), 'placeholder' => 'All specialties', 'options' => $specialtyOptions]) ?>
    <?= input_field(['name' => 'from', 'label' => 'From date', 'type' => 'date', 'required' => false, 'value' => $filters['from'], 'attrs' => ['min' => today()]]) ?>
    <?php if ($filters['doctor']): ?><input type="hidden" name="doctor" value="<?= (int) $filters['doctor'] ?>"><?php endif; ?>
    <div class="filters__actions">
        <button class="button button--primary" type="submit"><?= icon('search') ?>Show sessions</button>
        <?php if ($activeQuery): ?><a class="button button--ghost" href="<?= e(url('patient/schedule.php')) ?>">Clear</a><?php endif; ?>
    </div>
</form>

<?php if ($doctorName !== null): ?>
    <div class="alert alert--info notice"><?= icon('info') ?><p>Showing sessions with <strong><?= e($doctorName) ?></strong>. <a href="<?= e(url('patient/schedule.php')) ?>">Show all doctors</a></p></div>
<?php endif; ?>

<?php if (!$rows): ?>
    <?= empty_state(
        $activeQuery ? 'No sessions match your filters' : 'No upcoming sessions yet',
        $activeQuery ? 'Try another date or specialty, or clear the filters to see every upcoming session.' : 'New sessions appear here as soon as doctors publish them. Please check again soon.',
        $activeQuery ? button_link('Clear filters', 'patient/schedule.php', 'secondary') : button_link('Browse doctors', 'patient/doctors.php', 'secondary'),
        'booking'
    ) ?>
<?php else: ?>
    <p class="result-count" role="status">Showing <?= e(plural(count($rows), 'session')) ?><?= $page > 1 ? ' (page ' . $page . ')' : '' ?>.</p>
    <?php foreach ($groups as $date => $sessions): ?>
        <section class="date-group" aria-labelledby="date-<?= e($date) ?>">
            <h2 class="date-group__title" id="date-<?= e($date) ?>"><?= e(format_date_long($date)) ?></h2>
            <ul class="record-list">
                <?php foreach ($sessions as $session):
                    $left = spaces_left($session);
                    $mine = $session['my_appointment'] !== null; ?>
                    <li class="record">
                        <div class="record__main">
                            <h3 class="record__title"><?= e($session['title']) ?></h3>
                            <ul class="record__meta">
                                <li><?= icon('clock') ?><?= e(format_time($session['scheduletime'])) ?></li>
                                <li><?= icon('stethoscope') ?><?= e($session['docname']) ?><?= $session['sname'] ? ' · ' . e($session['sname']) : '' ?></li>
                            </ul>
                        </div>
                        <div class="record__aside">
                            <?php if ($mine): ?>
                                <span class="badge badge--confirmed"><?= icon('check') ?>You’re booked</span>
                                <a class="button button--secondary button--small" href="<?= e(url('patient/appointment.php', ['id' => (int) $session['my_appointment']])) ?>">View booking</a>
                            <?php elseif ($left === 0): ?>
                                <span class="badge badge--full">Full</span>
                            <?php else: ?>
                                <span class="spaces"><?= e(plural($left, 'place')) ?> left</span>
                                <a class="button button--primary button--small" href="<?= e(url('patient/booking.php', ['id' => (int) $session['scheduleid']])) ?>">Book<span class="visually-hidden"> <?= e($session['title'] . ' with ' . $session['docname'] . ' on ' . format_date($date) . ' at ' . format_time($session['scheduletime'])) ?></span></a>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endforeach; ?>
    <?= pagination($page, $hasMore, $activeQuery, 'patient/schedule.php') ?>
<?php endif; ?>
<?php
app_end();

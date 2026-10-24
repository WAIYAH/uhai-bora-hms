<?php
require __DIR__ . '/includes/bootstrap.php';

$search = mb_substr(query_string('q'), 0, 80);
list($specialtyList, $unavailable) = db_optional('listed_specialties', []);

if ($search !== '') {
    $needle = mb_strtolower($search);
    $specialtyList = array_values(array_filter($specialtyList, function ($row) use ($needle) {
        return mb_strpos(mb_strtolower($row['sname'] . ' ' . specialty_description($row['sname'])), $needle) !== false;
    }));
}

page_start([
    'title' => 'Services',
    'nav' => 'services',
    'description' => 'Browse the specialties with doctors listed in Uhai Bora and see upcoming appointment sessions.',
]);
?>
<section class="page-hero" aria-labelledby="services-title">
    <div class="container page-hero__grid">
        <div>
            <p class="eyebrow">Services</p>
            <h1 id="services-title">Find the right <em>specialty.</em></h1>
            <p class="lead">These are the specialties with doctors currently listed in Uhai Bora. Choose one to see upcoming sessions and book.</p>
        </div>
        <div class="page-hero__art">
            <img src="<?= e(asset('assets/illustrations/discover.svg')) ?>" alt="" width="360" height="240">
        </div>
    </div>
</section>

<section class="section section--sand" aria-labelledby="directory-title">
    <div class="container">
        <h2 id="directory-title" class="visually-hidden">Specialty directory</h2>
        <form class="filter-bar" method="get" action="<?= e(url('services.php')) ?>" role="search">
            <label class="field__label" for="specialty-search">Search specialties</label>
            <div class="filter-bar__row">
                <input class="input" id="specialty-search" type="search" name="q" value="<?= e($search) ?>" placeholder="For example: children, heart, skin" data-filter-input>
                <button class="button button--primary" type="submit"><?= icon('search') ?>Search</button>
            </div>
        </form>
        <p class="filter-status" role="status" aria-live="polite" data-filter-status><?php
            if ($search !== '' && !$unavailable) {
                echo e(count($specialtyList) === 0 ? 'No specialties match “' . $search . '”.' : plural(count($specialtyList), 'specialty', 'specialties') . ' match “' . $search . '”.');
            }
        ?></p>

        <?php if ($unavailable): ?>
            <div class="alert alert--warning" role="status"><?= icon('alert') ?><p>The specialty list is temporarily unavailable. Please refresh the page in a few minutes.</p></div>
        <?php elseif (!$specialtyList): ?>
            <?= empty_state(
                $search !== '' ? 'No matching specialties' : 'No specialties listed yet',
                $search !== '' ? 'Try a different word, or clear the search to see every listed specialty.' : 'Specialties appear here when doctors are added to Uhai Bora.',
                $search !== '' ? button_link('Clear search', 'services.php', 'secondary') : '',
                'discover'
            ) ?>
        <?php else: ?>
            <ul class="specialty-grid" data-filter-list>
                <?php foreach ($specialtyList as $specialty):
                    $description = specialty_description($specialty['sname']);
                    $isEmergency = stripos($specialty['sname'], 'emergency') !== false; ?>
                    <li class="specialty-card" data-filter-text="<?= e(mb_strtolower($specialty['sname'] . ' ' . $description)) ?>">
                        <h3><?= e($specialty['sname']) ?></h3>
                        <?php if ($description !== ''): ?><p><?= e($description) ?></p><?php endif; ?>
                        <ul class="specialty-card__meta">
                            <li><?= e(plural((int) $specialty['doctors'], 'doctor')) ?> listed</li>
                            <li><?= (int) $specialty['upcoming_sessions'] > 0
                                ? e(plural((int) $specialty['upcoming_sessions'], 'upcoming session')) . ' · next ' . e(format_date($specialty['next_session'], 'j M'))
                                : 'No upcoming sessions' ?></li>
                        </ul>
                        <?php if ($isEmergency): ?>
                            <a class="button button--secondary button--small" href="<?= e(url('contact.php')) ?>#urgent-help"><?= icon('heart-pulse') ?>Get urgent help</a>
                        <?php elseif ((int) $specialty['upcoming_sessions'] > 0): ?>
                            <a class="button button--primary button--small" href="<?= e(url('book.php', ['specialty' => (int) $specialty['id']])) ?>">See sessions<span class="visually-hidden"> for <?= e($specialty['sname']) ?></span><?= icon('arrow-right') ?></a>
                        <?php else: ?>
                            <p class="muted"><small>Check back soon for new sessions.</small></p>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</section>

<section class="section" aria-labelledby="unsure-title">
    <div class="container grid grid--2">
        <div class="info-card">
            <?= icon('info') ?>
            <h2 id="unsure-title">Not sure which specialty you need?</h2>
            <p>If you are unsure, a general practice appointment — when one is listed — is often a good place to start. The doctor can advise you on next steps.</p>
            <p><a href="<?= e(url('how-it-works.php')) ?>">How booking works</a></p>
        </div>
        <div class="info-card">
            <?= icon('shield') ?>
            <h2>Good to know</h2>
            <p>Listings show doctors and sessions added by the clinic. They are not a guarantee that a particular treatment is available and they are not medical advice.</p>
            <p><a href="<?= e(url('contact.php')) ?>#urgent-help">Urgent help</a></p>
        </div>
    </div>
</section>
<?php
page_end();

<?php
/**
 * Block information page (CMO reference layout)
 * Expects: $block (array with slug), $view (home|phcs|gallery|infrastructure|equipments|roadmap|staff)
 */
declare(strict_types=1);

$view = $view ?? 'home';
$slug = (string) ($block['slug'] ?? '');
$name = (string) ($block['name'] ?? 'Block');
$bmo = $block['bmo'] ?? [];
$hero = BlockMap::heroUrl($block['hero'] ?? null);
$photo = BlockMap::photoUrl($bmo['photo'] ?? null);
$phone = trim((string) ($bmo['phone'] ?? ''));
$phoneHref = preg_replace('/\D+/', '', $phone) ?: '';
if ($phone === '') {
    $phone = '—';
}

$cmoPageTitle = 'Block ' . $name;
$cmoSubtitle = 'Block ' . $name;
require BASE_PATH . 'includes/map-cmo-start.php';
?>

<section class="cmo-hero<?= $view === 'home' ? ' is-home' : ' is-section' ?>" style="background-image:url('<?= Helper::e($hero) ?>')">
    <div class="cmo-hero-inner">
        <?php if ($view === 'home'): ?>
            <aside class="cmo-profile-card">
                <img class="cmo-profile-photo" src="<?= Helper::e($photo) ?>" alt="<?= Helper::e(preg_replace('/\s+/', ' ', (string) ($bmo['name'] ?? 'BMO'))) ?>">
                <h1 class="cmo-profile-name"><?= Helper::e((string) ($bmo['name'] ?? 'Block Medical Officer')) ?></h1>
                <p class="cmo-profile-role"><?= Helper::e((string) ($bmo['title'] ?? 'Block Medical Officer')) ?></p>
                <?php if ($phoneHref !== ''): ?>
                    <a class="cmo-profile-phone" href="tel:<?= Helper::e($phoneHref) ?>">
                        <i class="bi bi-telephone-fill"></i> <?= Helper::e($phone) ?>
                    </a>
                <?php else: ?>
                    <div class="cmo-profile-phone"><i class="bi bi-telephone-fill"></i> <?= Helper::e($phone) ?></div>
                <?php endif; ?>
                <a class="cmo-btn cmo-staff-btn" href="<?= Helper::e(BlockMap::blockUrl($slug, 'staff')) ?>">
                    <i class="bi bi-people-fill"></i> Staff List
                </a>
            </aside>

            <div class="cmo-actions-card">
                <div class="cmo-action-grid">
                    <a class="cmo-action" href="<?= Helper::e(BlockMap::blockUrl($slug, 'gallery')) ?>">
                        <span class="cmo-action-icon is-gallery"><i class="bi bi-image"></i></span>
                        <h2 class="cmo-action-title">Photo Gallery</h2>
                        <span class="cmo-action-rule"></span>
                        <p class="cmo-action-text">View photos of hospital<br>and activities</p>
                    </a>
                    <a class="cmo-action" href="<?= Helper::e((string) ($block['maps_url'] ?? '#')) ?>" target="_blank" rel="noopener">
                        <span class="cmo-action-icon is-maps"><i class="bi bi-geo-alt-fill"></i></span>
                        <h2 class="cmo-action-title">Google Location</h2>
                        <span class="cmo-action-rule"></span>
                        <p class="cmo-action-text">View hospital location<br>on Google Maps</p>
                    </a>
                    <a class="cmo-action" href="<?= Helper::e(BlockMap::blockUrl($slug, 'phcs')) ?>">
                        <span class="cmo-action-icon is-phc"><i class="bi bi-hospital"></i></span>
                        <h2 class="cmo-action-title">PHC’s</h2>
                        <span class="cmo-action-rule"></span>
                        <p class="cmo-action-text">List of all PHC's<br>under this block</p>
                    </a>
                    <a class="cmo-action" href="<?= Helper::e(BlockMap::blockUrl($slug, 'infrastructure')) ?>">
                        <span class="cmo-action-icon is-infra"><i class="bi bi-buildings"></i></span>
                        <h2 class="cmo-action-title">Infrastructure</h2>
                        <span class="cmo-action-rule"></span>
                        <p class="cmo-action-text">Details of infrastructure<br>and facilities</p>
                    </a>
                    <a class="cmo-action" href="<?= Helper::e(BlockMap::blockUrl($slug, 'equipments')) ?>">
                        <span class="cmo-action-icon is-equip"><i class="bi bi-gear-wide-connected"></i></span>
                        <h2 class="cmo-action-title">Equipments Using</h2>
                        <span class="cmo-action-rule"></span>
                        <p class="cmo-action-text">Information about<br>major equipments</p>
                    </a>
                    <a class="cmo-action" href="<?= Helper::e(BlockMap::blockUrl($slug, 'roadmap')) ?>">
                        <span class="cmo-action-icon is-road"><i class="bi bi-map"></i></span>
                        <h2 class="cmo-action-title">Next Year Road Map</h2>
                        <span class="cmo-action-rule"></span>
                        <p class="cmo-action-text">Plan and proposed<br>developments</p>
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="cmo-content-card">
                <a class="cmo-back-link" href="<?= Helper::e(BlockMap::blockUrl($slug)) ?>">
                    <i class="bi bi-arrow-left"></i> Back to Block <?= Helper::e($name) ?>
                </a>

                <?php if ($view === 'phcs'): ?>
                    <h2>PHC’s under Block <?= Helper::e($name) ?></h2>
                    <p>Select an institution to open its information page.</p>
                    <?php
                    $phcs = $block['phcs'] ?? [];
                    $kindLabels = [
                        'PHC' => 'Primary Health Centres (PHC)',
                        'NT PHC' => 'NT PHCs',
                        'AAM SC' => 'Ayushman Arogya Mandir / Sub Centres',
                    ];
                    $grouped = [];
                    $hasKind = false;
                    foreach ($phcs as $phc) {
                        $kind = trim((string) ($phc['kind'] ?? ''));
                        if ($kind !== '') {
                            $hasKind = true;
                        }
                        $grouped[$kind !== '' ? $kind : '_'][] = $phc;
                    }
                    ?>
                    <?php if ($phcs === []): ?>
                        <div class="cmo-empty">No PHCs listed yet. Content will be added when provided by CMO Jammu.</div>
                    <?php elseif (!$hasKind): ?>
                        <div class="cmo-phc-list">
                            <?php foreach ($phcs as $phc): ?>
                                <a class="cmo-phc-item" href="<?= Helper::e(BlockMap::phcUrl($slug, (string) $phc['slug'])) ?>">
                                    <strong><?= Helper::e((string) $phc['name']) ?></strong>
                                    <span>Open PHC information <i class="bi bi-arrow-right"></i></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <?php
                        $kindOrder = array_merge(array_keys($kindLabels), array_keys($grouped));
                        $seen = [];
                        ?>
                        <?php foreach ($kindOrder as $kind): ?>
                            <?php
                            if (isset($seen[$kind]) || empty($grouped[$kind])) {
                                continue;
                            }
                            $seen[$kind] = true;
                            ?>
                            <div class="cmo-phc-group">
                                <h3 class="cmo-group-title"><?= Helper::e($kindLabels[$kind] ?? $kind) ?></h3>
                                <div class="cmo-phc-list">
                                    <?php foreach ($grouped[$kind] as $phc): ?>
                                        <a class="cmo-phc-item" href="<?= Helper::e(BlockMap::phcUrl($slug, (string) $phc['slug'])) ?>">
                                            <strong><?= Helper::e((string) $phc['name']) ?></strong>
                                            <span>Open information <i class="bi bi-arrow-right"></i></span>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                <?php elseif ($view === 'gallery'): ?>
                    <h2>Photo Gallery</h2>
                    <p>Photos of hospital and activities for Block <?= Helper::e($name) ?>.</p>
                    <?php $gallery = $block['gallery'] ?? []; ?>
                    <?php if ($gallery === []): ?>
                        <div class="cmo-empty">Gallery photos will appear here once provided by CMO Jammu.</div>
                    <?php else: ?>
                        <div class="cmo-gallery-grid">
                            <?php foreach ($gallery as $img): ?>
                                <img src="<?= Helper::e(BlockMap::photoUrl($img)) ?>" alt="">
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                <?php elseif ($view === 'infrastructure'): ?>
                    <h2>Infrastructure</h2>
                    <?php $sectionData = $block['infrastructure'] ?? ''; require BASE_PATH . 'includes/map-data-section.php'; ?>

                <?php elseif ($view === 'equipments'): ?>
                    <h2>Equipments Using</h2>
                    <?php $sectionData = $block['equipments'] ?? ''; require BASE_PATH . 'includes/map-data-section.php'; ?>

                <?php elseif ($view === 'roadmap'): ?>
                    <h2>Next Year Road Map</h2>
                    <?php $sectionData = $block['roadmap'] ?? ''; require BASE_PATH . 'includes/map-data-section.php'; ?>

                <?php elseif ($view === 'staff'): ?>
                    <h2>Staff List</h2>
                    <?php $staff = $block['staff'] ?? []; ?>
                    <?php if ($staff === []): ?>
                        <div class="cmo-empty">Staff list will appear here once provided by CMO Jammu.</div>
                    <?php elseif (is_array($staff) && isset($staff['groups'])): ?>
                        <?php $sectionData = $staff; require BASE_PATH . 'includes/map-data-section.php'; ?>
                    <?php else: ?>
                        <table class="cmo-staff-table">
                            <thead>
                                <tr><th>Name</th><th>Role</th><th>Phone</th></tr>
                            </thead>
                            <tbody>
                            <?php foreach ($staff as $row): ?>
                                <tr>
                                    <td><?= Helper::e((string) ($row['name'] ?? '')) ?></td>
                                    <td><?= Helper::e((string) ($row['role'] ?? '')) ?></td>
                                    <td><?= Helper::e((string) ($row['phone'] ?? '—')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="cmo-empty">Section not found.</div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require BASE_PATH . 'includes/map-cmo-end.php'; ?>

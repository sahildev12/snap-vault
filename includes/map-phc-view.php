<?php
/**
 * PHC information page (same design language as Block page)
 * Expects: $block, $phc, $view
 */
declare(strict_types=1);

$view = $view ?? 'home';
$blockSlug = (string) ($phc['block_slug'] ?? $block['slug'] ?? '');
$blockName = (string) ($phc['block_name'] ?? $block['name'] ?? 'Block');
$phcSlug = (string) ($phc['slug'] ?? '');
$name = (string) ($phc['name'] ?? 'PHC');
$person = $phc['incharge'] ?? [];
$hero = BlockMap::heroUrl($phc['hero'] ?? null);
$photo = BlockMap::photoUrl($person['photo'] ?? null);
$phone = trim((string) ($person['phone'] ?? '—'));
$phoneHref = preg_replace('/\D+/', '', $phone) ?: '';

$cmoPageTitle = $name;
$cmoSubtitle = $name;
require BASE_PATH . 'includes/map-cmo-start.php';
?>

<section class="cmo-hero" style="background-image:url('<?= Helper::e($hero) ?>')">
    <div class="cmo-hero-inner">
        <?php if ($view === 'home'): ?>
            <aside class="cmo-profile-card">
                <img class="cmo-profile-photo" src="<?= Helper::e($photo) ?>" alt="<?= Helper::e((string) ($person['name'] ?? 'In-charge')) ?>">
                <h1 class="cmo-profile-name"><?= Helper::e((string) ($person['name'] ?? 'Medical Officer')) ?></h1>
                <p class="cmo-profile-role"><?= Helper::e((string) ($person['title'] ?? 'In-charge')) ?></p>
                <?php if ($phoneHref !== ''): ?>
                    <a class="cmo-profile-phone" href="tel:<?= Helper::e($phoneHref) ?>">
                        <i class="bi bi-telephone-fill"></i> <?= Helper::e($phone) ?>
                    </a>
                <?php else: ?>
                    <div class="cmo-profile-phone"><i class="bi bi-telephone-fill"></i> <?= Helper::e($phone) ?></div>
                <?php endif; ?>
                <a class="cmo-btn cmo-staff-btn" href="<?= Helper::e(BlockMap::phcUrl($blockSlug, $phcSlug, 'staff')) ?>">
                    <i class="bi bi-people-fill"></i> Staff List
                </a>
                <a class="cmo-btn cmo-staff-btn" style="margin-top:8px;background:#6b1c1c" href="<?= Helper::e(BlockMap::blockUrl($blockSlug, 'phcs')) ?>">
                    <i class="bi bi-arrow-left"></i> Back to PHC list
                </a>
            </aside>

            <div class="cmo-actions-card">
                <div class="cmo-action-grid">
                    <a class="cmo-action" href="<?= Helper::e(BlockMap::phcUrl($blockSlug, $phcSlug, 'details')) ?>">
                        <span class="cmo-action-icon is-info"><i class="bi bi-info-circle"></i></span>
                        <h2 class="cmo-action-title">PHC Details</h2>
                        <span class="cmo-action-rule"></span>
                        <p class="cmo-action-text">Information provided for this PHC</p>
                    </a>
                    <a class="cmo-action" href="<?= Helper::e(BlockMap::phcUrl($blockSlug, $phcSlug, 'gallery')) ?>">
                        <span class="cmo-action-icon is-gallery"><i class="bi bi-image"></i></span>
                        <h2 class="cmo-action-title">Photo Gallery</h2>
                        <span class="cmo-action-rule"></span>
                        <p class="cmo-action-text">View photos of this PHC</p>
                    </a>
                    <a class="cmo-action" href="<?= Helper::e((string) ($phc['maps_url'] ?? '#')) ?>" target="_blank" rel="noopener">
                        <span class="cmo-action-icon is-maps"><i class="bi bi-geo-alt-fill"></i></span>
                        <h2 class="cmo-action-title">Google Location</h2>
                        <span class="cmo-action-rule"></span>
                        <p class="cmo-action-text">View PHC location on Google Maps</p>
                    </a>
                    <a class="cmo-action" href="<?= Helper::e(BlockMap::phcUrl($blockSlug, $phcSlug, 'infrastructure')) ?>">
                        <span class="cmo-action-icon is-infra"><i class="bi bi-buildings"></i></span>
                        <h2 class="cmo-action-title">Infrastructure</h2>
                        <span class="cmo-action-rule"></span>
                        <p class="cmo-action-text">Details of infrastructure and facilities</p>
                    </a>
                    <a class="cmo-action" href="<?= Helper::e(BlockMap::phcUrl($blockSlug, $phcSlug, 'equipments')) ?>">
                        <span class="cmo-action-icon is-equip"><i class="bi bi-gear-wide-connected"></i></span>
                        <h2 class="cmo-action-title">Equipments Using</h2>
                        <span class="cmo-action-rule"></span>
                        <p class="cmo-action-text">Information about major equipments</p>
                    </a>
                    <a class="cmo-action" href="<?= Helper::e(BlockMap::phcUrl($blockSlug, $phcSlug, 'roadmap')) ?>">
                        <span class="cmo-action-icon is-road"><i class="bi bi-map"></i></span>
                        <h2 class="cmo-action-title">Next Year Road Map</h2>
                        <span class="cmo-action-rule"></span>
                        <p class="cmo-action-text">Plan and proposed developments</p>
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="cmo-content-card">
                <a class="cmo-back-link" href="<?= Helper::e(BlockMap::phcUrl($blockSlug, $phcSlug)) ?>">
                    <i class="bi bi-arrow-left"></i> Back to <?= Helper::e($name) ?>
                </a>
                <p class="text-muted" style="margin-top:-6px;margin-bottom:14px;font-size:13px">
                    Block <?= Helper::e($blockName) ?>
                </p>

                <?php if ($view === 'details'): ?>
                    <h2>PHC Details</h2>
                    <p><?= nl2br(Helper::e((string) ($phc['details'] ?? ''))) ?></p>

                <?php elseif ($view === 'gallery'): ?>
                    <h2>Photo Gallery</h2>
                    <?php $gallery = $phc['gallery'] ?? []; ?>
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
                    <p><?= nl2br(Helper::e((string) ($phc['infrastructure'] ?? ''))) ?></p>

                <?php elseif ($view === 'equipments'): ?>
                    <h2>Equipments Using</h2>
                    <p><?= nl2br(Helper::e((string) ($phc['equipments'] ?? ''))) ?></p>

                <?php elseif ($view === 'roadmap'): ?>
                    <h2>Next Year Road Map</h2>
                    <p><?= nl2br(Helper::e((string) ($phc['roadmap'] ?? ''))) ?></p>

                <?php elseif ($view === 'staff'): ?>
                    <h2>Staff List</h2>
                    <?php $staff = $phc['staff'] ?? []; ?>
                    <?php if ($staff === []): ?>
                        <div class="cmo-empty">Staff list will appear here once provided by CMO Jammu.</div>
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

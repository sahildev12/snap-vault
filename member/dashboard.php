<?php
/**
 * Member Dashboard
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';
require_once BASE_PATH . 'includes/auth_check.php';
$requiredRole = 'member';
require BASE_PATH . 'includes/role_check.php';

$userId = Auth::id();
$totalImages  = Upload::countByUser((int) $userId);
$todayUploads = Upload::countToday((int) $userId);
$recent       = Upload::recent(8, (int) $userId);

$pageTitle = 'Dashboard';
require BASE_PATH . 'includes/header.php';
?>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon bg-soft-teal"><i class="bi bi-images"></i></div>
            <div>
                <div class="stat-label">Total Uploaded</div>
                <div class="stat-value"><?= (int) $totalImages ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon bg-soft-amber"><i class="bi bi-calendar-day"></i></div>
            <div>
                <div class="stat-label">Today's Uploads</div>
                <div class="stat-value"><?= (int) $todayUploads ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <a href="<?= Helper::e(BASE_URL . 'member/upload.php') ?>" class="stat-card stat-card-cta text-decoration-none">
            <div class="stat-icon bg-soft-primary"><i class="bi bi-cloud-upload"></i></div>
            <div>
                <div class="stat-label">Quick Upload</div>
                <div class="stat-value fs-5">Capture or choose</div>
            </div>
        </a>
    </div>
</div>

<div class="card panel-card">
    <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
        <h2 class="h6 mb-0">My Recent Uploads</h2>
        <a href="<?= Helper::e(BASE_URL . 'member/gallery.php') ?>" class="btn btn-sm btn-outline-primary">View Gallery</a>
    </div>
    <div class="card-body">
        <?php if ($recent === []): ?>
            <div class="empty-state py-4">
                <i class="bi bi-inbox"></i>
                <p>No uploads yet. Start by uploading your first image.</p>
                <a href="<?= Helper::e(BASE_URL . 'member/upload.php') ?>" class="btn btn-primary">Upload Image</a>
            </div>
        <?php else: ?>
            <div class="row g-3 lightbox-gallery">
                <?php foreach ($recent as $row): ?>
                    <div class="col-6 col-md-3">
                        <div class="image-card">
                            <div class="image-card-thumb">
                                <img src="<?= Helper::e(Helper::uploadUrl($row['image'])) ?>" alt=""
                                     class="lightbox-trigger"
                                     data-src="<?= Helper::e(Helper::uploadUrl($row['image'])) ?>"
                                     data-title="<?= Helper::e(Helper::truncate($row['description'] ?? 'Image', 40)) ?>"
                                     data-meta="<?= Helper::e(Helper::formatDate($row['created_at'])) ?>">
                            </div>
                            <div class="image-card-body">
                                <div class="image-card-meta">
                                    <div class="image-card-meta-row">
                                        <span class="image-card-meta-item">
                                            <i class="bi bi-calendar3" aria-hidden="true"></i>
                                            <?= Helper::e(Helper::formatDate($row['created_at'], 'd M Y')) ?>
                                        </span>
                                        <?= Helper::flagBadge($row['flag']) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require BASE_PATH . 'includes/footer.php'; ?>

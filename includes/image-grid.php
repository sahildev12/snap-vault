<?php
/**
 * Shared image card grid
 * Expects: $images (rows), $canDelete (bool), optional $showUploader
 */
declare(strict_types=1);

$images = $images ?? [];
$canDelete = $canDelete ?? true;
$showUploader = $showUploader ?? false;

if ($images === []): ?>
    <div class="empty-state py-5">
        <i class="bi bi-images"></i>
        <p>No images found.</p>
    </div>
<?php else: ?>
    <div class="row g-3 lightbox-gallery" id="imageGrid">
        <?php foreach ($images as $img): ?>
            <div class="col-6 col-md-4 col-xl-3 image-grid-item" data-id="<?= (int) $img['id'] ?>">
                <div class="image-card">
                    <div class="image-card-thumb">
                        <img src="<?= Helper::e(Helper::uploadUrl($img['image'])) ?>"
                             alt="<?= Helper::e(Helper::truncate($img['description'] ?? '', 40)) ?>"
                             class="lightbox-trigger"
                             data-src="<?= Helper::e(Helper::uploadUrl($img['image'])) ?>"
                             data-title="<?= Helper::e($showUploader ? ($img['uploader_name'] ?? '') : Helper::truncate($img['description'] ?? 'Image', 50)) ?>"
                             data-meta="<?= Helper::e(Helper::formatDate($img['created_at'])) ?>">
                    </div>
                    <div class="image-card-body">
                        <div class="image-card-meta">
                            <div class="image-card-meta-row">
                                <span class="image-card-meta-item">
                                    <i class="bi bi-calendar3" aria-hidden="true"></i>
                                    <?= Helper::e(Helper::formatDate($img['created_at'], 'd M Y')) ?>
                                </span>
                                <?= Helper::flagBadge($img['flag']) ?>
                            </div>
                            <?php if ($showUploader): ?>
                                <div class="image-card-meta-item">
                                    <i class="bi bi-person" aria-hidden="true"></i>
                                    <?= Helper::e($img['uploader_name'] ?? '') ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($img['description'])): ?>
                            <div class="image-desc"><?= Helper::e(Helper::truncate($img['description'], 60)) ?></div>
                        <?php else: ?>
                            <div class="image-desc text-muted">No description</div>
                        <?php endif; ?>
                        <div class="image-card-actions mt-2">
                            <a href="<?= Helper::e(BASE_URL . 'download.php?id=' . (int) $img['id']) ?>"
                               class="btn btn-sm btn-outline-secondary" title="Download">
                                Download
                            </a>
                            <?php if ($canDelete): ?>
                                <button type="button"
                                        class="btn btn-sm btn-outline-danger btn-delete-image"
                                        data-id="<?= (int) $img['id'] ?>"
                                        title="Delete">
                                    Delete
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

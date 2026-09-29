<?php
/**
 * Presentation file list
 * Expects: $presentations (rows), $canDelete, $showUploader
 */
declare(strict_types=1);

$presentations = $presentations ?? [];
$canDelete = $canDelete ?? false;
$showUploader = $showUploader ?? false;
$canView = $canView ?? true;
$isHttps = Helper::isHttpsRequest();
?>
<?php if ($presentations === []): ?>
    <div class="card panel-card">
        <div class="card-body text-center py-5 text-muted">
            <i class="bi bi-easel display-6 d-block mb-3"></i>
            <p class="mb-0">No presentations uploaded yet.</p>
        </div>
    </div>
<?php else: ?>
    <div class="card panel-card">
        <div class="presentation-list">
            <?php foreach ($presentations as $row): ?>
                <?php
                $ext = Presentation::fileExtension($row);
                $downloadUrl = BASE_URL . 'download-presentation.php?id=' . (int) $row['id'];
                $inlineUrl = Presentation::inlineViewUrl((int) $row['id']);
                $publicUrl = Presentation::publicFileUrl((int) $row['id']);
                $officeUrl = Presentation::officeEmbedUrl((int) $row['id']);
                ?>
                <article class="presentation-item presentation-row" data-id="<?= (int) $row['id'] ?>">
                    <div class="presentation-item-main">
                        <span class="presentation-icon" aria-hidden="true">
                            <i class="bi <?= Helper::e(Presentation::fileIcon((string) $row['filename'])) ?>"></i>
                        </span>
                        <div class="presentation-item-copy min-w-0">
                            <div class="presentation-title">
                                <?= Helper::e($row['title']) ?>
                                <?php if (!empty($row['is_demo'])): ?>
                                    <span class="badge bg-secondary-subtle text-secondary-emphasis ms-1">Demo</span>
                                <?php endif; ?>
                            </div>
                            <div class="presentation-meta text-muted small">
                                <?= Helper::e($row['original_name']) ?>
                            </div>
                            <?php if (!empty($row['description'])): ?>
                                <div class="presentation-desc text-muted small">
                                    <?= Helper::e($row['description']) ?>
                                </div>
                            <?php endif; ?>
                            <div class="presentation-tags">
                                <?php if ($showUploader): ?>
                                    <span class="presentation-tag">
                                        <i class="bi bi-person" aria-hidden="true"></i>
                                        <?= Helper::e($row['uploader_name']) ?>
                                    </span>
                                <?php endif; ?>
                                <span class="presentation-tag">
                                    <i class="bi bi-calendar-event" aria-hidden="true"></i>
                                    <?= !empty($row['meeting_date'])
                                        ? Helper::e(Helper::formatDate($row['meeting_date'], 'd M Y'))
                                        : 'No meeting date' ?>
                                </span>
                                <span class="presentation-tag">
                                    <i class="bi bi-clock" aria-hidden="true"></i>
                                    <?= Helper::e(Helper::formatDate($row['created_at'], 'd M Y')) ?>
                                </span>
                                <span class="presentation-tag">
                                    <i class="bi bi-hdd" aria-hidden="true"></i>
                                    <?= Helper::e(Presentation::formatSize(isset($row['file_size']) ? (int) $row['file_size'] : null)) ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="presentation-actions">
                        <?php if ($canView): ?>
                            <button type="button"
                                    class="presentation-act presentation-act-view btn-view-presentation"
                                    title="View"
                                    data-id="<?= (int) $row['id'] ?>"
                                    data-title="<?= Helper::e($row['title']) ?>"
                                    data-filename="<?= Helper::e($row['original_name']) ?>"
                                    data-ext="<?= Helper::e($ext) ?>"
                                    data-inline-url="<?= Helper::e($inlineUrl) ?>"
                                    data-public-url="<?= Helper::e($publicUrl) ?>"
                                    data-office-url="<?= Helper::e($officeUrl) ?>"
                                    data-download-url="<?= Helper::e($downloadUrl) ?>"
                                    data-https="<?= $isHttps ? '1' : '0' ?>">
                                <i class="bi bi-eye" aria-hidden="true"></i>
                                <span>View</span>
                            </button>
                        <?php endif; ?>
                        <a href="<?= Helper::e($downloadUrl) ?>"
                           class="presentation-act presentation-act-download"
                           title="Download">
                            <i class="bi bi-download" aria-hidden="true"></i>
                            <span>Download</span>
                        </a>
                        <?php if ($canDelete): ?>
                            <button type="button"
                                    class="presentation-act presentation-act-delete btn-delete-presentation"
                                    data-id="<?= (int) $row['id'] ?>"
                                    title="Delete">
                                <i class="bi bi-trash" aria-hidden="true"></i>
                                <span>Delete</span>
                            </button>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>

    <?php require BASE_PATH . 'includes/presentation-viewer-modal.php'; ?>
<?php endif; ?>

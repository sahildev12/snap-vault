<?php
/**
 * Member — Upload and manage meeting presentations
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';
require_once BASE_PATH . 'includes/auth_check.php';
$requiredRole = 'member';
require BASE_PATH . 'includes/role_check.php';

$userId = (int) Auth::id();
$presentationsReady = SchemaMigrator::tableExists('presentations');
$errors = [];
$title = '';
$description = '';
$meetingDate = '';

if ($presentationsReady && Helper::isPost()) {
    Csrf::requireValid();

    $title = trim((string) Helper::input('title', ''));
    $description = trim((string) Helper::input('description', ''));
    $meetingDate = trim((string) Helper::input('meeting_date', ''));

    if ($title === '') {
        $errors[] = 'Please enter a presentation title.';
    } elseif (mb_strlen($title) > 200) {
        $errors[] = 'Title must be 200 characters or fewer.';
    }

    if ($meetingDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $meetingDate)) {
        $errors[] = 'Please enter a valid meeting date.';
    }

    if (empty($_FILES['presentation']['name'])) {
        $errors[] = 'Please select a presentation file.';
    }

    if ($errors === []) {
        $stored = Presentation::storeFile($_FILES['presentation']);
        if (!$stored['success']) {
            $errors[] = $stored['message'];
        } else {
            Presentation::create(
                $userId,
                $title,
                $stored['filename'],
                $stored['original_name'],
                $description,
                $meetingDate,
                $stored['file_size'] ?? null
            );
            Helper::setFlash('success', 'Presentation uploaded successfully. It is now available for CMO meetings.');
            Helper::redirect(BASE_URL . 'member/presentations.php');
        }
    }
}

$pageTitle = 'Presentations';
require BASE_PATH . 'includes/header.php';

if (!$presentationsReady) {
    $featureLabel = 'Presentations';
    require BASE_PATH . 'includes/migration-required.php';
    require BASE_PATH . 'includes/footer.php';
    return;
}

$page = max(1, (int) Helper::input('page', 1));
$filters = [
    'period' => (string) Helper::input('period', ''),
    'search' => trim((string) Helper::input('search', '')),
    'from'   => (string) Helper::input('from', ''),
    'to'     => (string) Helper::input('to', ''),
];

$result = Presentation::listByUser($userId, $filters, $page, PER_PAGE);

$query = http_build_query(array_filter($filters, static fn($v) => $v !== '' && $v !== null));
$baseUrl = BASE_URL . 'member/presentations.php?' . ($query !== '' ? $query . '&' : '') . 'page=';
?>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card panel-card">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="presentation-upload-badge"><i class="bi bi-easel"></i></span>
                    <div>
                        <h2 class="h5 mb-0">Upload Presentation</h2>
                        <p class="text-muted small mb-0">Share your meeting slides with the CMO office in advance.</p>
                    </div>
                </div>

                <?php if ($errors !== []): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0 ps-3">
                            <?php foreach ($errors as $err): ?>
                                <li><?= Helper::e($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post" enctype="multipart/form-data" id="presentationForm" novalidate>
                    <?= Csrf::field() ?>

                    <div class="mb-3">
                        <label class="form-label" for="title">Title</label>
                        <input type="text" name="title" id="title" class="form-control"
                               maxlength="200" required
                               placeholder="e.g. Monthly review — Pallanwala"
                               value="<?= Helper::e($title) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="meeting_date">Meeting date (optional)</label>
                        <input type="date" name="meeting_date" id="meeting_date" class="form-control"
                               value="<?= Helper::e($meetingDate) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="description">Notes (optional)</label>
                        <textarea name="description" id="description" class="form-control" rows="3"
                                  placeholder="Agenda, block name, or any notes for the CMO team…"><?= Helper::e($description) ?></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label" for="presentationFileTrigger">Presentation file</label>
                        <div class="file-field">
                            <div class="file-picker">
                                <input type="file" name="presentation" id="presentation_file" class="file-picker-input"
                                       accept=".ppt,.pptx,.pdf,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation,application/pdf">
                                <button type="button" class="btn btn-outline-secondary file-picker-btn" id="presentationFileTrigger">
                                    Choose File
                                </button>
                                <span class="file-picker-name" id="presentationFileName">No file chosen</span>
                            </div>
                        </div>
                        <div class="form-text">PPT, PPTX, or PDF · max 50MB</div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-100">
                        <i class="bi bi-cloud-arrow-up me-1"></i>Upload Presentation
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <?php
        $actionUrl = BASE_URL . 'member/presentations.php';
        $showMemberFilter = false;
        require BASE_PATH . 'includes/presentation-filter-bar.php';
        ?>

        <div class="toolbar-row mb-3">
            <div class="text-muted small">
                <strong><?= (int) $result['total'] ?></strong>
                presentation<?= $result['total'] === 1 ? '' : 's' ?>
            </div>
        </div>

        <?php
        $presentations = $result['rows'];
        $canDelete = true;
        $showUploader = false;
        require BASE_PATH . 'includes/presentation-list.php';
        ?>

        <?php if ($result['total'] > PER_PAGE): ?>
            <div class="mt-4">
                <?= Helper::pagination($result['total'], $page, PER_PAGE, $baseUrl) ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$extraScripts = <<<'JS'
<script>
(function () {
    const input = document.getElementById("presentation_file");
    const trigger = document.getElementById("presentationFileTrigger");
    const nameEl = document.getElementById("presentationFileName");
    const form = document.getElementById("presentationForm");

    trigger?.addEventListener("click", () => input?.click());
    input?.addEventListener("change", () => {
        const file = input.files && input.files[0];
        nameEl.textContent = file ? file.name : "No file chosen";
    });

    form?.addEventListener("submit", (e) => {
        const title = document.getElementById("title");
        if (!title?.value.trim()) {
            e.preventDefault();
            window.SnapVault?.toast("Please enter a presentation title.", "danger");
            return;
        }
        if (!input?.files?.length) {
            e.preventDefault();
            window.SnapVault?.toast("Please select a presentation file.", "danger");
        }
    });
})();
</script>
JS;

require BASE_PATH . 'includes/footer.php';
?>

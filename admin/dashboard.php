<?php
/**
 * Admin Dashboard
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';
require_once BASE_PATH . 'includes/auth_check.php';
$requiredRole = 'admin';
require BASE_PATH . 'includes/role_check.php';

$totalMembers   = User::countMembers();
$totalImages    = Upload::countAll();
$todayUploads   = Upload::countToday();
$flagCounts = [];
foreach (Helper::uploadFlags() as $f) {
    try {
        $flagCounts[$f] = Upload::countByFlag($f);
    } catch (Throwable $e) {
        AppLog::warning('Dashboard flag count failed', ['flag' => $f, 'error' => $e->getMessage()]);
        $flagCounts[$f] = 0;
    }
}
$taskCounts = Task::counts();
$recent         = Upload::recent(10);
$chart7         = Upload::last7DaysCounts();

$pageTitle = 'Dashboard';
require BASE_PATH . 'includes/header.php';
?>

<div class="row g-3 mb-4 stats-grid">
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-soft-primary"><i class="bi bi-people"></i></div>
            <div>
                <div class="stat-label">Team Members</div>
                <div class="stat-value"><?= (int) $totalMembers ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-soft-teal"><i class="bi bi-images"></i></div>
            <div>
                <div class="stat-label">Total Images</div>
                <div class="stat-value"><?= (int) $totalImages ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-soft-amber"><i class="bi bi-calendar-day"></i></div>
            <div>
                <div class="stat-label">Today's Uploads</div>
                <div class="stat-value"><?= (int) $todayUploads ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <a href="<?= Helper::e(BASE_URL . 'admin/tasks.php') ?>" class="stat-card text-decoration-none">
            <div class="stat-icon bg-soft-danger"><i class="bi bi-check2-square"></i></div>
            <div>
                <div class="stat-label">Open Tasks</div>
                <div class="stat-value"><?= (int) ($taskCounts['pending'] + $taskCounts['in_progress'] + $taskCounts['overdue']) ?></div>
            </div>
        </a>
    </div>
    <?php foreach ($flagCounts as $flagName => $flagTotal): ?>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="bi bi-flag"></i></div>
                <div>
                    <div class="stat-label"><?= Helper::e(ucfirst($flagName)) ?></div>
                    <div class="stat-value"><?= (int) $flagTotal ?></div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card panel-card h-100">
            <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
                <h2 class="h6 mb-0">Uploads — Last 7 Days</h2>
            </div>
            <div class="card-body">
                <canvas id="uploadsChart" height="120"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card panel-card h-100">
            <div class="card-header bg-transparent border-0">
                <h2 class="h6 mb-0">Flag Distribution</h2>
            </div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <canvas id="flagChart" height="180"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="card panel-card">
    <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
        <h2 class="h6 mb-0">Recent Upload Activity</h2>
        <a href="<?= Helper::e(BASE_URL . 'admin/folders.php') ?>" class="btn btn-sm btn-outline-primary">
            Open Image Manager
        </a>
    </div>
    <div class="card-body p-0">
        <?php if ($recent === []): ?>
            <div class="empty-state py-5">
                <i class="bi bi-inbox"></i>
                <p>No uploads yet.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 activity-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Member</th>
                            <th>Flag</th>
                            <th>Description</th>
                            <th>Uploaded</th>
                        </tr>
                    </thead>
                    <tbody class="lightbox-gallery">
                    <?php foreach ($recent as $row): ?>
                        <tr>
                            <td>
                                <img src="<?= Helper::e(Helper::uploadUrl($row['image'])) ?>"
                                     alt="" class="thumb-sm lightbox-trigger"
                                     data-src="<?= Helper::e(Helper::uploadUrl($row['image'])) ?>"
                                     data-title="<?= Helper::e($row['uploader_name']) ?>"
                                     data-meta="<?= Helper::e(Helper::formatDate($row['created_at'])) ?>">
                            </td>
                            <td><?= Helper::e($row['uploader_name']) ?></td>
                            <td><?= Helper::flagBadge($row['flag']) ?></td>
                            <td class="text-muted"><?= Helper::e(Helper::truncate($row['description'] ?? '', 40)) ?></td>
                            <td class="text-nowrap"><?= Helper::e(Helper::formatDate($row['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$extraScripts = '<script>
document.addEventListener("DOMContentLoaded", function () {
    const labels = ' . json_encode($chart7['labels']) . ';
    const counts = ' . json_encode($chart7['counts']) . ';
    const ctx = document.getElementById("uploadsChart");
    if (ctx && window.Chart) {
        new Chart(ctx, {
            type: "bar",
            data: {
                labels: labels,
                datasets: [{
                    label: "Uploads",
                    data: counts,
                    backgroundColor: "#0066cc",
                    borderRadius: 6,
                    maxBarThickness: 28
                }]
            },
            options: {
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0, color: "#86868b" }, grid: { color: "rgba(0,0,0,0.04)" }, border: { display: false } },
                    x: { ticks: { color: "#86868b" }, grid: { display: false }, border: { display: false } }
                }
            }
        });
    }
    const flagCtx = document.getElementById("flagChart");
    if (flagCtx && window.Chart) {
        new Chart(flagCtx, {
            type: "doughnut",
            data: {
                labels: ' . json_encode(array_map('ucfirst', array_keys($flagCounts))) . ',
                datasets: [{
                    data: ' . json_encode(array_values($flagCounts)) . ',
                    backgroundColor: ["#34c759", "#ff3b30", "#f5a524", "#6366f1"],
                    borderWidth: 0
                }]
            },
            options: {
                cutout: "68%",
                plugins: { legend: { position: "bottom", labels: { color: "#1d1d1f", boxWidth: 10, font: { size: 12 } } } }
            }
        });
    }
});
</script>';
require BASE_PATH . 'includes/footer.php';
?>

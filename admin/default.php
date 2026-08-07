<?php
/**
 * Admin — Default page template
 * Starter layout: intro, toolbar, content card, optional table.
 * Duplicate this file when building new admin screens.
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';
require_once BASE_PATH . 'includes/auth_check.php';
$requiredRole = 'admin';
require BASE_PATH . 'includes/role_check.php';

$search = trim((string) Helper::input('q', ''));

$pageTitle = 'Default Page';
require BASE_PATH . 'includes/header.php';
?>

<section class="page-intro">
    <p class="page-intro-text">
        This is the default page template. Replace this copy, then add your content below
        the toolbar. Keep spacing and components consistent with the
        <a href="<?= Helper::e(BASE_URL . 'admin/components.php') ?>" class="text-link">Components</a> page.
    </p>
</section>

<div class="page-toolbar">
    <form class="page-search" method="get" action="" role="search">
        <i class="bi bi-search page-search-icon" aria-hidden="true"></i>
        <input type="search" name="q" class="page-search-input"
               placeholder="Search"
               value="<?= Helper::e($search) ?>"
               aria-label="Search">
        <?php if ($search !== ''): ?>
            <a href="<?= Helper::e(BASE_URL . 'admin/default.php') ?>" class="page-search-clear" title="Clear" aria-label="Clear">
                <i class="bi bi-x-lg"></i>
            </a>
        <?php endif; ?>
    </form>
    <div class="page-toolbar-meta">
        <button type="button" class="btn btn-outline-secondary filter-btn">Secondary</button>
        <button type="button" class="btn btn-primary filter-btn">Primary</button>
    </div>
</div>

<div class="card panel-card mb-4">
    <div class="card-header bg-transparent border-0">
        <h2 class="h6 mb-0">Content Card</h2>
    </div>
    <div class="card-body">
        <p class="comp-body mb-3">
            Place primary page content here — forms, descriptions, or nested grids.
            Prefer hairline borders and parchment canvas; avoid extra shadows.
        </p>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-primary">Save</button>
            <button type="button" class="btn btn-outline-secondary">Cancel</button>
            <a href="<?= Helper::e(BASE_URL . 'admin/components.php') ?>" class="btn btn-outline-secondary">View Components</a>
        </div>
    </div>
</div>

<div class="card panel-card">
    <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
        <h2 class="h6 mb-0">Sample Data Table</h2>
        <span class="page-toolbar-count">Demo rows</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1</td>
                        <td>Primary action</td>
                        <td><code>btn-primary</code></td>
                        <td><?= Helper::flagBadge('normal') ?></td>
                        <td class="text-end"><button type="button" class="btn btn-outline-secondary filter-btn">Edit</button></td>
                    </tr>
                    <tr>
                        <td>2</td>
                        <td>Secondary action</td>
                        <td><code>btn-outline-secondary</code></td>
                        <td><?= Helper::flagBadge('important') ?></td>
                        <td class="text-end"><button type="button" class="btn btn-outline-secondary filter-btn">Edit</button></td>
                    </tr>
                    <tr>
                        <td>3</td>
                        <td>Dark utility</td>
                        <td><code>btn-dark-utility</code></td>
                        <td><?= Helper::statusBadge(1) ?></td>
                        <td class="text-end"><button type="button" class="btn btn-outline-danger filter-btn">Delete</button></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require BASE_PATH . 'includes/footer.php'; ?>

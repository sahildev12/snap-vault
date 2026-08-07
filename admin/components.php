<?php
/**
 * Admin — UI Components reference
 * Showcase of theme building blocks for SnapVault.
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';
require_once BASE_PATH . 'includes/auth_check.php';
$requiredRole = 'admin';
require BASE_PATH . 'includes/role_check.php';

// Live samples from database (when available)
$sampleMembers = User::listMembers('', 1, 5);
$sampleUploads = Upload::recent(5);
$stats = [
    'members'   => User::countMembers(),
    'images'    => Upload::countAll(),
    'today'     => Upload::countToday(),
    'important' => Upload::countByFlag('important'),
    'normal'    => Upload::countByFlag('normal'),
];

$pageTitle = 'Components';
require BASE_PATH . 'includes/header.php';
?>

<section class="page-intro">
    <p class="page-intro-text">
        Design system reference for SnapVault — fonts, colors, buttons, forms, tables, and patterns.
        Use this page to keep UI consistent across admin and member screens.
    </p>
</section>

<nav class="comp-toc" aria-label="Component sections">
    <a href="#typography">Typography</a>
    <a href="#colors">Colors</a>
    <a href="#buttons">Buttons</a>
    <a href="#forms">Forms</a>
    <a href="#badges">Badges</a>
    <a href="#alerts">Alerts</a>
    <a href="#cards">Cards</a>
    <a href="#table">Data Table</a>
    <a href="#filters">Filters</a>
    <a href="#empty">Empty State</a>
</nav>

<!-- Typography -->
<section class="comp-section" id="typography">
    <div class="comp-section-head">
        <h2 class="comp-section-title">Typography</h2>
        <p class="comp-section-desc">System UI stack · weight 400 / 600 · Apple-tight tracking on display sizes</p>
    </div>
    <div class="card panel-card">
        <div class="card-body comp-type-stack">
            <div class="comp-type-row">
                <span class="comp-type-meta">Display 34 / 600</span>
                <div class="comp-display-lg">SnapVault Image Vault</div>
            </div>
            <div class="comp-type-row">
                <span class="comp-type-meta">Title 21 / 600</span>
                <div class="comp-title">Page heading</div>
            </div>
            <div class="comp-type-row">
                <span class="comp-type-meta">Body 17 / 400</span>
                <p class="comp-body mb-0">
                    Body copy runs at 17px with relaxed leading. Capture, flag, and organize team photos
                    from a single quiet interface.
                </p>
            </div>
            <div class="comp-type-row">
                <span class="comp-type-meta">Caption 14 / 400</span>
                <div class="comp-caption">Secondary captions, button labels, table cells</div>
            </div>
            <div class="comp-type-row">
                <span class="comp-type-meta">Fine 12 / 400</span>
                <div class="comp-fine">Fine print, muted metadata, nav labels</div>
            </div>
            <div class="comp-type-row">
                <span class="comp-type-meta">Link</span>
                <div><a href="#" class="text-link">Action blue text link</a></div>
            </div>
        </div>
    </div>
</section>

<!-- Colors -->
<section class="comp-section" id="colors">
    <div class="comp-section-head">
        <h2 class="comp-section-title">Colors</h2>
        <p class="comp-section-desc">Single accent · parchment canvas · near-black ink</p>
    </div>
    <div class="comp-swatch-grid">
        <div class="comp-swatch"><span style="background:#0066cc"></span><code>Primary</code><small>#0066cc</small></div>
        <div class="comp-swatch"><span style="background:#0071e3"></span><code>Focus</code><small>#0071e3</small></div>
        <div class="comp-swatch"><span style="background:#1d1d1f"></span><code>Ink</code><small>#1d1d1f</small></div>
        <div class="comp-swatch"><span style="background:#86868b"></span><code>Muted</code><small>#86868b</small></div>
        <div class="comp-swatch"><span style="background:#f5f5f7;border:1px solid #e0e0e0"></span><code>Parchment</code><small>#f5f5f7</small></div>
        <div class="comp-swatch"><span style="background:#ffffff;border:1px solid #e0e0e0"></span><code>Canvas</code><small>#ffffff</small></div>
        <div class="comp-swatch"><span style="background:#34c759"></span><code>Success</code><small>#34c759</small></div>
        <div class="comp-swatch"><span style="background:#ff3b30"></span><code>Danger</code><small>#ff3b30</small></div>
    </div>
</section>

<!-- Buttons -->
<section class="comp-section" id="buttons">
    <div class="comp-section-head">
        <h2 class="comp-section-title">Buttons</h2>
        <p class="comp-section-desc">Primary pill · secondary outline · dark utility · compact filter size</p>
    </div>
    <div class="card panel-card">
        <div class="card-body">
            <div class="comp-row mb-4">
                <span class="comp-row-label">Primary</span>
                <div class="comp-row-content">
                    <button type="button" class="btn btn-primary">Primary</button>
                    <button type="button" class="btn btn-primary btn-lg">Primary Large</button>
                    <button type="button" class="btn btn-primary filter-btn">Filter Size</button>
                </div>
            </div>
            <div class="comp-row mb-4">
                <span class="comp-row-label">Secondary</span>
                <div class="comp-row-content">
                    <button type="button" class="btn btn-outline-primary">Outline Primary</button>
                    <button type="button" class="btn btn-outline-secondary">Secondary</button>
                    <button type="button" class="btn btn-outline-secondary filter-btn">Reset</button>
                </div>
            </div>
            <div class="comp-row mb-4">
                <span class="comp-row-label">Utility</span>
                <div class="comp-row-content">
                    <button type="button" class="btn btn-dark-utility">Dark Utility</button>
                    <button type="button" class="btn btn-outline-danger filter-btn">Delete</button>
                    <button type="button" class="btn btn-icon" aria-label="Icon"><i class="bi bi-list"></i></button>
                </div>
            </div>
            <div class="comp-row">
                <span class="comp-row-label">Links</span>
                <div class="comp-row-content">
                    <a href="#" class="text-link">Text link</a>
                    <a href="<?= Helper::e(BASE_URL . 'admin/default.php') ?>" class="text-link">Open Default Page</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Forms -->
<section class="comp-section" id="forms">
    <div class="comp-section-head">
        <h2 class="comp-section-title">Forms</h2>
        <p class="comp-section-desc">Inputs, themed selects, search, labels</p>
    </div>
    <div class="card panel-card">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="demoName">Full Name</label>
                    <input type="text" id="demoName" class="form-control" placeholder="Enter name">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="demoStatus">Status</label>
                    <select id="demoStatus" class="form-select sv-select">
                        <option value="1">Enabled</option>
                        <option value="0">Disabled</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="demoFlag">Flag</label>
                    <select id="demoFlag" class="form-select sv-select">
                        <option value="">All flags</option>
                        <option value="normal">Normal</option>
                        <option value="important">Important</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Search</label>
                    <div class="page-search" style="max-width:100%">
                        <i class="bi bi-search page-search-icon" aria-hidden="true"></i>
                        <input type="search" class="page-search-input" placeholder="Search…" aria-label="Search demo">
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label" for="demoDesc">Description</label>
                    <textarea id="demoDesc" class="form-control" rows="3" placeholder="Optional note…"></textarea>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Badges -->
<section class="comp-section" id="badges">
    <div class="comp-section-head">
        <h2 class="comp-section-title">Badges</h2>
        <p class="comp-section-desc">Flags and status chips</p>
    </div>
    <div class="card panel-card">
        <div class="card-body d-flex flex-wrap gap-2 align-items-center">
            <?= Helper::flagBadge('normal') ?>
            <?= Helper::flagBadge('important') ?>
            <?= Helper::statusBadge(1) ?>
            <?= Helper::statusBadge(0) ?>
        </div>
    </div>
</section>

<!-- Alerts -->
<section class="comp-section" id="alerts">
    <div class="comp-section-head">
        <h2 class="comp-section-title">Alerts</h2>
        <p class="comp-section-desc">Flash / feedback messages</p>
    </div>
    <div class="alert alert-success">Success — changes saved.</div>
    <div class="alert alert-danger">Danger — something went wrong.</div>
    <div class="alert alert-warning">Warning — please review this action.</div>
    <div class="alert alert-info mb-0">Info — helpful context for the user.</div>
</section>

<!-- Cards / Stats -->
<section class="comp-section" id="cards">
    <div class="comp-section-head">
        <h2 class="comp-section-title">Cards &amp; Stats</h2>
        <p class="comp-section-desc">Live counts from the database</p>
    </div>
    <div class="row g-3 mb-3">
        <div class="col-6 col-md">
            <div class="stat-card">
                <div class="stat-icon"><i class="bi bi-people"></i></div>
                <div>
                    <div class="stat-label">Members</div>
                    <div class="stat-value"><?= (int) $stats['members'] ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md">
            <div class="stat-card">
                <div class="stat-icon"><i class="bi bi-images"></i></div>
                <div>
                    <div class="stat-label">Images</div>
                    <div class="stat-value"><?= (int) $stats['images'] ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md">
            <div class="stat-card">
                <div class="stat-icon"><i class="bi bi-calendar-day"></i></div>
                <div>
                    <div class="stat-label">Today</div>
                    <div class="stat-value"><?= (int) $stats['today'] ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md">
            <div class="stat-card stat-card-cta text-decoration-none">
                <div class="stat-icon"><i class="bi bi-upload"></i></div>
                <div>
                    <div class="stat-label">CTA Card</div>
                    <div class="stat-value fs-5">Quick action</div>
                </div>
            </div>
        </div>
    </div>
    <div class="folder-grid" style="grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));">
        <div class="folder-card" style="cursor:default">
            <div class="folder-card-top">
                <img src="<?= Helper::e(Helper::profileUrl(null)) ?>" alt="" class="folder-avatar">
            </div>
            <div class="folder-name">Folder Card</div>
            <div class="folder-meta">12 images</div>
            <div class="folder-meta folder-meta-last">Last upload · Today</div>
        </div>
    </div>
</section>

<!-- Data Table -->
<section class="comp-section" id="table">
    <div class="comp-section-head">
        <h2 class="comp-section-title">Data Table</h2>
        <p class="comp-section-desc">Database-backed sample — team members</p>
    </div>
    <div class="card panel-card">
        <div class="card-body p-0">
            <?php if ($sampleMembers['rows'] === []): ?>
                <div class="empty-panel" style="border:0;margin:0;max-width:none">
                    <div class="empty-panel-icon"><i class="bi bi-table"></i></div>
                    <h2 class="empty-panel-title">No rows yet</h2>
                    <p class="empty-panel-text">Create a team member to populate this table sample.</p>
                    <a href="<?= Helper::e(BASE_URL . 'admin/members.php') ?>" class="btn btn-primary">Create Team Member</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Profile</th>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($sampleMembers['rows'] as $row): ?>
                            <tr>
                                <td><?= (int) $row['id'] ?></td>
                                <td><img src="<?= Helper::e(Helper::profileUrl($row['profile'])) ?>" alt="" class="avatar-sm"></td>
                                <td><?= Helper::e($row['name']) ?></td>
                                <td><code><?= Helper::e($row['username']) ?></code></td>
                                <td><?= Helper::statusBadge((int) $row['status']) ?></td>
                                <td><?= Helper::e(Helper::formatDate($row['created_at'], 'd M Y')) ?></td>
                                <td class="text-end">
                                    <a href="<?= Helper::e(BASE_URL . 'admin/members.php') ?>"
                                       class="btn btn-outline-secondary filter-btn">Manage</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <?php if ($sampleMembers['total'] > 0): ?>
            <div class="card-footer bg-transparent border-0 d-flex justify-content-between align-items-center">
                <span class="page-toolbar-count"><?= (int) $sampleMembers['total'] ?> total in database</span>
                <a href="<?= Helper::e(BASE_URL . 'admin/members.php') ?>" class="text-link">Open Members</a>
            </div>
        <?php endif; ?>
    </div>

    <div class="comp-section-head mt-4">
        <h2 class="comp-section-title" style="font-size:17px">Recent Uploads Table</h2>
    </div>
    <div class="card panel-card">
        <div class="card-body p-0">
            <?php if ($sampleUploads === []): ?>
                <div class="empty-state py-4 mb-0">
                    <p class="mb-0">No uploads in the database yet.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 data-table">
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
                        <?php foreach ($sampleUploads as $up): ?>
                            <tr>
                                <td>
                                    <img src="<?= Helper::e(Helper::uploadUrl($up['image'])) ?>" alt=""
                                         class="thumb-sm lightbox-trigger"
                                         data-src="<?= Helper::e(Helper::uploadUrl($up['image'])) ?>"
                                         data-title="<?= Helper::e($up['uploader_name']) ?>"
                                         data-meta="<?= Helper::e(Helper::formatDate($up['created_at'])) ?>">
                                </td>
                                <td><?= Helper::e($up['uploader_name']) ?></td>
                                <td><?= Helper::flagBadge($up['flag']) ?></td>
                                <td class="text-muted"><?= Helper::e(Helper::truncate($up['description'] ?? '', 40)) ?></td>
                                <td><?= Helper::e(Helper::formatDate($up['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Filters -->
<section class="comp-section" id="filters">
    <div class="comp-section-head">
        <h2 class="comp-section-title">Filter Bar</h2>
        <p class="comp-section-desc">Shared filter pattern used on galleries</p>
    </div>
    <?php
    $filters = [
        'period'    => '',
        'flag'      => '',
        'search'    => '',
        'from'      => '',
        'to'        => '',
        'member_id' => '',
    ];
    $actionUrl = BASE_URL . 'admin/components.php#filters';
    $showMemberFilter = true;
    $members = $sampleMembers['rows'];
    $hiddenId = null;
    require BASE_PATH . 'includes/filter-bar.php';
    ?>
</section>

<!-- Empty -->
<section class="comp-section" id="empty">
    <div class="comp-section-head">
        <h2 class="comp-section-title">Empty State</h2>
        <p class="comp-section-desc">Centered panel for zero-data screens</p>
    </div>
    <div class="empty-panel">
        <div class="empty-panel-icon"><i class="bi bi-inbox"></i></div>
        <h2 class="empty-panel-title">Nothing here yet</h2>
        <p class="empty-panel-text">Use this pattern when a list or gallery has no records.</p>
        <button type="button" class="btn btn-primary">Primary Action</button>
    </div>
</section>

<section class="comp-section">
    <div class="card panel-card">
        <div class="card-body d-flex flex-wrap gap-3 justify-content-between align-items-center">
            <div>
                <div class="comp-title mb-1">Default Page</div>
                <p class="comp-caption mb-0 text-muted">Blank starter layout with intro, toolbar, and content card.</p>
            </div>
            <a href="<?= Helper::e(BASE_URL . 'admin/default.php') ?>" class="btn btn-primary">Open Default Page</a>
        </div>
    </div>
</section>

<?php require BASE_PATH . 'includes/footer.php'; ?>

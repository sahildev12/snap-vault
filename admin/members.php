<?php
/**
 * Admin — Team members list (modals for view / add / edit)
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';
require_once BASE_PATH . 'includes/auth_check.php';
$requiredRole = 'admin';
require BASE_PATH . 'includes/role_check.php';

$search = trim((string) Helper::input('q', ''));
$page = max(1, (int) Helper::input('page', 1));
$result = User::listMembers($search, $page, MEMBERS_PER_PAGE);
$baseUrl = BASE_URL . 'admin/members.php?q=' . urlencode($search) . '&page=';
$placeholder = Helper::profileUrl(null);

$pageTitle = 'Team Members';
require BASE_PATH . 'includes/header.php';
?>

<div class="page-toolbar">
    <form class="page-search" method="get" action="" role="search">
        <i class="bi bi-search page-search-icon" aria-hidden="true"></i>
        <input type="search" name="q" class="page-search-input"
               placeholder="Search members"
               value="<?= Helper::e($search) ?>"
               aria-label="Search members">
        <?php if ($search !== ''): ?>
            <a href="<?= Helper::e(BASE_URL . 'admin/members.php') ?>" class="page-search-clear" title="Clear" aria-label="Clear">
                <i class="bi bi-x-lg"></i>
            </a>
        <?php endif; ?>
    </form>
    <div class="page-toolbar-meta">
        <?php if ($result['total'] > 0): ?>
            <span class="page-toolbar-count"><?= (int) $result['total'] ?> members</span>
        <?php endif; ?>
        <button type="button" class="btn btn-primary filter-btn" id="btnAddMember">
            Create Member
        </button>
    </div>
</div>

<div class="card panel-card">
    <div class="card-body p-0">
        <?php if ($result['rows'] === []): ?>
            <div class="empty-panel" style="border:0;box-shadow:none;margin:0;max-width:none">
                <div class="empty-panel-icon"><i class="bi bi-people"></i></div>
                <h2 class="empty-panel-title"><?= $search !== '' ? 'No matches' : 'No members yet' ?></h2>
                <p class="empty-panel-text">
                    <?= $search !== '' ? 'Try a different search.' : 'Create a team member to start collecting uploads.' ?>
                </p>
                <?php if ($search === ''): ?>
                    <button type="button" class="btn btn-primary" id="btnAddMemberEmpty">Create Member</button>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Profile</th>
                            <th>Full Name</th>
                            <th>Username</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($result['rows'] as $row): ?>
                        <tr>
                            <td><?= (int) $row['id'] ?></td>
                            <td>
                                <img src="<?= Helper::e(Helper::profileUrl($row['profile'])) ?>"
                                     alt="" class="avatar-sm">
                            </td>
                            <td><?= Helper::e($row['name']) ?></td>
                            <td><code><?= Helper::e($row['username']) ?></code></td>
                            <td><?= Helper::statusBadge((int) $row['status']) ?></td>
                            <td><?= Helper::e(Helper::formatDate($row['created_at'], 'd M Y')) ?></td>
                            <td class="text-end text-nowrap">
                                <div class="table-actions">
                                    <button type="button"
                                            class="btn btn-action btn-view-member"
                                            data-id="<?= (int) $row['id'] ?>"
                                            title="View" aria-label="View">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <button type="button"
                                            class="btn btn-action btn-edit-member"
                                            data-id="<?= (int) $row['id'] ?>"
                                            title="Edit" aria-label="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button type="button"
                                            class="btn btn-action btn-reset-member"
                                            data-id="<?= (int) $row['id'] ?>"
                                            data-name="<?= Helper::e($row['name']) ?>"
                                            title="Reset Password" aria-label="Reset Password">
                                        <i class="bi bi-key"></i>
                                    </button>
                                    <form method="post" action="<?= Helper::e(BASE_URL . 'admin/member-action.php') ?>" class="d-inline">
                                        <?= Csrf::field() ?>
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                        <?php $isActive = (int) $row['status'] === 1; ?>
                                        <button type="submit"
                                                class="btn btn-action <?= $isActive ? 'btn-action-toggle-on' : 'btn-action-toggle-off' ?>"
                                                title="<?= $isActive ? 'Disable' : 'Enable' ?> (ID <?= (int) $row['id'] ?>)"
                                                aria-label="<?= $isActive ? 'Disable' : 'Enable' ?> member #<?= (int) $row['id'] ?>">
                                            <i class="bi bi-toggle-<?= $isActive ? 'on' : 'off' ?>"></i>
                                        </button>
                                    </form>
                                    <button type="button"
                                            class="btn btn-action btn-action-danger btn-confirm-delete"
                                            data-form-id="deleteMember<?= (int) $row['id'] ?>"
                                            data-message="Delete member <?= Helper::e($row['name']) ?> and all their uploads?"
                                            title="Delete" aria-label="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    <form id="deleteMember<?= (int) $row['id'] ?>" method="post"
                                          action="<?= Helper::e(BASE_URL . 'admin/member-action.php') ?>" class="d-none">
                                        <?= Csrf::field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <?php if ($result['total'] > MEMBERS_PER_PAGE): ?>
        <div class="card-footer bg-transparent border-0">
            <?= Helper::pagination($result['total'], $page, MEMBERS_PER_PAGE, $baseUrl) ?>
        </div>
    <?php endif; ?>
</div>

<!-- Reset Password Modal (shared — must not live inside table rows) -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" action="<?= Helper::e(BASE_URL . 'admin/member-action.php') ?>" class="modal-content" id="resetPasswordForm">
            <?= Csrf::field() ?>
            <input type="hidden" name="action" value="reset">
            <input type="hidden" name="id" id="resetMemberId" value="">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title modal-title-with-icon">
                    <span class="modal-title-icon" aria-hidden="true"><i class="bi bi-key-fill"></i></span>
                    <span class="modal-title-copy">
                        <strong>Reset Password</strong>
                        <span>Set a new login password for this member</span>
                    </span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-3">
                <div class="reset-member-chip">
                    <i class="bi bi-person-circle" aria-hidden="true"></i>
                    <div>
                        <strong id="resetMemberName">—</strong>
                        <span id="resetMemberLabel">ID —</span>
                    </div>
                </div>
                <label class="form-label" for="resetPasswordInput">New Password</label>
                <div class="input-group password-toggle-group">
                    <input type="password" name="password" id="resetPasswordInput" class="form-control" minlength="6" required autocomplete="new-password" placeholder="At least 6 characters">
                    <button type="button" class="btn btn-outline-secondary password-toggle-btn" id="resetPasswordToggle"
                            title="Show password" aria-label="Show password" aria-pressed="false">
                        <i class="bi bi-eye" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-secondary btn-modal-action" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary btn-modal-action">
                    <i class="bi bi-check-lg me-1" aria-hidden="true"></i>Update Password
                </button>
            </div>
        </form>
    </div>
</div>

<!-- View Member Modal -->
<div class="modal fade" id="viewMemberModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title">Member Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img src="<?= Helper::e($placeholder) ?>" alt="" id="viewProfile" class="avatar-lg mb-3">
                <h3 class="comp-title mb-1" id="viewName">—</h3>
                <p class="text-muted mb-3" id="viewUsername">@—</p>
                <div class="d-flex justify-content-center gap-2 mb-3" id="viewStatus"></div>
                <div class="row g-2 text-start">
                    <div class="col-6">
                        <div class="stat-card" style="padding:14px">
                            <div>
                                <div class="stat-label">Images</div>
                                <div class="stat-value fs-4" id="viewImages">0</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stat-card" style="padding:14px">
                            <div>
                                <div class="stat-label">Today</div>
                                <div class="stat-value fs-4" id="viewToday">0</div>
                            </div>
                        </div>
                    </div>
                </div>
                <p class="comp-fine mt-3 mb-0" id="viewCreated"></p>
            </div>
            <div class="modal-footer border-0 justify-content-between">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="viewEditBtn">Edit</button>
            </div>
        </div>
    </div>
</div>

<!-- Add / Edit Member Modal -->
<div class="modal fade" id="memberFormModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form method="post" action="<?= Helper::e(BASE_URL . 'admin/member-action.php') ?>"
              enctype="multipart/form-data" class="modal-content" id="memberForm" novalidate>
            <?= Csrf::field() ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" id="memberId" value="0">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="memberFormTitle">Create Member</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <img src="<?= Helper::e($placeholder) ?>" alt="" id="memberProfilePreview" class="avatar-lg mb-3">
                    <div class="mx-auto" style="max-width:320px;text-align:left">
                        <label class="form-label" for="memberProfileTrigger">Profile Picture (optional)</label>
                        <div class="file-picker">
                            <input type="file" name="profile" id="memberProfileInput" class="file-picker-input"
                                   accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                            <button type="button" class="btn btn-outline-secondary file-picker-btn" id="memberProfileTrigger">
                                Choose file
                            </button>
                            <span class="file-picker-name" id="memberProfileName">No file chosen</span>
                        </div>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="memberName">Full Name</label>
                        <input type="text" name="name" id="memberName" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="memberUsername">Username</label>
                        <input type="text" name="username" id="memberUsername" class="form-control" required autocomplete="off">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="memberPassword">
                            Password <span class="text-muted fw-normal" id="passwordHint"></span>
                        </label>
                        <div class="input-group password-toggle-group">
                            <input type="password" name="password" id="memberPassword" class="form-control"
                                   minlength="6" autocomplete="new-password" placeholder="At least 6 characters">
                            <button type="button" class="btn btn-outline-secondary password-toggle-btn" id="memberPasswordToggle"
                                    title="Show password" aria-label="Show password" aria-pressed="false">
                                <i class="bi bi-eye" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="memberStatus">Status</label>
                        <select name="status" id="memberStatus" class="form-select sv-select">
                            <option value="1">Enabled</option>
                            <option value="0">Disabled</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-secondary btn-modal-action" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary btn-modal-action" id="memberFormSubmit">Create Member</button>
            </div>
        </form>
    </div>
</div>

<?php
$extraScripts = '<script>
(function () {
    const placeholder = ' . json_encode($placeholder) . ';
    const getUrl = ' . json_encode(BASE_URL . 'ajax/get-member.php') . ';
    const formModalEl = document.getElementById("memberFormModal");
    const viewModalEl = document.getElementById("viewMemberModal");
    const formModal = formModalEl ? new bootstrap.Modal(formModalEl) : null;
    const viewModal = viewModalEl ? new bootstrap.Modal(viewModalEl) : null;
    let currentViewId = 0;

    const memberId = document.getElementById("memberId");
    const memberName = document.getElementById("memberName");
    const memberUsername = document.getElementById("memberUsername");
    const memberPassword = document.getElementById("memberPassword");
    const memberStatus = document.getElementById("memberStatus");
    const memberPreview = document.getElementById("memberProfilePreview");
    const memberProfileInput = document.getElementById("memberProfileInput");
    const memberProfileTrigger = document.getElementById("memberProfileTrigger");
    const memberProfileName = document.getElementById("memberProfileName");
    const formTitle = document.getElementById("memberFormTitle");
    const formSubmit = document.getElementById("memberFormSubmit");
    const passwordHint = document.getElementById("passwordHint");
    const memberPasswordToggle = document.getElementById("memberPasswordToggle");

    function resetPasswordVisibility(input, toggle) {
        if (!input) return;
        input.type = "password";
        if (!toggle) return;
        toggle.setAttribute("aria-pressed", "false");
        toggle.setAttribute("title", "Show password");
        toggle.setAttribute("aria-label", "Show password");
        const icon = toggle.querySelector("i");
        if (icon) {
            icon.classList.remove("bi-eye-slash");
            icon.classList.add("bi-eye");
        }
    }

    function resetFilePicker() {
        if (memberProfileInput) memberProfileInput.value = "";
        if (memberProfileName) memberProfileName.textContent = "No file chosen";
    }

    function syncStatusSelect(value) {
        if (!memberStatus) return;
        memberStatus.value = String(value);
        memberStatus.dispatchEvent(new Event("change", { bubbles: true }));
    }

    function openCreate() {
        memberId.value = "0";
        memberName.value = "";
        memberUsername.value = "";
        memberPassword.value = "";
        memberPassword.required = true;
        resetPasswordVisibility(memberPassword, memberPasswordToggle);
        passwordHint.textContent = "";
        syncStatusSelect(1);
        memberPreview.src = placeholder;
        resetFilePicker();
        formTitle.textContent = "Create Member";
        formSubmit.textContent = "Create Member";
        formModal?.show();
    }

    async function loadMember(id) {
        const res = await fetch(getUrl + "?id=" + encodeURIComponent(id), {
            headers: { "Accept": "application/json", "X-Requested-With": "XMLHttpRequest" }
        });
        const data = await res.json();
        if (!data.success) {
            window.SnapVault?.toast(data.message || "Unable to load member.", "danger");
            return null;
        }
        return data.member;
    }

    async function openEdit(id) {
        const m = await loadMember(id);
        if (!m) return;
        memberId.value = String(m.id);
        memberName.value = m.name;
        memberUsername.value = m.username;
        memberPassword.value = "";
        memberPassword.required = false;
        resetPasswordVisibility(memberPassword, memberPasswordToggle);
        passwordHint.textContent = "(leave blank to keep)";
        syncStatusSelect(m.status);
        memberPreview.src = m.profile_url || placeholder;
        resetFilePicker();
        formTitle.textContent = "Edit Member";
        formSubmit.textContent = "Save Changes";
        formModal?.show();
    }

    async function openView(id) {
        const m = await loadMember(id);
        if (!m) return;
        currentViewId = m.id;
        document.getElementById("viewProfile").src = m.profile_url || placeholder;
        document.getElementById("viewName").textContent = m.name;
        document.getElementById("viewUsername").textContent = "@" + m.username;
        document.getElementById("viewImages").textContent = String(m.total_images);
        document.getElementById("viewToday").textContent = String(m.today_uploads);
        document.getElementById("viewCreated").textContent = "Joined " + m.created_label;
        document.getElementById("viewStatus").innerHTML = m.status === 1
            ? \'<span class="badge badge-normal">Active</span>\'
            : \'<span class="badge" style="background:#f5f5f7;color:#7a7a7a;">Disabled</span>\';
        viewModal?.show();
    }

    document.getElementById("btnAddMember")?.addEventListener("click", openCreate);
    document.getElementById("btnAddMemberEmpty")?.addEventListener("click", openCreate);

    document.body.addEventListener("click", (e) => {
        const editBtn = e.target.closest(".btn-edit-member");
        if (editBtn) {
            openEdit(editBtn.dataset.id);
            return;
        }
        const viewBtn = e.target.closest(".btn-view-member");
        if (viewBtn) {
            openView(viewBtn.dataset.id);
            return;
        }
        const resetBtn = e.target.closest(".btn-reset-member");
        if (resetBtn) {
            const id = resetBtn.dataset.id || "";
            const name = resetBtn.dataset.name || "—";
            document.getElementById("resetMemberId").value = id;
            document.getElementById("resetMemberName").textContent = name;
            document.getElementById("resetMemberLabel").textContent = id ? "ID " + id : "ID —";
            const input = document.getElementById("resetPasswordInput");
            const toggle = document.getElementById("resetPasswordToggle");
            input.value = "";
            resetPasswordVisibility(input, toggle);
            bootstrap.Modal.getOrCreateInstance(document.getElementById("resetPasswordModal")).show();
        }
    });

    document.getElementById("viewEditBtn")?.addEventListener("click", () => {
        viewModal?.hide();
        if (currentViewId) openEdit(currentViewId);
    });

    memberProfileTrigger?.addEventListener("click", () => memberProfileInput?.click());

    memberProfileInput?.addEventListener("change", function () {
        const file = this.files?.[0];
        if (!file) {
            if (memberProfileName) memberProfileName.textContent = "No file chosen";
            return;
        }
        if (memberProfileName) memberProfileName.textContent = file.name;
        memberPreview.src = URL.createObjectURL(file);
    });

    // Re-enhance select when modal opens (in case it was hidden at init)
    formModalEl?.addEventListener("shown.bs.modal", () => {
        const sel = document.getElementById("memberStatus");
        if (sel && sel.dataset.svEnhanced !== "1" && window.SnapVault) {
            /* already enhanced on DOMContentLoaded */
        }
    });
})();
</script>';
require BASE_PATH . 'includes/footer.php';
?>

<?php
/**
 * Shared footer — toasts, confirm modal, scripts
 */
declare(strict_types=1);

$hideSidebar = $hideSidebar ?? false;
?>
<?php if (!$hideSidebar): ?>
        </main>
    </div>
</div>
<?php endif; ?>

<!-- Toast container -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastContainer"></div>

<!-- Confirm delete modal -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="confirmModalLabel">Confirm</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="confirmModalBody">Are you sure?</div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmModalOk" style="background:#ff3b30;border-color:#ff3b30;">Delete</button>
            </div>
        </div>
    </div>
</div>

<!-- Lightbox modal -->
<div class="modal fade" id="lightboxModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content lightbox-content border-0">
            <button type="button" class="btn-close btn-close-white lightbox-close" data-bs-dismiss="modal" aria-label="Close"></button>
            <button type="button" class="lightbox-nav lightbox-prev" id="lightboxPrev" aria-label="Previous image">
                <i class="bi bi-chevron-left" aria-hidden="true"></i>
            </button>
            <button type="button" class="lightbox-nav lightbox-next" id="lightboxNext" aria-label="Next image">
                <i class="bi bi-chevron-right" aria-hidden="true"></i>
            </button>
            <div class="modal-body text-center p-0">
                <img src="" alt="Preview" id="lightboxImage" class="img-fluid lightbox-img">
            </div>
            <div class="modal-footer border-0 lightbox-footer">
                <div class="lightbox-caption">
                    <span id="lightboxTitle"></span>
                    <span class="lightbox-sep" id="lightboxSep" aria-hidden="true">·</span>
                    <span id="lightboxMeta"></span>
                </div>
                <span class="lightbox-counter" id="lightboxCounter"></span>
            </div>
        </div>
    </div>
</div>

<script src="<?= Helper::asset('assets/vendor/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= Helper::asset('assets/vendor/chartjs/chart.umd.min.js') ?>"></script>
<script>
    window.SNAPVAULT = {
        baseUrl: <?= json_encode(BASE_URL) ?>,
        csrfToken: <?= json_encode(Csrf::token()) ?>,
        chatPollSeconds: <?= (int) CHAT_POLL_SECONDS ?>
    };
</script>
<script src="<?= Helper::asset('assets/js/app.js') ?>"></script>
<?php if (!empty($extraScripts)): ?>
    <?= $extraScripts ?>
<?php endif; ?>
</body>
</html>

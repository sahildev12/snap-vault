<?php
declare(strict_types=1);
?>
<div class="modal fade presentation-viewer-modal" id="presentationViewerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-fullscreen-lg-down">
        <div class="modal-content">
            <div class="modal-header presentation-viewer-header">
                <div class="presentation-viewer-header-copy min-w-0">
                    <h5 class="modal-title text-truncate" id="presentationViewerTitle">Presentation</h5>
                    <div class="small text-muted text-truncate" id="presentationViewerSubtitle"></div>
                </div>
                <div class="presentation-viewer-header-actions">
                    <a href="#" class="btn btn-sm btn-outline-primary d-none presentation-viewer-download" id="presentationViewerDownload" target="_blank" rel="noopener">
                        <i class="bi bi-download me-1"></i>Download
                    </a>
                    <button type="button" class="btn-close presentation-viewer-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body p-0">
                <div class="presentation-viewer-stage" id="presentationViewerStage">
                    <div class="presentation-viewer-loading" id="presentationViewerLoading">
                        <div class="spinner-border text-primary" role="status" aria-hidden="true"></div>
                        <span class="presentation-viewer-loading-title">Loading preview…</span>
                        <span class="presentation-viewer-loading-status" id="presentationViewerLoadingStatus">Please wait while the file is prepared.</span>
                    </div>
                    <iframe id="presentationViewerFrame"
                            class="presentation-viewer-frame d-none"
                            title="Presentation preview"
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"></iframe>
                    <div class="presentation-viewer-fallback d-none" id="presentationViewerFallback">
                        <i class="bi bi-easel presentation-viewer-fallback-icon" aria-hidden="true"></i>
                        <p class="presentation-viewer-fallback-text mb-0" id="presentationViewerFallbackText">Preview is not available here.</p>
                        <a href="#" class="btn btn-primary btn-sm presentation-viewer-fallback-btn" id="presentationViewerFallbackDownload">
                            <i class="bi bi-download" aria-hidden="true"></i>
                            <span>Download file</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

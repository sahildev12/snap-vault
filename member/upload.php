<?php
/**
 * Member — Upload image (camera or file)
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';
require_once BASE_PATH . 'includes/auth_check.php';
$requiredRole = 'member';
require BASE_PATH . 'includes/role_check.php';

$errors = [];
$flag = 'normal';
$description = '';

if (Helper::isPost()) {
    Csrf::requireValid();

    $flag = (string) Helper::input('flag', 'normal');
    if (!in_array($flag, Helper::uploadFlags(), true)) {
        $flag = 'normal';
    }
    $description = trim((string) Helper::input('description', ''));

    if (empty($_FILES['image']['name'])) {
        $errors[] = 'Please select or capture an image.';
    } else {
        $stored = Upload::storeFile($_FILES['image'], UPLOAD_DIR);
        if (!$stored['success']) {
            $errors[] = $stored['message'];
        } else {
            Upload::create((int) Auth::id(), $stored['filename'], $flag, $description);
            Helper::setFlash('success', 'Image uploaded successfully.');
            Helper::redirect(BASE_URL . 'member/gallery.php');
        }
    }
}

$pageTitle = 'Upload Image';
require BASE_PATH . 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card panel-card">
            <div class="card-body p-4">
                <?php if ($errors !== []): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0 ps-3">
                            <?php foreach ($errors as $err): ?>
                                <li><?= Helper::e($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post" enctype="multipart/form-data" id="uploadForm" novalidate>
                    <?= Csrf::field() ?>

                    <div class="upload-dropzone mb-4" id="uploadDropzone">
                        <img src="" alt="Preview" id="imagePreview" class="upload-preview d-none">
                        <div class="upload-placeholder" id="uploadPlaceholder">
                            <i class="bi bi-cloud-arrow-up"></i>
                            <p class="mb-1">Preview appears here</p>
                            <small class="text-muted">JPG, PNG, WEBP · Max 10MB</small>
                        </div>
                    </div>

                    <div class="row g-2 g-sm-3 mb-3 upload-source-row">
                        <div class="col-6">
                            <button type="button" class="btn btn-outline-secondary w-100 upload-source-btn" id="cameraBtn">
                                <i class="bi bi-camera me-1" aria-hidden="true"></i>Open Camera
                            </button>
                        </div>
                        <div class="col-6">
                            <button type="button" class="btn btn-outline-secondary w-100 upload-source-btn" id="fileBtn">
                                <i class="bi bi-image me-1" aria-hidden="true"></i>Choose Image
                            </button>
                        </div>
                    </div>
                    <input type="file" name="image" id="imageField" class="d-none"
                           accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">

                    <div class="mb-3">
                        <label class="form-label d-block">Flag</label>
                        <div class="flag-toggle flag-toggle-wrap" role="group" aria-label="Flag">
                            <?php foreach (Helper::uploadFlags() as $flagOpt): ?>
                                <?php $flagId = 'flag' . ucfirst($flagOpt); ?>
                                <input type="radio" class="btn-check" name="flag" id="<?= Helper::e($flagId) ?>"
                                       value="<?= Helper::e($flagOpt) ?>"
                                       <?= $flag === $flagOpt ? 'checked' : '' ?>>
                                <label class="btn btn-outline-secondary flag-option" for="<?= Helper::e($flagId) ?>">
                                    <?= Helper::e(ucfirst($flagOpt)) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label" for="description">Description (optional)</label>
                        <textarea name="description" id="description" class="form-control" rows="3"
                                  placeholder="Add a short note…"><?= Helper::e($description) ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-100" id="submitUpload">
                        Upload
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Live camera capture (enumerates all device cameras) -->
<div class="modal fade" id="cameraModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content camera-modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title">Take Photo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <label class="form-label" for="cameraSelect">Camera</label>
                <div class="camera-select-wrap">
                    <select id="cameraSelect" class="form-select" aria-label="Choose camera"></select>
                </div>

                <div class="camera-stage" id="cameraStage">
                    <video id="cameraVideo" playsinline autoplay muted></video>
                    <div class="camera-stage-status" id="cameraStatus">Starting camera…</div>
                </div>
                <canvas id="cameraCanvas" class="d-none" aria-hidden="true"></canvas>
                <p class="camera-hint mb-0" id="cameraHint">
                    Pick a camera above, then tap Capture Photo.
                </p>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-secondary btn-modal-action" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-modal-action" id="capturePhotoBtn">
                    <i class="bi bi-camera-fill me-1" aria-hidden="true"></i>Capture Photo
                </button>
            </div>
        </div>
    </div>
</div>

<?php
$extraScripts = <<<'JS'
<script>
(function () {
    const preview = document.getElementById("imagePreview");
    const placeholder = document.getElementById("uploadPlaceholder");
    const imageField = document.getElementById("imageField");
    const cameraBtn = document.getElementById("cameraBtn");
    const fileBtn = document.getElementById("fileBtn");
    const form = document.getElementById("uploadForm");

    const cameraModalEl = document.getElementById("cameraModal");
    const cameraSelect = document.getElementById("cameraSelect");
    const cameraVideo = document.getElementById("cameraVideo");
    const cameraCanvas = document.getElementById("cameraCanvas");
    const cameraStatus = document.getElementById("cameraStatus");
    const capturePhotoBtn = document.getElementById("capturePhotoBtn");
    const cameraHint = document.getElementById("cameraHint");

    let cameraStream = null;
    let cameraModal = null;
    let cameras = [];

    function showPreview(file) {
        if (!file) return;
        const url = URL.createObjectURL(file);
        preview.src = url;
        preview.classList.remove("d-none");
        placeholder.classList.add("d-none");
    }

    function setStatus(text, isError) {
        if (!cameraStatus) return;
        cameraStatus.textContent = text || "";
        cameraStatus.classList.toggle("d-none", !text);
        cameraStatus.classList.toggle("is-error", !!isError);
    }

    function stopCamera() {
        if (cameraStream) {
            cameraStream.getTracks().forEach((t) => t.stop());
            cameraStream = null;
        }
        if (cameraVideo) {
            cameraVideo.srcObject = null;
        }
    }

    function assignFileToInput(file) {
        const dt = new DataTransfer();
        dt.items.add(file);
        imageField.files = dt.files;
        showPreview(file);
    }

    async function ensurePermission() {
        // Labels are blank until permission is granted at least once.
        try {
            const warm = await navigator.mediaDevices.getUserMedia({
                audio: false,
                video: { facingMode: { ideal: "environment" } }
            });
            warm.getTracks().forEach((t) => t.stop());
        } catch (e) {
            const warm = await navigator.mediaDevices.getUserMedia({ audio: false, video: true });
            warm.getTracks().forEach((t) => t.stop());
        }
    }

    async function listCameras() {
        const devices = await navigator.mediaDevices.enumerateDevices();
        cameras = devices.filter((d) => d.kind === "videoinput");
        cameraSelect.innerHTML = "";

        if (!cameras.length) {
            const opt = document.createElement("option");
            opt.value = "";
            opt.textContent = "No cameras found";
            cameraSelect.appendChild(opt);
            return cameras;
        }

        cameras.forEach((device, index) => {
            const opt = document.createElement("option");
            opt.value = device.deviceId;
            const label = (device.label || "").trim();
            opt.textContent = label || ("Camera " + (index + 1));
            cameraSelect.appendChild(opt);
        });

        return cameras;
    }

    async function startCamera(deviceId) {
        stopCamera();
        setStatus("Starting camera…");

        const constraints = {
            audio: false,
            video: deviceId
                ? {
                    deviceId: { exact: deviceId },
                    width: { ideal: 1920 },
                    height: { ideal: 1080 }
                }
                : {
                    facingMode: { ideal: "environment" },
                    width: { ideal: 1920 },
                    height: { ideal: 1080 }
                }
        };

        try {
            cameraStream = await navigator.mediaDevices.getUserMedia(constraints);
            cameraVideo.srcObject = cameraStream;
            await cameraVideo.play();
            setStatus("");
            capturePhotoBtn.disabled = false;
        } catch (err) {
            console.error(err);
            // Fallback without exact constraint
            if (deviceId) {
                try {
                    cameraStream = await navigator.mediaDevices.getUserMedia({
                        audio: false,
                        video: { deviceId: deviceId }
                    });
                    cameraVideo.srcObject = cameraStream;
                    await cameraVideo.play();
                    setStatus("");
                    capturePhotoBtn.disabled = false;
                    return;
                } catch (err2) {
                    console.error(err2);
                }
            }
            setStatus("Could not open this camera. Try another one.", true);
            capturePhotoBtn.disabled = true;
            window.SnapVault?.toast("Could not access that camera.", "danger");
        }
    }

    async function openCameraStudio() {
        if (!window.isSecureContext) {
            window.SnapVault?.toast("Camera needs HTTPS. Open the site with https://", "danger");
            return;
        }
        if (!navigator.mediaDevices?.getUserMedia) {
            window.SnapVault?.toast("Camera is not supported in this browser.", "danger");
            return;
        }

        cameraModal = bootstrap.Modal.getOrCreateInstance(cameraModalEl);
        capturePhotoBtn.disabled = true;
        setStatus("Requesting camera permission…");
        cameraModal.show();

        try {
            await ensurePermission();
            await listCameras();
            if (!cameras.length) {
                setStatus("No cameras detected on this device.", true);
                return;
            }
            await startCamera(cameraSelect.value || cameras[0].deviceId);
            if (cameraHint) {
                cameraHint.textContent = cameras.length + " camera" + (cameras.length === 1 ? "" : "s") + " found. Switch anytime from the list.";
            }
        } catch (err) {
            console.error(err);
            setStatus("Camera permission denied or unavailable.", true);
            window.SnapVault?.toast("Please allow camera access, then try again.", "danger");
        }
    }

    cameraBtn?.addEventListener("click", openCameraStudio);

    cameraSelect?.addEventListener("change", function () {
        if (this.value) startCamera(this.value);
    });

    // Keep list updated if cables / USB cams change
    navigator.mediaDevices?.addEventListener?.("devicechange", async () => {
        if (!cameraModalEl?.classList.contains("show")) return;
        const current = cameraSelect.value;
        await listCameras();
        if (current && [...cameraSelect.options].some((o) => o.value === current)) {
            cameraSelect.value = current;
        } else if (cameraSelect.value) {
            startCamera(cameraSelect.value);
        }
    });

    capturePhotoBtn?.addEventListener("click", function () {
        if (!cameraStream || !cameraVideo.videoWidth) {
            window.SnapVault?.toast("Camera is not ready yet.", "danger");
            return;
        }

        const w = cameraVideo.videoWidth;
        const h = cameraVideo.videoHeight;
        cameraCanvas.width = w;
        cameraCanvas.height = h;
        const ctx = cameraCanvas.getContext("2d");
        ctx.drawImage(cameraVideo, 0, 0, w, h);

        cameraCanvas.toBlob(function (blob) {
            if (!blob) {
                window.SnapVault?.toast("Could not capture photo.", "danger");
                return;
            }
            const file = new File([blob], "capture-" + Date.now() + ".jpg", { type: "image/jpeg" });
            assignFileToInput(file);
            stopCamera();
            cameraModal?.hide();
            window.SnapVault?.toast("Photo captured.", "success");
        }, "image/jpeg", 0.92);
    });

    cameraModalEl?.addEventListener("hidden.bs.modal", stopCamera);

    fileBtn?.addEventListener("click", function () {
        imageField.value = "";
        imageField.removeAttribute("capture");
        imageField.setAttribute("accept", ".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp");
        imageField.click();
    });

    imageField?.addEventListener("change", function () {
        showPreview(this.files?.[0]);
    });

    form?.addEventListener("submit", function (e) {
        if (!imageField.files?.length) {
            e.preventDefault();
            window.SnapVault?.toast("Please select or capture an image.", "danger");
        }
    });
})();
</script>
JS;

require BASE_PATH . 'includes/footer.php';
?>

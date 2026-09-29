/**
 * SnapVault front-end interactions
 * Sidebar, lightbox, toasts, confirm delete, custom selects
 */
(function () {
    'use strict';

    const cfg = window.SNAPVAULT || { baseUrl: '/snap-vault/', csrfToken: '' };

    const SnapVault = {
        toast(message, type = 'success') {
            const container = document.getElementById('toastContainer');
            if (!container) return;

            const el = document.createElement('div');
            el.className = `toast align-items-center text-bg-${type} border-0 show`;
            el.setAttribute('role', 'alert');
            el.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">${escapeHtml(message)}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>`;
            container.appendChild(el);

            const toast = new bootstrap.Toast(el, { delay: 3500 });
            toast.show();
            el.addEventListener('hidden.bs.toast', () => el.remove());
        },

        setChatUnread(count) {
            const badge = document.getElementById('notifUnreadBadge') || document.getElementById('chatUnreadBadge');
            if (!badge) return;
            const n = Number(count) || 0;
            badge.textContent = n > 99 ? '99+' : String(n);
            badge.classList.toggle('d-none', n <= 0);
        },

        setNotifUnread(count) {
            this.setChatUnread(count);
        },

        confirm(message) {
            return new Promise((resolve) => {
                const modalEl = document.getElementById('confirmModal');
                const body = document.getElementById('confirmModalBody');
                const okBtn = document.getElementById('confirmModalOk');
                if (!modalEl || !body || !okBtn) {
                    resolve(window.confirm(message));
                    return;
                }

                body.textContent = message;
                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);

                const cleanup = () => {
                    okBtn.onclick = null;
                    modalEl.removeEventListener('hidden.bs.modal', onCancel);
                };

                const onCancel = () => {
                    cleanup();
                    resolve(false);
                };

                okBtn.onclick = () => {
                    cleanup();
                    modal.hide();
                    resolve(true);
                };

                modalEl.addEventListener('hidden.bs.modal', onCancel, { once: true });
                modal.show();
            });
        },

        async deleteImage(id) {
            const ok = await this.confirm('Delete this image permanently?');
            if (!ok) return false;

            const form = new FormData();
            form.append('id', String(id));
            form.append('_csrf', cfg.csrfToken);

            try {
                const res = await fetch(cfg.baseUrl + 'ajax/delete-image.php', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': cfg.csrfToken,
                        'Accept': 'application/json',
                    },
                    body: form,
                });
                const data = await res.json();
                if (!data.success) {
                    this.toast(data.message || 'Delete failed.', 'danger');
                    return false;
                }
                this.toast(data.message || 'Image deleted.', 'success');
                const card = document.querySelector(`.image-grid-item[data-id="${id}"]`);
                if (card) {
                    card.style.transition = 'opacity 0.25s ease, transform 0.25s ease';
                    card.style.opacity = '0';
                    card.style.transform = 'scale(0.95)';
                    setTimeout(() => card.remove(), 250);
                }
                return true;
            } catch (err) {
                this.toast('Network error while deleting.', 'danger');
                return false;
            }
        },

        async deletePresentation(id) {
            const ok = await this.confirm('Delete this presentation permanently?');
            if (!ok) return false;

            const form = new FormData();
            form.append('id', String(id));
            form.append('_csrf', cfg.csrfToken);

            try {
                const res = await fetch(cfg.baseUrl + 'ajax/delete-presentation.php', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': cfg.csrfToken,
                        'Accept': 'application/json',
                    },
                    body: form,
                });
                const data = await res.json();
                if (!data.success) {
                    this.toast(data.message || 'Delete failed.', 'danger');
                    return false;
                }
                this.toast(data.message || 'Presentation deleted.', 'success');
                const row = document.querySelector(`.presentation-row[data-id="${id}"]`);
                if (row) {
                    row.style.transition = 'opacity 0.25s ease';
                    row.style.opacity = '0';
                    setTimeout(() => row.remove(), 250);
                }
                return true;
            } catch (err) {
                this.toast('Network error while deleting.', 'danger');
                return false;
            }
        },

        openPresentationViewer(btn) {
            const modalEl = document.getElementById('presentationViewerModal');
            const frame = document.getElementById('presentationViewerFrame');
            const loading = document.getElementById('presentationViewerLoading');
            const loadingStatus = document.getElementById('presentationViewerLoadingStatus');
            const fallback = document.getElementById('presentationViewerFallback');
            const fallbackText = document.getElementById('presentationViewerFallbackText');
            const fallbackDownload = document.getElementById('presentationViewerFallbackDownload');
            const titleEl = document.getElementById('presentationViewerTitle');
            const subtitleEl = document.getElementById('presentationViewerSubtitle');
            const downloadBtn = document.getElementById('presentationViewerDownload');

            if (!modalEl || !frame || !btn) return;

            const ext = (btn.dataset.ext || '').toLowerCase();
            const title = btn.dataset.title || 'Presentation';
            const filename = btn.dataset.filename || '';
            const inlineUrl = btn.dataset.inlineUrl || '';
            const publicUrl = btn.dataset.publicUrl || '';
            const officeUrl = btn.dataset.officeUrl || '';
            const downloadUrl = btn.dataset.downloadUrl || '';
            const isHttps = btn.dataset.https === '1' || window.location.protocol === 'https:';

            let blobUrl = null;
            let statusTimer = null;
            let hideTimer = null;
            let timeoutTimer = null;

            const clearTimers = () => {
                if (statusTimer) clearInterval(statusTimer);
                if (hideTimer) clearTimeout(hideTimer);
                if (timeoutTimer) clearTimeout(timeoutTimer);
                statusTimer = null;
                hideTimer = null;
                timeoutTimer = null;
            };

            const revokeBlob = () => {
                if (blobUrl) {
                    URL.revokeObjectURL(blobUrl);
                    blobUrl = null;
                }
            };

            const setStatus = (message) => {
                if (loadingStatus && message) loadingStatus.textContent = message;
            };

            const showLoading = (message) => {
                setStatus(message || 'Please wait while the file is prepared.');
                loading?.classList.remove('d-none');
                fallback?.classList.add('d-none');
            };

            const hideLoading = () => {
                loading?.classList.add('d-none');
            };

            const showFallback = (message) => {
                clearTimers();
                hideLoading();
                frame.classList.add('d-none');
                fallback?.classList.remove('d-none');
                if (fallbackText && message) fallbackText.textContent = message;
            };

            const showFrame = (url, options = {}) => {
                frame.classList.remove('d-none');
                frame.onload = () => {
                    if (typeof options.onLoad === 'function') {
                        options.onLoad();
                        return;
                    }
                    hideLoading();
                };
                frame.onerror = () => {
                    showFallback(options.errorMessage || 'Could not load preview. Please download the file instead.');
                };
                frame.src = url;
            };

            const verifyPublicFile = async () => {
                if (!publicUrl) return true;
                try {
                    const response = await fetch(publicUrl, { method: 'HEAD', cache: 'no-store' });
                    return response.ok;
                } catch (err) {
                    return false;
                }
            };

            const loadPdfPreview = async () => {
                showLoading('Fetching PDF from server…');
                const response = await fetch(inlineUrl, { credentials: 'same-origin', cache: 'no-store' });
                if (!response.ok) {
                    throw new Error('Could not load the PDF. Try downloading the file instead.');
                }

                const contentType = (response.headers.get('content-type') || '').toLowerCase();
                if (contentType.includes('text/html')) {
                    throw new Error('Your session may have expired. Refresh the page and try again.');
                }

                setStatus('Rendering PDF preview…');
                const blob = await response.blob();
                if (!blob.size) {
                    throw new Error('The PDF file appears to be empty.');
                }

                revokeBlob();
                blobUrl = URL.createObjectURL(blob);
                showFrame(blobUrl, {
                    onLoad: () => {
                        hideTimer = setTimeout(hideLoading, 400);
                    },
                    errorMessage: 'Could not render the PDF preview. Please download the file instead.',
                });
            };

            const loadOfficePreview = () => {
                const messages = [
                    'Connecting to Microsoft Office viewer…',
                    'Downloading document for preview…',
                    'Converting slides for in-browser viewing…',
                    'Still preparing preview — large files can take a minute…',
                ];
                let step = 0;
                showLoading(messages[0]);
                statusTimer = setInterval(() => {
                    step = Math.min(step + 1, messages.length - 1);
                    setStatus(messages[step]);
                }, 4500);

                showFrame(officeUrl, {
                    onLoad: () => {
                        setStatus('Finalizing preview…');
                        hideTimer = setTimeout(hideLoading, 5000);
                    },
                    errorMessage: 'Office viewer could not open this file. Please download it instead.',
                });

                timeoutTimer = setTimeout(async () => {
                    if (!loading || loading.classList.contains('d-none')) return;

                    if (publicUrl) {
                        setStatus('Trying alternate preview viewer…');
                        const googleUrl = 'https://docs.google.com/gview?embedded=true&url=' + encodeURIComponent(publicUrl);
                        showFrame(googleUrl, {
                            onLoad: () => {
                                hideTimer = setTimeout(() => {
                                    if (loading && !loading.classList.contains('d-none')) {
                                        showFallback('Preview is taking too long. Download the file to open it in PowerPoint.');
                                    } else {
                                        hideLoading();
                                    }
                                }, 15000);
                            },
                            errorMessage: 'Preview is not available right now. Please download the file instead.',
                        });
                        return;
                    }

                    showFallback('Preview timed out. Download the file to open it in PowerPoint.');
                }, 30000);
            };

            titleEl.textContent = title;
            subtitleEl.textContent = filename;
            if (downloadBtn) {
                downloadBtn.href = downloadUrl;
                downloadBtn.classList.toggle('d-none', !downloadUrl);
            }
            if (fallbackDownload) fallbackDownload.href = downloadUrl;

            clearTimers();
            revokeBlob();
            showLoading('Opening presentation…');
            frame.classList.add('d-none');
            frame.src = 'about:blank';

            modalEl._presentationCleanup = () => {
                clearTimers();
                revokeBlob();
                frame.src = 'about:blank';
            };

            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();

            (async () => {
                try {
                    if (ext === 'pdf') {
                        await loadPdfPreview();
                        return;
                    }

                    if (ext === 'ppt' || ext === 'pptx') {
                        if (!isHttps || !officeUrl) {
                            showFallback('PowerPoint preview needs HTTPS on your live server. Download the file to open it locally.');
                            return;
                        }

                        showLoading('Checking document availability…');
                        const reachable = await verifyPublicFile();
                        if (!reachable) {
                            showFallback('The preview service cannot reach this file on the server. Download it instead, or ask your admin to check file permissions.');
                            return;
                        }

                        loadOfficePreview();
                        return;
                    }

                    showFallback('Preview is not supported for this file type. Please download it instead.');
                } catch (err) {
                    showFallback(err.message || 'Could not load preview. Please download the file instead.');
                }
            })();

            if (!modalEl.dataset.viewerBound) {
                modalEl.dataset.viewerBound = '1';
                modalEl.addEventListener('hidden.bs.modal', () => {
                    if (typeof modalEl._presentationCleanup === 'function') {
                        modalEl._presentationCleanup();
                    }
                });
            }
        },
    };

    window.SnapVault = SnapVault;

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function closeAllSelects(except) {
        document.querySelectorAll('.sv-select-wrap.open').forEach((wrap) => {
            if (wrap !== except) wrap.classList.remove('open');
        });
    }

    function enhanceSelect(select) {
        if (select.dataset.svEnhanced === '1') return;
        select.dataset.svEnhanced = '1';

        const wrap = document.createElement('div');
        wrap.className = 'sv-select-wrap';
        select.parentNode.insertBefore(wrap, select);
        wrap.appendChild(select);

        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'sv-select-trigger';
        trigger.setAttribute('aria-haspopup', 'listbox');
        trigger.setAttribute('aria-expanded', 'false');

        const menu = document.createElement('ul');
        menu.className = 'sv-select-menu';
        menu.setAttribute('role', 'listbox');

        function selectedLabel() {
            const opt = select.options[select.selectedIndex];
            return opt ? opt.textContent.trim() : '';
        }

        function renderOptions() {
            menu.innerHTML = '';
            Array.from(select.options).forEach((opt, index) => {
                const li = document.createElement('li');
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'sv-select-option' + (opt.selected ? ' is-selected' : '');
                btn.setAttribute('role', 'option');
                btn.setAttribute('aria-selected', opt.selected ? 'true' : 'false');
                btn.dataset.value = opt.value;
                btn.dataset.index = String(index);
                btn.textContent = opt.textContent.trim();

                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    select.selectedIndex = index;
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                    trigger.textContent = selectedLabel();
                    wrap.classList.remove('open');
                    trigger.setAttribute('aria-expanded', 'false');
                    renderOptions();
                });

                btn.addEventListener('mouseenter', () => {
                    menu.querySelectorAll('.sv-select-option').forEach((o) => o.classList.remove('is-active'));
                    btn.classList.add('is-active');
                });

                li.appendChild(btn);
                menu.appendChild(li);
            });
        }

        trigger.textContent = selectedLabel();
        renderOptions();

        trigger.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            const willOpen = !wrap.classList.contains('open');
            closeAllSelects(wrap);
            wrap.classList.toggle('open', willOpen);
            trigger.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
            if (willOpen) {
                const selected = menu.querySelector('.is-selected');
                selected?.classList.add('is-active');
            }
        });

        select.addEventListener('change', () => {
            trigger.textContent = selectedLabel();
            renderOptions();
        });

        wrap.appendChild(trigger);
        wrap.appendChild(menu);
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.body.addEventListener('click', (e) => {
            const btn = e.target.closest('.password-toggle-btn');
            if (!btn) return;
            e.preventDefault();
            const group = btn.closest('.password-toggle-group');
            const input = btn.dataset.target
                ? document.getElementById(btn.dataset.target)
                : group?.querySelector('input[type="password"], input[type="text"]');
            if (!input) return;
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.setAttribute('aria-pressed', show ? 'true' : 'false');
            btn.setAttribute('title', show ? 'Hide password' : 'Show password');
            btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            const icon = btn.querySelector('i');
            if (icon) {
                icon.classList.toggle('bi-eye', !show);
                icon.classList.toggle('bi-eye-slash', show);
            }
        });

        // Notifications dropdown (AJAX poll)
        const notifBadge = document.getElementById('notifUnreadBadge');
        const notifList = document.getElementById('notifList');
        const notifMarkAll = document.getElementById('notifMarkAll');
        const csrfToken = cfg.csrfToken || '';

        function escNotif(s) {
            return String(s ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        function renderNotifications(rows) {
            if (!notifList) return;
            if (!rows || !rows.length) {
                notifList.innerHTML = '<div class="notif-empty text-muted small">No notifications yet.</div>';
                return;
            }
            notifList.innerHTML = rows.map((n) => {
                const unread = Number(n.is_read) === 0;
                const href = n.link ? escNotif(n.link) : '#';
                return (
                    '<a class="notif-item' + (unread ? ' is-unread' : '') + '" href="' + href + '" data-id="' + escNotif(n.id) + '">' +
                        '<div class="notif-item-title">' + escNotif(n.title) + '</div>' +
                        (n.body ? '<div class="notif-item-body">' + escNotif(n.body) + '</div>' : '') +
                        '<div class="notif-item-time">' + escNotif(n.created_at || '') + '</div>' +
                    '</a>'
                );
            }).join('');
        }

        async function pollNotifications() {
            if (!notifBadge && !notifList) return;
            try {
                const res = await fetch(cfg.baseUrl + 'ajax/notifications.php?action=list', {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json' },
                });
                const data = await res.json();
                if (!data.success) return;
                SnapVault.setNotifUnread(data.unread);
                renderNotifications(data.notifications || []);
            } catch (e) { /* ignore */ }
        }

        async function markNotifications(id) {
            const fd = new FormData();
            fd.set('action', 'mark');
            fd.set('_csrf', csrfToken);
            if (id) fd.set('id', String(id));
            try {
                const res = await fetch(cfg.baseUrl + 'ajax/notifications.php?action=mark', {
                    method: 'POST',
                    body: fd,
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json' },
                });
                const data = await res.json();
                if (data.success) {
                    SnapVault.setNotifUnread(data.unread);
                    pollNotifications();
                }
            } catch (e) { /* ignore */ }
        }

        if (notifBadge || notifList) {
            pollNotifications();
            setInterval(pollNotifications, (cfg.chatPollSeconds || 3) * 1000);
        }

        notifMarkAll?.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            markNotifications(null);
        });

        notifList?.addEventListener('click', (e) => {
            const item = e.target.closest('.notif-item');
            if (!item) return;
            const id = item.dataset.id;
            if (id) markNotifications(id);
        });

        document.querySelectorAll('select.sv-select').forEach(enhanceSelect);

        document.addEventListener('click', (e) => {
            if (!e.target.closest('.sv-select-wrap')) {
                closeAllSelects();
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeAllSelects();
        });

        const sidebar = document.getElementById('appSidebar');
        const backdrop = document.getElementById('sidebarBackdrop');
        const toggle = document.getElementById('sidebarToggle');

        function closeSidebar() {
            sidebar?.classList.remove('open');
            backdrop?.classList.remove('show');
        }

        toggle?.addEventListener('click', () => {
            sidebar?.classList.toggle('open');
            backdrop?.classList.toggle('show');
        });
        backdrop?.addEventListener('click', closeSidebar);

        // Collapsible sidebar groups (Hostinger-style)
        const storageKey = 'snapvault_sidebar_groups';
        let savedGroups = {};
        try {
            savedGroups = JSON.parse(localStorage.getItem(storageKey) || '{}') || {};
        } catch (e) {
            savedGroups = {};
        }

        document.querySelectorAll('.nav-group').forEach((group) => {
            const key = group.dataset.group;
            const btn = group.querySelector('.nav-group-toggle');
            const hasActive = !!group.querySelector('.nav-link.active');

            // Restore saved state, but keep open if current page is inside
            if (key && Object.prototype.hasOwnProperty.call(savedGroups, key) && !hasActive) {
                group.classList.toggle('is-open', !!savedGroups[key]);
            } else if (hasActive) {
                group.classList.add('is-open');
            }

            const syncAria = () => {
                btn?.setAttribute('aria-expanded', group.classList.contains('is-open') ? 'true' : 'false');
            };
            syncAria();

            btn?.addEventListener('click', () => {
                group.classList.toggle('is-open');
                syncAria();
                if (key) {
                    savedGroups[key] = group.classList.contains('is-open');
                    try {
                        localStorage.setItem(storageKey, JSON.stringify(savedGroups));
                    } catch (err) { /* ignore */ }
                }
            });
        });

        // Auto-dismiss flash alerts
        document.querySelectorAll('.js-auto-alert').forEach((el) => {
            setTimeout(() => {
                const inst = bootstrap.Alert.getOrCreateInstance(el);
                inst.close();
            }, 3500);
        });

        const lightboxModal = document.getElementById('lightboxModal');
        const lightboxImage = document.getElementById('lightboxImage');
        const lightboxTitle = document.getElementById('lightboxTitle');
        const lightboxMeta = document.getElementById('lightboxMeta');
        const lightboxSep = document.getElementById('lightboxSep');
        const lightboxCounter = document.getElementById('lightboxCounter');
        const lightboxPrev = document.getElementById('lightboxPrev');
        const lightboxNext = document.getElementById('lightboxNext');

        let lightboxGallery = [];
        let lightboxIndex = 0;

        function lightboxScope(trigger) {
            return trigger.closest('.lightbox-gallery, #imageGrid, tbody') || document;
        }

        function collectGallery(trigger) {
            return Array.from(lightboxScope(trigger).querySelectorAll('.lightbox-trigger'));
        }

        function renderLightbox() {
            if (!lightboxGallery.length || !lightboxImage) return;
            const trigger = lightboxGallery[lightboxIndex];
            const title = (trigger.dataset.title || '').trim();
            const meta = (trigger.dataset.meta || '').trim();

            lightboxImage.src = trigger.dataset.src || trigger.src || '';
            lightboxImage.alt = title || 'Preview';
            if (lightboxTitle) lightboxTitle.textContent = title;
            if (lightboxMeta) lightboxMeta.textContent = meta;
            if (lightboxSep) lightboxSep.classList.toggle('d-none', !(title && meta));
            if (lightboxCounter) {
                lightboxCounter.textContent = lightboxGallery.length > 1
                    ? (lightboxIndex + 1) + ' / ' + lightboxGallery.length
                    : '';
            }

            const multi = lightboxGallery.length > 1;
            lightboxPrev?.classList.toggle('d-none', !multi);
            lightboxNext?.classList.toggle('d-none', !multi);
        }

        function stepLightbox(delta) {
            if (lightboxGallery.length < 2) return;
            lightboxIndex = (lightboxIndex + delta + lightboxGallery.length) % lightboxGallery.length;
            renderLightbox();
        }

        function openLightbox(trigger) {
            if (!lightboxModal || !trigger) return;
            lightboxGallery = collectGallery(trigger);
            lightboxIndex = Math.max(0, lightboxGallery.indexOf(trigger));
            if (lightboxIndex < 0) lightboxIndex = 0;
            renderLightbox();
            bootstrap.Modal.getOrCreateInstance(lightboxModal).show();
        }

        document.body.addEventListener('click', (e) => {
            const trigger = e.target.closest('.lightbox-trigger');
            if (!trigger || !lightboxModal) return;
            e.preventDefault();
            openLightbox(trigger);
        });

        lightboxPrev?.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            stepLightbox(-1);
        });

        lightboxNext?.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            stepLightbox(1);
        });

        document.addEventListener('keydown', (e) => {
            if (!lightboxModal?.classList.contains('show')) return;
            if (e.key === 'ArrowLeft') {
                e.preventDefault();
                stepLightbox(-1);
            } else if (e.key === 'ArrowRight') {
                e.preventDefault();
                stepLightbox(1);
            }
        });

        document.body.addEventListener('click', async (e) => {
            const btn = e.target.closest('.btn-confirm-delete');
            if (!btn) return;
            e.preventDefault();
            const message = btn.dataset.message || 'Are you sure?';
            const formId = btn.dataset.formId;
            const ok = await SnapVault.confirm(message);
            if (ok && formId) {
                document.getElementById(formId)?.submit();
            }
        });

        document.body.addEventListener('click', (e) => {
            const btn = e.target.closest('.btn-delete-image');
            if (!btn) return;
            e.preventDefault();
            const id = btn.dataset.id;
            if (id) SnapVault.deleteImage(id);
        });

        document.body.addEventListener('click', (e) => {
            const btn = e.target.closest('.btn-delete-presentation');
            if (!btn) return;
            e.preventDefault();
            const id = btn.dataset.id;
            if (id) SnapVault.deletePresentation(id);
        });

        document.body.addEventListener('click', (e) => {
            const btn = e.target.closest('.btn-view-presentation');
            if (!btn) return;
            e.preventDefault();
            SnapVault.openPresentationViewer(btn);
        });

        const period = document.getElementById('filterPeriod');
        period?.addEventListener('change', () => {
            const show = period.value === 'range';
            document.querySelectorAll('.range-fields').forEach((el) => {
                el.classList.toggle('d-none', !show);
            });
        });

        // Page search: live submit (debounced) + single clear control
        document.querySelectorAll('form.page-search').forEach((form) => {
            const input = form.querySelector('.page-search-input');
            if (!input) return;

            let clearBtn = form.querySelector('.page-search-clear');
            if (!clearBtn) {
                clearBtn = document.createElement('button');
                clearBtn.type = 'button';
                clearBtn.className = 'page-search-clear';
                clearBtn.title = 'Clear';
                clearBtn.setAttribute('aria-label', 'Clear');
                clearBtn.innerHTML = '<i class="bi bi-x-lg" aria-hidden="true"></i>';
                form.appendChild(clearBtn);
            }

            const syncClear = () => {
                clearBtn.classList.toggle('is-hidden', input.value.trim() === '');
            };
            syncClear();

            let timer = null;
            let lastSent = input.value;

            const submitSearch = () => {
                if (input.value === lastSent) return;
                lastSent = input.value;
                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit();
                } else {
                    form.submit();
                }
            };

            input.addEventListener('input', () => {
                syncClear();
                clearTimeout(timer);
                timer = setTimeout(submitSearch, 400);
            });

            clearBtn.addEventListener('click', (e) => {
                e.preventDefault();
                clearTimeout(timer);
                if (input.value === '' && lastSent === '') return;
                input.value = '';
                syncClear();
                lastSent = null;
                submitSearch();
            });

            if (new URLSearchParams(window.location.search).has('q')) {
                input.focus();
                const len = input.value.length;
                try {
                    input.setSelectionRange(len, len);
                } catch (_) { /* ignore */ }
            }
        });
    });
})();

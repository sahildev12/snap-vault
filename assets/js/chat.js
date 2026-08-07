/**
 * Chat UI polling + send
 */
(function () {
    'use strict';

    const box = document.getElementById('chatMessages');
    const form = document.getElementById('chatForm');
    const bodyInput = document.getElementById('chatBody');
    if (!box || !form) return;

    const peerId = box.dataset.peer;
    const selfId = Number(box.dataset.self || 0);
    const endpoint = (window.SNAPVAULT?.baseUrl || '/') + 'ajax/chat.php';
    let lastId = 0;
    let timer = null;

    function esc(s) {
        return String(s ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function render(messages, append) {
        if (!append) box.innerHTML = '';
        messages.forEach((m) => {
            const mine = Number(m.from_user_id) === selfId;
            const el = document.createElement('div');
            el.className = 'chat-bubble' + (mine ? ' is-mine' : '');
            el.innerHTML =
                '<div class="chat-bubble-body">' + esc(m.body) + '</div>' +
                '<div class="chat-bubble-meta">' + esc(m.from_name || '') + ' · ' + esc(m.created_at || '') + '</div>';
            box.appendChild(el);
            lastId = Math.max(lastId, Number(m.id) || 0);
        });
        box.scrollTop = box.scrollHeight;
    }

    async function poll(full) {
        const url = endpoint + '?action=poll&with=' + encodeURIComponent(peerId) +
            (full ? '' : '&after=' + lastId);
        try {
            const res = await fetch(url, { credentials: 'same-origin' });
            const data = await res.json();
            if (!data.success) return;
            if (data.messages?.length) {
                render(data.messages, !full && lastId > 0);
            } else if (full) {
                box.innerHTML = '<div class="text-muted small p-3">No messages yet. Say hello.</div>';
            }
            if (typeof data.unread === 'number') {
                window.SnapVault?.setChatUnread?.(data.unread);
            }
        } catch (e) {
            /* ignore transient errors */
        }
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const body = bodyInput.value.trim();
        if (!body) return;
        const fd = new FormData(form);
        fd.set('action', 'send');
        try {
            const res = await fetch(endpoint + '?action=send', {
                method: 'POST',
                body: fd,
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' },
            });
            const data = await res.json();
            if (!data.success) {
                window.SnapVault?.toast(data.message || 'Send failed', 'danger');
                return;
            }
            bodyInput.value = '';
            if (data.message) render([data.message], true);
            else poll(false);
        } catch (err) {
            window.SnapVault?.toast('Send failed', 'danger');
        }
    });

    poll(true);
    const every = (window.SNAPVAULT?.chatPollSeconds || 3) * 1000;
    timer = setInterval(() => poll(false), every);
    window.addEventListener('beforeunload', () => clearInterval(timer));
})();

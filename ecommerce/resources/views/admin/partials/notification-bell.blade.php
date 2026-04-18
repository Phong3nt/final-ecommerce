<div id="notification-bell" style="position:relative;display:inline-block;cursor:pointer;"
    onclick="toggleNotificationPanel()">
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
        <path d="M13.73 21a2 2 0 0 1-3.46 0" />
    </svg>
    <span id="bell-badge"
        style="display:none;position:absolute;top:-6px;right:-6px;background:#e53e3e;color:#fff;border-radius:50%;width:18px;height:18px;font-size:11px;text-align:center;line-height:18px;"></span>
</div>

<div id="notification-panel"
    style="display:none;position:absolute;right:0;top:40px;width:320px;background:#fff;border:1px solid #e2e8f0;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,0.15);z-index:1000;">
    <div
        style="padding:12px 16px;border-bottom:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center;">
        <strong>Notifications</strong>
        <button onclick="markAllRead()"
            style="font-size:12px;color:#3182ce;border:none;background:none;cursor:pointer;">Mark all read</button>
    </div>
    <div id="notification-list" style="max-height:320px;overflow-y:auto;padding:8px 0;">
        <p style="text-align:center;color:#718096;padding:16px;">Loading…</p>
    </div>
</div>

<script>
    (function () {
        function loadNotifications() {
            fetch('/admin/notifications', { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.json())
                .then(data => {
                    const badge = document.getElementById('bell-badge');
                    badge.textContent = data.unread_count;
                    badge.style.display = data.unread_count > 0 ? 'inline-block' : 'none';

                    const list = document.getElementById('notification-list');
                    if (!data.notifications.length) {
                        list.innerHTML = '<p style="text-align:center;color:#718096;padding:16px;">No notifications</p>';
                        return;
                    }
                    list.innerHTML = data.notifications.map(n => `
                    <div style="padding:10px 16px;border-bottom:1px solid #f7fafc;background:${n.read_at ? '#fff' : '#ebf8ff'}">
                        <div style="font-size:13px;">${n.message}</div>
                        ${!n.read_at ? `<button onclick="markRead(${n.id})" style="font-size:11px;color:#3182ce;border:none;background:none;cursor:pointer;margin-top:4px;">Mark read</button>` : ''}
                    </div>`).join('');
                });
        }

        window.toggleNotificationPanel = function () {
            const panel = document.getElementById('notification-panel');
            panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
            if (panel.style.display === 'block') loadNotifications();
        };

        window.markRead = function (id) {
            fetch(`/admin/notifications/${id}/read`, {
                method: 'PATCH',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '', 'Accept': 'application/json' }
            }).then(() => loadNotifications());
        };

        window.markAllRead = function () {
            fetch('/admin/notifications/read-all', {
                method: 'PATCH',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '', 'Accept': 'application/json' }
            }).then(() => loadNotifications());
        };

        // Poll unread count every 30 s
        loadNotifications();
        setInterval(loadNotifications, 30000);
    })();
</script>
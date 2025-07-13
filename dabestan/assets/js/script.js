document.addEventListener('DOMContentLoaded', function() {

    // --- Sidebar Toggle for Mobile ---
    const menuToggle = document.getElementById('menu-toggle');
    const sidebar = document.querySelector('.sidebar');

    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }

    // --- Live Persian Date and Time ---
    const timeElement = document.getElementById('time');
    const dateElement = document.getElementById('date');

    function updateTime() {
        if (timeElement && dateElement) {
            const now = new Date();
            // Time
            timeElement.textContent = now.toLocaleTimeString('fa-IR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            // Date
            dateElement.textContent = new Intl.DateTimeFormat('fa-IR', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                weekday: 'long'
            }).format(now);
        }
    }

    // Update time immediately and then every second
    updateTime();
    setInterval(updateTime, 1000);


    // --- Notifications ---
    const notificationIcon = document.getElementById('notification-icon');
    const notificationCount = document.getElementById('notification-count');
    const notificationDropdown = document.getElementById('notification-dropdown');
    const notificationList = document.getElementById('notification-list');
    let notificationsLoaded = false;

    function fetchNotifications() {
        fetch('../includes/fetch_notifications.php')
            .then(response => response.json())
            .then(data => {
                if (data.error) return;

                // Update count badge
                if (data.unread_count > 0) {
                    notificationCount.textContent = data.unread_count;
                    notificationCount.style.display = 'block';
                } else {
                    notificationCount.style.display = 'none';
                }

                // Populate dropdown
                notificationList.innerHTML = ''; // Clear old list
                if (data.notifications.length > 0) {
                    data.notifications.forEach(notif => {
                        const item = document.createElement('div');
                        item.className = 'notification-item';
                        item.innerHTML = `
                            <a href="/dabestan/${notif.link}">${notif.message}</a>
                            <small>${notif.created_at}</small>
                        `;
                        notificationList.appendChild(item);
                    });
                } else {
                    notificationList.innerHTML = '<div class="notification-item">هیچ اعلان جدیدی وجود ندارد.</div>';
                }
            })
            .catch(error => console.error('Error fetching notifications:', error));
    }

    if (notificationIcon) {
        // Fetch notifications on page load
        fetchNotifications();
        // And then fetch every 30 seconds
        setInterval(fetchNotifications, 30000);

        notificationIcon.addEventListener('click', function() {
            notificationDropdown.classList.toggle('show');

            // If dropdown is opened and there are unread notifications, mark them as read
            if (notificationDropdown.classList.contains('show') && notificationCount.style.display === 'block') {
                fetch('../includes/mark_notifications_read.php', { method: 'POST' })
                    .then(() => {
                        notificationCount.style.display = 'none'; // Hide count immediately
                    });
            }
        });

        // Close dropdown if clicked outside
        document.addEventListener('click', function(event) {
            if (!notificationIcon.contains(event.target) && !notificationDropdown.contains(event.target)) {
                notificationDropdown.classList.remove('show');
            }
        });
    }

    // Activate Feather Icons
    feather.replace();

    // Sidebar Submenu Toggle
    document.querySelectorAll('.has-submenu > a').forEach(function(menu) {
        menu.addEventListener('click', function(event) {
            event.preventDefault();
            let parentLi = this.parentElement;

            // Close other open submenus
            document.querySelectorAll('.has-submenu.open').forEach(function(openMenu) {
                if(openMenu !== parentLi) {
                    openMenu.classList.remove('open');
                    openMenu.querySelector('.submenu').style.maxHeight = null;
                }
            });

            // Toggle current submenu
            parentLi.classList.toggle('open');
            let submenu = this.nextElementSibling;
            if (submenu.style.maxHeight) {
                submenu.style.maxHeight = null;
            } else {
                submenu.style.maxHeight = submenu.scrollHeight + "px";
            }
        });
    });

    // Edit Telegram Chat ID
    const editChatIdBtn = document.getElementById('edit-chat-id');
    const chatIdInput = document.getElementById('telegram_chat_id');
    const submitArea = document.getElementById('telegram-submit-area');

    function toggleChatIdEdit(isInitial) {
        const isCurrentlyEmpty = chatIdInput.value.trim() === '';
        if (isInitial && !isCurrentlyEmpty) {
            return; // Do nothing on load if already has value
        }

        chatIdInput.readOnly = !chatIdInput.readOnly;
        if (!chatIdInput.readOnly) {
            chatIdInput.focus();
            submitArea.style.display = 'flex';
            editChatIdBtn.textContent = 'لغو';
        } else {
            submitArea.style.display = 'none';
            editChatIdBtn.textContent = 'ویرایش';
        }
    }

    if (editChatIdBtn && chatIdInput && submitArea) {
        // Allow editing by default if the field is empty on page load
        if (chatIdInput.value.trim() === '') {
            toggleChatIdEdit(true);
        }
        editChatIdBtn.addEventListener('click', () => toggleChatIdEdit(false));
    }

    // Send Test Telegram Message
    const testMessageBtn = document.getElementById('send-test-message');
    if (testMessageBtn) {
        testMessageBtn.addEventListener('click', function() {
            const chatId = chatIdInput.value;
            if (!chatId) {
                alert('لطفاً ابتدا شناسه چت را وارد کنید.');
                return;
            }
            // Using fetch to call a dedicated PHP script for sending the test message
            fetch('send_test_telegram.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ chat_id: chatId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('پیام تست با موفقیت ارسال شد!');
                } else {
                    alert('خطا در ارسال پیام تست: ' + data.error);
                }
            })
            .catch(err => alert('خطای اساسی در ارسال درخواست.'));
        });
    }
});

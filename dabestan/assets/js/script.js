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
            // Set locale to Persian
            moment.locale('fa');

            // Get current time and format it
            const now = moment();
            const timeString = now.format('HH:mm:ss');
            // Corrected format string to remove extra 'j'
            const dateString = now.format('dddd، D MMMM YYYY');

            // Update the elements
            timeElement.textContent = timeString;
            dateElement.textContent = dateString;
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
});

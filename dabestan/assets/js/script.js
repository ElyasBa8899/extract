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
            const dateString = now.format('jYYYY/jMM/jDD');

            // Update the elements
            timeElement.textContent = timeString;
            dateElement.textContent = dateString;
        }
    }

    // Update time immediately and then every second
    updateTime();
    setInterval(updateTime, 1000);

});

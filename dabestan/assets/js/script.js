document.addEventListener('DOMContentLoaded', function() {

    // --- Hamburger Menu Toggle ---
    const hamburger = document.querySelector('.hamburger-menu');
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');

    if (hamburger && sidebar) {
        hamburger.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }

    // --- Feather Icons ---
    // Make sure feather icons are replaced
    if (typeof feather !== 'undefined') {
        feather.replace();
    }

    // --- Datetime Display ---
    const dateElement = document.getElementById('persian-date');
    const timeElement = document.getElementById('persian-time');

    function updateTime() {
        if (typeof jmoment === 'undefined') {
            if (dateElement) dateElement.innerText = "در حال بارگذاری تاریخ...";
            return;
        }
        const now = jmoment();
        if (dateElement) {
            dateElement.innerText = now.format('dddd، jD jMMMM jYYYY');
        }
        if (timeElement) {
            timeElement.innerText = now.format('HH:mm:ss');
        }
    }

    // Update time immediately and then every second
    updateTime();
    setInterval(updateTime, 1000);

    // --- Password Visibility Toggle ---
    const togglePassword = document.querySelector('.toggle-password');
    if (togglePassword) {
        togglePassword.addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.setAttribute('data-feather', 'eye-off');
            } else {
                passwordInput.type = 'password';
                icon.setAttribute('data-feather', 'eye');
            }
            feather.replace(); // Re-render the icon
        });
    }

    // --- Sidebar Active Link ---
    const currentPath = window.location.pathname.split('/').pop();
    const navLinks = document.querySelectorAll('.sidebar-nav a');
    navLinks.forEach(link => {
        if (link.getAttribute('href').includes(currentPath)) {
            link.parentElement.classList.add('active');
        }
    });

});

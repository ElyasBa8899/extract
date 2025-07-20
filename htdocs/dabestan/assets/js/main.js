document.addEventListener('DOMContentLoaded', function() {
    const hamburger = document.querySelector('.hamburger-menu');
    const sidebar = document.getElementById('sidebar');
    const mainContainer = document.getElementById('main-container');
    const body = document.body;

    if (hamburger && sidebar) {
        hamburger.addEventListener('click', function() {
            sidebar.classList.toggle('open');
            body.classList.toggle('sidebar-open');
            mainContainer.classList.toggle('sidebar-open');
        });
    }

    // Function to update the clock
    function updateClock() {
        const clockElement = document.getElementById('live-clock');
        if (clockElement) {
            const now = new Date();
            const timeString = now.toLocaleTimeString('fa-IR', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false
            });
            clockElement.textContent = timeString;
        }
    }

    // Function to display Jalali date
    function displayJalaliDate() {
        const dateElement = document.getElementById('jalali-date');
        if (dateElement) {
            // Using the built-in Intl object which has good support in modern browsers
            const today = new Date();
            const jalaliDate = new Intl.DateTimeFormat('fa-IR', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            }).format(today);
            dateElement.textContent = jalaliDate;
        }
    }

    // Initial calls and intervals
    updateClock();
    setInterval(updateClock, 1000);
    displayJalaliDate();

});

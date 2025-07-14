</main>
    </div>

    <!-- jQuery -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

    <!-- Jalaali Moment for Persian Calendar -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment-jalaali/0.9.2/moment-jalaali.js"></script>

    <!-- Persian Datepicker -->
    <script src="https://unpkg.com/persian-date@1.1.0/dist/persian-date.min.js"></script>
    <script src="https://unpkg.com/persian-datepicker@1.2.0/dist/js/persian-datepicker.min.js"></script>

    <!-- Feather Icons -->
    <script src="https://unpkg.com/feather-icons"></script>

    <!-- Custom Scripts -->
    <script src="/dabestan/assets/js/script.js"></script>
    <script>
        // Initialize Persian Datepicker on all elements with the class 'persian-datepicker'
        $(document).ready(function() {
            $(".persian-datepicker").pDatepicker({
                format: 'YYYY/MM/DD',
                autoClose: true,
                initialValue: false, // Do not set a default value
                observer: true, // Re-initializes on new elements
                calendar: {
                    persian: {
                        locale: 'fa'
                    }
                },
                toolbox: {
                    enabled: true,
                    calendarSwitch: {
                        enabled: true,
                        format: 'YYYY'
                    },
                    todayButton: {
                        enabled: true,
                        text: {
                            fa: 'امروز'
                        }
                    },
                     submitButton: {
                        enabled: true,
                        text: {
                            fa: 'تایید'
                        }
                    }
                }
            });
        });
    </script>
    <?php
    // Close the database connection at the very end of the script
    if (isset($link) && $link instanceof mysqli && $link->thread_id) {
        $link->close();
    }
    ?>
</body>
</html>

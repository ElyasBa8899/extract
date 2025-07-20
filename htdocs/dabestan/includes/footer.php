</div> <!-- .content-wrapper -->
    </main> <!-- #main-content -->

</div> <!-- #main-container -->

<!-- Main JS File -->
<script src="<?php echo BASE_URL; ?>/assets/js/main.js"></script>

<!-- You can add more JS files here -->

</body>
</html>
<?php
// Close the database connection if it's open
global $db;
if ($db) {
    $db->close();
}
?>

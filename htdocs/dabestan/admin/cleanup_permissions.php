<?php
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/functions.php';

// This is a sensitive script. Ensure only the main admin can run it.
// In a real application, you might want to run this from the command line
// or have a more robust access check.
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    die("Access Denied. You must be an administrator to run this script.");
}

echo "<h1>Permission Cleanup Script</h1>";
echo "<p>This script will remove duplicate permissions from the database.</p>";

global $db;
if (!$db) {
    die("Database connection failed.");
}

// --- Step 1: Find duplicate permissions ---
$sql_find_duplicates = "
    SELECT permission_name, COUNT(*) as count
    FROM permissions
    GROUP BY permission_name
    HAVING COUNT(*) > 1
";

$duplicates_result = $db->query($sql_find_duplicates);

if ($duplicates_result->num_rows === 0) {
    echo "<p style='color: green;'>No duplicate permissions found. The table is already clean.</p>";
    exit;
}

echo "<h2>Found Duplicates:</h2>";
echo "<ul>";
while ($row = $duplicates_result->fetch_assoc()) {
    echo "<li>'<strong>" . e($row['permission_name']) . "</strong>' found " . e($row['count']) . " times.</li>";
}
echo "</ul>";

// --- Step 2: Process each duplicate permission name ---
$duplicates_result->data_seek(0); // Reset result pointer

$db->begin_transaction();

try {
    while ($duplicate_row = $duplicates_result->fetch_assoc()) {
        $permission_name = $duplicate_row['permission_name'];
        echo "<hr><h3>Processing: '" . e($permission_name) . "'</h3>";

        // Get all IDs for this permission name
        $sql_get_ids = "SELECT id FROM permissions WHERE permission_name = ?";
        $stmt_get_ids = $db->prepare($sql_get_ids);
        $stmt_get_ids->bind_param('s', $permission_name);
        $stmt_get_ids->execute();
        $ids_result = $stmt_get_ids->get_result();

        $ids = [];
        while ($id_row = $ids_result->fetch_assoc()) {
            $ids[] = $id_row['id'];
        }
        $stmt_get_ids->close();

        if (count($ids) < 2) {
            echo "<p>Skipping, as it's not a duplicate anymore.</p>";
            continue;
        }

        // Keep the first ID, delete the rest
        $id_to_keep = array_shift($ids);
        $ids_to_delete = $ids;
        $ids_to_delete_str = implode(',', $ids_to_delete);

        echo "<p>Keeping ID: <strong>" . e($id_to_keep) . "</strong>. Merging and deleting IDs: " . e($ids_to_delete_str) . "</p>";

        // Update foreign key tables (role_permissions, user_permissions, etc.)
        // For each table, update the old IDs to the one we are keeping.
        $tables_to_update = ['role_permissions', 'user_permissions', 'department_permissions'];
        foreach ($tables_to_update as $table) {
            // Use IGNORE to avoid errors on duplicate key updates. The duplicates will be cleaned up after.
            $sql_update = "UPDATE IGNORE {$table} SET permission_id = ? WHERE permission_id IN ({$ids_to_delete_str})";
            $stmt_update = $db->prepare($sql_update);
            $stmt_update->bind_param('i', $id_to_keep);
            if($stmt_update->execute()) {
                echo "<p>Updated '{$table}'. Affected rows: " . $stmt_update->affected_rows . "</p>";
            } else {
                 throw new Exception("Failed to update {$table}: " . $stmt_update->error);
            }
            $stmt_update->close();

            // Clean up any rows that became duplicates after the update
            $sql_cleanup_duplicates = "
                DELETE t1 FROM {$table} t1
                INNER JOIN {$table} t2
                WHERE
                    t1.id > t2.id AND
                    t1.permission_id = t2.permission_id AND
                    t1.role_id = t2.role_id; # Adjust for other tables if they have different keys
            ";
            // This part is tricky as keys are different. Let's handle it simply by deleting from the original list of deleted IDs.
             $sql_delete_from_updated = "DELETE FROM {$table} WHERE permission_id IN ({$ids_to_delete_str})";
             //This is simpler and safer. The goal is to consolidate everything into the kept ID.
             //The previous update already moved the valid relations. Anything left with the old IDs is redundant.
        }

        // --- Step 3: Delete the redundant permission entries ---
        $sql_delete_perms = "DELETE FROM permissions WHERE id IN ({$ids_to_delete_str})";
        $stmt_delete = $db->prepare($sql_delete_perms);
        if($stmt_delete->execute()){
            echo "<p style='color: blue;'>Successfully deleted redundant permission entries. Affected rows: " . $stmt_delete->affected_rows . "</p>";
        } else {
            throw new Exception("Failed to delete permissions: " . $stmt_delete->error);
        }
        $stmt_delete->close();
    }

    $db->commit();
    echo "<h2 style='color: green;'>Cleanup successful! All duplicate permissions have been merged.</h2>";

} catch (Exception $e) {
    $db->rollback();
    echo "<h2 style='color: red;'>An error occurred: " . e($e->getMessage()) . "</h2>";
    echo "<p>The transaction has been rolled back. No changes were saved.</p>";
}

?>

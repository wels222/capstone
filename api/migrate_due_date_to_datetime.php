<?php
require_once __DIR__ . '/_bootstrap.php';
// Simple one-off migration helper to convert tasks.due_date from DATE -> DATETIME
// Usage: run this once from the server (php migrate_due_date_to_datetime.php) or open in browser while secure.

require_once __DIR__ . '/../db.php';
header('Content-Type: text/plain');

echo "Starting migration to convert tasks.due_date to DATETIME\n";

try {
    // Check column data type
    $colCheck = $pdo->prepare("SELECT DATA_TYPE FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'tasks' AND column_name = 'due_date'");
    $colCheck->execute();
    $row = $colCheck->fetch(PDO::FETCH_ASSOC);
    $current = $row['DATA_TYPE'] ?? null;
    echo "Current column type: " . ($current ?: 'not found') . "\n";

    if (!$current) {
        echo "No due_date column found on tasks table. Exiting.\n";
        exit(1);
    }

    if (strtolower($current) === 'datetime') {
        echo "Column is already DATETIME; nothing to do.\n";
        exit(0);
    }

    // Before altering, back up the original values for safety (optional)
    $backupTable = 'tasks_due_date_backup_' . date('Ymd_His');
    echo "Creating backup table: $backupTable ...\n";
    $pdo->exec("CREATE TABLE $backupTable AS SELECT id, due_date FROM tasks");
    echo "Backup created. Rows: " . $pdo->query("SELECT COUNT(*) FROM $backupTable")->fetchColumn() . "\n";

    // Alter column to DATETIME
    echo "Altering column to DATETIME...\n";
    $pdo->exec("ALTER TABLE tasks MODIFY COLUMN due_date DATETIME DEFAULT NULL");
    echo "Altered column.\n";

    // Update existing rows where time portion is missing (date-only) to append 00:00:00
    // If any values already have a time part, leave them alone.
    echo "Normalizing rows: setting time to 00:00:00 for date-only values...\n";
    $updateSql = "UPDATE tasks SET due_date = CONCAT(due_date, ' 00:00:00') WHERE due_date IS NOT NULL AND CHAR_LENGTH(due_date) = 10"; // 'YYYY-MM-DD' length 10
    $affected = $pdo->exec($updateSql);
    echo "Rows updated: " . ($affected === false ? 0 : $affected) . "\n";

    echo "Migration completed successfully. Please remove this script after verifying results.\n";
    exit(0);
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}

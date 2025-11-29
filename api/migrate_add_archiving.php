<?php
require_once __DIR__ . '/_bootstrap.php';
// api/migrate_add_archiving.php
// Idempotent migration to add soft-archive columns to core tables.
// Usage: open this file in the browser once, or invoke via HTTP to run.
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

$tables = [
    'users',
    'events',
    'tasks',
];

$results = [];

try {
    foreach ($tables as $table) {
        // Check existing columns
        $stmt = $pdo->prepare("SELECT COLUMN_NAME FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ?");
        $stmt->execute([$table]);
        $cols = array_map(function($r){ return strtolower($r['COLUMN_NAME']); }, $stmt->fetchAll(PDO::FETCH_ASSOC));

        // Add is_archived
        if (!in_array('is_archived', $cols)) {
            $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN is_archived TINYINT(1) NOT NULL DEFAULT 0 AFTER `updated_at`");
            $results[] = "{$table}: added is_archived";
        } else {
            $results[] = "{$table}: is_archived exists";
        }
        // Add archived_at
        if (!in_array('archived_at', $cols)) {
            $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN archived_at DATETIME NULL DEFAULT NULL AFTER `is_archived`");
            $results[] = "{$table}: added archived_at";
        } else {
            $results[] = "{$table}: archived_at exists";
        }
        // Helpful index on is_archived
        try { $pdo->exec("ALTER TABLE `{$table}` ADD INDEX idx_is_archived (is_archived)"); } catch (PDOException $e) { /* ignore */ }
    }

    echo json_encode(['success' => true, 'message' => 'Migration complete', 'details' => $results]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Migration failed', 'details' => $e->getMessage(), 'progress' => $results]);
}

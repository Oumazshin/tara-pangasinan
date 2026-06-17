<?php
require_once __DIR__ . '/includes/db.php';

try {
    $pdo->exec("ALTER TABLE reviews ADD COLUMN photo_url VARCHAR(500) DEFAULT NULL;");
    echo "Successfully added photo_url column to reviews table.\n";
} catch (PDOException $e) {
    if ($e->getCode() == '42S21') { // Duplicate column name
        echo "Column photo_url already exists in reviews table.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

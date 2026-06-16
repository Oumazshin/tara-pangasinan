<?php
/**
 * PDO Connection Singleton.
 *
 * Provides a single shared $pdo instance for any endpoint that includes this file.
 * The guard `if (!isset($pdo))` ensures the connection is created only once
 * even if multiple files include this script.
 */

if (!isset($pdo)) {

    $cfg = require __DIR__ . '/../config/database.php';

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $cfg['host'],
        $cfg['port'],
        $cfg['dbname'],
        $cfg['charset']
    );

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // throw on SQL errors
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // return associative arrays
        PDO::ATTR_EMULATE_PREPARES   => false,                   // real prepared statements
    ];

    try {
        $pdo = new PDO($dsn, $cfg['user'], $cfg['password'], $options);
    } catch (PDOException $e) {
        // Log real error server-side; send a safe generic message to the client
        error_log('PDO connection error: ' . $e->getMessage());
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error'   => 'Database connection failed.',
            'code'    => 'db_connection_failed',
        ]);
        exit;
    }
}

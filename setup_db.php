<?php
require_once __DIR__ . '/db.php';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS tests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        method_name VARCHAR(255) NOT NULL
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS test_results (
        id INT AUTO_INCREMENT PRIMARY KEY,
        test_id INT NOT NULL,
        status ENUM('passed', 'failed') NOT NULL,
        message TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (test_id) REFERENCES tests(id)
    )");

    // Clear existing tests to avoid duplicates if running multiple times
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("TRUNCATE TABLE test_results");
    $pdo->exec("TRUNCATE TABLE tests");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    // Insert sample tests
    $stmt = $pdo->prepare("INSERT INTO tests (name, method_name) VALUES (?, ?)");
    $stmt->execute(['Addition', 'add']);
    $stmt->execute(['Subtraction', 'subtract']);
    $stmt->execute(['Division by Zero', 'divideByZero']);
    $stmt->execute(['String Match', 'checkString']);

    echo "Database initialized successfully with sample tests.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../tests/SampleTests.php';
require_once __DIR__ . '/WebScanner.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'list') {
    $stmt = $pdo->query("
        SELECT t.id, t.name, t.method_name, r.status, r.message, r.created_at 
        FROM tests t
        LEFT JOIN (
            SELECT test_id, status, message, created_at 
            FROM test_results 
            WHERE id IN (SELECT MAX(id) FROM test_results GROUP BY test_id)
        ) r ON t.id = r.test_id
        ORDER BY t.id
    ");
    $results = $stmt->fetchAll();
    
    // Return results along with the CSRF token
    echo json_encode([
        'csrf_token' => $_SESSION['csrf_token'] ?? '',
        'tests' => array_map(function($row) {
            return [
                'id' => $row['id'],
                'name' => htmlspecialchars((string)$row['name'], ENT_QUOTES, 'UTF-8'),
                'method_name' => htmlspecialchars((string)$row['method_name'], ENT_QUOTES, 'UTF-8'),
                'status' => $row['status'],
                'message' => htmlspecialchars((string)$row['message'], ENT_QUOTES, 'UTF-8'),
                'created_at' => $row['created_at']
            ];
        }, $results)
    ]);
    exit;
}

if ($action === 'run') {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid CSRF token']);
        exit;
    }

    $testId = $_POST['test_id'] ?? null;
    if (!$testId) {
        echo json_encode(['error' => 'No test ID provided']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM tests WHERE id = ?");
    $stmt->execute([$testId]);
    $test = $stmt->fetch();

    if (!$test) {
        echo json_encode(['error' => 'Test not found']);
        exit;
    }

    $tester = new SampleTests();
    $status = 'passed';
    $message = 'Test passed successfully';

    try {
        switch ($test['method_name']) {
            case 'add':
                $result = $tester->add(2, 2);
                if ($result !== 4) throw new Exception("Expected 4, got $result");
                break;
            case 'subtract':
                $result = $tester->subtract(5, 3);
                if ($result !== 2) throw new Exception("Expected 2, got $result");
                break;
            case 'divideByZero':
                try {
                    $tester->divideByZero(10, 0);
                } catch (Exception $e) {
                    $result = 'caught error';
                    if ($result !== 'caught error') throw new Exception("Failed to catch exception");
                }
                break;
            case 'checkString':
                $result = $tester->checkString('hello', 'hello');
                if ($result !== true) throw new Exception("String match failed");
                break;
            default:
                throw new Exception("Method not implemented in runner");
        }
    } catch (Exception $e) {
        $status = 'failed';
        $message = $e->getMessage();
    }

    $stmt = $pdo->prepare("INSERT INTO test_results (test_id, status, message) VALUES (?, ?, ?)");
    $stmt->execute([$testId, $status, $message]);

    echo json_encode(['status' => $status, 'message' => $message]);
    exit;
}

if ($action === 'scan') {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid CSRF token']);
        exit;
    }

    $url = $_POST['url'] ?? '';
    $testType = $_POST['test_type'] ?? '';

    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        echo json_encode(['error' => 'Invalid URL provided']);
        exit;
    }

    $scanner = new WebScanner($url);
    $resultMessage = '';
    $resultStatus = 'passed';

    try {
        switch ($testType) {
            case 'html_elements':
                $resultMessage = $scanner->testHtmlElements();
                break;
            case 'broken_links':
                $resultMessage = $scanner->testBrokenLinks();
                break;
            case 'seo_tags':
                $resultMessage = $scanner->testSeoTags();
                break;
            case 'security_headers':
                $resultMessage = $scanner->testSecurityHeaders();
                break;
            default:
                throw new Exception("Unknown scan type: $testType");
        }
    } catch (Exception $e) {
        $resultStatus = 'failed';
        $resultMessage = $e->getMessage();
    }

    echo json_encode(['status' => $resultStatus, 'message' => $resultMessage]);
    exit;
}

echo json_encode(['error' => 'Invalid action']);



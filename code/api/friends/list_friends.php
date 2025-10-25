<?php
//headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['message' => 'Method Not Allowed']);
    exit;
}

//initializing the API
include_once(__DIR__ . '/../../core/index.php');
require_once(__DIR__ . '/../../includes/middleware.php');

$friend = new Friend($db);

try {
    $userId = $_GET['userId'] ?? null;
    $friend->userId = $userId;
    $friends = $friend->listFriends();
    $count = count($friends);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Friends retrieved',
        'data' => [
            'friends' => $friends,
            'count' => $count
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error', 'error' => $e->getMessage()]);
}
?>
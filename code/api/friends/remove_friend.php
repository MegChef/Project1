<?php
//headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['message' => 'Method Not Allowed']);
    exit;
}

//initializing the API
include_once(__DIR__ . '/../../core/index.php');
require_once(__DIR__ . '/../../includes/middleware.php');

$user = authenticate($_SERVER, $db);

error_log("User data: " . print_r($user, true));

$friend = new Friend($db);

try{
    //get raw posted data
    $json = file_get_contents("php://input");
    //decode JSON data
    $data = json_decode($json);

    //validate input
    if (!isset($data->friendId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Friend ID is required']);
        exit;
    }

    //set friend properties
    if (is_array($user)) {
        $userId = $user['id'] ?? $user['user_id'] ?? $user['userId'] ?? null;
    } else {
        $userId = $user->id ?? $user->user_id ?? $user->userId ?? null;
    }

    if (!$userId) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid user authentication']);
        exit;
    }

    $friend->userId = $userId;
    $friend->friendId = $data->friendId;

    $result = $friend->removeFriend();

    if ($result['success']) {
        http_response_code(200);
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error', 'error' => $e->getMessage()]);
}
?>
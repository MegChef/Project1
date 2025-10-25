<?php
//headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['message' => 'Method Not Allowed']);
    exit;
}

//initializing the API
include_once(__DIR__ . '/../../core/index.php');
require_once(__DIR__ . '/../../includes/middleware.php');

//instantiate friend
$friend = new Friend($db);

try{
    //get raw posted data
    $json = file_get_contents("php://input");
    //decode JSON data
    $data = json_decode($json);

    //validate required fields
    if (empty($data->senderId) || empty($data->receiverId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'senderId and receiverId required']);
        exit();
    }

    //set user properties
    $friend->senderId = $data->senderId;
    $friend->receiverId = $data->receiverId;

    $result = $friend->sendRequest();

    if ($result['success']) {
        http_response_code(201);
        echo json_encode(['message' => 'Friend request sent']);
    } else {
        http_response_code(400);
        echo json_encode(['message' => 'Friend request was not able to be sent']);
    }
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error', 'error' => $e->getMessage()]);
}
?>
<?php
//headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(['message' => 'Method Not Allowed']);
    exit;
}

//initializing the API
include_once(__DIR__ . '/../../core/index.php');
require_once(__DIR__ . '/../../includes/middleware.php');

//instantiate friend
$friend = new Friend($db);

//get raw posted data
$json = file_get_contents("php://input");
//decode JSON data
$data = json_decode($json);

if (empty($data->requestId) || empty($data->receiverId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'requestId and receiverId required']);
    exit();
}

//set friend properties
$friend->requestId = $data->requestId;
$friend->receiverId = $data->receiverId;

//register user
$result = $friend->rejectRequest();

if ($result['success']) {
    http_response_code(200);
    echo json_encode($result);
} else {
    http_response_code(400);
    echo json_encode($result);
}
?>
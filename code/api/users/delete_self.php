<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once(__DIR__ . '/../../core/index.php');
require_once(__DIR__ . '/../../includes/middleware.php');

$user = new User($db);

$data = json_decode(file_get_contents("php://input"));

if (empty($data->id) || empty($data->password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'User ID and password required']);
    exit();
}

$user->id = $data->id;
$user->password = $data->password;

$result = $user->deleteSelf();

if ($result['success']) {
    http_response_code(200);
    echo json_encode($result);
} else {
    http_response_code(400);
    echo json_encode($result);
}
?>
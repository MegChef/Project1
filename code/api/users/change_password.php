<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once('../core/index.php');
require_once('../includes/middleware.php');

$user = new User($db);


$data = json_decode(file_get_contents("php://input"));

if (empty($data->id) || empty($data->oldPassword) || empty($data->newPassword)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$user->id = $data->id;
$user->oldPass = $data->oldPassword;
$user->newPass = $data->newPassword;

$result = $user->changePassword();

if ($result['success']) {
    http_response_code(200);
    echo json_encode($result);
} else {
    http_response_code(400);
    echo json_encode($result);
}
    
?>
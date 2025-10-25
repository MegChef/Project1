<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

//only DELETE method allowed
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['message' => 'Method Not Allowed']);
    exit();
}

include_once(__DIR__ . '/../../core/index.php');
require_once(__DIR__ . '/../../includes/middleware.php');

//authenticate admin user from token
$admin = authenticate(getallheaders(), $db);

if (!isset($admin['role']) || $admin['role'] !== 'admin') { // token is invalid or user is not an admin
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Access denied. Admins only.'
    ]);
    exit();
}

$data = json_decode(file_get_contents("php://input"));

if (empty($data->id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'User ID required']);
    exit();
}

$user = new User($db);
$user->id = $data->id;
$result = $user->adminDelete();

if ($result['success']) {
    http_response_code(200);
    echo json_encode($result);
} else {
    http_response_code(400);
    echo json_encode($result);
}
?>
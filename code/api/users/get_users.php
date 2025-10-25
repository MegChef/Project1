<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

//handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

//only GET method allowed
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['message' => 'Method Not Allowed']);
    exit();
}

//include dependencies
include_once(__DIR__ . '/../../core/index.php');
require_once(__DIR__ . '/../../includes/middleware.php');

//authenticate token (returns decoded token data)
$userData = authenticate(getallheaders(), $db);

//simple inline admin check
if (empty($userData['role']) || strtolower($userData['role']) !== 'admin') {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Forbidden: Admins only'
    ]);
    exit();
}

//instantiate User class and get all users
$user = new User($db);
$user->id = $userData['user_id'];
$result = $user->getAllUsers();

if ($result['success']) {
    http_response_code(200);
    echo json_encode($result);
} else {
    http_response_code(500);
    echo json_encode($result);
}
?>


<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../core/auth.php';
include_once(__DIR__ . '/../../core/index.php');
require_once(__DIR__ . '/../../includes/middleware.php');

$headers = getallheaders();

if (isset($headers['Authorization'])) {
    $token = str_replace('Bearer ', '', $headers['Authorization']);
} else {
    $token = null;
}

$authenticate = new Auth( $db);
$authenticate->token = $token;

$result = $authenticate->logout();
    
http_response_code(200);
echo json_encode($result);

?>
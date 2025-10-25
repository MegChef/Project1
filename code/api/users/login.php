<?php
//headers
header('Access-Contorl-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

//initializing the API
include_once(__DIR__ . '/../../core/index.php');
require_once(__DIR__ . '/../../includes/middleware.php');

$authenticate = new Auth($db);

//get raw posted data
$json = file_get_contents("php://input");
//decode JSON data
$data = json_decode($json);

//validate required fields
if (empty($data->username) || empty($data->password)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Username and password is required'
    ]);
    exit();
}

//set user properties
$authenticate->username = $data->username;
$authenticate->password = $data->password;

//register user
$result = $authenticate->login();

if($result['success']){
    http_response_code(200);
    echo json_encode($result);
}else{
    http_response_code(401);
    echo json_encode($result);
}
?>
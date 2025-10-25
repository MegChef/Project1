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

//initializing the API
include_once(__DIR__ . '/../../core/index.php');
require_once(__DIR__ . '/../../includes/middleware.php');

//instantiate user
$user = new User($db);

//get raw posted data
$json = file_get_contents("php://input");
//decode JSON data
$data = json_decode($json);

//validate required fields
if (empty($data->username)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Username is required'
    ]);
    exit();
}

if (empty($data->password)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Password is required'
    ]);
    exit();
}

//set user properties
$user->username = $data->username;
$user->password = $data->password;

//register user
$result = $user->register();

//check if registration was successful
if($result['success']){
    http_response_code(201); //created
    echo json_encode([
        'success' => true,
        'message' => $result['message'],
        'userId' => $result['userId'] ?? null
    ]);
}else{
    //registration failed
    http_response_code(400); //bad request
    echo json_encode([
        'success' => false,
        'message' => $result['message']
    ]);
}
?>
<?php
//authentication middleware (verifies JWT/token)

require_once __DIR__ . '/../core/auth.php';

function authenticate($headers, $db) {
    $authHeader = null;
    
    if (isset($headers['Authorization'])) {
        $authHeader = $headers['Authorization'];
    } elseif (isset($headers['HTTP_AUTHORIZATION'])) {
        $authHeader = $headers['HTTP_AUTHORIZATION'];
    } elseif (function_exists('apache_request_headers')) {
        $apacheHeaders = apache_request_headers();
        if (isset($apacheHeaders['Authorization'])) {
            $authHeader = $apacheHeaders['Authorization'];
        }
    }

    if (!$authHeader) {
        http_response_code(401);
        echo json_encode(['error' => 'Missing Authorization header']);
        exit;
    }

    $token = str_replace('Bearer ', '', $authHeader);

    $auth = new Auth($db);
    $auth->token = $token;
    $result = $auth->verifyToken();

    if (!$result['success']) {
        http_response_code(403);
        echo json_encode(['error' => $result['message']]);
        exit;
    }

    return $result['data'];
}
?>

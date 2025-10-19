<?php
// api/middleware.php
require_once __DIR__ . '/vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$SECRET_KEY = 'supersecretkey'; // Change to your own strong secret

function generateJWT($userId) {
    global $SECRET_KEY;
    $payload = [
        "sub" => $userId,
        "iat" => time(),
        "exp" => time() + 3600
    ];
    return JWT::encode($payload, $SECRET_KEY, 'HS256');
}

/**
 * Validate JWT and return payload if valid
 */
function validateJWT($token) {
    global $SECRET_KEY;

    try {
        $decoded = JWT::decode($token, new Key($SECRET_KEY, 'HS256'));
        return (array) $decoded; // Convert object to array
    } catch (Exception $e) {
        return false;
    }
}

function authenticate($pdo, $headers) {
    if (!isset($headers['Authorization'])) {
        http_response_code(401);
        echo json_encode(["error" => "Authorization header missing"]);
        echo "\n";
        exit;
    }

    $authHeader = $headers['Authorization'];
    if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        $token = $matches[1];
        $payload = validateJWT($token);

        if ($payload) {
            $stmt = $pdo->prepare("SELECT id, username, role FROM users WHERE id = ?");
            $stmt->execute([$payload['sub']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user) return $user;
        }
    }

    http_response_code(401);
    echo json_encode(["error" => "Invalid token"]);
    echo "\n";
    exit;
}
?>

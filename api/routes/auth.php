<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../middleware.php';

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = rtrim($path, '/');

// POST /auth/login
if ($method === 'POST' && preg_match('/\/auth\/login$/', $path)) {
    $data = json_decode(file_get_contents("php://input"), true);
    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';

    $stmt = $pdo->prepare("SELECT id, password FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password'])) {
        http_response_code(401);
        echo json_encode(["error" => "Invalid username or password"]);
        echo "\n";
        exit;
    }

    $token = generateJWT($user['id']);
    echo json_encode(["token" => $token]);
    echo "\n";
    exit;
}

#User logs out
// POST /auth/logout
if ($method === 'POST' && $path === '/api/auth/logout') {
    // Get token from Authorization header
    $headers = getallheaders();
    if (!isset($headers['Authorization'])) {
        http_response_code(401);
        echo json_encode(["error" => "Authorization header missing"]);
        echo "\n";
        exit;
    }

    $authHeader = $headers['Authorization'];
    if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        $token = $matches[1];

        // OPTIONAL: Here you could store the token in a blacklist table if you want
        // But for now we just tell the client to delete it

        echo json_encode(["message" => "Logged out successfully"]);
        echo "\n";
        exit;
    }
    http_response_code(400);
    echo json_encode(["error" => "Invalid Authorization header"]);
    echo "\n";
    exit;
}

http_response_code(404);
echo json_encode(["error" => "Auth endpoint not found"]);
echo "\n";
?>

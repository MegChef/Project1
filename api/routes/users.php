<?php
// api/routes/users.php
require_once __DIR__ . "/../db.php";
require_once __DIR__ . "/../middleware.php";

$method = $_SERVER['REQUEST_METHOD'];
$path = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

// POST /api/users/register
if ($method === 'POST' && $path === '/api/users/register') {
    $data = json_decode(file_get_contents("php://input"), true);
    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';

    if (!$username || !$password) {
        echo json_encode(["error" => "Username and password required"]);
        echo "\n";
        exit;
    }

    // Hash the password
    $hashed = password_hash($password, PASSWORD_BCRYPT);
    $role = 'user'; // default role

    // Insert new user
    $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    try {
        $stmt->execute([$username, $hashed, $role]);
        echo json_encode(["message" => "User registered successfully"]);
        echo "\n";
    } catch (PDOException $e) {
        http_response_code(400);
        echo json_encode(["error" => "Username already exists"]);
        echo "\n";
    }
    exit;
}

// 🔒 AUTH REQUIRED BELOW THIS LINE
$headers = getallheaders();
$user = authenticate($pdo, $headers);
$userId = $user['id'];
$userRole = $user['role'] ?? 'user';

// GET /api/users - list all users (admin only)
if ($method === 'GET' && $path === '/api/users') {
    if ($userRole !== 'admin') {
        http_response_code(403);
        echo json_encode(["error" => "Forbidden"]);
        echo "\n";
        exit;
    }
    $stmt = $pdo->query("SELECT id, username, role, createdAt FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($users);
    echo "\n";
    exit;
}

// DELETE /users/me - delete own profile
if ($method === 'DELETE' && $path === '/api/users/me') {
    try {
        // Delete all friendships involving this user
        $stmt = $pdo->prepare("DELETE FROM friendships WHERE userId=? OR friendId=?");
        $stmt->execute([$userId, $userId]);

        // Delete all friend requests involving this user
        $stmt = $pdo->prepare("DELETE FROM friendRequests WHERE senderId=? OR receiverId=?");
        $stmt->execute([$userId, $userId]);

        // Now delete the user
        $stmt = $pdo->prepare("DELETE FROM users WHERE id=?");
        $stmt->execute([$userId]);

        echo json_encode(["message" => "Profile deleted"]);
        echo "\n";
        exit;
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Failed to delete profile", "details" => $e->getMessage()]);
        echo "\n";
        exit;
    }
}

// POST /users/me/password - change password
if ($method === 'POST' && $path === '/api/users/me/password') {

    $data = json_decode(file_get_contents('php://input'), true);
    $newPassword = password_hash($data['password'], PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("UPDATE users SET password=? WHERE id=?");
    $stmt->execute([$newPassword, $userId]);

    echo json_encode(["message" => "Password updated"]);
    echo "\n";
    exit;
}

// Admin routes: view or delete user by ID
// Fetch the current user's role
$stmt = $pdo->prepare("SELECT role FROM users WHERE id=?");
$stmt->execute([$userId]);
$userRole = $stmt->fetchColumn();

// DELETE /users/{user_id} - admin deletes a user
if ($method === 'DELETE' && preg_match('#^/api/users/(\d+)$#', $path, $matches)) {
    if ($userRole !== 'admin') {
        http_response_code(403);
        echo json_encode(["error" => "Forbidden"]);
        echo "\n";
        exit;
    }

    $userIdToDelete = $matches[1];
    $stmt = $pdo->prepare("DELETE FROM users WHERE id=?");
    $stmt->execute([$userIdToDelete]);

    echo json_encode(["message" => "User deleted"]);
    echo "\n";
    exit;
}

http_response_code(404);
echo json_encode(["error" => "User endpoint not found", "path" => $path]);
echo "\n";
?>

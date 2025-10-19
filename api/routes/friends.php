<?php
// api/routes/friends.php
require_once __DIR__ . "/../db.php";
require_once __DIR__ . "/../middleware.php";

$method = $_SERVER['REQUEST_METHOD'];
$fullPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$headers = getallheaders();

$user = authenticate($pdo, $headers);
$userId = $user['id'];

// Remove '/api/friends' prefix
$prefix = '/api/friends';
if (strpos($fullPath, $prefix) === 0) {
    $path = substr($fullPath, strlen($prefix));
} else {
    $path = '';
}

// Normalize path
$path = '/' . trim($path, '/'); // root becomes '/'

// ---------------------
// GET /friends
// ---------------------
if ($method === 'GET' && $path === '/') {
    $stmt = $pdo->prepare("
        SELECT f.friendId, u.username
        FROM friendships f
        JOIN users u ON f.friendId = u.id
        WHERE f.userId = ?
    ");
    $stmt->execute([$userId]);
    $friends = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($friends);
    echo "\n";
    exit;
}

// GET /friends/requests - list pending friend requests for the logged-in user
if ($method === 'GET' && $path === '/requests') {
    $stmt = $pdo->prepare("SELECT fr.id AS requestId, u.username AS sender
                           FROM friendRequests fr
                           JOIN users u ON fr.senderId = u.id
                           WHERE fr.receiverId = ? AND fr.status = 'pending'");
    $stmt->execute([$userId]);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($requests);
    echo "\n";
    exit;
}

// ---------------------
// POST /friends/request/{receiverId}
// ---------------------
if ($method === 'POST' && preg_match('#^/request/(\d+)$#', $path, $matches)) {
    $receiverId = (int)$matches[1];

    if ($receiverId === $userId) {
        http_response_code(400);
        echo json_encode(["error" => "Cannot send request to yourself"]);
        echo "\n";
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT * FROM friendRequests
        WHERE senderId=? AND receiverId=? AND status='pending'
    ");
    $stmt->execute([$userId, $receiverId]);
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(["error" => "Friend request already sent"]);
        echo "\n";
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO friendRequests (senderId, receiverId, status, createdAt)
        VALUES (?, ?, 'pending', NOW())
    ");
    $stmt->execute([$userId, $receiverId]);

    echo json_encode(["message" => "Friend request sent"]);
    echo "\n";
    exit;
}

// ---------------------
// POST /friends/accept/{requestId}
// ---------------------
if ($method === 'POST' && preg_match('#^/accept/(\d+)$#', $path, $matches)) {
    $requestId = (int)$matches[1];

    $stmt = $pdo->prepare("
        SELECT * FROM friendRequests
        WHERE id=? AND receiverId=? AND status='pending'
    ");
    $stmt->execute([$requestId, $userId]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        http_response_code(404);
        echo json_encode(["error" => "Friend request not found"]);
        echo "\n";
        exit;
    }

    // Accept request
    $stmt = $pdo->prepare("UPDATE friendRequests SET status='accepted' WHERE id=?");
    $stmt->execute([$requestId]);

    // Add both directions to friendships
    $stmt = $pdo->prepare("
        INSERT INTO friendships (userId, friendId, createdAt)
        VALUES (?, ?, NOW()), (?, ?, NOW())
    ");
       $stmt->execute([$userId, $request['senderId'], $request['senderId'], $userId]);

    echo json_encode(["message" => "Friend request accepted"]);
    echo "\n";
    exit;
}

// POST /friends/reject/{request_id} - reject friend request
if ($method === 'POST' && preg_match('/^\/reject\/(\d+)$/', $path, $matches)) {
    $requestId = $matches[1];

    // Get request
    $stmt = $pdo->prepare("SELECT * FROM friendRequests WHERE id=? AND receiverId=? AND status='pending'");
    $stmt->execute([$requestId, $userId]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        http_response_code(404);
        echo json_encode(["error" => "Friend request not found"]);
        echo "\n";
        exit;
    }

    // Update status to rejected
    $stmt = $pdo->prepare("UPDATE friendRequests SET status='rejected' WHERE id=?");
    $stmt->execute([$requestId]);

    echo json_encode(["message" => "Friend request rejected"]);
    echo "\n";
    exit;
}

// ---------------------
// DELETE /friends/{friendId}
// ---------------------
if ($method === 'DELETE' && preg_match('#^/(\d+)$#', $path, $matches)) {
    $friendId = (int)$matches[1];

    $stmt = $pdo->prepare("
        DELETE FROM friendships
        WHERE (userId=? AND friendId=?) OR (userId=? AND friendId=?)
    ");
    $stmt->execute([$userId, $friendId, $friendId, $userId]);

    echo json_encode(["message" => "Friend removed"]);
    echo "\n";
    exit;
}

// ---------------------
// Default: 404
// ---------------------
http_response_code(404);
echo json_encode(["error" => "Friend endpoint not found"]);
echo "\n";
?>
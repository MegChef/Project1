<?php
// api/index.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json");

require_once __DIR__ . "/db.php";
require_once __DIR__ . "/middleware.php";

$request = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// normalize: remove /api prefix and trailing slash
if (strpos($request, '/api') === 0) {
    $request = substr($request, 4);
}
$request = rtrim($request, '/');

if (preg_match('/^\/auth/', $request)) {
    require __DIR__ . "/routes/auth.php";
} elseif (preg_match('/^\/users/', $request)) {
    require __DIR__ . "/routes/users.php";
} elseif (preg_match('/^\/friends/', $request)) {
    require __DIR__ . "/routes/friends.php";
} else {
    http_response_code(404);
    echo json_encode(["error" => "Endpoint not found", "path" => $request]);
}
?>

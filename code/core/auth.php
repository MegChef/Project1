<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

class Auth{
    //database stuff
    private $conn;
    private $usersTable = 'users';
    private $tokensTable = 'revoked_tokens';

    //auth properties
    public $token;
    public $username;
    public $password;
    public $userId;

    //constructor with db connection
    public function __construct($db){
        $this->conn = $db;
    }

    //login user and generate JWT token
    public function login(){
        //validate inputs
        if (empty($this->username) || empty($this->password)) {
            return [
                'success' => false, 
                'message' => 'Username and password are required'
            ];
        }

        //create query
        $query = 'SELECT id, username, password, role
        FROM ' . $this->usersTable . '
        WHERE username = :username
        LIMIT 1';

        //prepare statement
        $stmt = $this->conn->prepare($query);

        //clean the data
        $this->username = htmlspecialchars(strip_tags($this->username));

        //bind parameter
        $stmt->bindParam(':username', $this->username);

        //execute query
        if (!$stmt->execute()) {
            return [
                'success' => false, 
                'message' => 'Database error: ' . $stmt->errorInfo()[2]
            ];
        }

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        //verify user exists and password is correct
        if ($user && password_verify($this->password, $user['password'])) {
            //generate unique token ID for revocation tracking
            $tokenId = bin2hex(random_bytes(16));

            //create JWT payload
            $payload = [
                'jti' => $tokenId,              //JWT ID for revocation
                'iat' => time(),                //issued at
                'exp' => time() + (60 * 60),    //expires in 1 hour
                'user_id' => $user['id'],
                'username' => $user['username'],
                'role' => $user['role'] ?? 'user'
            ];

            //encode JWT
            $jwt = JWT::encode($payload, JWT_SECRET, 'HS256');
            return [
                'success' => true,
                'message' => 'Login successful',
                'token' => $jwt,
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'role' => $user['role']
                ],
                'expires_in' => 3600  // seconds
            ];
        }
        return [
            'success' => false, 
            'message' => 'Invalid username or password'
        ];
    }

    public function logout(){
        if (empty($this->token)) {
            return [
                'success' => false, 
                'message' => 'No token provided'
            ];
        }

        try {
            //decode token to get its ID
            $decoded = JWT::decode($this->token, new Key(JWT_SECRET, 'HS256'));

            //add token to revoked list
            if ($this->revokeToken($decoded->jti, $decoded->exp)) {
                return [
                    'success' => true, 
                    'message' => 'Logged out successfully'
                ];
            }

            return [
                'success' => false, 
                'message' => 'Failed to revoke token'
            ];

        } catch (Exception $e) {
            return [
                'success' => false, 
                'message' => 'Invalid token: ' . $e->getMessage()
            ];
        }
    }

    public function verifyToken(){
        if (empty($this->token)) {
            return [
                'success' => false, 
                'message' => 'No token provided',
                'data' => null
            ];
        }

        try {
            //decode the token
            $decoded = JWT::decode($this->token, new Key(JWT_SECRET, 'HS256'));

            //check if token is revoked
            if ($this->isTokenRevoked($decoded->jti)) {
                return [
                    'success' => false, 
                    'message' => 'Token has been revoked',
                    'data' => null
                ];
            }

            // Return decoded token data
            return [
                'success' => true,
                'message' => 'Token is valid',
                'data' => (array) $decoded
            ];

        } catch (\Firebase\JWT\ExpiredException $e) {
            return [
                'success' => false, 
                'message' => 'Token has expired',
                'data' => null
            ];
        } catch (\Firebase\JWT\SignatureInvalidException $e) {
            return [
                'success' => false, 
                'message' => 'Token signature is invalid',
                'data' => null
            ];
        } catch (Exception $e) {
            return [
                'success' => false, 
                'message' => 'Invalid token: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    //revoke a token by adding it to revoked tokens table
    private function revokeToken($tokenId, $expiresAt) {
        $query = 'INSERT INTO ' . $this->tokensTable . ' 
                (token_id, revoked_at, expires_at) 
                VALUES (:token_id, NOW(), FROM_UNIXTIME(:expires_at))';

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':token_id', $tokenId);
        $stmt->bindParam(':expires_at', $expiresAt);

        return $stmt->execute();
    }

    //check if token is revoked
    private function isTokenRevoked($tokenId) {
        $query = 'SELECT token_id FROM ' . $this->tokensTable . ' 
                WHERE token_id = :token_id 
                LIMIT 1';

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':token_id', $tokenId);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }
}
?>
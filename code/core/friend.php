<?php

require_once __DIR__ . '/../includes/config.php';

class Friend{
    //database stuff
    private $conn;
    private $table = 'friend_requests';
    private $friendsTable = 'friends';

    //friend properties
    public $userId;
    public $requestId;
    public $friendId;
    public $senderId;
    public $receiverId;
    public $status;
    public $createdAt;

    //constructor with db connection
    public function __construct($db){
        $this->conn = $db;
    }

    //send friend request
    public function sendRequest(){
        // Check if request already exists
        if ($this->requestExists()) {
            return ['success' => false, 'message' => 'Friend request already exists'];
        }

        // Check if they're already friends
        if ($this->areFriends($this->senderId, $this->receiverId)) {
            return ['success' => false, 'message' => 'You are already friends'];
        }

        // Check if user is trying to add themselves
        if ($this->senderId == $this->receiverId) {
            return ['success' => false, 'message' => 'Cannot send friend request to yourself'];
        }

        //create query
        $query = 'INSERT INTO ' . $this->table . ' SET senderId = :senderId, receiverId = :receiverId, status = :status, createdAt = NOW()';

        //prepare statement
        $stmt = $this->conn->prepare($query);

        //clean the data
        $this->senderId = htmlspecialchars(strip_tags($this->senderId));
        $this->receiverId = htmlspecialchars(strip_tags($this->receiverId));
        $this->status = 'pending';

        //binding  parameter to our query
        $stmt->bindParam(':senderId', $this->senderId);
        $stmt->bindParam(':receiverId', $this->receiverId);
        $stmt->bindParam(':status', $this->status);

        //execute the query
        if($stmt->execute()){
            return ['success' => true, 'message' => 'Friend request sent successfully'];
        }

        //print error if something goes wrong
        printf("Error %s. \n", $stmt->errorInfo()[2]);
        return ['success' => false, 'message' => 'Failed to send friend request successfully'];
    }

    public function areFriends($userId1, $userId2) {
        $query = 'SELECT * FROM friends 
                WHERE (userId = :user1 AND friendId = :user2)
                OR (userId = :user2 AND friendId = :user1)';
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user1', $userId1);
        $stmt->bindParam(':user2', $userId2);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    // Check if friend request already exists
    private function requestExists() {
        $query = 'SELECT * FROM ' . $this->table . ' 
                WHERE ((senderId = :senderId AND receiverId = :receiverId)
                OR (senderId = :receiverId AND receiverId = :senderId))
                AND status = "pending"';

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':senderId', $this->senderId);
        $stmt->bindParam(':receiverId', $this->receiverId);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    //get list of friend requests
    public function getRequests(){
        //create query
        $query = 'SELECT fr.*, requestId, u.username
            FROM ' .$this->table . ' fr
            JOIN users u ON fr.senderId = u.id
            WHERE fr.receiverId = :userId AND fr.status = "pending"
            ORDER BY fr.createdAt DESC';

        //prepare statement
        $stmt = $this->conn->prepare($query);

        //clean the data
        $this->userId = htmlspecialchars(strip_tags($this->userId));

        //bind parameters
        $stmt->bindParam(':userId', $this->userId);

        //execute query
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    //accept sent friend request
    public function acceptRequest(){
        //create query
        $query = 'UPDATE ' . $this->table . ' 
        SET status = "accepted"
        WHERE requestId = :requestId AND receiverId = :receiverId';

        //prepare statement
        $stmt = $this->conn->prepare($query);

        //clean the data
        $this->requestId = htmlspecialchars(strip_tags($this->requestId));
        $this->receiverId = htmlspecialchars(strip_tags($this->receiverId));

        //binding  parameter to our query
        $stmt->bindParam(':requestId', $this->requestId);
        $stmt->bindParam(':receiverId', $this->receiverId);

        //execute the query
        if($stmt->execute() && $stmt->rowCount() > 0){
            // Add to friends table (both directions for easy querying)
            $this->addToFriendsTable();
            return ['success' => true, 'message' => 'Friend request accepted'];
        }

        //print error if something goes wrong
        printf("Error %s. \n", $stmt->errorInfo()[2]);
        return ['success' => false, 'message' => 'Failed to accept friend request'];
    }

    // Add friendship to friends table
    private function addToFriendsTable() {
        // Get sender and receiver from the request
        $query = 'SELECT senderId, receiverId FROM ' . $this->table . ' WHERE requestId = :requestId';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':requestId', $this->requestId);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $senderId = $row['senderId'];
            $receiverId = $row['receiverId'];

            // Insert both directions
            $query = 'INSERT INTO ' . $this->friendsTable . ' (userId, friendId, createdAt) 
                    VALUES (:user1, :user2, NOW())';
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':user1' => $senderId, ':user2' => $receiverId]);
            $stmt->execute([':user1' => $receiverId, ':user2' => $senderId]);
        }
    }

    //reject sent friend request
    public function rejectRequest(){
        //create query
        $query = 'UPDATE ' . $this->table . ' 
        SET status = "rejected"
        WHERE requestId = :requestId AND receiverId = :receiverId';

        //prepare statement
        $stmt = $this->conn->prepare($query);

        //clean the data
        $this->requestId = htmlspecialchars(strip_tags($this->requestId));
        $this->receiverId = htmlspecialchars(strip_tags($this->receiverId));

        //binding  parameter to our query
        $stmt->bindParam(':requestId', $this->requestId);
        $stmt->bindParam(':receiverId', $this->receiverId);

        //execute the query
        if($stmt->execute() && $stmt->rowCount() > 0){
            return ['success' => true, 'message' => 'Friend request rejected'];
        }

        //print error if something goes wrong
        printf("Error %s. \n", $stmt->errorInfo()[2]);
        return ['success' => false, 'message' => 'Failed to reject friend request'];
    }

    //list friends
    public function listFriends(){
        //create query
        $query = 'SELECT f.*, u.id, u.username
            FROM ' .$this->friendsTable . ' f
            JOIN users u ON f.friendId = u.id
            WHERE f.userId = :userId
            ORDER BY u.username ASC';

        //prepare statement
        $stmt = $this->conn->prepare($query);

        //clean the data
        $this->userId = htmlspecialchars(strip_tags($this->userId));

        //bind parameters
        $stmt->bindParam(':userId', $this->userId, PDO::PARAM_INT);

        //execute query
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    //remove a friend
    public function removeFriend(){
        // Delete both directions of the friendship
        //create query
        $query = 'DELETE FROM ' . $this->friendsTable . '
            WHERE (userId = :userId1 AND friendId = :userId2)
            OR (userId = :userId2 AND friendId = :userId1)';

        //prepare statement
        $stmt = $this->conn->prepare($query);

        //clean the data
        $this->userId = htmlspecialchars(strip_tags($this->userId));
        $this->friendId = htmlspecialchars(strip_tags($this->friendId));
        
        //bind parameters
        $stmt->bindParam(':userId1', $this->userId);
        $stmt->bindParam(':userId2', $this->friendId);

        //execute query
        if ($stmt->execute() && $stmt->rowCount() > 0) {
            return ['success' => true, 'message' => 'Friend removed successfully'];
        }
        
        return ['success' => false, 'message' => 'Failed to remove friend'];
    }
}
?>
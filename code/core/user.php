<?php
class User{
    //database stuff
    private $conn;
    private $table = 'users';

    //user properties
    public $username;
    public $password;
    public $id;
    public $newPass;
    public $oldPass;
    public $createdAt;

    //constructor with db connection
    public function __construct($db){
        $this->conn = $db;
    }

    //register new user
    public function register(){
        //check if username already exists
        if ($this->userExists()) {
            return ['success' => false, 'message' => 'Username already exists'];
        }

        //validate inputs
        if (empty($this->username) || empty($this->password)) {
            return ['success' => false, 'message' => 'Username and password are required'];
        }

        //create query
        $query = 'INSERT INTO ' . $this->table . ' 
        SET username = :username, 
            password = :password,
            role = "user",
            createdAt = NOW()';

        //prepare statement
        $stmt = $this->conn->prepare($query);

        //clean the data
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->password = htmlspecialchars(strip_tags($this->password));

        $hashed = password_hash($this->password, PASSWORD_BCRYPT);

        // Bind parameters
        $stmt->bindParam(':username', $this->username);
        $stmt->bindParam(':password', $hashed);            

        //execute query
        if($stmt->execute()){
            return ['success' => true, 'message' => 'User registered successfully', 'userId' => $this->conn->lastInsertId()];
        }

        printf("Error %s. \n", $stmt->error);
        return ['success' => false, 'message' => 'Failed to register user'];
    }

    //check if username already exists
    private function userExists() {
        $query = 'SELECT id FROM ' . $this->table . ' WHERE username = :username';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $this->username);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    //change user password
    public function changePassword(){
        //validate inputs
        if (empty($this->oldPass) || empty($this->newPass)) {
            return ['success' => false, 'message' => 'Old and new passwords are required'];
        }

        //verify old password first
        $query = 'SELECT password FROM ' . $this->table . ' WHERE id = :id';
        $stmt = $this->conn->prepare($query);
        $this->id = htmlspecialchars(strip_tags($this->id));
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();

        if ($stmt->rowCount() == 0) {
            return ['success' => false, 'message' => 'User not found'];
        }

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        //verify old password
        if (!password_verify($this->oldPass, $row['password'])) {
            return ['success' => false, 'message' => 'Current password is incorrect'];
        }

        //create query
        $query = 'UPDATE ' . $this->table . ' 
        SET password = :password
        WHERE id = :id';

        $hashed = password_hash($this->newPass, PASSWORD_BCRYPT);

        //prepare statement
        $stmt = $this->conn->prepare($query);

        //bind parameters
        $stmt->bindParam(':password', $hashed);
        $stmt->bindParam(':id', $this->id);

        //execute the query
        if($stmt->execute() && $stmt->rowCount() > 0){
            return ['success' => true, 'message' => 'Password changed successfully'];
        }
        
        //print error if something goes wrong
        printf("Error %s. \n", $stmt->error);
        return ['success' => true, 'message' => 'Password change unsuccessful'];
    }

    //delete user account
    public function deleteSelf(){
        //create query
        $query = 'DELETE FROM ' . $this->table . ' WHERE id = :id';
        
        //prepare statement
        $stmt = $this->conn->prepare($query);
        
        //clean the data
        $this->id = htmlspecialchars(strip_tags($this->id));
        
        //binding the id parameter to our query
        $stmt->bindParam(':id', $this->id);

        //execute the query
        if($stmt->execute()){
            return ['success' => true, 'message' => 'User deleted successfully'];
        }

        //print error if something goes wrong
        printf("Error %s. \n", $stmt->error);
        return ['success' => true, 'message' => 'User could not be deleted'];
    }

    //delete any user account (admin)
    public function adminDelete(){
        //create query
        $query = 'DELETE FROM ' . $this->table . ' WHERE id = :id';
        
        //prepare statement
        $stmt = $this->conn->prepare($query);
        
        //clean the data
        $this->id = htmlspecialchars(strip_tags($this->id));
        
        //binding the id parameter to our query
        $stmt->bindParam(':id', $this->id);

        //execute the query
        if($stmt->execute()){
            return ['success' => true, 'message' => 'User deleted successfully'];
        }

        //print error if something goes wrong
        printf("Error %s. \n", $stmt->error);
        return ['success' => true, 'message' => 'User could not be deleted'];
    }

    public function getAllUsers() {
        //check if current user is admin
        $query = 'SELECT role FROM ' . $this->table . ' WHERE id = :id';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();
        $role = $stmt->fetchColumn();

        if ($role !== 'admin') {
            return ['success' => false, 'message' => 'Access denied. Admins only.'];
        }

        //create query to select all users
        $query = 'SELECT id, username, role, createdAt FROM ' . $this->table . ' ORDER BY createdAt DESC';
        
        //prepare statement
        $stmt = $this->conn->prepare($query);

        //execute query
        if ($stmt->execute()) {
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ['success' => true, 'users' => $users];
        }

        //if something goes wrong
        return ['success' => false, 'message' => 'Failed to retrieve users'];
    }
}
?>
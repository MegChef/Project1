<?php

$db_host = 'localhost';
$db_name =  'project1_db';
$db_user = 'root';
$db_pass = 'Megawesome543!';

define('JWT_SECRET', '9d7f8a6b3c1e4f2a5d6b7c8e9f0a1b2c');

try{
    $db = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8",$db_user,$db_pass);
    
    //Setting attributes
    $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $db->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true); //setting up database connection; makes connection faster than a normal database
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    define('APP_NAME', 'SOCIAL PLATFORM USER MANAGEMENT');
    
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

?>
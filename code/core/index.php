<?php

//if the DS operator is defined do nothing
//if the DS operator is not defined; get defined
defined('DS') ? null : define('DS', DIRECTORY_SEPARATOR);
defined('SITE_ROOT') ? null : define('SITE_ROOT', dirname(__DIR__)); //one level up from /core/

//paths to includes and core
defined('INC_PATH') ? null : define('INC_PATH', SITE_ROOT . DS . 'includes');
defined('CORE_PATH') ? null : define('CORE_PATH', SITE_ROOT . DS . 'core');

//load the config file first
require_once(INC_PATH . DS . "config.php");

//connect to MySQL using PDO
try {
    $db = new PDO(
        "mysql:host={$db_host};dbname={$db_name};charset=utf8",
        $db_user,
        $db_pass
    );
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

//core classes
require_once(CORE_PATH . DS . "auth.php");
require_once(CORE_PATH . DS . "friend.php");   
require_once(CORE_PATH . DS . "user.php");   

?>
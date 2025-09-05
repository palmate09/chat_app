<?php
require __DIR__ . '/../vendor/autoload.php'; 

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../'); 
$dotenv->load(); 

$db_host = $_ENV['DB_HOST']; 
$db_name = $_ENV['DB_NAME']; 
$db_pass = $_ENV['DB_PASS']; 
$db_user = $_ENV['DB_USER']; 

$jwt_secret = $_ENV['JWT_SECRET']; 

return [
    'db_host' => $db_host, 
    'db_name' => $db_name, 
    'db_pass' => $db_pass, 
    'db_user' => $db_user,
    'jwt_secret' => $jwt_secret
]; 

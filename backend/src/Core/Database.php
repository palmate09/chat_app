<?php
namespace Core; 

use PDO; 

class Database{
    private static $instance = null; 
    private $room; 

    private function __construct(){
        $config = require __DIR__ . '/../../config/config.php'; 
        $this->conn = new PDO(
            "mysql:host={$config['db_host']}; dbname={$config['db_name']};charset=utf8mb4", 
            $config['db_user'],
            $config['db_pass']
        );
        $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
    }

    public static function getInstance(){
        if(self::$instance === null){
            self::$instance = new self(); 
        }

        return self::$instance->conn; 
    }
}


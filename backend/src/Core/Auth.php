<?php
namespace Core; 

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Auth{
    public static function generateToken($payload){
        $config = require __DIR__ . '/../../config/config.php'; 
        return JWT::encode($payload, $config['jwt_secret'], 'HS256'); 
    }

    public static function verifyToken($token){
        $config = require __DIR__ . '/../../config/config.php';
        try{
            return (array) JWT::decode($token, new Key($config['jwt_secret'], 'HS256')); 
        }
        catch(\Exception $e){
            return false; 
        }
    }
}
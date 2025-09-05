<?php

require __DIR__ . '/../vendor/autoload.php'; 

use Core\Router; 
use Core\Response; 
use Core\Auth; 
use Core\RequestValidator; 
use Models\User; 

// load config 
require __DIR__ . '/../config/config.php';

function auth(){
    $headers = getallheaders(); 

    if(!isset($headers['Authorization'])){
        Response::json(["status" => "error", "message" => "Unauthorized"]); 
    }

    $authHeader = $headers['Authorization'] ?? ""; 
    $token = trim(str_replace("Bearer", "", $authHeader)); 
    $id = Auth::verifyToken($token);

    return $id; 
}

//simple router example
$router = new Router(); 

$router->post('/register', function($request){
    $input = json_decode(file_get_contents('php://input'), true);

    $username = $input['username']; 
    $password = $input['password']; 
    $email = $input['email']; 
    $name = $input['name']; 

    RequestValidator::validate([
        "username" => $username,
        "password" => $password, 
        "email" => $email,
        "name" => $name
    ]);

    $user = new User(); 

    $user_id = $user->register($username, $password, $name, $email); 

    return Response::json(["status" => "success", "message" => "user register successfully", "id" => $user_id], 201); 
});


$router->post('/login', function($request){
    $input = json_decode(file_get_contents('php://input'), true);

    $identifier = $input['identifier'];  
    $password = $input['password'];
    
    
    if(empty($identifier)){
        Response::json(["status" => "error", "message" => "email or username is required to fill"], 404); 
    } 

    $user = new User(); 
    $user_login = $user->login($identifier, $password); 

    Response::json(["status" => "error", "message" => "login successfully done", "data" => $user_login['user'], "token" => $user_login['token']]);
});

$router->get('/profile', function($request){
    $user_id = auth()['id']; 
    
    $user = new User(); 
    $user_data = $user->showProfile($user_id); 
    
    Response::json(["status" => "success", "message" => "user received successfully!", "data" => $user_data]); 
}); 

$router->put('/profile', function($request){
    $input = json_decode(file_get_contents('php://input'), true);
    $user_id = auth()['id']; 
    $allowed = ["username", "email", "password", "name"]; 
    
    $user = new User(); 
    $updated_user = $user->updateProfile($user_id, $input, $allowed); 

    Response::json(["status" => "success", "message" => "profile updated successfully!", "updated_data" => $updated_user]); 
});

$router->delete('/profile', function($request){
    $user_id = auth()['id'];

    $user = new User(); 
    $user->deleteUser($user_id);

    Response::json(["status" => "success", "message" => "profile deleted successfully!"]); 
});


$router->run(); 

<?php

require __DIR__ . '/../vendor/autoload.php'; 

use Core\Router; 
use Core\Response; 
use Core\Auth; 


// load config 
require __DIR__ . '/../../config/config.php'; 

//simple router example
$router = new Router(); 

$router->post('/register', function($request){
    $data = json_decode(file_get_contents('php://input'), true); 
});


$router->post('/login', function($request){

}); 

$router->run(); 

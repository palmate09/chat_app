<?php
namespace Core; 

use Core\Responose; 

class Router{
    private $routes = [
        'GET' => [], 
        'POST' => [], 
        'PUT' => [], 
        'DELETE' => []
    ];

    //Register get route
    public function get($path, $callback){
        $this->routes['GET'][$path] = $callback; 
    }
    
    //Register post route
    public function post($path, $callback){
        $this->routes['POST'][$path] = $callback;
    }

    //Register put route
    public function put($path, $callback){
        $this->routes['PUT'][$path] = $callback;
    }

    //Register delete route
    public function delete($path, $callback){
        $this->routes['DELETE'][$path] = $callback;
    }

    // dispatch the request
    public function run(){
        $method = $_SERVER['REQUEST_METHOD']; 
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        if(isset($this->routes[$method][$path])){
            $callback = $this->routes[$method][$path]; 
            $callback($_REQUEST);
        }   
        else{
            Response::json(['error' => 'Routes not found'], 404); 
        }
    }
}
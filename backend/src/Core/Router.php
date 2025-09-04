<?php
namespace Core; 

class Router{
    private $routes = [
        'GET' => [], 
        'POST' => [], 
        'PUT' => [], 
        'DELETE' => []
    ];

    //Register get route
    private function get($path, $callback){
        $this->routes['GET'][$path] = $callback; 
    }
    
    //Register post route
    private function post($path, $callback){
        $this->routes['POST'][$path] = $callback;
    }

    //Register put route
    private function put($path, $callback){
        $this->routes['PUT'][$path] = $callback;
    }

    //Register delete route
    private function delete($path, $callback){
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
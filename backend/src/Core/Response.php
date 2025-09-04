<?php
namespace Core; 


class Response{
    public static function json($data, $status=200){
        http_response_code($status); 
        header('Content-Type: application/json'); 
        echo json_decode($data); 
        exit; 
    }
}
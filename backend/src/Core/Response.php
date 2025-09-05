<?php
namespace Core; 


class Response{
    public static function json(array $data, $status=200){
        http_response_code($status); 
        header('Content-Type: application/json'); 
        echo json_encode($data); 
        exit; 
    }
}

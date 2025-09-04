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


class RequestValidator {
    public static function validate(array $input){
        foreach($input as $inputName => $value){
            if(empty($value)){
                Response::json(["status" => "error", "message" => "$inputName is required to fill"]); 
            }
        }
    }
}
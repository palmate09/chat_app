<?php

namespace Core; 

class RequestValidator {
    public static function validate(array $input){
        foreach($input as $inputName => $value){
            if(empty($value)){
                return Response::json(["status" => "error", "message" => "$inputName is required to fill"], 404); 
            }
        }
    }
}
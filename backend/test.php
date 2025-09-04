<?php

$allowed = ["name", "email", "password"];

$name = 'shubham'; 
$email = 'pal@gmail.com'; 

$input = [$name , $email]; 

$ans = array_intersect_key($input, array_flip($allowed)); 

var_dump($ans); 

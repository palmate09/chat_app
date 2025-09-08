<?php

require __DIR__ . '/../../vendor/autoload.php'; 

// getting the GuzzleHttp
use GuzzleHttp\Client; 
use GuzzleHttp\Exception\RequestException; 

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

function getClient(){
    return new Client(['base_uri' => 'http://localhost:8000', 'http_errors' => false]); 
}

beforeEach(function(){
    $host    = $_ENV['DB_HOST']; 
    $name = $_ENV['DB_NAME']; 
    $pass = $_ENV['DB_PASS']; 
    $user = $_ENV['DB_USER'];
    
    try{

        $conn = new PDO("mysql:host=$host;dbname=$name;charset=utf8", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        $conn->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $conn->exec("TRUNCATE TABLE users;");
        $conn->exec("TRUNCATE TABLE contacts;");
        $conn->exec("TRUNCATE TABLE chat_rooms;");
        $conn->exec("TRUNCATE TABLE chat_room_members;");
        $conn->exec("TRUNCATE TABLE messages;");
        $conn->exec("TRUNCATE TABLE message_status");

    }
    catch(\PDOException $e){
        throw new \PDOException($e->getMessage(), (int)$e->getCode());
    }
});


// USER TESTS

describe('User Api', function(){

    beforeEach(function(){
        $this->client = getClient(); 
    });

    test('post /register  register the new user', function(){

        $userData = [
            "username" => "aashutosh", 
            "name" => "aashu barhate", 
            "email" => "example@gmail.com",
            "password" => "aahu123"
        ];

        $response = $this->client->post('/register', [
            "json" => $userData
        ]);

        expect($response->getStatusCode())->toBe(201); 
    }); 

    test('post /login login to the user', function(){

        $userData = [
            "username" => "aashutosh", 
            "name" => "aashu barhate", 
            "email" => "example@gmail.com",
            "password" => "aashu123"
        ]; 

        $this->client->post('/login', [
            "json" => $userData
        ]);

        $userLoginData = [
            "identifier" => $userData['username'], 
            "password" => $userData['password']
        ];


        $response = $this->client->post('/login', [
            "json" => $userLoginData
        ]);

        expect($response->getStatusCode())->toBe(201); 
    });

    describe('when user register and login', function(){
        beforeEach(function(){
            $userData = [
                "username" => "aashutosh", 
                "name" => "aashu barhate", 
                "email" => "example@gmail.com",
                "password" => "aashu123"
            ]; 

            $this->client->post('/login', [
                "json" => $$userData
            ]);

            $userLoginData = [
                "identifier" => "aashutosh", 
                "password" => "aashu123"
            ];

            $response = $this->client->post('/login', [
                "json" => $userLoginData
            ]);

            $body = json_decode($response->getBody()->getContents(), true); 
            
            $this->login = $body; 
        });

        test('get /getProfile fetches the profile from user', function(){
            
            $this->client->get('/getProfile', [

            ]);
        });

    });
}); 
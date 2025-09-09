<?php

require __DIR__ . '/../../vendor/autoload.php'; 

// getting the GuzzleHttp
use GuzzleHttp\Client; 
use GuzzleHttp\Exception\RequestException; 
use WebSocket\wsClient; 

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

function getClient(){
    return new Client(['base_uri' => 'http://localhost:8000', 'http_errors' => false]); 
}

beforeEach(function(){
    $host = $_ENV['DB_HOST']; 
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

        $this->client->post('/register', [
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
        $body = json_decode($response->getBody(), true);
        expect($body)->toHaveKey('token');  
    });

    describe('when user register and login', function(){
        beforeEach(function(){
            $userData = [
                "username" => "aashutosh", 
                "name" => "aashu barhate", 
                "email" => "example@gmail.com",
                "password" => "aashu123"
            ]; 

            $this->client->post('/register', [
                "json" => $userData
            ]);

            $userLoginData = [
                "identifier" => $userData['username'], 
                "password" => $userData['password']
            ];

            $response = $this->client->post('/login', [
                "json" => $userLoginData
            ]);

            $body = json_decode($response->getBody()->getContents(), true);
                        
            $this->login = $body;

        });

        test('get /profile fetches the profile from user', function(){

            $this->token = $this->login['token'];  

            $response = $this->client->get('/profile', [
                "headers" => ["Authorization" => "Bearer ".$this->token]
            ]);

            expect($response->getStatusCode())->toBe(200); 
        });

        test('update /profile updates the profile data of the user', function(){

            $this->token = $this->login['token']; 

            $updateData = [
                "username" => "aashu"
            ];

            $response = $this->client->put('/profile', [
                "headers" => ["Authorization" => "Bearer ".$this->token], 
                "json" => $updateData
            ]);

            expect($response->getStatusCode())->toBe(200); 
        });
        
        test('delete /profile deletes the profile data of the user', function(){

            $this->token = $this->login['token']; 

            $response = $this->client->delete('/profile', [
                "headers" => ["Authorization" => "Bearer ".$this->token]
            ]);

            expect($response->getStatusCode())->toBe(200); 
        }); 
    });
})->skip(); 


// --- CONTACT ENDPOINT TESTS ---

describe('Contact endpoint', function(){
    beforeEach(function(){

        $this->client = getClient();

        $userData = [
            "username" => "aashutosh", 
            "name" => "aashu barhate", 
            "email" => "example@gmail.com",
            "password" => "aashu123"
        ]; 
        
        $this->client->post('/register', [
            "json" => $userData
        ]);
        
        $userLoginData = [
            "identifier" => $userData['username'], 
            "password" => $userData['password']
        ];
        
        $response = $this->client->post('/login', [
            "json" => $userLoginData
        ]);
        
        $body = json_decode($response->getBody()->getContents(), true);
                    
        $this->login = $body;

        $newuserData = [
            "username" => "shubham", 
            "name" => "shubham palmate", 
            "email" => "shubham@gmail.com",
            "password" => "shubham123"
        ]; 
        
        $register = $this->client->post('/register', [
            "json" => $newuserData
        ]); 

        $body = json_decode($register->getBody(), true);

        $this->contact_id = $body['id'];

    }); 

    test('post /contacts/request send the request to the friend', function(){

        $this->token = $this->login['token'];

        $contact_id = $this->contact_id;
        
        $response = $this->client->post("/contacts/request?contact_id=$contact_id", [
            "headers" => ["Authorization" => "Bearer ".$this->token] 
        ]);  

        expect($response->getStatusCode())->toBe(201);
    }); 

    test('update /contacts/add accepts the request by the friend', function(){

        $this->token = $this->login['token']; 

        $contact_id = $this->contact_id; 

        $this->client->post("/contacts/request?contact_id=$contact_id",[
            "headers" => ["Authorization" => "Bearer ".$this->token]
        ]); 

        $response = $this->client->put("/contacts/add?contact_id=$contact_id", [
            "headers" => ["Authorization" => "Bearer ".$this->token] 
        ]); 

        expect($response->getStatusCode())->toBe(200); 
    });

    test('update /contacts/update unblock the user', function(){

        $this->token = $this->login['token']; 

        $contact_id = $this->contact_id;

        $this->client->post("/contacts/request?contact_id=$contact_id",[
            "headers" => ["Authorization" => "Bearer ".$this->token]
        ]);

        $response = $this->client->put("/contacts/update?contact_id=$contact_id", [
            "headers" => ["Authorization" => "Bearer ".$this->token]
        ]); 

        expect($response->getStatusCode())->toBe(200); 
    }); 

    test('get /contacts/get shows all the contacts/friends', function(){

        $this->token = $this->login['token'];

        $contact_id = $this->contact_id; 

        $response = $this->client->get("/contacts/get", [
            "headers" => ["Authorization" => "Bearer ".$this->token]
        ]); 

        expect($response->getStatusCode())->toBe(200); 
    }); 

    test('get /contacts/get_particuar_contact show the particular contact/friend', function(){

        $this->token = $this->login['token'];

        $contact_id = $this->contact_id;

        $this->client->post("/contacts/request?contact_id=$contact_id",[
            "headers" => ["Authorization" => "Bearer ".$this->token]
        ]);

        $response = $this->client->get("/contacts/get_particular_contact?contact_id=$contact_id", [
            "headers" => ["Authorization" => "Bearer ".$this->token]
        ]); 

        expect($response->getStatusCode())->toBe(200); 
    }); 

    test('put /contacts/remove_status unfollow the friend by the user', function(){

        $this->token = $this->login['token'];
        
        $contact_id = $this->contact_id;
        
        $this->client->post("/contacts/request?contact_id=$contact_id",[
            "headers" => ["Authorization" => "Bearer ".$this->token]
        ]);

        $accepted = $this->client->put("/contacts/add?contact_id=$contact_id", [
            "headers" => ["Authorization" => "Bearer ".$this->token] 
        ]);

        $response = $this->client->put("/contacts/remove_status?contact_id=$contact_id", [
            "headers" => ["Authorization" => "Bearer ".$this->token]
        ]); 

        expect($response->getStatusCode())->toBe(200);
    }); 

    test('delete /contacts/remove_contact delete the contact of the user', function(){

        $this->token = $this->login['token']; 
        $contact_id = $this->contact_id;

        $response = $this->client->delete("/contacts/remove_contact?contact_id=$contact_id", [
            "headers" => ["Authorization" => "Bearer ".$this->token]
        ]);

        expect($response->getStatusCode())->toBe(200);
    }); 
})->skip();


// -- Chat_room tests -- 
describe("ChatRoom endpoints tests", function(){
    beforeEach(function(){

        $this->client = getClient();

        $userData = [
            "username" => "aashutosh", 
            "name" => "aashu barhate", 
            "email" => "example@gmail.com",
            "password" => "aashu123"
        ]; 
        
        $this->client->post('/register', [
            "json" => $userData
        ]);
        
        $userLoginData = [
            "identifier" => $userData['username'], 
            "password" => $userData['password']
        ];
        
        $response = $this->client->post('/login', [
            "json" => $userLoginData
        ]);
        
        $body = json_decode($response->getBody()->getContents(), true);
                    
        $this->login = $body;

        $newuserData = [
            "username" => "shubham", 
            "name" => "shubham palmate", 
            "email" => "shubham@gmail.com",
            "password" => "shubham123"
        ]; 
        
        $register = $this->client->post('/register', [
            "json" => $newuserData
        ]); 

        $body = json_decode($register->getBody(), true);

        $this->contact_id = $body['id'];
    });

    test('post /room/new_room creates the new chat room', function(){

        $this->token = $this->login['token']; 

        $contact_id = $this->contact_id; 

        $inputData = [
            "name" => "Duess", 
            "is_group" => true
        ];

        $response = $this->client->post("/room/new_room?contact_id=$contact_id", [
            "headers" => ["Authorization" => "Bearer ".$this->token],
            "json" => $inputData
        ]); 

        $body = json_decode($response->getBody(), true); 

        expect($response->getStatusCode())->toBe(201); 
    }); 

    test('get /room/show_user_rooms fetches all the room data of user', function(){
        $this->token = $this->login['token']; 

        $response = $this->client->get("/room/show_user_rooms", [
            "headers" => ["Authorization" => "Bearer ".$this->token]
        ]); 

        expect($response->getStatusCode())->toBe(200); 
    });

    test('get /room/show_group_rooms fetches all groups', function(){
        $this->token = $this->login['token'];

        $response = $this->client->get("/room/show_group_rooms", [
            "headers" => ["Authorization" => "Bearer ".$this->token]
        ]);

        expect($response->getStatusCode())->toBe(200); 
    });    

    describe('when the room exists', function(){

        beforeEach(function(){
            $this->token = $this->login['token'];

            $contact_id = $this->contact_id; 

            $Data = [
                "is_group" => false
            ];

            $chatRoom = $this->client->post("/room/new_room?contact_id=$contact_id", [
                "headers" => ["Authorization" => "Bearer ".$this->token],
                "json" => $Data
            ]);
            
            $body = json_decode($chatRoom->getBody(), true); 

            $this->room_id = $body['id']['id'];
        }); 

        test('get /room/show_rooms fetches particular room data', function(){
            $this->token = $this->login['token'];
            $room_id = $this->room_id;
            
            $response = $this->client->get("/room/show_room?room_id=$room_id", [
                "headers" => ["Authorization" => "Bearer ".$this->token]
            ]); 

            $body = json_decode($response->getBody(), true); 
 

            expect($response->getStatusCode())->toBe(200); 
        });
        
        test('update /room/update_room updates particular room data', function(){
            $this->token = $this->login['token']; 
            $room_id = $this->room_id; 

            $updateData = [
                "name" => "divide"
            ]; 

            $response = $this->client->put("/room/update_room?room_id=$room_id", [
                "headers" => ["Authorization" => "Bearer ".$this->token],
                "json" => $updateData
            ]); 

            expect($response->getStatusCode())->toBe(200); 
        }); 

        test('delete /room/delete_room delete the particular room data', function(){
            $this->token = $this->login['token']; 
            $room_id = $this->room_id; 

            $response = $this->client->delete("/room/delete_room?room_id=$room_id", [
                "headers" => ["Authorization" => "Bearer ".$this->token]
            ]); 

            expect($response->getStatusCode())->toBe(200);
        });
    });
})->skip(); 

// -- Chat Room Members tests -- 
describe("ChatMember endpoint tests", function(){
    beforeEach(function(){
        $this->client = getClient();

        $userData = [
            "username" => "aashutosh", 
            "name" => "aashu barhate", 
            "email" => "example@gmail.com",
            "password" => "aashu123"
        ]; 
        
        $this->client->post('/register', [
            "json" => $userData
        ]);
        
        $userLoginData = [
            "identifier" => $userData['username'], 
            "password" => $userData['password']
        ];
        
        $response = $this->client->post('/login', [
            "json" => $userLoginData
        ]);
        
        $body = json_decode($response->getBody()->getContents(), true);
                    
        $this->login = $body;

        $newuserData = [
            "username" => "shubham", 
            "name" => "shubham palmate", 
            "email" => "shubham@gmail.com",
            "password" => "shubham123"
        ]; 
        
        $register = $this->client->post('/register', [
            "json" => $newuserData
        ]); 

        $body = json_decode($register->getBody(), true);

        $this->contact_id = $body['id'];

        $inputData = [
            "name" => "Duess", 
            "is_group" => true
        ];

        $newResponse = $this->client->post("/room/new_room?contact_id={$this->contact_id}", [
            "headers" => ["Authorization" => "Bearer ".$this->login['token']],
            "json" => $inputData
        ]); 

        $newBody = json_decode($newResponse->getBody(), true);

        $this->room_id = $newBody['id']; 
    });

    test('post /room/add_new_member adds new member by admin only', function(){

        $this->token = $this->login['token']; 
        $user_id = $this->contact_id; 
        $room_id = $this->room_id['id']; 

        $inputData = [
            "role" => "member"
        ];

        $response = $this->client->post("/room/add_new_member?user_id=$user_id&room_id=$room_id", [
            "headers" => ["Authorization" => "Bearer ".$this->token],
            "json" => $inputData
        ]); 

        $body = json_decode($response->getBody(), true); 

        expect($response->getStatusCode())->toBe(201); 
    });

    describe('when the member exits', function(){
        beforeEach(function(){
            $this->token = $this->login['token'];
            $user_id = $this->contact_id; 
            $room_id = $this->room_id['id']; 

            $this->member_id = $this->room_id['new_id']; 
        }); 

        test('delete /room/remove_member removes member from the group',function(){
            $this->token = $this->login['token']; 
            $room_id = $this->room_id['id']; 
            $member_id =$this->member_id;
            
            $response = $this->client->delete("/room/remove_member?room_id=$room_id&member_id=$member_id", [
                "headers" => ["Authorization" => "Bearer ".$this->token]
            ]);
            
            expect($response->getStatusCode())->toBe(200); 
        }); 

        test('get /room/get_member shows complete member data', function(){
            $this->token = $this->login['token']; 
            $room_id = $this->room_id['id']; 
            $member_id = $this->member_id;
            
            $response = $this->client->get("/room/get_member?room_id=$room_id&member_id=$member_id", [
                "headers" => ["Authorization" => "Bearer ".$this->token]
            ]); 

            expect($response->getStatusCode())->toBe(200); 
        }); 

        test('get /room/get_all_members show all the members in the group', function(){
            $this->token = $this->login['token']; 
            $room_id = $this->room_id['id']; 

            $response = $this->client->get("/room/get_all_members?room_id=$room_id", [
                "headers" => ["Authorization" => "Bearer ".$this->token]
            ]);

            expect($response->getStatusCode())->toBe(200); 
        }); 
    });
})->skip(); 


// -- WebSocket Test ---
describe("WebSocket endpoint tests", function(){
    beforeEach(function(){
        $this->client = getClient();

        $userData = [
            "username" => "aashutosh", 
            "name" => "aashu barhate", 
            "email" => "example@gmail.com",
            "password" => "aashu123"
        ]; 
        
        $this->client->post('/register', [
            "json" => $userData
        ]);
        
        $userLoginData = [
            "identifier" => $userData['username'], 
            "password" => $userData['password']
        ];
        
        $response = $this->client->post('/login', [
            "json" => $userLoginData
        ]);
        
        $body = json_decode($response->getBody()->getContents(), true);
                    
        $this->login = $body;

        $newuserData = [
            "username" => "shubham", 
            "name" => "shubham palmate", 
            "email" => "shubham@gmail.com",
            "password" => "shubham123"
        ]; 
        
        $register = $this->client->post('/register', [
            "json" => $newuserData
        ]); 

        $body1 = json_decode($register->getBody(), true);

        $this->contact_id = $body1['id'];

        $this->token = $this->login['token']; 

        $contact_id = $this->contact_id; 

        $this->client->post("/contacts/request?contact_id=$contact_id",[
            "headers" => ["Authorization" => "Bearer ".$this->token]
        ]); 

        $this->client->put("/contacts/add?contact_id=$contact_id", [
            "headers" => ["Authorization" => "Bearer ".$this->token] 
        ]);

        $inputData = [
            "is_group" => false
        ];

        $response1 = $this->client->post("/room/new_room?contact_id=$contact_id", [
            "headers" => ["Authorization" => "Bearer ".$this->token],
            "json" => $inputData
        ]); 

        $body2 = json_decode($response1->getBody(), true);

        var_dump($body2);  

        $this->room_id = $response1['id']['id'];

        $room_id = $this->room_id; 

        $this->wsclient = new wsClient("ws://localhost:8080?user_id=$user_id&contact_id=$contact_id&room_id=$room_id");
    });

    afterEach(function(){
        $this->wsclient->close(); 
    });

    test('user connected to the ws server', function(){
        expect($this->client)->toBeInstanceOf(wsClient::class);
    });
});

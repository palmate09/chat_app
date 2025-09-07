<?php

require __DIR__ . '/../vendor/autoload.php'; 

use Core\Router; 
use Core\Response; 
use Core\Auth; 
use Core\RequestValidator; 
use Models\User; 
use Models\Contact; 
use Models\Message; 
use Models\ChatRoom; 
use Models\ChatMember; 

// load config 
require __DIR__ . '/../config/config.php';

function auth(){
    $headers = getallheaders(); 

    if(!isset($headers['Authorization'])){
        Response::json(["status" => "error", "message" => "Unauthorized"], 403); 
    }

    $authHeader = $headers['Authorization'] ?? ""; 
    $token = trim(str_replace("Bearer", "", $authHeader)); 
    $id = Auth::verifyToken($token);

    return $id; 
}

//simple router example
$router = new Router(); 

// user login and profile endpoints; 
$router->post('/register', function($request){
    $input = json_decode(file_get_contents('php://input'), true);

    $username = $input['username']; 
    $password = $input['password']; 
    $email = $input['email']; 
    $name = $input['name']; 

    RequestValidator::validate([
        "username" => $username,
        "password" => $password, 
        "email" => $email,
        "name" => $name
    ]);

    $user = new User(); 

    $user_id = $user->register($username, $password, $name, $email); 

    return Response::json(["status" => "success", "message" => "user register successfully", "id" => $user_id], 201); 
});


$router->post('/login', function($request){
    $input = json_decode(file_get_contents('php://input'), true);

    $identifier = $input['identifier'];  
    $password = $input['password'];
    
    
    if(empty($identifier)){
        Response::json(["status" => "error", "message" => "email or username is required to fill"], 404); 
    } 

    $user = new User(); 
    $user_login = $user->login($identifier, $password); 

    Response::json(["status" => "error", "message" => "login successfully done", "data" => $user_login['user'], "token" => $user_login['token']], 200);
});

$router->get('/profile', function($request){
    $user_id = auth()['id']; 
    
    $user = new User(); 
    $user_data = $user->showProfile($user_id); 
    
    Response::json(["status" => "success", "message" => "user received successfully!", "data" => $user_data], 200); 
}); 

$router->put('/profile', function($request){
    $input = json_decode(file_get_contents('php://input'), true);
    $user_id = auth()['id']; 
    $allowed = ["username", "email", "password", "name"]; 
    
    $user = new User(); 
    $updated_user = $user->updateProfile($user_id, $input, $allowed); 

    Response::json(["status" => "success", "message" => "profile updated successfully!", "updated_data" => $updated_user], 200); 
});

$router->delete('/profile', function($request){
    $user_id = auth()['id'];

    $user = new User(); 
    $user->deleteUser($user_id);

    Response::json(["status" => "success", "message" => "profile deleted successfully!"], 200); 
});


// contact endpoints;
// sends the follow request
$router->post('/contacts/request', function($request){
    $contact_id = $_GET['contact_id'];
    $user_id = auth()['id']; 

    $contact = new Contact();
    $contact->request($contact_id, $user_id);
    
    Response::json(["status" => "success", "message" => "follow request sent successfully"], 201);
});

// update the status 
$router->put('/contacts/add',function($request){
    $contact_id = $_GET['contact_id'];
    $user_id = auth()['id'];

    $contact = new Contact(); 
    $data = $contact->add($contact_id, $user_id);

    Response::json(["status" => "success", "message" => "follow request has been accepted", "data" => $data], 200);
});

// update the status as accepted from blocked
$router->put('/contacts/update', function($request){
    $contact_id = $_GET['contact_id']; 
    $user_id = auth()['id']; 

    $contact = new Contact(); 
    $data = $contact->update($contact_id, $user_id); 

    Response::json(["status" => "success", "message" => "status has been updated successfully", "data" => $data], 200);
}); 

// show all the contacts of the user_id
$router->get('/contacts/get', function($request){
    $user_id = auth()['id']; 

    $contact = new Contact();
    $data = $contact->show($user_id);
    
    Response::json(["status" => "success", "message" => "all the contacts received successfully", "data" => $data], 200); 
});

// show particular contact detail of user_id and contact_id
$router->get('/contacts/get_particular_contact', function($request){
    $user_id= auth()['id']; 
    $contact_id = $_GET['contact_id'];

    $contact = new Contact(); 
    $data = $contact->show_particular_contact($user_id, $contact_id); 

    Response::json(["status" => "success", "message" => "the particular contact recieved", "data" => $data], 200);
});

// remove the status
$router->delete('/contact/remove_status', function($request){
    $user_id = auth()['id']; 
    $contact_id = $_GET['contact_id']; 

    $contact = new Contact(); 
    $contact->removeStatus($user_id, $contact_id);

    Response::json(["status" => "success", "message" => "You have been unfollowed by you'r friend"], 200); 
});

// remove the friend/contact
$router->delete('/contact/remove_contact', function($request){
    $user_id = auth()['id']; 
    $contact_id = $_GET['contact_id']; 

    $contact = new Contact(); 
    $contact->removeContact($user_id, $contact_id);

    Response::json(["status" => "success", "message" => "contact has been removed successfully"], 200);
});


// chat Room endpoint

// create the chat room 
$router->post('/room/new_room', function($request){
    $input = json_decode(file_get_contents('php://input'), true); 
    $user_id = auth()['id'];
    $contact_id = $_GET['contact_id']; 
    $name = $input['name']; 
    $is_group = $input['is_group'];  
    
    $room = new ChatRoom();
    $room->create($user_id, $is_group, $name, $contact_id); 
    
    Response::json(["status" => "success", "message" => "chat room created successfully"], 201); 

});

// show all the rooms of particular user
$router->get('/room/show_user_rooms', function($request){
    $user_id = auth()['id']; 

    $room = new ChatRoom(); 
    $data = $room->show_user_rooms($user_id);

    Response::json(["status" => "success", "message" => "All chats received successfully", "data" => $data], 200); 
});

// show all the groups of the particular user; 
$router->get('/room/show_group_rooms', function($request){
    $user_id = auth()['id']; 

    $room = new ChatRoom(); 
    $data = $room->show_group_rooms($user_id);

    Response::json(["status" => "success", "message" => "All groups received successfully", "data" => $data], 200); 
});

// show the particular room data for particular user; 
$router->get('/room/show_room', function($request){
    $id = $_GET['room_id'];
    $user_id = auth()['id']; 
   
    $room = new ChatRoom(); 
    $data = $room->show_room($id, $user_id); 

    Response::json(["status" => "success", "message" => "Received Particular Room data of the user", "data" => $data], 200);     
});

// upate the particular room data for the paritcular user; 
$router->put('/room/update_room', function($request){
    $input = json_decode(file_get_contents('php://input'), true);
    $room_id = $_GET['room_id'];
    $name = $input['name']; 
    
    $room = new ChatRoom(); 
    $data = $room->update($room_id, $name);

    Response::json(["status" => "success", "message" => "Room Data updated successfully", "data" => $data], 200);
});

// delete the room data 
$router->delete('/room/delete_room', function($request){ 
    $room_id = $_GET['room_id']; 

    $room = new ChatRoom();
    $room->update($room_id);

    Response::json(["status" => "success", "message" => "Received Particular Room data of the user"], 200); 
});

// ChatMember endpoints

// add the new member by the admin in the group
$router->post('/room/add_new_member', function($request){
    $input = json_decode(file_get_contents('php://input'), true); 
    $room_id = $_GET['room_id']; 
    $role = $input['role'];
    $user_id = $_GET['user_id']; 
    $admin_id = auth()['id']; 

    $member = new ChatMember(); 
    $member->addMember($room_id, $admin_id, $user_id, $role); 

    Response::json(["status" => "success", "message" => "New member added successfully"], 201);

}); 

// remove the member by the admin in the group
$router->delete('/room/remove_member', function($request){
    $member_id = $_GET['memebr_id']; 
    $room_id = $_GET['room_id']; 
    $user_id = auth()['id']; 

    $member = new ChatMember(); 
    $member->removeMember($member_id, $room_id, $user_id); 

    Response::json(["status" => "success", "message" => "member has been deleted successfully"], 200);
}); 

// show all the member for the particular group
$router->get('/room/get_all_member', function($request){
    $member_id = $_GET['member_id']; 
    $room_id = $_GET['room_id'];

    $member = new ChatMember(); 
    $data = $member->showAllMemeber($member_id, $room_id);

    Response::json(["status" => "success", "message" => "All members received successfully", "data" => $data], 200); 
}); 

// Message  endpoints 

// show the message
$router->get('/message/showMessage', function($request){

    $room_id = $_GET['room_id']; 

    $message = new Message(); 
    $data = $message->showMessage($room_id); 

    Response::json(["status" => "success", "message" => "message received", "data" => $data], 200); 
}); 

// update the particular message by the user
$router->put('/message/update_message', function($request){
    $input = json_decode(file_get_contents('php://input'), true); 
    $message_id = $_GET['message_id']; 
    $content = $input['content'];
    $user_id = auth()['id']; 

    $message = new Message(); 
    $data = $message->updateMessage($message_id, $user_id, $content); 

    Response::json(["status" => "success", "message" => "message received", "data" => $data], 200); 
});

// remove the message 
$router->delete('/message/delete_message', function($request){
    $message_id = $_GET['message_id'];
    
    $message = new Message(); 
    $data = $message->removeMessage($message_id);

    Response::json(["status" => "success", "message" => "message deleted successfully"], 200); 
}); 


$router->run(); 
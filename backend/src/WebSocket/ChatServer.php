<?php
namespace WebSocket;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Core\Auth; 
use Models\User; 
use Models\Message;  

class ChatServer implements MessageComponentInterface {
    protected $clients; 
    protected $userConnections = []; 
    private $userModel; 
    private $messageModel; 


    public function __construct(){
        $this->clients = new \SplObjectStorage(); 
        $this->userModel = new User(); 
        $this->messageModel = new Message(); 
        echo 'websocket server started'; 
    }

    public function onOpen(ConnectionInterface $conn) {
        // it will save all the clients connected to this ws server
        $this->clients->attach($conn); 

        //Extract token from the query string; 
        $queryString = $conn->httpRequest->getUri()->getQuery(); 
        parse_str($queryString, $params);
        $user_id = $params['user_id'] ?? null;
        $room_id = $params['room_id'] ?? null; 
        $receiver_id = $params['receiver_id'];
        
        if(!$user_id || !$room_id || !$receiver_id){
            $conn->close(); 
            return; 
        }

        // $payload = Auth::verifyToken($token); 
        // if(!$payload || !isset($payload['id'])){
        //     $conn->close(); 
        //     return;
        // }

        // $user_id = $payload['id']; 
        // $conn->user = $payload; 

        $conn->userId = $user_id;
        $conn->roomId = $room_id; 
        $conn->receiverId = $receiver_id; 

        if(!isset($this->userConnections[$user_id])){
            $this->userConnections[$user_id] = [];  
        }

        if(!isset($this->userConnections[$room_id])){
            $this->userConnections[$room_id] = []; 
        }

        if(!isset($this->userConnections[$receiver_id])){
            $this->userConnections[$receiver_id] = []; 
        }

        $this->userConnections[$user_id] = $conn; 
        $this->userConnections[$room_id] = $conn; 
        $this->userConnections[$receiver_id] = $conn; 

        $this->userModel->updateStatus($user_id, 'online');
        
        echo "User $user_id Connected with Room $room_id\n"; 
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true); 
        if(!$data) return; 
        
        $sender_id = $from->userId;
        $room_id = $from->roomId; 
        
        switch($data['action']){

            case "send": 
                $receiver_id = $from->receiverId; 

                $msg_data = $this->messageModel->sendMessage(
                    $room_id,
                    $sender_id,
                    $data['text'],
                    $receiver_id
                ); 

                $msg_id = $msg_data['message_id'];
                $payload = json_encode([
                    'action' => 'send',
                    'room_id' => $room_id, 
                    'sender_id' => $sender_id,
                    'receiver_id' => $receiver_id ?? null,
                    'message_text' => $data['text'], 
                    'message_id' => $msg_id,
                    "created_at" => date("Y-m-d H:i:s") 
                ]);

                // Broadcast message for all the connected members;
                $this->broadcast($payload, $sender_id, $data); 
                break; 
            
            // update message
            case "update": 
                $updated = $this->messageModel->updateMessage(
                    $data['message_id'],
                    $sender_id, 
                    $data['new_text']
                ); 

                if($updated){
                    $payload = json_encode([
                        "action" => "update", 
                        "message_id" => $data['message_id'],
                        "new_text" => $data["new_text"],
                        "updated_at" => date("Y-m-d H:i:s")
                    ]); 

                    $this->broadcast($payload, $sender_id, $data); 
                }
                break; 

            // remove message
            case "remove": 
                $remove = $this->messageModel->removeMessage(
                    $data['message_id']
                ); 

                if($remove){
                    $payload = json_encode([
                        "action" => "delete", 
                        "message_id" => $data["message_id"]
                    ]); 

                    $this->broadcast($payload, $sender_id, $data);
                }
                break; 
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn); 

        foreach($this->userConnections as $user_id => $connections){
            $index = array_search($conn, $connections, true); 

            if($index !== false){
                unset($this->userConnections[$user_id][$index]); 
                if(empty($this->userConnections[$user_id])){
                    unset($this->userConnections[$user_id]); 
                    $this->userModel->updateStatus($user_id, 'offline'); 
                    echo "User $user_id dissconnected\n"; 
                }
                break; 
            }
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "Error: ".$e->getMessage()."\n"; 
        $conn->close(); 
    }

    private function broadcast(string $payload, string $senderId, array $data) {
        if (!empty($data['receiver_id'])) {
            // private chat
            $receiverId = $data['receiver_id'];
            if (isset($this->userConnections[$receiverId])) {
                foreach ($this->userConnections[$receiverId] as $conn) {
                    $conn->send($payload);
                }
            }
            foreach ($this->userConnections[$senderId] as $conn) {
                $conn->send($payload);
            }
        } elseif (!empty($data['room_id'])) {
            // room chat
            foreach ($this->userConnections as $uid => $connections) {
                foreach ($connections as $conn) {
                    $conn->send($payload);
                }
            }
        }
    }
}

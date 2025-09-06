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
        $token = $params['token'] ?? null;
        
        if(!$token){
            $conn->close(); 
            return; 
        }

        $payload = Auth::verifyToken($token); 
        if(!$payload || !isset($payload['id'])){
            $conn->close(); 
            return;
        }

        $user_id = $payload['id']; 
        $conn->user = $payload; 

        if(!isset($this->userConnections[$user_id])){
            $this->userConnections[$user_id] = [];  
        }

        $this->userConnections[$user_id]  = $conn; 
        $this->userModel->updateStatus($user_id, 'online');
        
        echo "User $user_id Connected\n"; 
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true); 
        if(!$data) return; 

        $msg_data = $this->messageModel($data['room_id'], $data['sender_id'], $data['message_text']); 
        $msg_id = $msg_data['message_id']; 
        //Broadcast to all online members
        foreach($this->userConnections as $uid => $connections){
            foreach($connections as $conn){
                $conn->send(json_encode([
                    'room_id' => $data['room_id'], 
                    'sender_id' => $data['sender_id'],
                    'message_text'=> $data['message_text'], 
                    'message_id'=> $msg_id,
                    'created_at' => date("Y-m-d H:i:s")
                ])); 
            }
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
}

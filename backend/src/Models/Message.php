<?php

namespace Models; 

use Core\Database; 
use Core\RequestValidator; 
use Core\Response; 
use Ramsey\Uuid\Uuid;
use PDO; 

class Message{
    private $db; 

    public function __construct(){
        $this->db = Database::getInstance(); 
    }

    public function sendMessage(string $room_id, string $user_id, string $content, ?string $receiver_id=null): ?array{
        
        RequestValidator::validate([
            "room id" => $room_id,
            "user id" => $user_id, 
            "content" => $content
        ]); 

        try{
            $id = Uuid::uuid4()->toString(); 
            $sql1 = "INSERT INTO messages(id,room_id, sender_id, content, receiver_id) VALUES (?,?,?,?,?)"; 
            $stmt = $this->db->prepare($sql1); 
            $stmt->execute([$id, $room_id, $user_id, $content, $receiver_id]);
            $msg_id = $id;
            
            // create message_status for all members
            $sql = "SELECT user_id FROM chat_room_members WHERE room_id =?"; 
            $stmt2 = $this->db->prepare($sql); 
            $stmt2->execute([$room_id]); 
            $members = $stmt2->fetchAll(PDO::FETCH_COLUMN);
            
            $new_id = Uuid::uuid4()->toString(); 
            $stmt3 = $this->db->prepare("INSERT INTO message_status(id, message_id, user_id) VALUES(?,?,?)"); 
            foreach($members as $uid){
                $stmt3->execute([$new_id, $msg_id, $uid]); 
            }

            return [
                "message_id" => $msg_id, 
                "message_status_id" => $new_id 
            ]; 
        }
        catch(\Exception $e){
            return Response::json(["status" => "error", "message" => $e->getMessage()], 500); 
        }
    }

    // issue :- we should see the receiver name relatively like
    //  if on shubham side the chat_room name should be gautami 
    // but on the gautami side chat_room name should be shubham 
    // as it is private chat 
    public function showMessage(string $room_id): ?array{

        RequestValidator::validate([
            "room id" => $room_id
        ]); 

        try{
            $sql1 = 'SELECT is_group FROM chat_rooms WHERE id = ?'; 
            $stmt1= $this->db->prepare($sql1);
            $stmt1->execute([$room_id]);
            $roomData = $stmt1->fetch(PDO::FETCH_ASSOC); 

            if($roomData['is_group'] === 1){
                $sql = "SELECT 
                        m.id AS message_id, m.content, 
                        c.name AS receiver,
                        u.name AS sender
                    FROM messages m
                    LEFT JOIN users u ON u.id = m.sender_id
                    LEFT JOIN chat_rooms c ON c.id = m.room_id
                    WHERE room_id = ?
                    ORDER BY m.created_at ASC";

                $stmt = $this->db->prepare($sql); 
                $stmt->execute([$room_id]);
                $message = $stmt->fetchAll(PDO::FETCH_ASSOC); 

                return $message;
            }
            else{
                $sql2 = "SELECT 
                            m.id AS message_id, m.content,
                            U.name AS receiver,
                            u.name AS sender
                        FROM messages m
                        LEFT JOIN users u ON u.id = m.sender_id
                        LEFT JOIN users U ON U.id = m.receiver_id
                        WHERE m.room_id = ?
                        ORDER BY m.created_at ASC";
                $stmt2 = $this->db->prepare($sql2);
                $stmt2->execute([$room_id]); 
                $message2 = $stmt2->fetchAll(PDO::FETCH_ASSOC); 
                
                return $message2; 
            }
        }
        catch(\Exception $e){
            return Response::json(["status" => "error", "message" => $e->getMessage()], 500); 
        }
    }

    public function updateMessage(string $id, string $user_id, string $content): ?array{
        RequestValidator::validate([
            "message id" => $id,
            "content" => $content, 
            "user id" => $user_id
        ]); 

        try{
            // update the messages content
            $sql = "UPDATE messages SET content = ? WHERE id = ? AND user_id = ?";
            $stmt = $this->db->prepare($sql); 
            $stmt->execute([$content, $id, $user_id]);
            
            // show the updated message
            $sql1 = "SELECT content, sender_id FROM messages WHERE id = ?";
            $stmt2 = $this->db->prepare($sql1);
            $stmt2->execute([$id]);
            $updated_message = $stmt2->fetch(PDO::FETCH_ASSOC);

            return $updated_message; 
        }
        catch(\Exception $e){
            return Response::json(["status" => "error", "message" => $e->getMessage()], 500); 
        }
    }

    public function removeMessage(string $id): null{
        RequestValidator::validate([
            "message id" => $id
        ]); 

        try{
            $sql = 'DELETE FROM messages WHERE id = ?'; 
            $stmt = $this->db->prepare($sql); 
            $stmt->execute([$id]);
            
            return null; 
        }
        catch(\Exception $e){
            return Response::json(["status" => "error", "message" => $e->getMessage()]); 
        }
    }
}
